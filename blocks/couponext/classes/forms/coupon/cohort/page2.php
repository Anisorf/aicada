<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Coursegrouping coupon generator form (step 2)
 *
 * File         page2.php
 * Encoding     UTF-8
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_couponext\forms\coupon\cohort;

defined('MOODLE_INTERNAL') || die();

use block_couponext\helper;
use block_couponext\coupon\generatoroptions;

require_once($CFG->libdir . '/formslib.php');

/**
 * block_couponext\forms\coupon\cohort\page2
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page2 extends \moodleform {

    /**
     * @var generatoroptions
     */
    protected $generatoroptions;

    /**
     * Get reference to database
     * @return \moodle_database
     */
    protected function db() {
        global $DB;
        return $DB;
    }

    /**
     * form definition
     */
    public function definition() {
        $mform = & $this->_form;

        list($this->generatoroptions) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('heading:cohortlinkcourses', 'block_couponext'));
        if (!$strinfo = get_config('block_couponext', 'info_coupon_cohort_courses')) {
            $strinfo = get_string('missing_config_info', 'block_couponext');
        }
        $mform->addElement('static', 'info', '', $strinfo);
        // Collect cohort records.
        $cohorts = $this->db()->get_records_list('cohort', 'id', $this->generatoroptions->cohorts);

        // Now we'll show the cohorts one by one.
        foreach ($cohorts as $cohort) {

            // Header for the cohort.
            $mform->addElement('header', 'cohortsheader[]', $cohort->name);

            // Collect courses connected to cohort.
            $cohortcourses = helper::get_courses_by_cohort($cohort->id);

            // If we have connected courses we'll display them.
            if ($cohortcourses) {
                $headingstr = array();
                foreach ($cohortcourses as $course) {
                    $headingstr[] = $course->fullname;
                }
                $mform->addElement('static', 'connected_courses',
                        get_string('label:connected_courses', 'block_couponext'), implode('<br/>', $headingstr));
            } else {
                $mform->addElement('static', 'connected_courses[' . $cohort->id . '][]',
                        get_string('label:connected_courses', 'block_couponext'),
                        get_string('label:no_courses_connected', 'block_couponext'));
            }

            // Collect not connected courses.
            $notconnectedcourses = helper::get_unconnected_cohort_courses($cohort->id);

            // If we have not connected courses we'll display them.
            if ($notconnectedcourses) {
                $arrnotconnectedcourses = array();
                foreach ($notconnectedcourses as $notconnectedcourse) {
                    $arrnotconnectedcourses[$notconnectedcourse->id] = $notconnectedcourse->fullname;
                }
                $attributes = array('size' => min(20, count($arrnotconnectedcourses)));
                $selectconnectcourses = &$mform->addElement('select', 'connect_courses[' . $cohort->id . ']',
                        get_string('label:coupon_connect_course', 'block_couponext'), $arrnotconnectedcourses, $attributes);
                $mform->addHelpButton('connect_courses[' . $cohort->id . ']', 'label:coupon_connect_course', 'block_couponext');
                $selectconnectcourses->setMultiple(true);
            }

            // That's the end of the loop.
        }

        $this->add_action_buttons(true, get_string('button:next', 'block_couponext'));
    }

}

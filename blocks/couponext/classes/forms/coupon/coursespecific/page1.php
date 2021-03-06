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
 * Course specific coupon generator form (step 1)
 *
 * File         page1.php
 * Encoding     UTF-8
 *
 * @package     block_couponext
 *
 * @copyright   teldh
 * @author      Frosina Koceva <frosina.koceva@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_couponext\forms\coupon\coursespecific;

defined('MOODLE_INTERNAL') || die();

use block_couponext\helper;
use block_couponext\coupon\generatoroptions;

require_once($CFG->libdir . '/formslib.php');

/**
 * block_couponext\forms\coupon\coursespecific\page1
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page1 extends \moodleform {

    /**
     * @var generatoroptionsfor
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

        $mform->addElement('header', 'header', get_string('heading:courseandvars', 'block_couponext'));
        if (!$strinfo = get_config('block_couponext', 'info_coupon_course')) {
            $strinfo = get_string('missing_config_info', 'block_couponext');
        }
        $mform->addElement('static', 'info', '', $strinfo);

        // Coupon logo selection.
        \block_couponext\logostorage::add_select_form_elements($mform);

        // Add custom batchid.
        $mform->addElement('text', 'batchid', get_string('label:batchid', 'block_couponext'));
        $mform->setType('batchid', PARAM_TEXT);
        $mform->addHelpButton('batchid', 'label:batchid', 'block_couponext');

        // Select course(s). This should not be done in case of coursespecific option where the course is not predefined but is choosen by the student
        /*$multiselect = true;
        if (!empty($this->_customdata['coursemultiselect'])) {
            $multiselect = (bool)$this->_customdata['coursemultiselect'];
        }

        $mform->addElement('header', 'header', get_string('heading:input_course', 'block_couponext'));

        // First we'll get some useful info.
        $courses = helper::get_visible_courses();

        // And create data for multiselect.
        $arrcoursesselect = array();
        foreach ($courses as $course) {
            $arrcoursesselect[$course->id] = $course->fullname;
        }

        $attributes = array('size' => min(20, count($arrcoursesselect)));
        // Course id.
        $selectcourse = &$mform->addElement('select', 'coupon_courses',
                get_string('label:coupon_courses', 'block_couponext'), $arrcoursesselect, $attributes);
        $selectcourse->setMultiple($multiselect);
        $mform->addRule('coupon_courses', get_string('error:required', 'block_couponext'), 'required', null, 'client');
        $mform->addHelpButton('coupon_courses', 'label:coupon_courses', 'block_couponext');

        // Select role(s).
        $roles = helper::get_role_menu(null, true);
        $attributes = [];
        // Role id.
        $selectrole = &$mform->addElement('select', 'coupon_role',
                get_string('label:coupon_role', 'block_couponext'), $roles, $attributes);
        $selectrole->setMultiple(false);
        $mform->setDefault('coupon_role', helper::get_default_coupon_role()->id);
        $mform->addHelpButton('coupon_role', 'label:coupon_role', 'block_couponext');*/

        // Configurable enrolment time.
        $mform->addElement('duration', 'enrolment_period',
                get_string('label:enrolment_period', 'block_couponext'), array('size' => 40, 'optional' => true));
        $mform->setDefault('enrolment_period', '0');
        $mform->addHelpButton('enrolment_period', 'label:enrolment_period', 'block_couponext');

        $this->add_action_buttons(true, get_string('button:next', 'block_couponext'));
    }

    /**
     * Validate input
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;
        // Make sure batch id is unique if provided.
        $err = parent::validation($data, $files);
        if (!empty($data['batchid']) && $DB->record_exists('block_couponext', ['batchid' => $data['batchid']])) {
            $err['batchid'] = get_string('err:batchid', 'block_couponext');
        }
        return $err;
    }

}

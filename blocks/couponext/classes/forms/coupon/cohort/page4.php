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
 * Cohort coupon generator form (step 4)
 *
 * File         page4.php
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
 * block_couponext\forms\coupon\cohort\page4
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page4 extends \moodleform {

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

        if (!$strinfo = get_config('block_couponext', 'info_coupon_confirm')) {
            $strinfo = get_string('missing_config_info', 'block_couponext');
        }
        $mform->addElement('static', 'info', '', $strinfo);

        // We need the generator options here.
        switch ($this->generatoroptions->generatormethod) {
            case 'amount':
                helper::add_amount_generator_elements($mform);
                break;
            case 'manual':
                helper::add_manual_generator_elements($mform, $this->generatoroptions->type);
                break;
            case 'csv':
                helper::add_csv_generator_elements($mform, $this->generatoroptions->type);
                break;
        }

        // Collect cohort records.
        $cohorts = $this->db()->get_records_list('cohort', 'id', $this->generatoroptions->cohorts);

        // Cohorts to add.
        foreach ($cohorts as $cohort) {
            $mform->addElement('header', 'cohortsheader[]', $cohort->name);
            // Fetch the courses that are connected to this cohort.
            if ($cohortcourses = helper::get_courses_by_cohort($cohort->id)) {
                $headingstr = array();
                foreach ($cohortcourses as $course) {
                    $headingstr[] = $course->fullname;
                }
                $mform->addElement('static', 'connected_courses',
                        get_string('label:connected_courses', 'block_couponext'), implode('<br/>', $headingstr));
            } else {
                $mform->addElement('static', 'connected_courses[' . $cohort->id . ']',
                        get_string('label:connected_courses', 'block_couponext'),
                        get_string('label:no_courses_connected', 'block_couponext'));
            }
        }

        $this->add_action_buttons(true, get_string('button:next', 'block_couponext'));
    }

    /**
     * Perform validation.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        // Set which fields to validate, depending on form used.
        if ($this->generatoroptions->generatormethod == 'csv') {
            $data2validate = array(
                'email_body' => $data['email_body'],
                'date_send_coupons' => $data['date_send_coupons']
            );
        } else if ($this->generatoroptions->generatormethod == 'manual') {
            $data2validate = array(
                'email_body' => $data['email_body_manual'],
                'date_send_coupons' => $data['date_send_coupons_manual']
            );
        } else {
            $data2validate = array(
                'coupon_amount' => $data['coupon_amount'],
                'alternative_email' => $data['alternative_email']
            );
        }

        // Validate.
        $errors = parent::validation($data2validate, $files);

        // Custom validate.
        if ($this->generatoroptions->generatormethod == 'amount') {
            // Max amount of coupons.
            $maxcouponsamount = get_config('block_couponext', 'max_coupons');
            if (empty($data['generatecodesonly'])) {
                if ($data['coupon_amount'] > $maxcouponsamount || $data['coupon_amount'] < 1) {
                    $errors['coupon_amount'] = get_string('error:coupon_amount_too_high',
                            'block_couponext', array('min' => '0', 'max' => $maxcouponsamount));
                }
            }
            // Alternative email required if use_alternative_email is checked.
            if (isset($data['use_alternative_email']) && empty($data['alternative_email'])) {

                $errors['alternative_email'] = get_string('error:alternative_email_required', 'block_couponext');
            }
        } else if ($this->generatoroptions->generatormethod == 'csv') {
            $csvcontent = $this->get_file_content('coupon_recipients');
            if (!$csvcontent || empty($csvcontent)) {
                $errors['coupon_recipients'] = get_string('required');
            }
        } else {
            // Force comma as separator since we defined manual entry this way.
            $validationresult = helper::validate_coupon_recipients($data['coupon_recipients_manual'], ',');
            if ($validationresult !== true) {
                $errors['coupon_recipients_manual'] = $validationresult;
            }
        }

        return $errors;
    }

    /**
     * Get content of uploaded file.
     *
     * @param string $elname name of file upload element
     * @return string|bool false in case of failure, string if ok
     */
    public function get_file_content($elname) {
        global $USER;

        $element = $this->_form->getElement($elname);
        if ($element instanceof \MoodleQuickForm_filepicker || $element instanceof \MoodleQuickForm_filemanager) {
            $values = $this->_form->exportValues($elname);
            if (empty($values[$elname])) {
                return false;
            }
            $draftid = $values[$elname];
            $fs = get_file_storage();
            $context = \context_user::instance($USER->id);
            if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false)) {
                return false;
            }
            $file = reset($files);

            return $file->get_content();
        } else if (isset($_FILES[$elname])) {
            return file_get_contents($_FILES[$elname]['tmp_name']);
        }

        return false;
    }

    /**
     * Use this method to a cancel and submit button to the end of your form. Pass a param of false
     * if you don't want a cancel button in your form. If you have a cancel button make sure you
     * check for it being pressed using is_cancelled() and redirecting if it is true before trying to
     * get data with get_data().
     *
     * @param bool $cancel whether to show cancel button, default true
     * @param string $submitlabel label for submit button, defaults to get_string('savechanges')
     */
    public function add_action_buttons($cancel = true, $submitlabel = null) {
        if (is_null($submitlabel)) {
            $submitlabel = get_string('savechanges');
        }
        $mform =& $this->_form;
        if ($cancel) {
            // When two elements we need a group.
            $buttonarray = array();
            $buttonarray[] = &$mform->createElement('button', 'preview', get_string('preview-pdf', 'block_couponext'),
                    ['id' => 'block-coupon-preview-btn']);
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
            $buttonarray[] = &$mform->createElement('cancel');
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
        } else {
            // No group needed.
            $mform->addElement('submit', 'submitbutton', $submitlabel);
            $mform->closeHeaderBefore('submitbutton');
        }
    }

}

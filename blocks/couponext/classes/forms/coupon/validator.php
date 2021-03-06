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
 * Form implementation to let a user input a coupon code.
 *
 * File         validator.php
 * Encoding     UTF-8
 *
 * @package     block_couponext
 *PARAM_INT
 * @copyright   Sebsoft.nl
 * @author      Menno de Ridder <menno@sebsoft.nl>
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_couponext\forms\coupon;

defined('MOODLE_INTERNAL') || die();

//moodleform is defined in formslib.php
require_once($CFG->libdir . '/formslib.php');

/**
 * block_couponext\forms\coupon\validator
 * For creating a form in moodle, you have to create class extending moodleform class and override definition for including form elements.
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      Menno de Ridder <menno@sebsoft.nl>
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @author      Frosina Koceva <frosina.koceva@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validator extends \moodleform {

    /**
     * form definition
     */
    public function definition() {
        $mform = & $this->_form;
        $mform->addElement('header', 'header', get_string('heading:input_coupon', 'block_couponext'));
        // All we need is the coupon code.
        $mform->addElement('text', 'coupon_code', get_string('label:coupon_code', 'block_couponext'));
        $mform->addRule('coupon_code', get_string('error:required', 'block_couponext'), 'required', null, 'client');
        $mform->addRule('coupon_code', get_string('error:required', 'block_couponext'), 'required', null, 'server');
        $mform->setType('coupon_code', PARAM_ALPHANUM);
        $mform->addHelpButton('coupon_code', 'label:coupon_code', 'block_couponext');
        // F: And the course id to which the user want to enrol.
        $mform->addElement('text', 'course_id', get_string('label:course_id', 'block_couponext'));
        $mform->addRule('course_id', get_string('error:required', 'block_couponext'), 'required', 0, 'client'); // course_id is 0 when no course is selected
        $mform->addRule('course_id', get_string('error:required', 'block_couponext'), 'required', 0, 'server');
        $mform->setType('course_id', PARAM_INT);
        $mform->addHelpButton('course_id', 'label:course_id', 'block_couponext');


        $this->add_action_buttons(false, get_string('button:submit_coupon_code', 'block_couponext'));
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
        global $DB, $USER;
        $errors = parent::validation($data, $files);

        $conditions = array(
            'submission_code' => $data['coupon_code'],
            'claimed' => 0,
        );
        $coupon = $DB->get_record('block_couponext', $conditions);
        // F: If my custom form with course_id than call get_type_instance by passing course_id, else act as per default
        // if(!empty($data['course_id'])){
        if(empty($coupon)){
            $errors['coupon_code'] = get_string('error:invalid_coupon_code', 'block_couponext');
        }
        else if(!empty($coupon) && $data['course_id']==0){
            $errors['coupon_code'] = get_string('error:invalid_course_id', 'block_couponext');
        }
        else if($data['course_id']>0){
            // TODO check if the course_id is correct in respect of the coupon
            $typeproc = \block_couponext\coupon\typebase::get_type_instance_with_course($data['coupon_code'], $data['course_id']);
        }
        /* else{
            // Get type processor.
            $typeproc = \block_couponext\coupon\typebase::get_type_instance($data['coupon_code']);
        } */
        // Get type processor.
        // $typeproc = \block_couponext\coupon\typebase::get_type_instance($data['coupon_code']);

        // F: COURSE CODE
        /*try {
            // Assert not yet used.
            $typeproc->assert_not_claimed();
            // Assert specialized.
            // TODO F: not working well, check if necessary
            $typeproc->assert_internal_checks($USER->id);
        }
        */
        try {
            if (!empty($coupon)) {
                \block_couponext\helper::already_enroled_check($USER->id, $coupon->submission_code);
            }
        }
        catch (Exception $ex) {
            $errors['coupon_code'] = $ex->getMessage();
        }

        return $errors;
    }

    /**
     * Override form identifier. This is to fix namespace issues for Moodle < 2.9
     * @return string
     */
    protected function get_form_identifier() {
        $class = get_class($this);
        return preg_replace('/[^a-z0-9_]/i', '_', $class);
    }

}
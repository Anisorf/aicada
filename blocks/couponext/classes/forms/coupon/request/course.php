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
 * Form for course coupon requests.
 *
 * File         course.php
 * Encoding     UTF-8
 *
 * @package     block_coupon
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_couponext\forms\coupon\request;

defined('MOODLE_INTERNAL') || die();

use block_couponext\helper;
require_once($CFG->libdir . '/formslib.php');

/**
 * block_couponext\forms\coupon\request\course
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends \moodleform {

    /**
     * Allowed options for user
     * @var \stdClass
     */
    private $options;

    /**
     * form definition
     */
    public function definition() {
        global $DB;
        $mform = & $this->_form;

        list($requestinstance, $user) = $this->_customdata;
        $this->options = json_decode($requestinstance->configuration);

        // Logo selection if applicable.
        if ($this->get_option($this->options, 'allowselectlogo', false)) {
            // Coupon logo selection.
            \block_couponext\logostorage::add_select_form_elements($mform);
        } else {
            $mform->addElement('hidden', 'logo');
            $mform->setType('logo', PARAM_INT);
            $mform->setConstant('logo', $this->get_option($this->options, 'logo', -1));
        }

        // Select course(s).
        $courses = $DB->get_records_list('course', 'id', $this->get_option($this->options, 'courses', []));
        $arrcoursesselect = array();
        foreach ($courses as $course) {
            $arrcoursesselect[$course->id] = $course->fullname;
        }

        $attributes = array('size' => min(20, count($arrcoursesselect)));
        $selectcourse = &$mform->addElement('select', 'coupon_courses',
                get_string('label:coupon_courses', 'block_couponext'), $arrcoursesselect, $attributes);
        $selectcourse->setMultiple(true);
        $mform->addRule('coupon_courses', get_string('error:required', 'block_couponext'), 'required', null, 'client');
        $mform->addHelpButton('coupon_courses', 'label:coupon_courses', 'block_couponext');

        // Add role selection if applicable.
        if ($this->get_option($this->options, 'allowselectrole', false)) {
            $roles = helper::get_role_menu(null, true);
            $mform->addElement('select', 'coupon_role',
                    get_string('label:coupon_role', 'block_couponext'), $roles, $attributes);
            $mform->setDefault('coupon_role', helper::get_default_coupon_role()->id);
            $mform->addHelpButton('coupon_role', 'label:coupon_role', 'block_couponext');
        } else {
            $mform->addElement('hidden', 'coupon_role');
            $mform->setType('coupon_role', PARAM_INT);
            $mform->setConstant('coupon_role', $this->get_option($this->options, 'role'));
        }

        // Amount of coupons.
        $mform->addElement('static', '_role', '', '<hr/>');
        $mform->addElement('text', 'coupon_amount', get_string('label:coupon_amount', 'block_couponext'));
        $mform->setType('coupon_amount', PARAM_INT);
        $mform->addRule('coupon_amount', get_string('error:numeric_only', 'block_couponext'), 'numeric');
        $mform->addRule('coupon_amount', get_string('required'), 'required');
        $mform->addRule('coupon_amount', null, 'nonzero');
        $mform->addHelpButton('coupon_amount', 'label:coupon_amount', 'block_couponext');

        // Use alternative email address.
        $mform->addElement('advcheckbox', 'use_alternative_email', get_string('label:use_alternative_email', 'block_couponext'));
        $mform->setType('use_alternative_email', PARAM_BOOL);
        $mform->setDefault('use_alternative_email', false);

        // Email address to mail to.
        $mform->addElement('text', 'alternative_email', get_string('label:alternative_email', 'block_couponext'));
        $mform->setType('alternative_email', PARAM_EMAIL);
        $mform->setDefault('alternative_email', $user->email);
        $mform->addRule('alternative_email', get_string('error:invalid_email', 'block_couponext'), 'email', null);
        $mform->addHelpButton('alternative_email', 'label:alternative_email', 'block_couponext');
        $mform->disabledIf('alternative_email', 'use_alternative_email', 'notchecked');

        // Generate_pdf checkbox.
        if ($this->get_option($this->options, 'allowselectseperatepdf', false)) {
            $mform->addElement('checkbox', 'generate_pdf', get_string('label:generate_pdfs', 'block_couponext'));
            $mform->addHelpButton('generate_pdf', 'label:generate_pdfs', 'block_couponext');
        } else {
            $mform->addElement('hidden', 'generate_pdf');
            $mform->setType('generate_pdf', PARAM_INT);
            $mform->setConstant('generate_pdf', $this->get_option($this->options, 'seperatepdfdefault'));
        }

        // Render QR code checkbox.
        if ($this->get_option($this->options, 'allowselectqr', false)) {
            $mform->addElement('advcheckbox', 'renderqrcode', get_string('label:renderqrcode', 'block_couponext'));
            $mform->addHelpButton('renderqrcode', 'label:renderqrcode', 'block_couponext');
            $mform->setDefault('renderqrcode', 1);
        } else {
            $mform->addElement('hidden', 'renderqrcode');
            $mform->setType('renderqrcode', PARAM_INT);
            $mform->setConstant('renderqrcode', $this->get_option($this->options, 'qrdefault'));
        }

        // Enrolment period selection.
        if ($this->get_option($this->options, 'allowselectenrolperiod', false)) {
            $mform->addElement('duration', 'enrolment_period',
                    get_string('label:enrolment_period', 'block_couponext'), array('size' => 40, 'optional' => true));
            $mform->setDefault('enrolment_period', $this->get_option($this->options, 'enrolperioddefault', 0));
            $mform->addHelpButton('enrolment_period', 'label:enrolment_period', 'block_couponext');
        } else {
            $mform->addElement('hidden', 'enrolment_period');
            $mform->setType('enrolment_period', PARAM_INT);
            $mform->setConstant('enrolment_period', $this->get_option($this->options, 'enrolperioddefault'));
        }

        $this->add_action_buttons(true, get_string('request:coupons', 'block_couponext'));
    }

    /**
     * get option or default value
     * @param \stdClass $config
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function get_option($config, $key, $default = '') {
        if (empty($config)) {
            return $default;
        }
        if (isset($config->{$key})) {
            return $config->{$key};
        }
        return $default;
    }

}
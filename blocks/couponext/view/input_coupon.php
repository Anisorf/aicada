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
 * validate couponext input
 *
 * File         input_coupon.php
 * Encoding     UTF-8
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      Menno de Ridder <menno@sebsoft.nl>
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');

use block_couponext\helper;
use block_couponext\forms\coupon\validator;

// id is the block_instance id
$id = required_param('id', PARAM_INT);

$instance = $DB->get_record('block_instances', array('id' => $id), '*', MUST_EXIST);
$context       = \context_block::instance($instance->id);
$coursecontext = $context->get_course_context(false);
$course = false;
if ($coursecontext !== false) {
    $course = $DB->get_record("course", array("id" => $coursecontext->instanceid));
}
if ($course === false) {
    $course = get_site();
}

require_login($course, true);

$PAGE->navbar->add(get_string('view:input_coupon:title', 'block_couponext'));

$url = new moodle_url($CFG->wwwroot . '/blocks/couponext/view/input_coupon.php', array('id' => $id));
$PAGE->set_url($url);

$PAGE->set_title(get_string('view:input_coupon:title', 'block_couponext'));
$PAGE->set_heading(get_string('view:input_coupon:heading', 'block_couponext'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');

// Make sure the moodle editmode is off.
helper::force_no_editing_mode();

require_capability('block/couponext:inputcoupons', $context);
// Include the form.
try {
    $mform = new validator($url);
    if ($mform->is_cancelled()) { //Handle form cancel operation, if cancel button is present on form
        redirect(new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $course->id)));
    }
    else if ($data = $mform->get_data()) { //In this case you process validated data. $mform->get_data() returns data posted in form.

        // TODO F: its passing also the course id, make it posible to distinguish for different type of coupons (e.g. the course type does not need courseid like the coursespecific type)
        // Get type processor.
        $typeproc = block_couponext\coupon\typebase::get_type_instance($data->coupon_code, $data->course_id);
        // Perform assertions.
        $typeproc->assert_not_claimed();
        $typeproc->assert_internal_checks($USER->id);

        // Process the claim.
        // The base is to just claim, but various coupons might have their own processing.
        // F: the original process_claim was : $typeproc->process_claim($USER->id);
        if(is_null($data->course_id))
            $typeproc->process_claim($USER->id);
        else if(!is_null($data->course_id))
            $typeproc->process_claim_coursespecific($USER->id, $data->course_id);
    } else {
        // this branch is executed if the form is submitted but the data doesn't validate
        echo $OUTPUT->header();
        echo '<div class="block-coupon-container">';
        $mform->display();
        echo '</div>';
        echo $OUTPUT->footer();
    }
} catch (block_couponext\exception $e) {
    \core\notification::error($e->getMessage());
} catch (\Exception $ex) {
    \core\notification::error(get_string('err:coupon:generic'));
}

// TODO Redirect to Dashboard /my/index.php, but it could be better to redirect to the course on successful submitting of coupon code + course id
redirect($CFG->wwwroot . '/my');
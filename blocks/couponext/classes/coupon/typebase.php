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
 * Coupon type base
 *
 * File         typebase.php
 * Encoding     UTF-8
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_couponext\coupon;

defined('MOODLE_INTERNAL') || die();

use block_couponext\coupon\generatoroptions;
use block_couponext\exception;

/**
 * block_couponext\coupon\typebase
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class typebase {

    /**
     * @var \stdClass
     */
    protected $coupon;

    /**
     * Create a new instance
     * @param \stdClass $coupon record from database
     */
    public function __construct($coupon) {
        $this->coupon = $coupon;
    }

    /**
     * Claim coupon.
     * @param int $foruserid user that claims coupon. Current userid if not given.
     * @param mixed $options any options required by the instance
     */
    abstract public function claim($foruserid = null, $options = null);

    /**
     * Return whether this coupon type has extended claim options.
     * @return bool.
     */
    abstract public function has_extended_claim_options();

    /**
     * Process the claim.
     * @param int $foruserid user that claims coupon. Current userid if not given.
     */
    public function process_claim($foruserid = null) {
        global $CFG;
        // The base is: call claim. Should be sufficient for most coupons.
        $this->claim($foruserid);

        $redirect = (empty($this->coupon->redirect_url)) ? $CFG->wwwroot . "/my" : $this->coupon->redirect_url;
        redirect($redirect, get_string('success:coupon_used', 'block_couponext'));
    }

    /**
     * Process the claim for this user and this course
     * @param null $foruserid
     * @param null $forcourseid
     * @throws \coding_exception
     * @throws \moodle_exception
     * @author Frosina Koceva
     */
    public function process_claim_coursespecific($foruserid = null, $forcourseid = null){
         global $CFG;
         // The base is: call claim. Should be sufficient for most coupons.
         $this->claim($foruserid, $forcourseid);

         // $redirect = (empty($this->coupon->redirect_url)) ? $CFG->wwwroot . "/my" : $this->coupon->redirect_url;
        $redirect = (empty($this->coupon->redirect_url)) ?  $this->coupon->redirect_url : "$CFG->wwwroot/course/view.php?id=$forcourseid";
        redirect($redirect, get_string('success:coupon_used', 'block_couponext'));
     }

    /**
     * Assert claimable.
     * @throws \block_couponext\exception
     */
    public function assert_not_claimed() {
        if ((bool)$this->coupon->claimed) {
            get_string('error:coupon_already_used', 'block_couponext');
        }
    }

    /**
     * Assert other. This can be anything really.
     *
     * @param int $userid user claiming.
     * @throws exception
     */
    public function assert_internal_checks($userid) {
        return;
    }

    /**
     * Trigger event that this coupon is claimed.
     */
    protected function trigger_coupon_claimed() {
        // Trigger event.
        $event = \block_couponext\event\coupon_used::create(
                        array(
                            'objectid' => $this->coupon->id,
                            'relateduserid' => $this->coupon->userid,
                            'context' => \context_user::instance($this->coupon->userid),
                            'other' => [
                                'code' => $this->coupon->submission_code,
                                'type' => $this->coupon->typ
                            ]
                        )
        );
        $event->add_record_snapshot('block_couponext', $this->coupon);
        $event->trigger();
    }

    /**
     * Get class instance for given couponcode
     *
     * @param string $couponcode code to claim
     * @return self
     * @throws \block_couponext\exception
     */
    public static function get_type_instance($couponcode) {
        global $DB;
        // Get record.
        $conditions = array(
            'submission_code' => $couponcode
        );
        // F: gets the DB record of block_coupon table corresponding that has submission_code equal to couponcode
        $coupon = $DB->get_record('block_couponext', $conditions, '*', MUST_EXIST);
        // Base validation.
        if (empty($coupon)) {
            throw new exception('error:invalid_coupon_code');
        } else if (!is_null($coupon->userid) && $coupon->typ != generatoroptions::ENROLEXTENSION) {
            throw new exception('error:coupon_already_used');
        }

        // All these checks aren't strictly needed but alas, OOP FTW.
        $class = '\\block_couponext\\coupon\\types\\' . $coupon->typ;
        if (!class_exists($class)) {
            throw new exception('err:no-such-processor', $coupon->typ);
        }
        $rc = new \ReflectionClass($class);
        if (!$rc->implementsInterface('\\block_couponext\\coupon\\icoupontype')) {
            throw new exception('err:processor-implements', $coupon->typ);
        }

        // Load coupon type and claim().
        $instance = $rc->newInstance($coupon);
        return $instance;
    }

    /**
     * Get class instance for given couponcode and course_id
     *
     * @param string $couponcode code to claim
     * @param int $course_id course id to which to enrol
     * @return self
     * @throws \block_couponext\exception
     * @author Frosina Koceva
     */
    public static function get_type_instance_with_course($couponcode, $course_id) {
        global $DB;
        // Get record.
        $conditions = array(
            'submission_code' => $couponcode
        );
        // F: gets the DB record of block_coupon table corresponding that has submission_code equal to couponcode
        $coupon = $DB->get_record('block_couponext', $conditions, '*', MUST_EXIST);
        // Base validation.
        if (empty($coupon)) {
            throw new exception('error:invalid_coupon_code');
        }
        else if ($course_id == 0) { // Check and throw exception when if no course was selected
            throw new exception('error:invalid_course_id');
        }
        else if (!is_null($coupon->userid) && $coupon->typ != generatoroptions::ENROLEXTENSION) {
            throw new exception('error:coupon_already_used');
        }

        // All these checks aren't strictly needed but alas, OOP FTW.
        $class = '\\block_couponext\\coupon\\types\\' . $coupon->typ;
        if (!class_exists($class)) {
            throw new exception('err:no-such-processor', $coupon->typ);
        }
        $rc = new \ReflectionClass($class);
        if (!$rc->implementsInterface('\\block_couponext\\coupon\\icoupontype')) {
            throw new exception('err:processor-implements', $coupon->typ);
        }

        // Load coupon type and claim().
        $instance = $rc->newInstance($coupon, $course_id);
        return $instance;
    }

}

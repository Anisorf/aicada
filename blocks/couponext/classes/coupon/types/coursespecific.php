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
 * Specific Course type coupon processor
 *
 * File         coursespecific.php
 * Encoding     UTF-8
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      Frosina Koceva
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * */

namespace block_couponext\coupon\types;

defined('MOODLE_INTERNAL') || die();

use block_couponext\coupon\icoupontype;
use block_couponext\coupon\typebase;
use block_couponext\exception;

/**
 * block_couponext\coupon\types\coursespecific
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      Frosina Koceva
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursespecific extends typebase implements icoupontype {

    /**
     * Claim coupon for a specific course
     * @param int $foruserid user that claims coupon. Current userid if not given.
     * @param mixed $options any options required by the instance (in this case options equals to course id)
     */
    public function claim($foruserid = null, $options = null) {
        global $CFG, $DB, $USER;
        // Because we're outside course context we've got to include libraries manually.
        require_once($CFG->dirroot . '/group/lib.php');

        // Validate.
        if ($this->coupon->typ !== \block_couponext\coupon\generatoroptions::COURSESPECIFIC) {
            throw new exception('invalid-coupon-type');
        }
        // Claim.
        if (empty($foruserid)) {
            $foruserid = $USER->id;
        }

        // Validate correct user, if applicable.
        if (!empty($this->coupon->userid) && $this->coupon->userid != $foruserid) {
            throw new exception('coupon:claim:wronguser', 'block_couponext');
        }

        // Determine role.
        if (empty($this->coupon->roleid)) {
            $role = \block_couponext\helper::get_default_coupon_role();
        } else {
            $role = $DB->get_record('role', ['id' => $this->coupon->roleid]);
        }

        // F: enrol, insert a new record to block_couponext_courses of coupon id course id
        // An object for the added course.
        $record = (object) array(
            'couponid' => $this->coupon->id,
            'courseid' => $options
        );
        // And insert in db.
        if (!$DB->insert_record('block_couponext_courses', $record)) {
            $errors[] = 'Failed to create course link ' . $options . ' record for coupon id ' .  $this->coupon->id . '.';
        }

        $couponcourses = $DB->get_records('block_couponext_courses', array('couponid' => $this->coupon->id));
        // Set enrolment period.
        $endenrolment = 0;
        if (!is_null($this->coupon->enrolperiod) && $this->coupon->enrolperiod > 0) {
            $endenrolment = time() + $this->coupon->enrolperiod;
        }

        foreach ($couponcourses as $couponcourse) {
            // Make sure we only enrol if its not enrolled yet.
            $context = \context_course::instance($couponcourse->courseid);
            if (is_null($context) || $context === false) {
                throw new exception('error:course-not-found');
            }
            // Now we can enrol.
            if (!enrol_try_internal_enrol($couponcourse->courseid, $foruserid, $role->id, time(), $endenrolment)) {
                throw new exception('error:unable_to_enrol');
            }
            // Mark the context for cache refresh.
            $context->mark_dirty();
            remove_temp_course_roles($context);
        }

        // And add user to groups.
        $coupongroups = $DB->get_records('block_couponext_groups', array('couponid' => $this->coupon->id));
        if (!empty($coupongroups)) {
            foreach ($coupongroups as $coupongroup) {
                // Check if the group exists.
                if (!$DB->get_record('groups', array('id' => $coupongroup->groupid))) {
                    throw new exception('error:missing_group');
                }
                // Add user if its not a member yet.
                if (!groups_is_member($coupongroup->groupid, $foruserid)) {
                    groups_add_member($coupongroup->groupid, $foruserid);
                }
            }
        }

        // And finally update the coupon record.
        $this->coupon->claimed = 1;
        $this->coupon->userid = $foruserid;
        $time = time();
        $this->coupon->timemodified = $time;
        $this->coupon->timeclaimed = $time;
        $DB->update_record('block_couponext', $this->coupon);
    }

    /**
     * Return whether this coupon type has extended claim options.
     * @return bool false.
     */
    public function has_extended_claim_options() {
        return false;
    }

    /**
     * Assert claimable.
     * @throws exception
     */
    public function assert_not_claimed() {
        // Call parent.
        parent::assert_not_claimed();
        // Specialized.
        if (!is_null($this->coupon->userid)) {
            throw new exception('error:coupon_already_used');
        }
    }

    /**
     * Assert other. This can be anything really.
     *
     * @param int $userid user claiming.
     * @throws exception
     */
    public function assert_internal_checks($userid) {
        global $DB;

        // Assert we have at least ONE course we can sign up to..
        // F: gets from block_coupon_courses table the records that has this couponid
        $couponcourses = $DB->get_records('block_couponext_courses', array('couponid' => $this->coupon->id));
        $cansignup = false;
        foreach ($couponcourses as $couponcourse) {
            // F: check if this user id and courseid exist in mdl_enrol joined with mdl_user_enrol
            $ee = enrol_get_enrolment_end($couponcourse->courseid, $userid);
            if ($ee === false) {
                $cansignup = true;
            }
        }
       /*  TODO F: this was commented, check if in general is needed this assert_internal_checks
       if (!$cansignup) {
            throw new exception('error:already-enrolled-in-courses');
        }*/
    }

}

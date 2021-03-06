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
 * Privacy provider.
 *
 * File         provider.php
 * Encoding     UTF-8
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_couponext\privacy;

defined('MOODLE_INTERNAL') || die;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy provider.
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Provides meta data that is stored about a user with block_couponext
     *
     * @param  collection $collection A collection of meta data items to be added to.
     * @return  collection Returns the collection of metadata.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'block_couponext',
            [
                'userid' => 'privacy:metadata:block_couponext:userid',
                'for_user_email' => 'privacy:metadata:block_couponext:for_user_email',
                'for_user_name' => 'privacy:metadata:block_couponext:for_user_name',
                'for_user_gender' => 'privacy:metadata:block_couponext:for_user_gender',
                'email_body' => 'privacy:metadata:block_couponext:email_body',
                'submission_code' => 'privacy:metadata:block_couponext:submission_code',
                'claimed' => 'privacy:metadata:block_couponext:claimed',
                'roleid' => 'privacy:metadata:block_couponext:roleid',
                'timecreated' => 'privacy:metadata:block_couponext:timecreated',
                'timemodified' => 'privacy:metadata:block_couponext:timemodified',
                'timeexpired' => 'privacy:metadata:block_couponext:timeexpired',
            ]
        );
        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        // Add system context.
        $contextlist->add_system_context();

        // I'm unsure if we should also include the course contexts.
        // I'm also unsure if we should include the cohort linked contexts.
        // If we should, we'll implement those too.

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            // Check that the context is a system context.
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }

            // Add owned coupon records.
            $sql = "SELECT c.* FROM {block_couponext} c WHERE c.ownerid = :userid";
            $params = ['userid' => $user->id];
            $alldata = [];
            $coupons = $DB->get_recordset_sql($sql, $params);
            foreach ($coupons as $coupon) {
                $alldata[$context->id][] = (object)[
                        'userid' => $coupon->userid,
                        'for_user_email' => $coupon->for_user_email,
                        'for_user_name' => $coupon->for_user_name,
                        'for_user_gender' => $coupon->for_user_gender,
                        'email_body' => $coupon->email_body,
                        'submission_code' => $coupon->submission_code,
                        'claimed' => transform::yesno($coupon->claimed),
                        'roleid' => $coupon->roleid,
                        'timecreated' => transform::datetime($coupon->timecreated),
                        'timemodified' => transform::datetime($coupon->timemodified),
                        'timeexpired' => transform::datetime($coupon->timeexpired),
                    ];
            }
            $coupons->close();

            // The data is organised in: {?}/ownedcoupons.json.
            array_walk($alldata, function($coupondata, $contextid) {
                $context = \context::instance_by_id($contextid);
                writer::with_context($context)->export_related_data(
                    ['block_couponext'],
                    'ownedcoupons',
                    (object)['coupon' => $coupondata]
                );
            });

            // Add MY coupons.
            $sql = "SELECT c.* FROM {block_couponext} c WHERE c.userid = :userid AND c.ownerid <> :ownerid";
            $params = ['userid' => $user->id, 'ownerid' => $user->id];
            $alldata = [];
            $coupons = $DB->get_recordset_sql($sql, $params);
            foreach ($coupons as $coupon) {
                $alldata[$context->id][] = (object)[
                        'userid' => $coupon->userid,
                        'for_user_email' => $coupon->for_user_email,
                        'for_user_name' => $coupon->for_user_name,
                        'for_user_gender' => $coupon->for_user_gender,
                        'email_body' => $coupon->email_body,
                        'submission_code' => $coupon->submission_code,
                        'claimed' => transform::yesno($coupon->claimed),
                        'roleid' => $coupon->roleid,
                        'timecreated' => transform::datetime($coupon->timecreated),
                        'timemodified' => transform::datetime($coupon->timemodified),
                        'timeexpired' => transform::datetime($coupon->timeexpired),
                    ];
            }
            $coupons->close();

            // The data is organised in: {?}/claimedcoupons.json.
            array_walk($alldata, function($coupondata, $contextid) {
                $context = \context::instance_by_id($contextid);
                writer::with_context($context)->export_related_data(
                    ['block_couponext'],
                    'claimedcoupons',
                    (object)['coupon' => $coupondata]
                );
            });
        }
    }

    /**
     * Delete all use data which matches the specified context.
     *
     * @param context $context The module context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        // Delete all coupon related records.
        $DB->delete_records('block_couponext');
        $DB->delete_records('block_couponext_cohorts');
        $DB->delete_records('block_couponext_groups');
        $DB->delete_records('block_couponext_courses');
        $DB->delete_records('block_couponext_errors');

    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        if (!is_siteadmin($user->id)) {
            // Usemain admin.
            $admin = get_admin();
        } else {
            // User is an admin. Find first OTHER admin to change ownership to.
            $admins = get_admins();
            $next = true;
            while ($next) {
                $current = array_shift($admins);
                if ($current->id != $user->id) {
                    $admin = $current;
                    $next = false;
                }
                if (empty($admins)) {
                    // This case should never EVER happen.
                    $admin = (object)['id' => 0];
                    $next = false;
                }
            }
        }

        foreach ($contextlist->get_contexts() as $context) {
            // Check that the context is a system context.
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }

            // For coupons that are owned by the given user, we will NOT remove them.
            // We will, however, reset the ownership to the main ADMIN.
            $sql = 'UPDATE {block_couponext} SET ownerid = :adminid WHERE ownerid = :userid';
            $params = ['adminid' => $admin->id, 'userid' => $user->id];
            $DB->execute($sql, $params);

            // Now remove any coupons that are connected to the GIVEN user.
            $couponids = $DB->get_fieldset_select('block_couponext', 'id', 'userid = ?', [$user->id]);
            // Delete links and errors.
            $DB->delete_records_list('block_couponext_cohorts', 'couponid', $couponids);
            $DB->delete_records_list('block_couponext_groups', 'couponid', $couponids);
            $DB->delete_records_list('block_couponext_courses', 'couponid', $couponids);
            $DB->delete_records_list('block_couponext_errors', 'couponid', $couponids);
            // Delete coupons.
            $DB->delete_records_list('block_couponext', 'id', $couponids);
        }

    }

}

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
 * Helper
 *
 * File         helper.php
 * Encoding     UTF-8
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      Menno de Ridder <menno@sebsoft.nl>
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_couponext;

defined('MOODLE_INTERNAL') || die();

/**
 * block_couponext\helper
 *
 * Helper class for various functionality
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      Menno de Ridder <menno@sebsoft.nl>
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * __construct() HIDE: WE'RE STATIC
     */
    protected function __construct() {
        // Static's only please!
    }

    /**
     * Collect all courses connected to the provided cohort ID
     *
     * @param int $cohortid cohortidapplicable_formats
     * @return bool false if no courses are connected or an array of course records
     */
    final static public function get_courses_by_cohort($cohortid) {
        global $DB;

        $sql = "
            SELECT c.id, c.fullname FROM {enrol} e
            LEFT JOIN {course} c ON e.courseid = c.id
            WHERE customint1 = ?
            AND e.enrol = 'cohort' AND c.visible = 1 AND c.id != 1
            ORDER BY c.fullname ASC";
        $cohortcourses = $DB->get_records_sql($sql, array($cohortid));

        return (count($cohortcourses) > 0) ? $cohortcourses : false;
    }

    /**
     * Get a list of courses that have NOT been enabled for cohort enrolment for a given cohort.
     *
     * @param int $cohortid
     * @return array
     */
    final static public function get_unconnected_cohort_courses($cohortid) {
        global $DB;

        $sql = "
            SELECT c.id, c.fullname FROM {course} c
            WHERE c.id != 1 AND c.visible = 1
            AND c.id NOT IN (
                SELECT courseid FROM {enrol} e
                WHERE e.customint1 = ?
                AND e.enrol = 'cohort'
            )
            ORDER BY c.fullname ASC";
        $unconnectedcourses = $DB->get_records_sql($sql, array($cohortid));

        return (!empty($unconnectedcourses)) ? $unconnectedcourses : false;
    }

    /**
     * Get a list of all cohorts
     *
     * @param string $fields the fields to get
     * @return array
     */
    static public final function get_cohorts($fields = 'id,name,idnumber') {
        global $DB;
        $cohorts = $DB->get_records('cohort', null, 'name ASC', $fields);
        return (!empty($cohorts)) ? $cohorts : false;
    }

    /**
     * Get a list of all visible courses
     *
     * @param string $fields the fields to get
     * @return array
     */
    static public final function get_visible_courses($fields = 'id,shortname,fullname,idnumber') {
        global $DB;
        $select = "id != 1 AND visible = 1";
        $courses = $DB->get_records_select('course', $select, null, 'fullname ASC', $fields);

        return (!empty($courses)) ? $courses : false;
    }

    /**
     * Get a list of all visible categories, I'm not using it
     *
     * @param string $fields the fields to get
     * @return array
     */
    static public final function get_visible_categories($fields = 'id,name,idnumber') {
        global $DB;
        $select = "id != 1 AND visible = 1";
        $categories = $DB->get_records_select('mdl_course_categories', $select, null, 'fullname ASC', $fields);

        return (!empty($categories)) ? $categories : false;
    }

    /**
     * Get a list of coupons for a given owner.
     * If the owner is NULL or 0, this gets all coupons.
     *
     * @param int|null $ownerid
     * @return array
     */
    static public final function get_coupons_by_owner($ownerid = null) {
        global $DB;

        $params = array();
        $sql = "SELECT * FROM {block_couponext} WHERE userid IS NOT NULL";
        if (!empty($ownerid)) {
            $sql .= "AND ownerid = ?";
            $params[] = $ownerid;
        }
        $coupons = $DB->get_records_sql($sql);

        return (!empty($coupons)) ? $coupons : false;
    }

    /**
     * Gather all coupons that need sending out.
     *
     * @return array
     */
    public static final function get_coupons_to_send() {
        global $DB;
        $senddate = time();
        $sql = "
            SELECT * FROM {block_couponext} v
            WHERE senddate < ? AND issend = 0 AND for_user_email IS NOT NULL";
        $coupons = $DB->get_records_sql($sql, array($senddate), 0, 500);

        return $coupons;
    }

    /**
     * Checks if the cron has send all the coupons generated at specific time by specific owner.
     *
     * @param int $ownerid
     * @param int $timecreated
     * @return bool
     */
    public static final function has_sent_all_coupons($ownerid, $timecreated) {
        global $DB;
        $conditions = array(
            'issend' => 0,
            'ownerid' => $ownerid,
            'timecreated' => $timecreated
        );
        return ($DB->count_records('block_couponext', $conditions) === 0);
    }

    /**
     * Claim a coupon
     *
     * @param string $code coupon submission code
     * @param int $foruserid user for which coupon is claimed. If not given: current user.
     */
    public static function claim_coupon($code, $foruserid = null) {
        global $CFG;
        $instance = couponext\typebase::get_type_instance($code);
        $instance->claim($foruserid);

        return (empty($instance->coupon->redirect_url)) ? $CFG->wwwroot . "/my" : $$instance->coupon->redirect_url;
    }

    /**
     * MailCoupons
     * This function will mail the generated coupons.
     *
     * @param array $coupons An array of generated coupons
     * @param string $emailto The email address the coupons are to be send to
     * @param bool $generatesinglepdfs Whether each coupon gets a PDF or 1 PDF for all coupons
     * @param bool $emailbody string|bool email body or false of it'll be autogenerated
     * @param bool $initiatedbycron whether or not this method was called by cron
     * @param string|null $batchid batch ID
     */
    public static final function mail_coupons($coupons, $emailto, $generatesinglepdfs = false,
            $emailbody = false, $initiatedbycron = false, $batchid = null) {
        global $DB, $CFG;
        raise_memory_limit(MEMORY_HUGE);

        // Prepare time identifier and batchid.
        $ts = date('dmYHis');
        if (empty($batchid)) {
            $batchid = uniqid();
        }

        // One PDF for each coupon.
        if ($generatesinglepdfs) {

            // Initiate archive.
            $zip = new \ZipArchive();
            $relativefilename = "coupons-{$batchid}-{$ts}.zip";
            $filename = "{$CFG->dataroot}/{$relativefilename}";
            if (file_exists($filename)) {
                unlink($filename);
            }

            $zip->open($filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            $increment = 1;
            foreach ($coupons as $coupon) {
                // Generate the PDF.
                $pdfgen = new couponext\pdf(get_string('pdf:titlename', 'block_couponext'));
                // Fill the coupon with text.
                $pdfgen->set_templatemain(get_string('default-coupon-page-template-main', 'block_couponext'));
                $pdfgen->set_templatebotleft(get_string('default-coupon-page-template-botleft', 'block_couponext'));
                $pdfgen->set_templatebotright(get_string('default-coupon-page-template-botright', 'block_couponext'));
                // Generate it.
                $pdfgen->generate($coupon);
                // FI enables storing on local system, this could be nice to have?
                $pdfstr = $pdfgen->Output('coupon_' . $increment . '.pdf', 'S');
                // Add PDF to the zip.
                $zip->addFromString("coupon_$increment.pdf", $pdfstr);
                // And up the increment.
                $increment++;
            }

            $zippedsuccessfully = $zip->close();
            if (!$zippedsuccessfully) {
                // TODO! Future implementation should notify and break processing.
                $zippedsuccessfully = $zippedsuccessfully;
            }

            // All coupons in 1 PDF.
        } else {

            $pdfgen = new couponext\pdf(get_string('pdf:titlename', 'block_couponext'));
            $pdfgen->set_templatemain(get_string('default-coupon-page-template-main', 'block_couponext'));
            $pdfgen->set_templatebotleft(get_string('default-coupon-page-template-botleft', 'block_couponext'));
            $pdfgen->set_templatebotright(get_string('default-coupon-page-template-botright', 'block_couponext'));
            $pdfgen->generate($coupons);

            $relativefilename = "coupons-{$batchid}-{$ts}.pdf";
            $filename = "{$CFG->dataroot}/{$relativefilename}";
            if (file_exists($filename)) {
                unlink($filename);
            }

            $pdfgen->Output($filename, 'F');
        }

        // Try mailing...
        global $USER;
        if ($initiatedbycron) {
            $supportuser = \core_user::get_support_user();
            $firstname = $supportuser->firstname;
            $lastname = $supportuser->lastname;
            $username = $supportuser->username;
            $mailformat = $CFG->defaultpreference_mailformat;
        } else {
            $firstname = $USER->firstname;
            $lastname = $USER->lastname;
            $username = $USER->username;
            $mailformat = $USER->mailformat;
        }
        $recipient = self::get_dummy_user_record($emailto, $firstname, $lastname, $username);
        $recipient->mailformat = $mailformat;

        $from = \core_user::get_noreply_user();
        $subject = get_string('coupon_mail_subject', 'block_couponext');
        // Set email body.
        if ($emailbody !== false) {
            $messagehtml = $emailbody;
        } else {
            $downloadurl = new \moodle_url($CFG->wwwroot . '/blocks/couponext/download.php', ['bid' => $batchid, 't' => $ts]);
            $bodyparams = array(
                'to_name' => fullname($USER),
                'from_name' => fullname($USER),
                'downloadlink' => \html_writer::link($downloadurl, get_string('here', 'block_couponext'))
            );
            $messagehtml = get_string('coupon_mail_content', 'block_couponext', $bodyparams);
        }
        $messagetext = format_text_email($messagehtml, FORMAT_HTML);

        // Try to force &amp; issue in "format_text_email" AGAIN.
        // Various tests have shown the text based email STILL displays "&amp;" entities.
        $messagetext = str_replace('&amp;', '&', $messagetext);
        $mailstatus = static::do_email_to_user($recipient, $from, $subject, $messagetext, $messagehtml);
        // Also send notification in moodle itself.
        if ($mailstatus) {
            couponnotification::send_notification($USER->id, $batchid, $ts);
        }

        if ($mailstatus) {
            // Set the coupons to send state.
            foreach ($coupons as $count => $coupon) {
                $coupon->senddate = time();
                $coupon->issend = 1;
                $DB->update_record('block_couponext', $coupon);
            }
        } else {
            // We NEED a notification somehow.
            foreach ($coupons as $count => $coupon) {
                $error = new \stdClass();
                $error->couponid = $coupon->id;
                $error->errortype = 'email';
                $error->errormessage = get_string('coupon:send:fail', 'block_couponext', 'failed');
                $error->timecreated = time();
                $error->iserror = 1;
                $DB->insert_record('block_couponext_errors', $error);
            }
        }

        return [$mailstatus, $batchid, $ts];
    }

    /**
     * Helper function to return dummy noreply user record.
     *
     * @param string $email
     * @param string $firstname
     * @param string $lastname
     * @param string $username
     * @param int $id
     *
     * @return stdClass
     */
    public static function get_dummy_user_record($email, $firstname, $lastname, $username = 'noreply', $id = -500) {
        $dummyuser = new \stdClass();
        $dummyuser->id = $id;
        $dummyuser->email = $email;
        $dummyuser->firstname = $firstname;
        $dummyuser->username = $username;
        $dummyuser->lastname = $lastname;
        $dummyuser->confirmed = 1;
        $dummyuser->suspended = 0;
        $dummyuser->deleted = 0;
        $dummyuser->picture = 0;
        $dummyuser->auth = 'manual';
        $dummyuser->firstnamephonetic = '';
        $dummyuser->lastnamephonetic = '';
        $dummyuser->middlename = '';
        $dummyuser->alternatename = '';
        $dummyuser->imagealt = '';
        return $dummyuser;
    }

    /**
     * Send confirmation email when the cron has send all the coupons
     *
     * @param int $ownerid
     * @param string $batchid
     * @param int $timecreated
     * @return bool
     */
    public static final function confirm_coupons_sent($ownerid, $batchid, $timecreated) {
        // TODO: DEPRECATE: replaced by notifications :).
        global $DB;

        $owner = $DB->get_record('user', array('id' => $ownerid));
        $supportuser = \core_user::get_noreply_user();
        $a = new \stdClass();
        $a->timecreated = userdate($timecreated, get_string('strftimedate', 'langconfig'));
        $a->batchid = $batchid;
        $messagehtml = get_string("confirm_coupons_sent_body", 'block_couponext', $a);
        $messagetext = format_text_email($messagehtml, FORMAT_MOODLE);
        $subject = get_string('confirm_coupons_sent_subject', 'block_couponext');

        return static::do_email_to_user($owner, $supportuser, $subject, $messagetext, $messagehtml);
    }

    /**
     * Load the course completion info
     *
     * @param object $user User object from database
     * @param object $cinfo Course object from database
     */
    public static final function load_course_completioninfo($user, $cinfo) {
        global $DB, $CFG;
        static $cstatus, $completioninfo = array();

        require_once($CFG->dirroot . '/lib/gradelib.php');
        require_once($CFG->dirroot . '/grade/querylib.php');
        require_once($CFG->dirroot . '/lib/completionlib.php');

        // Completion status 'cache' values (speed up, lass!).
        if ($cstatus === null) {
            $cstatus = array();
            $cstatus['started'] = get_string('report:status_started', 'block_couponext');
            $cstatus['notstarted'] = get_string('report:status_not_started', 'block_couponext');
            $cstatus['complete'] = get_string('report:status_completed', 'block_couponext');
        }
        // Completion info 'cache' (speed up, lass!).
        if (!isset($completioninfo[$cinfo->id])) {
            $completioninfo[$cinfo->id] = new \completion_info($cinfo);
        }

        $ci = new \stdClass();
        $ci->complete = false;
        $ci->str_status = $cstatus['notstarted'];
        $ci->date_started = '-';
        $ci->date_complete = '-';
        $ci->str_grade = '-';
        $ci->gradeinfo = null;

        // Ok, fill out real data according to completion status/info.
        $com = $completioninfo[$cinfo->id];
        if ($com->is_tracked_user($user->id)) {
            // Do we have an enrolment for the course for this user.
            $sql = 'SELECT ue.* FROM {user_enrolments} ue
                    JOIN {enrol} e ON ue.enrolid=e.id
                    WHERE ue.userid = ? AND e.courseid = ?
                    ORDER BY timestart ASC, timecreated ASC';
            $records = $DB->get_records_sql($sql, array($user->id, $cinfo->id));

            if (count($records) === 1) {
                $record = array_shift($records);
                $ci->time_started = (($record->timestart > 0) ? $record->timestart : $record->timecreated);
                $ci->date_started = date('d-m-Y H:i:s', $ci->time_started);
            } else {
                $started = 0;
                $created = 0;

                foreach ($records as $record) {
                    if ($record->timestart > 0) {
                        $started = ($started == 0) ? $record->timestart : min($record->timestart, $started);
                    }
                    $created = ($created == 0) ? $record->timecreated : min($record->timecreated, $created);
                }

                $ci->time_started = (($started > 0) ? $started : $created);
                $ci->date_started = date('d-m-Y H:i:s', ($started > 0) ? $started : $created);
            }

            if ($com->is_course_complete($user->id)) {
                // Fetch details for course completion.
                $ci->complete = true;
                $comcom = new \completion_completion(array(
                    'userid' => $user->id,
                    'course' => $cinfo->id
                ));
                $ci->date_complete = date('d-m-Y H:i:s', $comcom->timecompleted);
                $ci->gradeinfo = grade_get_course_grade($user->id, $cinfo->id);
                if ($ci->gradeinfo !== false) {
                    $ci->str_grade = $ci->gradeinfo->str_grade;
                }
                $ci->str_status = $cstatus['complete'];
            } else {
                $ci->str_status = $cstatus['started'];
            }
        }

        return $ci;
    }

    /**
     * Format a datestring in short or long format
     *
     * @param int $time
     * @param bool $inctime
     * @return string user date
     */
    final static public function render_date($time, $inctime = true) {
        return userdate($time, get_string($inctime ? 'report:dateformat' : 'report:dateformatymd', 'block_couponext'));
    }

    /**
     * Make sure editing mode is off and moodle doesn't use complete overview
     * @param moodle_url $redirecturl
     */
    public static function force_no_editing_mode($redirecturl = '') {
        global $USER, $PAGE;
        if (!empty($USER->editing)) {
            $USER->editing = 0;

            if (empty($redirecturl)) {
                $params = $PAGE->url->params();
                $redirecturl = new \moodle_url($PAGE->url, $params);
            }
            redirect($redirecturl);
        }
    }

    /**
     * Load recipients from a CSV string
     * @param string $recipientsstr
     * @param string $delimiter
     * @return boolean|\stdClass
     */
    public static final function get_recipients_from_csv($recipientsstr, $delimiter = ',') {

        $recipients = array();
        $count = 0;

        // Split up in rows.
        $expectedcolumns = array('e-mail', 'gender', 'name');
        $recipientsstr = str_replace("\r", '', $recipientsstr);
        if (!$csvdata = str_getcsv($recipientsstr, "\n")) {
            return false;
        }
        // Split up in columns.
        foreach ($csvdata as &$row) {

            // Get the next row.
            $row = str_getcsv($row, $delimiter);

            // Check if we're looking at the first row.
            if ($count == 0) {

                $expectedrow = array();
                // Set the columns we'll need.
                foreach ($row as $key => &$column) {

                    $column = trim(strtolower($column));
                    if (!in_array($column, $expectedcolumns)) {
                        continue;
                    }

                    $expectedrow[$key] = $column;
                }
                // If we're missing columns.
                if (count($expectedcolumns) != count($expectedrow)) {
                    return false;
                }

                // Now set which columns we'll need to use when extracting the information.
                $namekey = array_search('name', $expectedrow);
                $emailkey = array_search('e-mail', $expectedrow);
                $genderkey = array_search('gender', $expectedrow);

                $count++;
                continue;
            }

            $recipient = new \stdClass();
            $recipient->name = trim($row[$namekey]);
            $recipient->email = trim($row[$emailkey]);
            $recipient->gender = trim($row[$genderkey]);

            $recipients[] = $recipient;
        }

        return $recipients;
    }

    /**
     * Load coupons from a CSV string
     * @param string $recipientsstr
     * @param string $delimiter
     * @return boolean|\stdClass
     */
    public static final function get_coupons_from_csv($recipientsstr) {

        $recipients = array();
        $count = 0;

        // Split up in rows.
        // $expectedcolumns = array('e-mail', 'gender', 'name');
        $expectedcolumns = array('code');
        /*$recipientsstr = str_replace("\r", '', $recipientsstr);
        if (!$csvdata = str_getcsv($recipientsstr, "\n")) {
            return false;
        }*/
        // Split up in columns.
        $csvdata = str_getcsv($recipientsstr, "\n");
        foreach ($csvdata as &$row) {

            // Get the next row.
            $row = str_getcsv($row);

            // Check if we're looking at the first row.
            if ($count == 0) {

                $expectedrow = array();
                // Set the columns we'll need.
                foreach ($row as $key => &$column) {

                    $column = trim(strtolower($column));
                    if (!in_array($column, $expectedcolumns)) {
                        continue;
                    }

                    $expectedrow[$key] = $column;
                }
                // If we're missing columns.
                if (count($expectedcolumns) != count($expectedrow)) {
                    return false;
                }

                // Now set which columns we'll need to use when extracting the information.
                $couponkey = array_search('code', $expectedrow);
                # $emailkey = array_search('e-mail', $expectedrow);
                # $genderkey = array_search('gender', $expectedrow);

                $count++;
                continue;
            }

            $recipient = new \stdClass();
            $recipient->couponkey = trim($row[$couponkey]);
            // $recipient->email = trim($row[$emailkey]);
            // $recipient->gender = trim($row[$genderkey]);

            $recipients[] = $recipient;
        }

        return $recipients;
    }

    /**
     * Validate given recipients
     * @param array $csvdata
     * @param string $delimiter
     * @return array|true true if valid, array or error messages if invalid
     */
    public static final function validate_coupon_recipients($csvdata, $delimiter) {

        $error = false;
        $maxcoupons = get_config('block_couponext', 'max_coupons');

        if (!$recipients = self::get_recipients_from_csv($csvdata, $delimiter)) {
            // Required columns aren't found in the csv.
            $error = get_string('error:recipients-columns-missing', 'block_couponext', 'e-mail,gender,name');
        } else {
            // No recipient rows were added to the csv.
            if (empty($recipients)) {
                $error = get_string('error:recipients-empty', 'block_couponext');
                // Check max of the file.
            } else if (count($recipients) > $maxcoupons) {
                $error = get_string('error:recipients-max-exceeded', 'block_couponext');
            } else {
                // Lets run through the file to check on email addresses.
                foreach ($recipients as $recipient) {
                    if (!filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                        $error = get_string('error:recipients-email-invalid', 'block_couponext', $recipient);
                    }
                }
            }
        }

        return ($error === false) ? true : $error;
    }

    /**
     * Validate given coupons
     * @param array $csvdata
     * @param string $delimiter
     * @return array|true true if valid, array or error messages if invalid
     */
    public static final function validate_coupon($csvdata) {

        $error = false;
        $maxcoupons = get_config('block_couponext', 'max_coupons');

        if (!$recipients = self::get_coupons_from_csv($csvdata)) {
            // Required columns aren't found in the csv.
            $error = get_string('error:recipients-columns-missing', 'block_couponext', 'e-mail,gender,name');
        } else {
            // No recipient rows were added to the csv.
            if (empty($recipients)) {
                $error = get_string('error:recipients-empty', 'block_couponext');
                // Check max of the file.
            } else if (count($recipients) > $maxcoupons) {
                $error = get_string('error:recipients-max-exceeded', 'block_couponext');
            } else {
                // Lets run through the file to check on coupons.
                foreach ($recipients as $recipient) {
                    // TODO code check if its valid
                    /*if (!filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                        $error = get_string('error:recipients-email-invalid', 'block_couponext', $recipient);
                    }*/
                }
            }
        }

        return ($error === false) ? true : $error;
    }

    /**
     * Get default assigned role to use with coupons.
     *
     * @return false|\stdClass
     */
    public static function get_default_coupon_role() {
        global $DB;
        $config = get_config('block_couponext');
        $role = $DB->get_record('role', array('id' => $config->defaultrole));
        return $role;
    }

    /**
     * Get role menu.
     *
     * @param \context|null $context
     * @param bool $addempty
     * @return false|\stdClass
     */
    public static function get_role_menu($context = null, $addempty = false) {
        $roleoptions = array();
        if ($roles = get_all_roles($context)) {
            $roleoptions = role_fix_names($roles, $context, ROLENAME_ORIGINAL, true);
        }
        if ($addempty) {
            $rs = array('' => get_string('select'));
            foreach ($roleoptions as $k => $v) {
                $rs[$k] = $v;
            }
            $roleoptions = $rs;
        }
        return $roleoptions;
    }

    /**
     * Get course connected to coupons
     *
     * @param \stdClass $coupon
     * @return array result, keys are courseids, values are course shortnames
     */
    public static function get_coupon_courses($coupon) {
        global $DB;
        $sqls = array();
        $params = array();
        $sqls[] = 'SELECT c.id,c.shortname FROM {course} c JOIN {block_couponext_courses} cc ON cc.courseid=c.id AND cc.couponid = ?';
        $params[] = $coupon->id;
        $sqls[] = 'SELECT c.id,c.shortname FROM {block_couponext_cohorts} cc
                JOIN {enrol} e ON (e.customint1=cc.cohortid AND e.enrol=?)
                JOIN {course} c ON e.courseid=c.id
                WHERE cc.couponid = ?';
        $params[] = 'cohort';
        $params[] = $coupon->id;
        $sql = 'SELECT * FROM ((' . implode(') UNION (', $sqls) . ')) x';
        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Get courses connected to all coupons
     *
     * @param bool $includeempty whether or not to include an empty element
     * @return array result, keys are courseids, values are course shortnames
     */
    public static function get_coupon_course_menu($includeempty = true) {
        global $DB;
        $sqls = array();
        $params = array();
        $sqls[] = 'SELECT c.id,c.shortname FROM {course} c JOIN {block_couponext_courses} cc ON cc.courseid=c.id';
        $sqls[] = 'SELECT c.id,c.shortname FROM {block_couponext_cohorts} cc
                JOIN {enrol} e ON (e.customint1=cc.cohortid AND e.enrol=?)
                JOIN {course} c ON e.courseid=c.id
            ';
        $params[] = 'cohort';
        $sql = 'SELECT DISTINCT * FROM ((' . implode(') UNION (', $sqls) . ')) x';
        $rs = $DB->get_records_sql_menu($sql, $params);
        if ($includeempty) {
            $rs = array(0 => '...') + $rs;
        }
        return $rs;
    }

    /**
     * Get cohort connected to all coupons
     *
     * @param bool $includeempty whether or not to include an empty element
     * @return array result, keys are cohort ids, values are cohort names
     */
    public static function get_coupon_cohort_menu($includeempty = true) {
        global $DB;
        $sql = 'SELECT DISTINCT c.id,c.name FROM {block_couponext_cohorts} cc
                JOIN {cohort} c ON cc.cohortid=c.id';
        $rs = $DB->get_records_sql_menu($sql);
        if ($includeempty) {
            $rs = array(0 => '...') + $rs;
        }
        return $rs;
    }

    /**
     * Get batchids connected to all coupons
     *
     * @param bool $includeempty whether or not to include an empty element
     * @return array result, keys are batch ids, values are batch ids
     */
    public static function get_coupon_batch_menu($includeempty = true) {
        global $DB;
        $sql = 'SELECT batchid FROM {block_couponext} c ORDER BY batchid ASC';
        $rs = [];
        if ($includeempty) {
            $rs = array(0 => '...');
        }
        $ids = $DB->get_fieldset_sql($sql);
        foreach ($ids as $id) {
            $rs[$id] = $id;
        }
        return $rs;
    }

    /**
     * Cleanup coupons given the options
     * @param \stdClass $options
     * @param string $operator (SELECT, DELETE)
     * @param string $fields
     */
    public static function cleanup_coupons_query($options, $operator = 'SELECT', $fields = 'id') {
        global $DB;
        $options = (object)(array)$options;
        if (!isset($options->type)) {
            $options->type = 0; // All.
        }
        if (!isset($options->used)) {
            $options->used = 1; // Used only.
        }
        $params = array();
        $where = array();
        // Assemble query.
        // Owner.
        if (!empty($options->ownerid)) {
            $where[] = 'ownerid = :ownerid';
            $params['ownerid'] = $options->ownerid;
        }
        // Timing.
        if (!empty($options->timebefore)) {
            $where[] = 'timecreated < :timebefore';
            $params['timebefore'] = $options->timebefore;
        }
        if (!empty($options->timeafter)) {
            $where[] = 'timecreated > :timeafter';
            $params['timeafter'] = $options->timeafter;
        }
        // Usage.
        switch($options->used) {
            case 2: // Unused.
                $where[] = '(userid IS NULL or userid = 0 OR claimed = 0)';
                break;
            case 1: // Used.
                $where[] = '(userid IS NOT NULL AND userid <> 0 OR claimed = 1)';
                break;
            case 0:
            default:
                break;
        }
        // Removal query.
        if ($options->type == 1) {
            // Course coupons.
            $subselect = 'SELECT DISTINCT couponid FROM {block_couponext_courses}';
            if (!empty($options->course)) {
                list($insql, $inparams) = $DB->get_in_or_equal($options->course, SQL_PARAMS_NAMED, 'courseid', true, 0);
                $subselect .= ' WHERE courseid ' . $insql;
                $where[] = 'id IN ('.$subselect.')';
                $params += $inparams;
            }
        } else if ($options->type == 2) {
            // Cohort coupons.
            $subselect = 'SELECT DISTINCT couponid FROM {block_couponext_cohorts}';
            if (!empty($options->cohort)) {
                list($insql, $inparams) = $DB->get_in_or_equal($options->cohort, SQL_PARAMS_NAMED, 'cohortid', true, 0);
                $subselect .= ' WHERE cohortid ' . $insql;
                $where[] = 'id IN ('.$subselect.')';
                $params += $inparams;
            }
        } else if ($options->type == 3) {
            // Batch coupons.
            if (!empty($options->batchid)) {
                list($insql, $inparams) = $DB->get_in_or_equal($options->batchid, SQL_PARAMS_NAMED, 'batchid', true, 0);
                $where[] = 'batchid '.$insql;
                $params += $inparams;
            }
        }
        $sqlparts = array($operator, $fields, 'FROM {block_couponext}');
        if (!empty($where)) {
            $sqlparts[] = 'WHERE ' . implode(' AND ', $where);
        }
        return array(implode(' ', $sqlparts), $params);

    }

    /**
     * Cleanup coupons given the options
     * @param \stdClass $options
     */
    public static function cleanup_coupons($options) {
        global $DB;
        list($idquery, $idparams) = self::cleanup_coupons_query($options, 'SELECT', 'id');
        $couponids = $DB->get_fieldset_sql($idquery, $idparams);
        if (!empty($couponids)) {
            $DB->delete_records_list('block_couponext', 'id', $couponids);
            $DB->delete_records_list('block_couponext_courses', 'couponid', $couponids);
            $DB->delete_records_list('block_couponext_cohorts', 'couponid', $couponids);
            $DB->delete_records_list('block_couponext_groups', 'couponid', $couponids);
        }
        return count($couponids);
    }

    /**
     * Count coupons given the options
     * @param \stdClass $options
     * @return int number of found coupons given the options
     */
    public static function count_cleanup_coupons($options) {
        global $DB;
        list($idquery, $idparams) = self::cleanup_coupons_query($options, 'SELECT', 'id');
        $couponids = $DB->get_fieldset_sql($idquery, $idparams);
        return count($couponids);
    }

    /**
     * Find first block instance id for block_coupon
     *
     * @return int
     */
    public static function find_block_instance_id() {
        global $DB;
        $recs = $DB->get_records('block_instances', array('blockname' => 'couponext'));
        if (empty($recs)) {
            return 0;
        }
        $rec = reset($recs);
        return $rec->id;
    }

    /**
     * Add selector for generator method to form
     *
     * @param \MoodleQuickForm $mform
     */
    public static function add_generator_method_options($mform) {
        // Determine which type of settings we'll use.
        $radioarray = array();

        $radioarray[] = & $mform->createElement('radio', 'showform', '',
            get_string('showform-csv-coursespecific', 'block_couponext'), 'csv-coursespecific', array('onchange' => 'showHide(this.value)'));
        $radioarray[] = & $mform->createElement('radio', 'showform', '',
                get_string('showform-amount', 'block_couponext'), 'amount', array('onchange' => 'showHide(this.value)'));

        $radioarray[] = & $mform->createElement('radio', 'showform', '',
                get_string('showform-csv', 'block_couponext'), 'csv', array('onchange' => 'showHide(this.value)'));
        $radioarray[] = & $mform->createElement('radio', 'showform', '',
                get_string('showform-manual', 'block_couponext'), 'manual', array('onchange' => 'showHide(this.value)'));
        $mform->addGroup($radioarray, 'radioar', get_string('label:showform', 'block_couponext'), array('<br/>'), false);
        $mform->setDefault('showform', 'csv-coursespecific');
    }

    /**
     * Add element to Moodle form for "amount" settings
     *
     * @param \MoodleQuickForm $mform
     */
    public static function add_amount_generator_elements($mform) {
        // Send coupons based on Amount field.
        $mform->addElement('header', 'amountForm', get_string('heading:amountForm', 'block_couponext'));

        // Set email_to variable.
        $usealternativeemail = get_config('block_couponext', 'use_alternative_email');
        $alternativeemail = get_config('block_couponext', 'alternative_email');

        // Amount of coupons.
        $mform->addElement('text', 'coupon_amount', get_string('label:coupon_amount', 'block_couponext'));
        $mform->setType('coupon_amount', PARAM_INT);
        $mform->addRule('coupon_amount', get_string('error:numeric_only', 'block_couponext'), 'numeric');
        $mform->addRule('coupon_amount', get_string('required'), 'required');
        $mform->addHelpButton('coupon_amount', 'label:coupon_amount', 'block_couponext');

        // Use alternative email address.
        $mform->addElement('checkbox', 'use_alternative_email', get_string('label:use_alternative_email', 'block_couponext'));
        $mform->setType('use_alternative_email', PARAM_BOOL);
        $mform->setDefault('use_alternative_email', $usealternativeemail);

        // Email address to mail to.
        $mform->addElement('text', 'alternative_email', get_string('label:alternative_email', 'block_couponext'));
        $mform->setType('alternative_email', PARAM_EMAIL);
        $mform->setDefault('alternative_email', $alternativeemail);
        $mform->addRule('alternative_email', get_string('error:invalid_email', 'block_couponext'), 'email', null);
        $mform->addHelpButton('alternative_email', 'label:alternative_email', 'block_couponext');
        $mform->disabledIf('alternative_email', 'use_alternative_email', 'notchecked');

        // Generate codesonly checkbox.
        $mform->addElement('checkbox', 'generatecodesonly', get_string('label:generatecodesonly', 'block_couponext'));
        $mform->addHelpButton('generatecodesonly', 'label:generatecodesonly', 'block_couponext');
        $mform->setDefault('generatecodesonly', 1);

        // Generate_pdf checkbox.
        $mform->addElement('checkbox', 'generate_pdf', get_string('label:generate_pdfs', 'block_couponext'));
        $mform->addHelpButton('generate_pdf', 'label:generate_pdfs', 'block_couponext');
        $mform->disabledIf('generate_pdf', 'generatecodesonly', 'checked');

        // Render QR code checkbox.
        $mform->addElement('checkbox', 'renderqrcode', get_string('label:renderqrcode', 'block_couponext'));
        $mform->addHelpButton('renderqrcode', 'label:renderqrcode', 'block_couponext');
        // $mform->setDefault('renderqrcode', 1);
        $mform->disabledIf('renderqrcode', 'generatecodesonly', 'checked');
    }

    /**
     * Add element to Moodle form for "CSV" settings
     *
     * @param \MoodleQuickForm $mform
     * @param string $type coupon type
     */
    public static function add_csv_generator_elements($mform, $type) {
        global $CFG;
        // Determine which mailtemplate to use.
        $mailcontentdefault = '';
        switch ($type) {
            case 'course':
                $mailcontentdefault = get_string('coupon_mail_csv_content', 'block_couponext');
                break;
            case 'cohort':
                $mailcontentdefault = get_string('coupon_mail_csv_content_cohorts', 'block_couponext');
                break;
        }
        // Send coupons based on CSV upload.
        $mform->addElement('header', 'csvForm', get_string('heading:csvForm', 'block_couponext'));

        // Filepicker.
        $urldownloadcsv = new \moodle_url($CFG->wwwroot . '/blocks/couponext/sample.csv');
        $mform->addElement('filepicker', 'coupon_recipients',
                get_string('label:coupon_recipients', 'block_couponext'), null, array('accepted_types' => 'csv'));
        $mform->addHelpButton('coupon_recipients', 'label:coupon_recipients', 'block_couponext');
        $mform->addElement('static', 'coupon_recipients_desc', '', get_string('coupon_recipients_desc', 'block_couponext'));
        $mform->addElement('static', 'sample_csv', '', '<a href="' . $urldownloadcsv
                . '" target="_blank">' . get_string('download-sample-csv', 'block_couponext') . '</a>');

        $choices = self::get_delimiter_list();
        $mform->addElement('select', 'csvdelimiter', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('csvdelimiter', 'semicolon');
        } else {
            $mform->setDefault('csvdelimiter', 'comma');
        }

        // Editable email message.
        $mform->addElement('editor', 'email_body', get_string('label:email_body', 'block_couponext'), array('noclean' => 1));
        $mform->setType('email_body', PARAM_RAW);
        $mform->setDefault('email_body', array('text' => $mailcontentdefault));
        $mform->addRule('email_body', get_string('required'), 'required');
        $mform->addHelpButton('email_body', 'label:email_body', 'block_couponext');

        // Configurable enrolment time.
        $mform->addElement('date_selector', 'date_send_coupons', get_string('label:date_send_coupons', 'block_couponext'));
        $mform->addRule('date_send_coupons', get_string('required'), 'required');
        $mform->addHelpButton('date_send_coupons', 'label:date_send_coupons', 'block_couponext');
    }

    /**
     * Add element to Moodle form for "csv-coursespecific" settings
     *
     * @param \MoodleQuickForm $mform
     * @param string $type coupon type
     */
    public static function add_csv_coursespecific_generator_elements($mform, $type) {
        global $CFG;
        $mailcontentdefault = get_string('coupon_mail_csv_content_coursespecific', 'block_couponext');

        // Send coupons based on CSV upload.
        $mform->addElement('header', 'csvForm', get_string('heading:csvForm', 'block_couponext'));

        // Filepicker.
        $urldownloadcsv = new \moodle_url($CFG->wwwroot . '/blocks/couponext/couponspecific_sample.csv');
        $mform->addElement('filepicker', 'coupon',
            get_string('label:coupon', 'block_couponext'), null, array('accepted_types' => 'csv'));
        $mform->addHelpButton('coupon', 'label:coupon', 'block_couponext');
        $mform->addElement('static', 'coupon_recipients_desc', '', get_string('coupon_recipients_desc', 'block_couponext'));
        $mform->addElement('static', 'couponspecific_sample_csv', '', '<a href="' . $urldownloadcsv
            . '" target="_blank">' . get_string('download-sample-csv', 'block_couponext') . '</a>');

/*      $choices = self::get_delimiter_list();
        $mform->addElement('select', 'csvdelimiter', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('csvdelimiter', 'semicolon');
        } else {
            $mform->setDefault('csvdelimiter', 'comma');
        }*/

        // Editable email message.
/*      $mform->addElement('editor', 'email_body', get_string('label:email_body', 'block_couponext'), array('noclean' => 1));
        $mform->setType('email_body', PARAM_RAW);
        $mform->setDefault('email_body', array('text' => $mailcontentdefault));
        $mform->addRule('email_body', get_string('required'), 'required');
        $mform->addHelpButton('email_body', 'label:email_body', 'block_couponext');*/

        // Configurable enrolment time.
        $mform->addElement('date_selector', 'date_send_coupons', get_string('label:date_send_coupons', 'block_couponext'));
        $mform->addRule('date_send_coupons', get_string('required'), 'required');
        $mform->addHelpButton('date_send_coupons', 'label:date_send_coupons', 'block_couponext');
    }

    /**
     * Add element to Moodle form for "Manual recipients" settings
     *
     * @param \MoodleQuickForm $mform
     * @param string $type coupon type
     */
    public static function add_manual_generator_elements($mform, $type) {
        // Determine which mailtemplate to use.
        $mailcontentdefault = '';
        switch ($type) {
            case 'course':
                $mailcontentdefault = get_string('coupon_mail_csv_content', 'block_couponext');
                break;
            case 'cohort':
                $mailcontentdefault = get_string('coupon_mail_csv_content_cohorts', 'block_couponext');
                break;
        }
        // Send coupons based on CSV upload.
        $mform->addElement('header', 'manualForm', get_string('heading:manualForm', 'block_couponext'));

        // Textarea recipients.
        $mform->addElement('textarea', 'coupon_recipients_manual',
                get_string("label:coupon_recipients", 'block_couponext'), 'rows="10" cols="100"');
        $mform->addRule('coupon_recipients_manual', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('coupon_recipients_manual', 'label:coupon_recipients_txt', 'block_couponext');
        $mform->setDefault('coupon_recipients_manual', 'E-mail,Gender,Name');

        $mform->addElement('static', 'coupon_recipients_desc', '', get_string('coupon_recipients_desc', 'block_couponext'));

        // Editable email message.
        $mform->addElement('editor', 'email_body_manual', get_string('label:email_body', 'block_couponext'), array('noclean' => 1));
        $mform->setType('email_body_manual', PARAM_RAW);
        $mform->setDefault('email_body_manual', array('text' => $mailcontentdefault));
        $mform->addRule('email_body_manual', get_string('required'), 'required');
        $mform->addHelpButton('email_body_manual', 'label:email_body', 'block_couponext');

        // Configurable enrolment time.
        $mform->addElement('date_selector', 'date_send_coupons_manual', get_string('label:date_send_coupons', 'block_couponext'));
        $mform->addRule('date_send_coupons_manual', get_string('required'), 'required');
        $mform->addHelpButton('date_send_coupons_manual', 'label:date_send_coupons', 'block_couponext');
    }

    /**
     * Get list of cvs delimiters
     *
     * @return array suitable for selection box
     */
    public static function get_delimiter_list() {
        $delimiters = array('comma' => ',', 'semicolon' => ';', 'colon' => ':', 'tab' => '\\t');
        return $delimiters;
    }

    /**
     * Get delimiter character
     *
     * @param string $delimitername separator name
     * @return string delimiter char
     */
    public static function get_delimiter($delimitername) {
        switch ($delimitername) {
            case 'colon':
                return ':';
            case 'semicolon':
                return ';';
            case 'tab':
                return "\t";
            case 'comma':
                return ',';
            default:
                return ',';  // If anything else comes in, default to comma.
        }
    }

    /**
     * Add default configuration form elements for the coupon generator for course or cohort type coupons.
     *
     * @param \MoodleQuickForm $mform
     * @param string $type 'cohort' or 'course'
     */
    public static function std_coupon_add_default_confirm_form_elements($mform, $type) {
        $mform->addElement('header', 'header', get_string('heading:info', 'block_couponext'));
        if (!$strinfo = get_config('block_couponext', 'info_coupon_confirm')) {
            $strinfo = get_string('missing_config_info', 'block_couponext');
        }
        $mform->addElement('static', 'info', '', $strinfo);

        self::add_generator_method_options($mform, $type);

        // Add elements for when we'd be generating arbitrary amounts.
        self::add_amount_generator_elements($mform);

        // Add elements for when we'd be generating based on CSV upload.
        self::add_csv_generator_elements($mform, $type);

        // Add elements for when we'd be generating based on manual entries.
        self::add_manual_generator_elements($mform, $type);
    }

    /**
     * Get all coupons based on the given parameters
     *
     * @param string $type Type of coupons to get reports for ('course', 'cohort', 'enrolext' or 'all' (default))
     * @param int $ownerid ID of the creator of the coupons.
     * @param date $fromdate Request coupon reports created from this date.
     *          If given this should be passed in American format (yyyy-mm-dd)
     * @param date $todate Request coupon reports created until this date.
     *          If given this should be passed in American format (yyyy-mm-dd)
     */
    public static function get_all_coupons($type = 'all', $ownerid = null, $fromdate = null, $todate = null) {
        global $DB;
        $params = [];
        $where = [];
        if ($type !== 'all') {
            $where[] = 'type = :typ';
            $params['typ'] = $type;
        }
        if (!empty($ownerid)) {
            $where[] = 'ownerid = :ownerid';
            $params['ownerid'] = $ownerid;
        }
        if (!empty($fromdate)) {
            $where[] = 'timecreated >= :fromdate';
            $params['fromdate'] = $fromdate;
        }
        if (!empty($todate)) {
            $where[] = 'timecreated <= :todate';
            $params['todate'] = $todate;
        }
        $sql = 'SELECT c.id, c.submission_code, c.timecreated, c.claimed, c.userid, c.typ
            , ' . $DB->sql_fullname() . ' as userfullname, u.email as useremail, u.idnumber as useridnumber
            FROM {block_couponext} c
            LEFT JOIN {user} u ON c.userid=u.id';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * MailCoupons
     * This function will mail the generated coupons.
     *
     * @param \stdClass $user request user
     * @param array $coupons An array of generated coupons
     * @param coupon\generatoroptions $generatoroptions
     * @param string $extramessage
     */
    public static final function mail_requested_coupons($user, $coupons, $generatoroptions, $extramessage = '') {
        global $DB, $CFG;
        raise_memory_limit(MEMORY_HUGE);

        // Prepare time identifier and batchid.
        $ts = date('dmYHis');
        if (empty($generatoroptions->batchid)) {
            $generatoroptions->batchid = uniqid();
        }

        // One PDF for each coupon.
        if ($generatoroptions->generatesinglepdfs) {

            // Initiate archive.
            $zip = new \ZipArchive();
            $relativefilename = "coupons-{$generatoroptions->batchid}-{$ts}.zip";
            $filename = "{$CFG->dataroot}/{$relativefilename}";
            if (file_exists($filename)) {
                unlink($filename);
            }

            $zip->open($filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            $increment = 1;
            foreach ($coupons as $coupon) {
                // Generate the PDF.
                $pdfgen = new couponext\pdf(get_string('pdf:titlename', 'block_couponext'));
                // Fill the coupon with text.
                $pdfgen->set_templatemain(get_string('default-coupon-page-template-main', 'block_couponext'));
                $pdfgen->set_templatebotleft(get_string('default-coupon-page-template-botleft', 'block_couponext'));
                $pdfgen->set_templatebotright(get_string('default-coupon-page-template-botright', 'block_couponext'));
                // Generate it.
                $pdfgen->generate($coupon);
                // FI enables storing on local system, this could be nice to have?
                $pdfstr = $pdfgen->Output('coupon_' . $increment . '.pdf', 'S');
                // Add PDF to the zip.
                $zip->addFromString("coupon_$increment.pdf", $pdfstr);
                // And up the increment.
                $increment++;
            }

            $zippedsuccessfully = $zip->close();
            if (!$zippedsuccessfully) {
                // TODO! Future implementation should notify and break processing.
                $zippedsuccessfully = $zippedsuccessfully;
            }

            // All coupons in 1 PDF.
        } else {

            $pdfgen = new couponext\pdf(get_string('pdf:titlename', 'block_couponext'));
            $pdfgen->set_templatemain(get_string('default-coupon-page-template-main', 'block_couponext'));
            $pdfgen->set_templatebotleft(get_string('default-coupon-page-template-botleft', 'block_couponext'));
            $pdfgen->set_templatebotright(get_string('default-coupon-page-template-botright', 'block_couponext'));
            $pdfgen->generate($coupons);

            $relativefilename = "coupons-{$generatoroptions->batchid}-{$ts}.pdf";
            $filename = "{$CFG->dataroot}/{$relativefilename}";
            if (file_exists($filename)) {
                unlink($filename);
            }

            $pdfgen->Output($filename, 'F');
        }

        if (!empty($generatoroptions->emailto)) {
            $user->email = $generatoroptions->emailto;
        }

        $from = \core_user::get_noreply_user();

        $downloadurl = new \moodle_url($CFG->wwwroot . '/blocks/couponext/download.php',
                ['bid' => $generatoroptions->batchid, 't' => $ts]);
        $a = new \stdClass();
        $a->fullname = fullname($user);
        $a->signoff = generate_email_signoff();
        $a->downloadlink = \html_writer::link($downloadurl, get_string('here', 'block_couponext'));
        $a->custommessage = '';
        if (!empty($extramessage)) {
            $a->custommessage = get_string('request:accept:custommessage', 'block_couponext', $extramessage);
        }

        $subject = get_string('request:accept:subject', 'block_couponext', $a);
        $messagehtml = get_string('request:accept:content', 'block_couponext', $a);
        $messagetext = format_text_email($messagehtml, FORMAT_HTML);

        // Try to force &amp; issue in "format_text_email" AGAIN.
        // Various tests have shown the text based email STILL displays "&amp;" entities.
        $messagetext = str_replace('&amp;', '&', $messagetext);
        $mailstatus = static::do_email_to_user($user, $from, $subject, $messagetext, $messagehtml);
        // Also send notification in moodle itself.
        if ($mailstatus) {
            couponnotification::send_request_accept_notification($user->id, $generatoroptions->batchid, $ts, $extramessage);
        }

        if ($mailstatus) {
            // Set the coupons to send state.
            foreach ($coupons as $count => $coupon) {
                $coupon->senddate = time();
                $coupon->issend = 1;
                $DB->update_record('block_couponext', $coupon);
            }
        } else {
            // We NEED a notification somehow.
            foreach ($coupons as $count => $coupon) {
                $error = new \stdClass();
                $error->couponid = $coupon->id;
                $error->errortype = 'email';
                $error->errormessage = get_string('coupon:send:fail', 'block_couponext', 'failed');
                $error->timecreated = time();
                $error->iserror = 1;
                $DB->insert_record('block_couponext_errors', $error);
            }
        }

        return [$mailstatus, $generatoroptions->batchid, $ts];
    }

    /**
     * Send an email to a specified user.
     *
     * Mimicing Moodle here and storing the results.
     * We keep on getting issues with mail not being sent, so we decided to log EVERYTHING.
     *
     * @param stdClass $user  A {@link $USER} object
     * @param stdClass $from A {@link $USER} object
     * @param string $subject plain text subject line of the email
     * @param string $messagetext plain text version of the message
     * @param string $messagehtml complete html version of the message (optional)
     * @param string $attachment a file, either relative to $CFG->dataroot or a full path to a file in $CFG->tempdir
     * @param string $attachname the name of the file (extension indicates MIME)
     * @param bool $usetrueaddress determines whether $from email address should
     *          be sent out. Will be overruled by user profile setting for maildisplay
     * @param string $replyto Email address to reply to
     * @param string $replytoname Name of reply to recipient
     * @param int $wordwrapwidth custom word wrap width, default 79
     * @return bool Returns true if mail was sent OK and false if there was an error.
     */
    public static function do_email_to_user($user, $from, $subject, $messagetext, $messagehtml = '',
            $attachment = '', $attachname = '', $usetrueaddress = true,
            $replyto = '', $replytoname = '', $wordwrapwidth = 79) {
        global $CFG, $DB;
        $debuglevel = $CFG->debug;
        $CFG->debug = DEBUG_DEVELOPER; // Highest level.

        ob_start();
        $result = email_to_user($user, $from, $subject, $messagetext, $messagehtml,
                $attachment, $attachname, $usetrueaddress, $replyto, $replytoname, $wordwrapwidth);
        $debugstr = ob_get_clean();
        if ($result === false || !empty($debugstr)) {
            $debugstr = 'Sending email to ' . fullname($user) . ' (' . $user->email . ') from ' .
                fullname($from) . ' (' . $from->email . ')<br/><br/>' . $debugstr;
        }

        if (!empty($debugstr)) {
            // Store "error" record.
            $error = new \stdClass();
            $error->couponid = 0;
            $error->errortype = 'debugemail';
            $error->errormessage = strip_tags($debugstr, 'ul,li,p,pre,br');
            $error->timecreated = time();
            $error->iserror = ($result ? 0 : 1);
            $DB->insert_record('block_couponext_errors', $error);
        }

        // Reset old level!
        $CFG->debug = $debuglevel;
        return $result;
    }


    /**
     * Checks if the cron has send all the coupons generated at specific time by specific owner.
     *
     * @param int $userid
     * @param int $couponcode
     * @return bool
     */
    public static final function already_enroled_check($userid, $couponcode) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/cohort/lib.php');
        // Get record.
        $conditions = array(
            'submission_code' => $couponcode,
            'claimed' => 0,
        );
        $coupon = $DB->get_record('block_couponext', $conditions);
        if (empty($coupon)) {
            return true;
        }
        switch ($coupon->typ) {
            case coupon\generatoroptions::COURSE:
            case coupon\generatoroptions::COURSESPECIFIC:
                break;
            case coupon\generatoroptions::ENROLEXTENSION:
                $couponcourses = $DB->get_records('block_couponext_courses', array('couponid' => $coupon->id));
                $cansignup = false;
                foreach ($couponcourses as $couponcourse) {
                    $ee = enrol_get_enrolment_end($couponcourse->courseid, $userid);
                    if ($ee === false) {
                        $cansignup = true;
                    }
                }
                if (!$cansignup) {
                    throw new exception('error:already-enrolled-in-courses');
                }
                break;
            case coupon\generatoroptions::COHORT:
                $couponcohorts = $DB->get_records('block_couponext_cohorts', array('couponid' => $coupon->id));
                $cansignup = false;
                foreach ($couponcohorts as $couponcohort) {
                    $ee = cohort_is_member($couponcohort->cohortid, $userid);
                    if ($ee === false) {
                        $cansignup = true;
                    }
                }
                if (!$cansignup) {
                    throw new exception('error:already-enrolled-in-cohorts');
                }
                break;
        }
    }

}

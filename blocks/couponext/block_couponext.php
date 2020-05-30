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
 * Block implementation
 *
 * File         block_couponext.php
 * Encoding     UTF-8
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      Menno de Ridder <menno@sebsoft.nl>
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_couponext\helper;

defined('MOODLE_INTERNAL') || die();

// require_once($CFG->libdir . '/formslib.php');

/**
 * block_couponext
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      Menno de Ridder <menno@sebsoft.nl>
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_couponext extends block_base { //F: the class name must start with block, the coupon part could change

    /**
     * initializes block
     */
    public function init() {
        //F: the init method purpose is to give values to any class member variables that need instantiating.
        global $CFG; //F: Moodle configuration file config.php
        $this->title = get_string('blockname', 'block_couponext'); //F: the title displayed in the header of our block, blockname is defined in the /lang language file
        include($CFG->dirroot . '/blocks/couponext/version.php');
        $this->version = $plugin->version;
        $this->cron = $plugin->cron;
    }

    /**
     * Get/load block contents
     * @return stdClass
     */
    public function get_content() {
        global $CFG, $DB, $USER; // F: $DB Database connection. Used for all access to the database.
        if ($this->content !== null) { // F: This variable holds all the actual content that is displayed inside each block. Valid values for it are either NULL or an object of class stdClass,
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = ''; // F: This is a string of arbitrary length and content. It is displayed inside the main area of the block, and can contain HTML.
        $this->content->footer = ''; // F: This is a string of arbitrary length and contents. It is displayed below the text, using a smaller font size. It can also contain HTML.

        if (empty($this->instance)) { // F: $this->instance is a member variable that holds all the specific information that differentiates one block instance (i.e., the PHP object that embodies it) from another. It is an object of type stdClass retrieved by calling get_record on the table mdl_block_instance. Its member variables, then, directly correspond to the fields of that table. It is initialized immediately after the block object itself is constructed.
            print_error('No instance ' . 'block_couponext');
        }

        $arrparams = array();
        $arrparams['id'] = $this->instance->id;
        $arrparams['courseid'] = $this->course->id;

        // We'll fill the array of menu items with everything the logged in user has permission to.
        $menuitems = array();

        // The "button class" for links.
        $linkseparator = '<br/>'; // renders the links of the block on new line
        $cfgbuttonclass = get_config('block_couponext', 'buttonclass'); // F: prende dalla pagina di settings quello che è setato nel DB soto mdl_config_plugins per buttonclass
        $btnclass = 'btn-coupon';
        if ($cfgbuttonclass != 'none') {
            $btnclass .= ' '. $cfgbuttonclass;
            $linkseparator = '<br/>';
        }
        // Generate Coupon.
        $baseparams = array('id' => $this->instance->id);
        if (has_capability('block/couponext:generatecoupons', $this->context)) {
            $urlgeneratecoupons = new moodle_url($CFG->wwwroot . '/blocks/couponext/view/generator/index.php', $baseparams);
            $menuitems[] = html_writer::link($urlgeneratecoupons,
                    get_string('url:generate_coupons', 'block_couponext'), ['class' => $btnclass]);

            $urlmanagelogos = new moodle_url($CFG->wwwroot . '/blocks/couponext/view/managelogos.php', $baseparams);
            $menuitems[] = html_writer::link($urlmanagelogos,
                    get_string('url:managelogos', 'block_couponext'), ['class' => $btnclass]);

            // Add link to requests.
            $requestusersurl = new \moodle_url($CFG->wwwroot . '/blocks/couponext/view/requests/admin.php',
                    $baseparams + ['action' => 'users']);
            $menuitems[] = html_writer::link($requestusersurl,
                    get_string('tab:requestusers', 'block_couponext'), ['class' => $btnclass]);
            $requestsurl = new \moodle_url($CFG->wwwroot . '/blocks/couponext/view/requests/admin.php',
                    $baseparams + ['action' => 'requests']);
            $menuitems[] = html_writer::link($requestsurl,
                    get_string('tab:requests', 'block_couponext'), ['class' => $btnclass]);
        }

        // View Reports.
        if (has_capability('block/couponext:viewreports', $this->context)) {
            $urlreports = new moodle_url($CFG->wwwroot . '/blocks/couponext/view/reports.php', $baseparams);
            $urlunusedreports = new moodle_url($CFG->wwwroot . '/blocks/couponext/view/couponview.php',
                    array('id' => $this->instance->id, 'tab' => 'unused'));

            $menuitems[] = html_writer::link($urlreports,
                    get_string('url:view_reports', 'block_couponext'), ['class' => $btnclass]);
            $menuitems[] = html_writer::link($urlunusedreports,
                    get_string('url:view_unused_coupons', 'block_couponext'), ['class' => $btnclass]);
        }

        // Input Coupon.
        // F: Input Coupon & select course.
        if (has_capability('block/couponext:inputcoupons', $this->context)) {
            $urlinputcoupon = new moodle_url($CFG->wwwroot . '/blocks/couponext/view/input_coupon.php', $baseparams);

            // F: get courses, I do not use $mform since I should extend this class also with \moodleform (multiple extend can't be done).
            $courses = helper::get_visible_courses();
            // And create data for select.
            $arrcoursesselect = array();
            foreach ($courses as $course) {
                $arrcoursesselect[$course->id] = $course->fullname;
            }
            $attributes = array('size' => min(20, count($arrcoursesselect)));

            $couponform = "
                <form action='$urlinputcoupon' method='post' class='form-group'>
                    <table>
                        <tr><td>" . get_string('label:enter_coupon_code', 'block_couponext') . ":</td></tr>
                        <tr><td><input type='text' name='coupon_code' placeholder='Inserisci codice' class='form-control' required></td></tr>
                        
                        <tr> <!-- F: the select box for selecting a course to which to enrol! -->
                             <td>" . $this->create_select_course_box($courses) . "</td> 
                        </tr>
                        <tr><td><input type='submit' name='submitbutton' value='"
                . get_string('button:submit_coupon_code', 'block_couponext') . "'></td></tr>
                    </table>
                    <input type='hidden' name='id' value='{$this->instance->id}' />
                    <input type='hidden' name='submitbutton' value='Submit Coupon' />
                    <input type='hidden' name='_qf__block_couponext_forms_coupon_validator' value='1' />
                    <input type='hidden' name='sesskey' value='" . sesskey() . "' />
                </form>";

            // $mform ->addElement('header', 'header', get_string('heading:input_course', 'block_couponext'));

            $displayinputhelp = (bool)get_config('block_couponext', 'displayinputhelp'); // F: prende dalla pagina di settings quello che è setato nel DB soto mdl_config_plugins per buttonclass
            if ($displayinputhelp) {
                $menuitems[] = "<div>".get_string('str:inputhelp', 'block_couponext')."<br/>{$couponform}</div>" ;

            } else {
                $menuitems[] = $couponform;
            }
        }

        // Signup using a coupon.
        if (!isloggedin() || isguestuser()) {
            $urlsignupcoupon = new moodle_url($CFG->wwwroot . '/blocks/couponext/view/signup.php', $baseparams);
            $signupurl = html_writer::link($urlsignupcoupon,
                    get_string('url:couponsignup', 'block_couponext'), ['class' => $btnclass]);
            $displaysignuphelp = (bool)get_config('block_couponext', 'displayregisterhelp'); // F: prende dalla pagina di settings quello che è setato nel DB soto mdl_config_plugins per buttonclass
            if ($displaysignuphelp) {
                $menuitems[] = "<div>".get_string('str:signuphelp', 'block_couponext')."<br/>{$signupurl}</div>";

            } else {
                $menuitems[] = $signupurl;
            }
        }

        // Add link to ability to request coupons if applicable.
        if ($DB->record_exists('block_couponext_rusers', ['userid' => $USER->id])) {
            $urlrequestcoupon = new moodle_url($CFG->wwwroot . '/blocks/couponext/view/requests/userrequest.php', $baseparams);
            $menuitems[] = html_writer::link($urlrequestcoupon,
                    get_string('request:coupons', 'block_couponext'), ['class' => $btnclass]);
        }

        // Now print the menu blocks.
        foreach ($menuitems as $item) {
            $this->content->footer .= $item . $linkseparator; // F: Here goes the Generate Coupon link ; Manage Coupon Img; Coupon Request Users; Coupon requests; View ReportsM Viuw unused coupons
        }
    }

    /**
     * Which page types this block may appear on.
     *
     * @return array page-type prefix => true/false.
     */
    public function applicable_formats() {
        return array('site-index' => true, 'my' => true); //F: if 'my' is true than this block is visible on My Moodle page
    }

    /**
     * block specialization
     * F:  it's guaranteed to be automatically called by Moodle as soon as our instance configuration is loaded and available
     * (that is, immediately after init() is called). That means before the block's content is computed for the first time,
     * and indeed before anything else is done with the block. Thus, providing a specialization() method is the natural choice
     * for any configuration data that needs to be acted upon or made available "as soon as possible", as in this case.
     */
    public function specialization() {
        global $COURSE;
        $this->course = $COURSE;
    }

    /**
     * Is each block of this type going to have instance-specific configuration?
     *
     * @return bool true
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Allow multiple instances of this block?
     *
     * @return bool false
     */
    public function instance_allow_multiple() {
        # return false;
        return true; // Permette di avere più di una istanza del blocco sulla stessa pagina.
    }

    /**
     * Do we hide the block header?
     *
     * @return bool false
     */
    public function hide_header() {
        return false;
    }

    /**
     * Run cron job
     *
     * @deprecated since Moodle 2.6
     * @return bool true always
     */
    public function cron() {
        return true;
    }

    /**
     * has own config?
     *
     * @return bool true
     */
    public function has_config() {
        return true; //F: se il plugin ha un suo settings.php, allora return true
    }


    /**
     * Generate the select course box
     *
     * @param $courses
     * @return string
     */
    public function create_select_course_box($courses){
        $out = '<select name="course_id" class="form-control">';
        $out .= '<option value="null">--Seleziona Corso</option>';
        foreach ($courses as $course){
            // $out .= '<option>' . $course->fullname . '</option>';
            $out .= "<option value=\"" . $course->id . "\" >" . $course->fullname . '</option>';
        }
        $out .= '</select>';
        return $out;
    }
}
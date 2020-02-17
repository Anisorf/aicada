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
 * Service definitions for block_coupon
 *
 * File         webservices.php
 * Encoding     UTF-8
 *
 * @package     block_coupon
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

// F: Declare the service
// This step is optional. You can pre-build a service including any web service functions, so the Moodle administrator doesn't need to do it.
$services = array(
    'couponservice' => array(           // F: the name of the web service
        'functions' => array(           // F: web service functions of this service
            'block_coupon_get_courses',
            'block_coupon_get_cohorts',
            'block_coupon_get_course_groups',
            'block_coupon_request_coupon_codes_for_course',
            'block_coupon_generate_coupons_for_course',
            'block_coupon_request_coupon_codes_for_cohorts',
            'block_coupon_generate_coupons_for_cohorts',
            'block_coupon_get_coupon_reports',
            'block_coupon_find_users',
            'block_coupon_find_courses',
        ),
        'requiredcapability' => '',  // F: if set, the web service user need this capability to access
        'restrictedusers' => 0,      // F: if enabled, the Moodle administrator must link some user to this service
        //into the administration
        'enabled' => 1,              // F: if enabled, the service can be reachable on a default installation
    )
);

// F: Declare the web service function
$functions = array(
    'block_coupon_get_courses' => array(                // F: web service function name
        'classname' => 'block_coupon_external',         // F: class containing the external function
        'methodname' => 'get_courses',                  // F: external function name
        'classpath' => 'blocks/coupon/externallib.php', // F: file containing the class/external function
        'description' => 'Get courses.',                // F: human readable description of the web service function
        'type' => 'read',                               // F: database rights of the web service function (read, write)
        'ajax' => true                                  // F: is the service available to 'internal' ajax calls.
    ),
    'block_coupon_get_cohorts' => array(
        'classname' => 'block_coupon_external',
        'methodname' => 'get_cohorts',
        'classpath' => 'blocks/coupon/externallib.php',
        'description' => 'Get cohorts.',
        'type' => 'read',
        'ajax' => true
    ),
    'block_coupon_get_course_groups' => array(
        'classname' => 'block_coupon_external',
        'methodname' => 'get_course_groups',
        'classpath' => 'blocks/coupon/externallib.php',
        'description' => 'Get course groups.',
        'type' => 'read',
        'ajax' => true
    ),
    'block_coupon_request_coupon_codes_for_course' => array(
        'classname' => 'block_coupon_external',
        'methodname' => 'request_coupon_codes_for_course',
        'classpath' => 'blocks/coupon/externallib.php',
        'description' => 'Generate coupon codes for a course.',
        'type' => 'write',
        'ajax' => true
    ),
    'block_coupon_generate_coupons_for_course' => array(
        'classname' => 'block_coupon_external',
        'methodname' => 'generate_coupons_for_course',
        'classpath' => 'blocks/coupon/externallib.php',
        'description' => 'Generate coupons for a course.',
        'type' => 'write',
        'ajax' => true
    ),
    'block_coupon_request_coupon_codes_for_cohorts' => array(
        'classname' => 'block_coupon_external',
        'methodname' => 'request_coupon_codes_for_cohorts',
        'classpath' => 'blocks/coupon/externallib.php',
        'description' => 'Generate coupon codes for cohort(s).',
        'type' => 'write',
        'ajax' => true
    ),
    'block_coupon_generate_coupons_for_cohorts' => array(
        'classname' => 'block_coupon_external',
        'methodname' => 'generate_coupons_for_cohorts',
        'classpath' => 'blocks/coupon/externallib.php',
        'description' => 'Generate coupons for cohort(s).',
        'type' => 'write',
        'ajax' => true
    ),
    'block_coupon_get_coupon_reports' => array(
        'classname' => 'block_coupon_external',
        'methodname' => 'get_coupon_reports',
        'classpath' => 'blocks/coupon/externallib.php',
        'description' => 'Get coupon reports.',
        'type' => 'read',
        'ajax' => true
    ),
    'block_coupon_find_users' => array(
        'classname' => 'block_coupon_external',
        'methodname' => 'find_users',
        'classpath' => 'blocks/coupon/externallib.php',
        'description' => 'Find users.',
        'type' => 'read',
        'ajax' => true
    ),
    'block_coupon_find_courses' => array(
        'classname' => 'block_coupon_external',
        'methodname' => 'find_courses',
        'classpath' => 'blocks/coupon/externallib.php',
        'description' => 'Find courses.',
        'type' => 'read',
        'ajax' => true
    ),
);

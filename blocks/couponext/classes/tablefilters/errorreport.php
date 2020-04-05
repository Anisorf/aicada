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
 * this file contains the table filter for the errorreport table
 *
 * File         errorreport.php
 * Encoding     UTF-8
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_couponext\tablefilters;

defined('MOODLE_INTERNAL') || die();

use \block_couponext\filtering\filtering;

/**
 * block_couponext\tables\errorreport
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class errorreport extends filtering {

    /**
     * Return all default filter names and advanced status
     * @return array
     */
    public function get_fields() {
        return array(
            'timecreated' => 0,
            'couponcode' => 0,
            'errortype' => 0,
            'batchid' => 0,
        );
    }

    /**
     * Creates known user filter if present
     * @param string $fieldname
     * @param boolean $advanced
     * @return object filter
     */
    public function get_field($fieldname, $advanced) {
        switch ($fieldname) {
            case 'timecreated':
                return new \user_filter_date('timecreated',
                        get_string('report:heading:timecreated', 'block_couponext'), $advanced, 'c.timecreated');
            case 'couponcode':
                return new \user_filter_text('couponcode',
                        get_string('report:coupon_code', 'block_couponext'), $advanced, 'c.submission_code');
            case 'batchid':
                return new \user_filter_text('batchid',
                        get_string('label:batchid', 'block_couponext'), $advanced, 'c.batchid');
            case 'errortype':
                // Only one type for now, but supported as filter option.
                $types = ['email' => 'E-mail'];
                return new \user_filter_select('errortype',
                        get_string('report:heading:errortype', 'block_couponext'), $advanced, 'e.errortype', $types);
            default:
                return null;
        }
    }

}
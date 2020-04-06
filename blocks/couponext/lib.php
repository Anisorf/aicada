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
 * library for coupon block.
 *
 * File         lib.php
 * Encoding     UTF-8
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

// F: The interface between the Moodle core and the plugin is defined here for the most plugin types.

/**
 * Add items to course navigation
 * @param navigation_node $parentnode
 * @param stdClass $course
 * @param context_course $context
 */
function block_couponext_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    // F: The moodle navigation system has hooks which allows plugins to add links to the navigation menu. Here a course navigation
    // extension
    global $CFG;
    if (!has_capability('block/couponext:extendenrolments', $context)) {
        return false;
    }
    $biid = \block_couponext\helper::find_block_instance_id();
    if (empty($biid)) {
        return;
    }
    $icon = new \pix_icon('coupon', get_string('couponext:extendenrol', 'block_couponext'), 'block_couponext');
    $icon = null;
    $conditions = array('cid' => $course->id, 'id' => $biid);
    $action = new \moodle_url($CFG->wwwroot . '/blocks/couponext/view/generator/extendenrolment.php', $conditions);
    $parentnode->add(get_string('couponext:extendenrol', 'block_couponext'), $action, navigation_node::TYPE_CUSTOM,
            get_string('couponext:extendenrol', 'block_couponext'), 'cpextendenrol', $icon);
}

/**
 * Send a file
 *
 * @param \stdClass $course
 * @param \stdClass $birecordorcm
 * @param \context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 */
function block_couponext_pluginfile($course, $birecordorcm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    $allowed = array('content', 'logos');
    if (!in_array($filearea, $allowed)) {
        send_file_not_found();
    }
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }
    require_login();

    // Get file.
    $fs = get_file_storage();
    $filename = array_pop($args);
    $itemid = array_shift($args);
    if (!is_numeric($itemid)) {
        $filepath = $itemid . ($args ? '/' . implode('/', $args) . '/' : '/');
        $itemid = 0;
    } else {
        $filepath = ($args ? '/' . implode('/', $args) . '/' : '/');
    }

    if (!$file = $fs->get_file($context->id, 'block_couponext', $filearea, $itemid, $filepath, $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    \core\session\manager::write_close();
    send_stored_file($file, 60 * 60, 0, $forcedownload, $options);
}

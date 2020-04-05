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
 * Version information for block_coupon
 *
 * File         version.php
 * Encoding     UTF-8
 *
 * @package     block_coupon
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$plugin = new stdClass();
# $plugin->version     = 2020010800;
$plugin->version     = 2020010801;
$plugin->requires    = 2017111300;      // YYYYMMDDHH (This is the release version for Moodle 3.4).

// F:  Starting with Moodle 2.7, plugin developers are encouraged to use the scheduled task API instead of the cron function feature.
$plugin->cron        = 0; // F: Allows to throttle the plugin's cron function calls. The set value represents a minimal required gap in seconds between two calls of the plugin's cron function. Note that the cron function is not supported in all plugin types (also note that, while the cron function is supported for authentication plugins, it is run every time the cron script runs, regardless of this value in version.php). This value is stored in the database. After changing this value, the version number must be incremented. For activity modules, if this value is not set or 0 the cron function is disabled.
$plugin->component   = 'block_couponext'; //F: Declare the type and name of this plugin. The full frankenstyle component name in the form of plugintype_pluginname. It is used during the installation and upgrade process for diagnostics and validation purposes to make sure the plugin code has been deployed to the correct location within the Moodle code tree.
$plugin->maturity    = MATURITY_STABLE;
$plugin->release     = '4.0.4 "not found-release" (build 2020010800)';
$plugin->dependencies = array(); // F: Allows to declare explicit dependency on other plugin(s) for this plugin to work. Moodle core checks these declared dependencies and will not allow the plugin installation and/or upgrade until all dependencies are satisfied.
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
 * Capability definitions for block_couponext
 *
 * File         access.php
 * Encoding     UTF-8
 *
 * @package     block_couponext
 *
 * @copyright   Sebsoft.nl
 * @author      Menno de Ridder <menno@sebsoft.nl>
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$capabilities = array(
    'block/couponext:administration' => array(
        'captype' => 'view',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
    'block/couponext:viewreports' => array(
        'captype' => 'view',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        )
    ),
    'block/couponext:viewallreports' => array(
        'captype' => 'view',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
    'block/couponext:generatecoupons' => array(
        'captype' => 'view',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        )
    ),
    'block/couponext:inputcoupons' => array(
        'captype' => 'view',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'user' => CAP_ALLOW,
            'guest' => CAP_PREVENT
        )
    ),
    'block/couponext:extendenrolments' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
    'block/couponext:addinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        # 'contextlevel' => CONTEXT_SYSTEM, //F: Per provare a rendere il blocco visibile anche sulla pagine profilo default , ma non funziona !
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
    'block/couponext:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        # 'contextlevel' => CONTEXT_SYSTEM, //F: Per provare a rendere il blocco visibile anche sulla pagine profilo default , ma non funziona !
        'archetypes' => array(   // F: give capabilites to specific user for instance add 'manager' => CAP_ALLOW or 'editingteacher'=>CAP_ALLOW
            'user' => CAP_ALLOW,
            'guest' => CAP_PREVENT
        )
    )
);
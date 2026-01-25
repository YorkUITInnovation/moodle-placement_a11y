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
 * Admin settings for accessibility placement.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Patrick Thibaudeau, York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Patrick Thibaudeau
 */

defined('MOODLE_INTERNAL') || die();

// This settings file prevents the default action management table from being shown,
// since our custom action (fix_accessibility) uses core_ai\aiactions\generate_text
// internally and doesn't need separate provider configuration.

// Add a simple info section instead of the action management table.
if ($hassiteconfig) {
    $settings->add(new \admin_setting_heading(
        'aiplacement_a11y/info',
        new \lang_string('pluginname', 'aiplacement_a11y'),
        new \lang_string('plugindescription', 'aiplacement_a11y')
    ));

    $settings->add(new \admin_setting_description(
        'aiplacement_a11y/how_it_works',
        new \lang_string('howitworks', 'aiplacement_a11y'),
        new \lang_string('howitworks_desc', 'aiplacement_a11y')
    ));

    $settings->add(new \admin_setting_description(
        'aiplacement_a11y/requirements',
        new \lang_string('requirements', 'aiplacement_a11y'),
        new \lang_string('requirements_desc', 'aiplacement_a11y')
    ));
}

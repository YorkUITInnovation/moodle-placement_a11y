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

namespace aiplacement_a11y\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function to get plugin settings for JavaScript.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Patrick Thibaudeau, York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_settings extends external_api {

    /**
     * Define parameters for the web service.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Get plugin settings.
     *
     * @return array Settings.
     */
    public static function execute(): array {
        // No context validation needed - these are public settings.

        $config = get_config('aiplacement_a11y');

        return [
            'autocheck_debounce' => isset($config->autocheck_debounce) ? (int)$config->autocheck_debounce : 2000,
        ];
    }

    /**
     * Define return structure for the web service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'autocheck_debounce' => new external_value(PARAM_INT, 'Debounce delay in milliseconds (0 = disabled)'),
        ]);
    }
}


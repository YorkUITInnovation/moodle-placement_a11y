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
 * External function to analyze accessibility issues without AI fix.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Patrick Thibaudeau, York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analyze_only extends external_api {

    /**
     * Define parameters for the web service.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(
                PARAM_INT,
                'The context ID',
                VALUE_REQUIRED,
            ),
            'htmlcontent' => new external_value(
                PARAM_RAW,
                'The HTML content to analyze',
                VALUE_REQUIRED,
            ),
        ]);
    }

    /**
     * Analyze accessibility issues only (no AI fix).
     *
     * @param int $contextid The context ID.
     * @param string $htmlcontent The HTML content to analyze.
     * @return array Analysis results.
     * @throws \moodle_exception
     */
    public static function execute(int $contextid, string $htmlcontent): array {
        // Parameter validation.
        [
            'contextid' => $contextid,
            'htmlcontent' => $htmlcontent,
        ] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'htmlcontent' => $htmlcontent,
        ]);

        // Verify context and permissions.
        $context = \context::instance_by_id($contextid);
        self::validate_context($context);

        $utils = new \aiplacement_a11y\utils();

        // Validate HTML.
        if (!$utils->is_valid_html($htmlcontent)) {
            throw new \moodle_exception('invalidhtml', 'aiplacement_a11y');
        }

        // Analyze accessibility issues (PHP DOM only - no AI calls).
        $analysis = $utils->analyze_accessibility_issues($htmlcontent);

        if (empty($analysis['issues'])) {
            // No issues found.
            return [
                'success' => true,
                'has_issues' => false,
                'issues_count' => 0,
            ];
        }

        // Return issue count without full details.
        return [
            'success' => true,
            'has_issues' => true,
            'issues_count' => count($analysis['issues']),
        ];
    }

    /**
     * Define return structure for the web service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation was successful'),
            'has_issues' => new external_value(PARAM_BOOL, 'Whether accessibility issues were found'),
            'issues_count' => new external_value(PARAM_INT, 'Number of issues found'),
        ]);
    }
}


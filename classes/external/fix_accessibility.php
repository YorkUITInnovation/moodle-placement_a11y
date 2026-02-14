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
use core_external\external_value;
use core_external\external_single_structure;

/**
 * External API to fix accessibility issues in HTML content.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Accessibility Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fix_accessibility extends external_api {

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
                'The HTML content to fix for accessibility',
                VALUE_REQUIRED,
            ),
        ]);
    }

    /**
     * Fix accessibility issues in HTML content.
     *
     * Analyzes HTML content for WCAG AA compliance issues and uses AI
     * to suggest fixes for:
     * - Missing alt text on images
     * - Missing or inadequate link text
     * - Insufficient color contrast
     * - Other common accessibility issues
     *
     * @param int $contextid The context ID.
     * @param string $htmlcontent The HTML content to analyze and fix.
     * @return array Result with fixed content and analysis report.
     * @throws \moodle_exception
     */
    public static function execute(
        int $contextid,
        string $htmlcontent
    ): array {
        global $USER;

        // Parameter validation.
        [
            'contextid' => $contextid,
            'htmlcontent' => $htmlcontent,
        ] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'htmlcontent' => $htmlcontent,
        ]);

        // Context validation and permission check.
        $context = \core\context::instance_by_id($contextid);
        self::validate_context($context);

        // Check user has permission to use HTML editor.
        if (!has_capability('moodle/course:manageactivities', $context) &&
            !has_capability('moodle/user:editownprofile', $context)) {
            throw new \moodle_exception('nopermission', 'aiplacement_a11y');
        }

        // Check if AI tools are enabled in the course context.
        $manager = \core\di::get(\core_ai\manager::class);
        if (!$manager->is_ai_tools_enabled_in_course($context)) {
            throw new \moodle_exception('ainotenabledincourse', 'core_ai');
        }

        // Validate and sanitize HTML.
        $utils = new \aiplacement_a11y\utils();
        if (!$utils->is_valid_html($htmlcontent)) {
            throw new \moodle_exception('invalidhtml', 'aiplacement_a11y');
        }

        // Analyze accessibility issues.
        $analysis = $utils->analyze_accessibility_issues($htmlcontent);

        if (empty($analysis['issues'])) {
            // No issues found - content is already compliant.
            return [
                'success' => true,
                'original_content' => $htmlcontent,
                'fixed_content' => $htmlcontent,
                'has_issues' => false,
                'issues_found' => 0,
                'analysis_report' => get_string('noaccessibilityissues', 'aiplacement_a11y'),
                'changes_made' => json_encode([]),
                'issues_data' => json_encode([]),
            ];
        }

        // Build the accessibility fix prompt.
        $prompt = $utils->build_accessibility_fix_prompt($htmlcontent, $analysis);

        try {
            // Use direct Azure API call (same method as single issue fixes).
            $fixed_content = $utils->fix_with_direct_azure_call($prompt);

            // Validate the fixed content is valid HTML.
            if (!$utils->is_valid_html($fixed_content)) {
                throw new \moodle_exception('generatedinvalidhtml', 'aiplacement_a11y');
            }

            // Get the detailed report of changes.
            $changes = $utils->identify_changes($htmlcontent, $fixed_content);

            // Render the full results using template
            global $OUTPUT;
            $template_context = [
                'issues_found' => count($analysis['issues']),
                'has_issues' => true,
                'status_message' => get_string('issuesfound', 'aiplacement_a11y', count($analysis['issues'])),
                'analysis_report' => $utils->generate_report($analysis, $changes),
                'original_content' => $htmlcontent,
                'fixed_content' => $fixed_content,
                'changes' => $changes,
            ];

            $detailed_report = $OUTPUT->render_from_template('aiplacement_a11y/fix_accessibility_results', $template_context);

            return [
                'success' => true,
                'original_content' => $htmlcontent,
                'fixed_content' => $fixed_content,
                'has_issues' => true,
                'issues_found' => count($analysis['issues']),
                'analysis_report' => $detailed_report,
                'changes_made' => json_encode($changes),
                'issues_data' => json_encode($analysis['issues']),
            ];

        } catch (\Exception $e) {
            throw new \moodle_exception('aierror', 'core_ai', '', $e->getMessage());
        }
    }

    /**
     * Define return structure for the web service.
     *
     * @return \core_external\external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation was successful'),
            'original_content' => new external_value(PARAM_RAW, 'The original HTML content'),
            'fixed_content' => new external_value(PARAM_RAW, 'The fixed HTML content'),
            'has_issues' => new external_value(PARAM_BOOL, 'Whether accessibility issues were found'),
            'issues_found' => new external_value(PARAM_INT, 'Number of accessibility issues found'),
            'analysis_report' => new external_value(PARAM_RAW, 'Detailed analysis report'),
            'changes_made' => new external_value(PARAM_RAW, 'JSON array of changes made'),
            'issues_data' => new external_value(PARAM_RAW, 'JSON array of issues data'),
        ]);
    }
}

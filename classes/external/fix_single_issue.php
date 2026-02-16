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
 * External API to fix a single accessibility issue.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Patrick Thibaudeau, York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fix_single_issue extends external_api {

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
                'The current HTML content',
                VALUE_REQUIRED,
            ),
            'issuetype' => new external_value(
                PARAM_TEXT,
                'The type of issue to fix',
                VALUE_REQUIRED,
            ),
            'issuedata' => new external_value(
                PARAM_RAW,
                'JSON-encoded issue data',
                VALUE_REQUIRED,
            ),
            'imagedata' => new external_value(
                PARAM_RAW,
                'Base64-encoded image data (for vision processing)',
                VALUE_DEFAULT,
                '',
            ),
        ]);
    }

    /**
     * Fix a single accessibility issue.
     *
     * @param int $contextid The context ID.
     * @param string $htmlcontent The current HTML content.
     * @param string $issuetype The type of issue to fix.
     * @param string $issuedata JSON-encoded issue data.
     * @param string $imagedata Base64-encoded image data (optional).
     * @return array Result with fixed content.
     * @throws \moodle_exception
     */
    public static function execute(
        int $contextid,
        string $htmlcontent,
        string $issuetype,
        string $issuedata,
        string $imagedata = ''
    ): array {
        global $USER;

        // Parameter validation.
        [
            'contextid' => $contextid,
            'htmlcontent' => $htmlcontent,
            'issuetype' => $issuetype,
            'issuedata' => $issuedata,
            'imagedata' => $imagedata,
        ] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'htmlcontent' => $htmlcontent,
            'issuetype' => $issuetype,
            'issuedata' => $issuedata,
            'imagedata' => $imagedata,
        ]);

        // Context validation and permission check.
        $context = \core\context::instance_by_id($contextid);
        self::validate_context($context);

        // Check user has permission.
        if (!has_capability('moodle/course:manageactivities', $context) &&
            !has_capability('moodle/user:editownprofile', $context)) {
            throw new \moodle_exception('nopermission', 'aiplacement_a11y');
        }

        // Check if AI tools are enabled.
        $manager = \core\di::get(\core_ai\manager::class);
        if (!$manager->is_ai_tools_enabled_in_course($context)) {
            throw new \moodle_exception('ainotenabledincourse', 'core_ai');
        }

        // Decode issue data.
        $issue = json_decode($issuedata, true);
        if ($issue === null) {
            throw new \moodle_exception('invalidissuedata', 'aiplacement_a11y');
        }

        $utils = new \aiplacement_a11y\utils();

        try {
            // For images, check if we need vision capability.
            $usevision = ($issuetype === 'missing_alt_text');

            if ($usevision && !empty($imagedata)) {
                // Use vision-specific prompt to generate alt text.
                $prompt = $utils->build_vision_alt_text_prompt();

                // Call AI with vision capability.
                $response = $utils->call_ai_provider_with_vision($prompt, $imagedata);

                // Parse JSON response to get alt text.
                $suggestion = json_decode($response, true);
                $alttext = '';
                if ($suggestion !== null && isset($suggestion['suggested_html'])) {
                    $alttext = trim($suggestion['suggested_html']);
                } else {
                    // Treat entire response as alt text.
                    $alttext = trim($response);
                }

                // Ensure alt text is not HTML.
                if (strpos($alttext, '<') !== false) {
                    // Try to extract just text from any accidental HTML.
                    $alttext = strip_tags($alttext);
                }

                // Limit to 125 characters.
                if (strlen($alttext) > 125) {
                    $alttext = substr($alttext, 0, 122) . '...';
                }

                // Replace the image alt text in the HTML content.
                $imgsrc = $issue['src'] ?? '';
                $fixedcontent = $utils->replace_image_alt_in_html($htmlcontent, $imgsrc, $alttext);
            } else {
                // Build the prompt based on issue type.
                $prompt = $utils->build_single_issue_fix_prompt($htmlcontent, $issuetype, $issue);

                // Use selected AI provider for other issues.
                $fixedcontent = $utils->call_ai_provider($prompt);
            }

            // Validate the fixed content.
            if (!$utils->is_valid_html($fixedcontent)) {
                throw new \moodle_exception('generatedinvalidhtml', 'aiplacement_a11y');
            }

            return [
                'success' => true,
                'fixed_content' => $fixedcontent,
                'issue_type' => $issuetype,
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
            'fixed_content' => new external_value(PARAM_RAW, 'The fixed HTML content'),
            'issue_type' => new external_value(PARAM_TEXT, 'The type of issue that was fixed'),
        ]);
    }
}


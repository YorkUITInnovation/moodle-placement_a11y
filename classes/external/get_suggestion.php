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
 * External function to get AI suggestion for fixing an accessibility issue.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Patrick Thibaudeau, York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_suggestion extends external_api {

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
                'The type of issue',
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
     * Get AI suggestion for fixing an accessibility issue.
     *
     * @param int $contextid The context ID.
     * @param string $htmlcontent The current HTML content.
     * @param string $issuetype The type of issue.
     * @param string $issuedata JSON-encoded issue data.
     * @param string $imagedata Base64-encoded image data (optional).
     * @return array Suggestion with reasoning and suggested HTML.
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

        // Verify context and permissions.
        $context = \context::instance_by_id($contextid);
        self::validate_context($context);

        // Decode issue data.
        $issue = json_decode($issuedata, true);
        if ($issue === null) {
            throw new \moodle_exception('invalidissuedata', 'aiplacement_a11y');
        }

        $utils = new \aiplacement_a11y\utils();

        try {
            // For images, use vision API with vision-specific prompt.
            $usevision = ($issuetype === 'missing_alt_text');

            if ($usevision && !empty($imagedata)) {
                // Use vision-specific prompt that focuses on describing the image.
                $prompt = $utils->build_vision_alt_text_prompt();

                // Get suggestion from AI with vision capability.
                $response = $utils->call_ai_provider_with_vision($prompt, $imagedata);

                // Parse JSON response.
                $suggestion = json_decode($response, true);
                if ($suggestion === null || !isset($suggestion['reasoning']) || !isset($suggestion['suggested_html'])) {
                    // If not JSON, treat response as alt text.
                    $alttext = trim($response);
                    if (strlen($alttext) > 125) {
                        $alttext = substr($alttext, 0, 122) . '...';
                    }
                    $suggestion = [
                        'reasoning' => 'Images without alt text are inaccessible to screen reader users. WCAG 2.1 Level A requires all non-decorative images to have alternative text.',
                        'suggested_html' => $alttext,
                    ];
                }

                // Use specialized alt text sanitization to ensure plain text.
                $suggestion = $utils->sanitize_alt_text_suggestion($suggestion);
            } else {
                // Build standard suggestion prompt for non-image issues.
                $prompt = $utils->build_suggestion_prompt($htmlcontent, $issuetype, $issue);

                // Get suggestion from AI provider.
                $response = $utils->call_ai_provider($prompt);

                // Parse JSON response.
                $suggestion = json_decode($response, true);
                if ($suggestion === null || !isset($suggestion['reasoning']) || !isset($suggestion['suggested_html'])) {
                    // If not JSON, try to extract from text response.
                    $suggestion = $utils->parse_suggestion_from_text($response, $htmlcontent, $issue);
                }

                // Sanitize and validate the suggestion before returning.
                $suggestion = $utils->sanitize_suggestion($suggestion);
            }

            return [
                'success' => true,
                'reasoning' => $suggestion['reasoning'] ?? '',
                'suggested_html' => $suggestion['suggested_html'] ?? '',
            ];

        } catch (\Exception $e) {
            throw new \moodle_exception('aierror', 'core_ai', '', $e->getMessage());
        }
    }

    /**
     * Define return structure for the web service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation was successful'),
            'reasoning' => new external_value(PARAM_RAW, 'Explanation of why this needs fixing'),
            'suggested_html' => new external_value(PARAM_RAW, 'The suggested HTML fix'),
        ]);
    }
}


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

namespace aiplacement_a11y;

/**
 * Utility functions for accessibility plugin.
 *
 * Provides HTML validation, accessibility analysis, and report generation.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Accessibility Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * Check if HTML content is valid.
     *
     * @param string $html The HTML content to validate.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_html(string $html): bool {
        if (empty(trim($html))) {
            return false;
        }

        // Basic validation - check for obvious issues.
        // Don't be too strict as Moodle editors produce non-strict HTML.
        $html = trim($html);

        // Check for at least some HTML-like structure or plain text.
        if (strlen($html) === 0) {
            return false;
        }

        return true;
    }

    /**
     * Analyze HTML content for accessibility issues.
     *
     * Identifies potential WCAG AA compliance problems:
     * - Images without alt text
     * - Links without descriptive text
     * - Potential color contrast issues
     * - Missing form labels
     * - Improper heading structure
     *
     * @param string $html The HTML content to analyze.
     * @return array Array with 'issues' key containing list of issues found.
     */
    public function analyze_accessibility_issues(string $html): array {
        $issues = [];
        $dom = new \DOMDocument('1.0', 'UTF-8');

        // Suppress warnings from loadHTML (it's very lenient).
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_use_internal_errors(false);

        $xpath = new \DOMXPath($dom);

        // Check for images without alt text.
        $images = $xpath->query('//img');
        foreach ($images as $img) {
            $alt = $img->getAttribute('alt');
            if (empty(trim($alt))) {
                $src = $img->getAttribute('src');
                $html_snippet = $dom->saveHTML($img);
                $issues[] = [
                    'type' => 'missing_alt_text',
                    'element' => 'img',
                    'src' => $src,
                    'severity' => 'high',
                    'description' => "Image missing alt text",
                    'html_snippet' => $html_snippet,
                ];
            }
        }

        // Check for links with weak text.
        $links = $xpath->query('//a[@href]');
        foreach ($links as $link) {
            $text = trim($link->textContent);
            $href = $link->getAttribute('href');

            // Flag empty links or links with only generic text.
            if (empty($text) || in_array(strtolower($text), ['click here', 'read more', 'link', 'here'])) {
                $html_snippet = $dom->saveHTML($link);
                $issues[] = [
                    'type' => 'weak_link_text',
                    'element' => 'a',
                    'href' => $href,
                    'current_text' => $text,
                    'severity' => 'high',
                    'description' => "Link has weak or missing text",
                    'html_snippet' => $html_snippet,
                ];
            }
        }


        // Check for color contrast issues.
        $elements_with_style = $xpath->query('//*[@style]');
        foreach ($elements_with_style as $elem) {
            $style = $elem->getAttribute('style');
            $text = trim($elem->textContent);

            // Only check elements with text content.
            if (empty($text)) {
                continue;
            }

            // Extract color and background-color from inline styles.
            $color = $this->extract_color_from_style($style, 'color');
            $bgcolor = $this->extract_color_from_style($style, 'background-color');

            // If no background color is set in inline style, assume white background (common default).
            if ($bgcolor === null) {
                $bgcolor = '#FFFFFF';
            }

            // If color is set, check contrast.
            if ($color !== null) {
                $contrast_ratio = $this->calculate_contrast_ratio($color, $bgcolor);

                // WCAG AA requires 4.5:1 for normal text, 3:1 for large text.
                // We'll use 4.5:1 as the threshold.
                if ($contrast_ratio < 4.5) {
                    $html_snippet = $dom->saveHTML($elem);
                    $issues[] = [
                        'type' => 'contrast_issue',
                        'element' => $elem->nodeName,
                        'severity' => 'high',
                        'color' => $color,
                        'background' => $bgcolor,
                        'contrast_ratio' => round($contrast_ratio, 2),
                        'description' => 'Insufficient color contrast',
                        'html_snippet' => $html_snippet,
                    ];
                }
            }
        }

        // Check for form inputs without labels.
        $inputs = $xpath->query('//input[@type="text" or @type="password" or @type="email" or not(@type)]');
        foreach ($inputs as $input) {
            $id = $input->getAttribute('id');
            if (!empty($id)) {
                $labels = $xpath->query("//label[@for='{$id}']");
                if ($labels->length === 0) {
                    $html_snippet = $dom->saveHTML($input);
                    $issues[] = [
                        'type' => 'missing_form_label',
                        'element' => 'input',
                        'id' => $id,
                        'severity' => 'high',
                        'description' => "Form input missing label",
                        'html_snippet' => $html_snippet,
                    ];
                }
            }
        }

        // Check for tables missing captions.
        $tables = $xpath->query('//table');
        foreach ($tables as $table) {
            $caption = $xpath->query('caption', $table);
            if ($caption->length === 0) {
                $issues[] = [
                    'type' => 'table_missing_caption',
                    'element' => 'table',
                    'severity' => 'low',
                    'description' => "Table missing caption",
                ];
            }
        }

        // Check for tables with merged cells (colspan or rowspan).
        foreach ($tables as $table) {
            $rows = $xpath->query('.//tr', $table);
            foreach ($rows as $row) {
                $cells = $xpath->query('.//td | .//th', $row);
                foreach ($cells as $cell) {
                    $colspan = $cell->getAttribute('colspan');
                    $rowspan = $cell->getAttribute('rowspan');
                    if (!empty($colspan) && (int)$colspan > 1) {
                        $issues[] = [
                            'type' => 'table_merged_cells',
                            'element' => 'table',
                            'severity' => 'low',
                            'description' => "Table has merged cells (colspan)",
                        ];
                        break 3; // Break out of all loops for this table
                    }
                    if (!empty($rowspan) && (int)$rowspan > 1) {
                        $issues[] = [
                            'type' => 'table_merged_cells',
                            'element' => 'table',
                            'severity' => 'low',
                            'description' => "Table has merged cells (rowspan)",
                        ];
                        break 3; // Break out of all loops for this table
                    }
                }
            }
        }

        // Check for tables missing proper headers (no th elements).
        foreach ($tables as $table) {
            $headers = $xpath->query('.//th', $table);
            if ($headers->length === 0) {
                $issues[] = [
                    'type' => 'table_missing_headers',
                    'element' => 'table',
                    'severity' => 'low',
                    'description' => "Table missing proper header row (th elements)",
                ];
            }
        }

        // Check for heading hierarchy (h3, h4, h5, h6 only).
        $headings = $xpath->query('//h3 | //h4 | //h5 | //h6');
        if ($headings->length > 0) {
            $last_level = null;
            $heading_stack = []; // Track hierarchy to detect issues

            foreach ($headings as $heading) {
                $tag_name = $heading->nodeName; // h3, h4, h5, or h6
                $level = (int)substr($tag_name, 1); // Extract number from tag name
                $content = trim($heading->textContent);
                $html_snippet = $dom->saveHTML($heading);

                // Check for character limit (1000+ characters).
                if (strlen($content) > 1000) {
                    $issues[] = [
                        'type' => 'heading_too_long',
                        'element' => $tag_name,
                        'severity' => 'medium',
                        'description' => "Heading contains over 1000 characters (found: " . strlen($content) . ")",
                        'character_count' => strlen($content),
                        'html_snippet' => $html_snippet,
                    ];
                }

                // Check heading hierarchy.
                if ($last_level !== null) {
                    // If current level skips more than one level deeper, it's an issue.
                    if ($level > $last_level + 1) {
                        $issues[] = [
                            'type' => 'heading_hierarchy_issue',
                            'element' => $tag_name,
                            'severity' => 'medium',
                            'description' => "Heading hierarchy broken: jumped from <h{$last_level}> to <h{$level}>",
                            'current_level' => $level,
                            'expected_max_level' => $last_level + 1,
                            'html_snippet' => $html_snippet,
                        ];
                    }
                }

                // If this is h3, it can be the first heading.
                if ($level === 3 && $last_level === null) {
                    $last_level = $level;
                } else if ($last_level !== null) {
                    $last_level = $level;
                } else if ($level !== 3) {
                    // First heading must be h3 (since h1 and h2 are never used).
                    $issues[] = [
                        'type' => 'heading_hierarchy_issue',
                        'element' => $tag_name,
                        'severity' => 'high',
                        'description' => "Content must start with <h3> as the first heading (h1 and h2 are not used)",
                        'current_level' => $level,
                        'expected_level' => 3,
                        'html_snippet' => $html_snippet,
                    ];
                    $last_level = $level;
                }
            }
        }

        // Check for unorganized content - paragraphs and other content without headings.
        // Get all heading and paragraph/div elements in document order.
        $all_content = $xpath->query('//h3 | //h4 | //h5 | //h6 | //p[not(ancestor::table)] | //div[not(ancestor::table)]');

        if ($all_content->length > 0) {
            $unheaded_text = '';
            $unheaded_elements = [];

            foreach ($all_content as $element) {
                $tag_name = $element->nodeName;

                // If we encounter a heading, we've organized our content so far.
                if (in_array($tag_name, ['h3', 'h4', 'h5', 'h6'])) {
                    // If we have accumulated unheaded content, report it.
                    if (strlen($unheaded_text) > 500 && count($unheaded_elements) > 0) {
                        $first_element = $unheaded_elements[0];
                        $issues[] = [
                            'type' => 'unheaded_content',
                            'element' => 'p/div',
                            'severity' => 'medium',
                            'description' => "Found " . strlen($unheaded_text) . " characters of content without a heading to organize it. Content should be grouped under appropriate headings (h3, h4, h5, or h6).",
                            'character_count' => strlen($unheaded_text),
                            'html_snippet' => $dom->saveHTML($first_element),
                        ];
                    }
                    // Reset for next section.
                    $unheaded_text = '';
                    $unheaded_elements = [];
                } else {
                    // This is a p or div - add to unheaded content.
                    $text = trim($element->textContent);
                    if (!empty($text)) {
                        $unheaded_text .= $text . "\n";
                        $unheaded_elements[] = $element;
                    }
                }
            }

            // Check if there's unheaded content at the end of the document.
            if (strlen($unheaded_text) > 500 && count($unheaded_elements) > 0) {
                $first_element = $unheaded_elements[0];
                $issues[] = [
                    'type' => 'unheaded_content',
                    'element' => 'p/div',
                    'severity' => 'medium',
                    'description' => "Found " . strlen($unheaded_text) . " characters of content without a heading to organize it. Content should be grouped under appropriate headings (h3, h4, h5, or h6).",
                    'character_count' => strlen($unheaded_text),
                    'html_snippet' => $dom->saveHTML($first_element),
                ];
            }
        }

        return [
            'issues' => $issues,
            'total_count' => count($issues),
            'high_severity' => count(array_filter($issues, fn($i) => $i['severity'] === 'high')),
            'medium_severity' => count(array_filter($issues, fn($i) => $i['severity'] === 'medium')),
            'low_severity' => count(array_filter($issues, fn($i) => $i['severity'] === 'low')),
        ];
    }

    /**
     * Build a detailed prompt for the AI to fix accessibility issues.
     *
     * @param string $html The original HTML content.
     * @param array $analysis The analysis results from analyze_accessibility_issues.
     * @return string The prompt to send to the AI provider.
     */
    public function build_accessibility_fix_prompt(string $html, array $analysis): string {
        $issue_summary = "Found " . count($analysis['issues']) . " accessibility issue(s):\n\n";

        foreach ($analysis['issues'] as $issue) {
            $issue_summary .= "- [{$issue['severity']}] {$issue['description']}\n";
        }

        $prompt = <<<PROMPT
You are an expert in web accessibility and WCAG AA compliance. 

I have HTML content that needs to be fixed to meet WCAG AA standards. Please analyze the following HTML and:

1. Add missing alt text to all images (meaningful descriptions)
2. Improve weak link text to be more descriptive
3. Fix color contrast issues - MAXIMIZE contrast by using high-contrast color combinations:
   - Black text (#000000) on white background (#FFFFFF) - Ratio: 21:1 (WCAG AAA)
   - White text (#FFFFFF) on black background (#000000) - Ratio: 21:1 (WCAG AAA)
   - Dark text on light backgrounds for body text (aim for 7:1 or higher ratio)
   - Light text on dark backgrounds for headings (aim for 7:1 or higher ratio)
   - Never use colors with ratios just barely above 4.5:1 - prioritize maximum contrast
4. Add labels to form inputs
5. Ensure proper heading hierarchy (h3, h4, h5, h6 only - h1 and h2 are not used)
   - First heading must be h3
   - Do not skip heading levels (e.g., don't jump from h3 to h5)
   - Keep headings under 1000 characters
6. Add captions to tables that are missing them
7. Restructure tables with merged cells to use proper header associations
8. Add proper header elements (th) to tables missing proper headers
9. Organize any unheaded content blocks by adding appropriate h3, h4, h5, or h6 headings to group related paragraphs

{$issue_summary}

IMPORTANT REQUIREMENTS:
- Return ONLY the fixed HTML content, no explanations
- Do not change the overall structure, just fix accessibility issues
- Keep all existing attributes and classes
- Ensure the HTML remains valid
- Use semantic HTML5 elements where appropriate
- Make alt text descriptive but concise (under 125 characters)
- Make link text descriptive and meaningful
- For contrast: Always maximize contrast using proven high-contrast combinations. Aim for 7:1+ ratio (WCAG AAA), not just 4.5:1 minimum
- For tables: add caption elements, use proper semantic structure with <thead> for header rows, <tbody> for data rows, use th for headers with scope attributes, avoid merged cells
- For headings: ensure proper hierarchy starting with h3, no level skipping, keep text under 1000 characters
- For unheaded content: add descriptive h3/h4/h5/h6 headings to organize content into logical sections. Do NOT remove paragraphs, only ADD headings.
- Preserve all existing functionality

HTML content to fix:
{$html}

Fixed HTML (complete):
PROMPT;

        return $prompt;
    }

    /**
     * Build a prompt for fixing a single accessibility issue.
     *
     * @param string $html The HTML content.
     * @param string $issuetype The type of issue.
     * @param array $issue The issue data.
     * @return string The prompt for AI.
     */
    public function build_single_issue_fix_prompt(string $html, string $issuetype, array $issue): string {
        $prompt = "You are an expert in web accessibility and WCAG AA compliance.\n\n";

        switch ($issuetype) {
            case 'missing_alt_text':
                $prompt .= "Fix the following image that is missing alt text:\n\n";
                $prompt .= "HTML snippet: {$issue['html_snippet']}\n\n";
                $prompt .= "Add a descriptive alt attribute (under 125 characters).\n";
                break;

            case 'weak_link_text':
                $prompt .= "Fix the following link with weak or generic text:\n\n";
                $prompt .= "Current HTML: {$issue['html_snippet']}\n";
                $prompt .= "Current link text: '{$issue['current_text']}'\n";
                $prompt .= "Link URL: {$issue['href']}\n\n";
                $prompt .= "Replace the link text with descriptive, meaningful text.\n";
                break;

            case 'contrast_issue':
                $prompt .= "Fix the following element with insufficient color contrast:\n\n";
                $prompt .= "HTML snippet: {$issue['html_snippet']}\n";
                $prompt .= "Current contrast ratio: {$issue['contrast_ratio']}:1\n";
                $prompt .= "Current foreground color: {$issue['color']}\n";
                $prompt .= "Current background color: {$issue['background']}\n";
                $prompt .= "Required minimum: 4.5:1 (WCAG AA standard)\n\n";
                $prompt .= "CRITICAL: Maximize contrast to improve accessibility beyond minimum requirements.\n";
                $prompt .= "Preferred approach:\n";
                $prompt .= "- If text is dark, use pure white background (#FFFFFF)\n";
                $prompt .= "- If text is light, use pure black or very dark gray background (#000000 or #1a1a1a)\n";
                $prompt .= "- Aim for a contrast ratio of 7:1 or higher (WCAG AAA standard)\n";
                $prompt .= "- Use one of these proven high-contrast combinations:\n";
                $prompt .= "  * Black text (#000000) on white background (#FFFFFF) - Ratio: 21:1\n";
                $prompt .= "  * White text (#FFFFFF) on black background (#000000) - Ratio: 21:1\n";
                $prompt .= "  * Dark blue (#003366) on yellow (#FFFF00) - Ratio: 19.56:1\n";
                $prompt .= "  * Dark text on light background is generally better for body text\n";
                $prompt .= "  * Light text on dark background is acceptable for headings\n\n";
                $prompt .= "Modify the inline style attribute to adjust ONLY the foreground color or background-color (whichever provides better contrast).\n";
                $prompt .= "Do NOT change both simultaneously - pick one and adjust it for maximum contrast.\n";
                break;

            case 'missing_form_label':
                $prompt .= "Fix the following form input that is missing a label:\n\n";
                $prompt .= "HTML snippet: {$issue['html_snippet']}\n\n";
                $prompt .= "Add an appropriate label element with a descriptive for attribute.\n";
                break;

            case 'table_missing_caption':
                $prompt .= "Fix the following table that is missing a caption:\n\n";
                $prompt .= "HTML snippet: {$issue['html_snippet']}\n\n";
                $prompt .= "Add a descriptive <caption> element to the table that summarizes the table's purpose or content.\n";
                break;

            case 'table_merged_cells':
                $prompt .= "Fix the following table with merged cells (colspan/rowspan):\n\n";
                $prompt .= "HTML snippet: {$issue['html_snippet']}\n\n";
                $prompt .= "Restructure the table to avoid merged cells. Use proper header associations with scope attributes (scope='row' or scope='col') on <th> elements instead.\n";
                break;

            case 'table_missing_headers':
                $prompt .= "Fix the following table that is missing proper header elements:\n\n";
                $prompt .= "HTML snippet: {$issue['html_snippet']}\n\n";
                $prompt .= "IMPORTANT: You MUST restructure the table as follows:\n";
                $prompt .= "1. Move the first row into a <thead> element\n";
                $prompt .= "2. Convert all cells in that first row from <td> to <th> elements\n";
                $prompt .= "3. Add appropriate scope attributes to each <th> (scope='col' for column headers, scope='row' for row headers)\n";
                $prompt .= "4. Ensure all remaining rows are within a <tbody> element\n";
                $prompt .= "5. Keep all other attributes and styling\n\n";
                $prompt .= "Example transformation:\n";
                $prompt .= "<table>\n";
                $prompt .= "  <thead>\n";
                $prompt .= "    <tr>\n";
                $prompt .= "      <th scope='col'>Header 1</th>\n";
                $prompt .= "      <th scope='col'>Header 2</th>\n";
                $prompt .= "    </tr>\n";
                $prompt .= "  </thead>\n";
                $prompt .= "  <tbody>\n";
                $prompt .= "    <tr>\n";
                $prompt .= "      <td>Data 1</td>\n";
                $prompt .= "      <td>Data 2</td>\n";
                $prompt .= "    </tr>\n";
                $prompt .= "  </tbody>\n";
                $prompt .= "</table>\n";
                break;

            case 'heading_hierarchy_issue':
                $prompt .= "Fix the following heading hierarchy issue:\n\n";
                $prompt .= "Problem: {$issue['description']}\n";
                $prompt .= "HTML snippet: {$issue['html_snippet']}\n\n";

                if (isset($issue['expected_level'])) {
                    $prompt .= "NOTE: The first heading in content must be <h3> (since h1 and h2 are not used in this context).\n";
                    $prompt .= "Current heading level: {$issue['current_level']}\n";
                    $prompt .= "Expected level: {$issue['expected_level']}\n\n";
                    $prompt .= "Please replace this heading with <h3> instead.\n";
                } else {
                    $prompt .= "Current heading level: <h{$issue['current_level']}>\n";
                    $prompt .= "Maximum allowed jump: 1 level deeper than the previous heading\n";
                    $prompt .= "Expected maximum level: <h{$issue['expected_max_level']}>\n\n";
                    $prompt .= "Please restructure the headings to maintain proper hierarchy. ";
                    $prompt .= "Replace this <h{$issue['current_level']}> with the appropriate heading level (up to <h{$issue['expected_max_level']}>).\n";
                }
                break;

            case 'heading_too_long':
                $prompt .= "Fix the following heading that exceeds the 1000 character limit:\n\n";
                $prompt .= "Current character count: {$issue['character_count']}\n";
                $prompt .= "Maximum allowed: 1000 characters\n";
                $prompt .= "HTML snippet: {$issue['html_snippet']}\n\n";
                $prompt .= "Please shorten the heading text to be under 1000 characters while maintaining its meaning and clarity.\n";
                break;

            case 'unheaded_content':
                $prompt .= "Fix the following issue: Content without proper heading organization.\n\n";
                $prompt .= "Problem: Found " . $issue['character_count'] . " characters of content without a heading to organize it.\n";
                $prompt .= "Location: {$issue['html_snippet']}\n\n";
                $prompt .= "IMPORTANT: You MUST add appropriate <h3>, <h4>, <h5>, or <h6> headings to organize this content into logical sections.\n";
                $prompt .= "Guidelines:\n";
                $prompt .= "- Group related paragraphs under descriptive headings\n";
                $prompt .= "- Use h3 for major sections, h4 for subsections, etc.\n";
                $prompt .= "- Maintain proper heading hierarchy (no level skipping)\n";
                $prompt .= "- Each heading should describe the content that follows it\n";
                $prompt .= "- Do NOT remove any content, only ADD headings to organize it\n\n";
                $prompt .= "Example structure:\n";
                $prompt .= "<h3>Main Topic</h3>\n";
                $prompt .= "<p>Content about the main topic...</p>\n";
                $prompt .= "<h4>Subtopic</h4>\n";
                $prompt .= "<p>Content about the subtopic...</p>\n";
                break;

            default:
                $prompt .= "Fix the following accessibility issue:\n\n";
                $prompt .= "Issue: {$issue['description']}\n";
                $prompt .= "HTML snippet: {$issue['html_snippet']}\n\n";
        }

        $prompt .= "\nIMPORTANT:\n";
        $prompt .= "- Return ONLY the complete, fixed HTML content\n";
        $prompt .= "- Fix ONLY this specific issue\n";
        $prompt .= "- Do not change anything else in the HTML\n";
        $prompt .= "- Ensure the HTML remains valid\n";
        $prompt .= "- Preserve all existing attributes and classes\n\n";
        $prompt .= "Complete HTML content to fix:\n{$html}\n\n";
        $prompt .= "Fixed HTML (complete):";

        return $prompt;
    }

    /**
     * Extract color value from inline style attribute.
     *
     * @param string $style The style attribute value.
     * @param string $property The CSS property to extract (e.g., 'color', 'background-color').
     * @return string|null The color value or null if not found.
     */
    private function extract_color_from_style(string $style, string $property): ?string {
        // Match property: value; patterns.
        $pattern = '/' . preg_quote($property, '/') . '\s*:\s*([^;]+)/i';
        if (preg_match($pattern, $style, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Convert color to RGB array.
     *
     * @param string $color Color in hex (#RGB or #RRGGBB) or rgb() format.
     * @return array|null Array with 'r', 'g', 'b' keys or null if invalid.
     */
    private function color_to_rgb(string $color): ?array {
        $color = trim($color);

        // Handle hex colors.
        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color, $matches)) {
            $hex = $matches[1];

            // Convert 3-digit to 6-digit hex.
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }

            return [
                'r' => hexdec(substr($hex, 0, 2)),
                'g' => hexdec(substr($hex, 2, 2)),
                'b' => hexdec(substr($hex, 4, 2)),
            ];
        }

        // Handle rgb() format.
        if (preg_match('/rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/i', $color, $matches)) {
            return [
                'r' => (int)$matches[1],
                'g' => (int)$matches[2],
                'b' => (int)$matches[3],
            ];
        }

        // Handle common color names.
        $color_names = [
            'white' => '#FFFFFF',
            'black' => '#000000',
            'red' => '#FF0000',
            'green' => '#008000',
            'blue' => '#0000FF',
            'yellow' => '#FFFF00',
            'gray' => '#808080',
            'grey' => '#808080',
        ];

        $lower_color = strtolower($color);
        if (isset($color_names[$lower_color])) {
            return $this->color_to_rgb($color_names[$lower_color]);
        }

        return null;
    }

    /**
     * Calculate relative luminance for a color.
     *
     * @param array $rgb Array with 'r', 'g', 'b' keys (0-255).
     * @return float Relative luminance (0-1).
     */
    private function get_relative_luminance(array $rgb): float {
        // Convert to 0-1 range and apply sRGB gamma correction.
        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;

        $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Calculate contrast ratio between two colors.
     *
     * @param string $color1 First color.
     * @param string $color2 Second color.
     * @return float Contrast ratio (1-21).
     */
    private function calculate_contrast_ratio(string $color1, string $color2): float {
        $rgb1 = $this->color_to_rgb($color1);
        $rgb2 = $this->color_to_rgb($color2);

        if ($rgb1 === null || $rgb2 === null) {
            return 21; // Return passing ratio if we can't parse colors.
        }

        $l1 = $this->get_relative_luminance($rgb1);
        $l2 = $this->get_relative_luminance($rgb2);

        // Ensure L1 is the lighter color.
        if ($l2 > $l1) {
            [$l1, $l2] = [$l2, $l1];
        }

        return ($l1 + 0.05) / ($l2 + 0.05);
    }

    /**
     * Identify specific changes between original and fixed content.
     *
     * @param string $original The original HTML.
     * @param string $fixed The fixed HTML.
     * @return array List of changes made.
     */
    public function identify_changes(string $original, string $fixed): array {
        $changes = [];

        $dom_original = new \DOMDocument('1.0', 'UTF-8');
        $dom_fixed = new \DOMDocument('1.0', 'UTF-8');

        libxml_use_internal_errors(true);
        @$dom_original->loadHTML('<?xml encoding="UTF-8">' . $original);
        @$dom_fixed->loadHTML('<?xml encoding="UTF-8">' . $fixed);
        libxml_use_internal_errors(false);

        $xpath_original = new \DOMXPath($dom_original);
        $xpath_fixed = new \DOMXPath($dom_fixed);

        // Compare images with alt text.
        $imgs_original = $xpath_original->query('//img');
        $imgs_fixed = $xpath_fixed->query('//img');

        foreach ($imgs_original as $i => $img) {
            if (isset($imgs_fixed[$i])) {
                $alt_before = $img->getAttribute('alt');
                $alt_after = $imgs_fixed[$i]->getAttribute('alt');

                if ($alt_before !== $alt_after) {
                    $changes[] = [
                        'type' => 'alt_text_added',
                        'before' => $alt_before ?: '(empty)',
                        'after' => $alt_after,
                        'element' => 'img',
                    ];
                }
            }
        }

        // Compare links text.
        $links_original = $xpath_original->query('//a[@href]');
        $links_fixed = $xpath_fixed->query('//a[@href]');

        foreach ($links_original as $i => $link) {
            if (isset($links_fixed[$i])) {
                $text_before = trim($link->textContent);
                $text_after = trim($links_fixed[$i]->textContent);

                if ($text_before !== $text_after) {
                    $changes[] = [
                        'type' => 'link_text_improved',
                        'before' => $text_before ?: '(empty)',
                        'after' => $text_after,
                        'element' => 'a',
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Generate a human-readable report of the analysis and fixes.
     *
     * @param array $analysis The analysis results.
     * @param array $changes The changes made.
     * @return string HTML-formatted report.
     */
    public function generate_report(array $analysis, array $changes): string {
        global $OUTPUT;

        // Add index to each issue for JavaScript tracking
        $issues_with_index = [];
        foreach ($analysis['issues'] as $index => $issue) {
            $issue['index'] = $index;
            $issues_with_index[] = $issue;
        }

        // Prepare context for template
        $context = [
            'issues' => $issues_with_index,
            'issues_count' => count($analysis['issues']),
            'changes' => $changes,
            'has_changes' => count($changes) > 0,
        ];

        // Render using Mustache template
        return $OUTPUT->render_from_template('aiplacement_a11y/analysis_report', $context);
    }

    /**
     * Fix an image using vision AI to generate alt text.
     *
     * @param string $html The HTML content.
     * @param array $issue The issue data.
     * @param \context $context The context.
     * @param int $userid The user ID.
     * @return string The fixed HTML content.
     * @throws \moodle_exception
     */
    public function fix_image_with_vision(string $html, array $issue, \context $context, int $userid): string {
        global $CFG;

        // Extract image src from the issue.
        $imgsrc = $issue['src'] ?? '';
        if (empty($imgsrc)) {
            throw new \moodle_exception('noimagesource', 'aiplacement_a11y');
        }

        // Try to get image as base64.
        $imagebase64 = $this->get_image_as_base64($imgsrc);

        if ($imagebase64 === null) {
            throw new \moodle_exception('cannotaccessimage', 'aiplacement_a11y');
        }

        // Build the vision prompt.
        $prompt = "Describe this image concisely for use as alt text (under 125 characters). ";
        $prompt .= "Focus on the main subject and action. Be descriptive but brief.";

        // Use AI to describe the image.
        $manager = \core\di::get(\core_ai\manager::class);

        // Create a generate_image action (or use appropriate vision action).
        // Note: This assumes the AI provider supports vision. We'll use generate_text with image context.
        $action = new \core_ai\aiactions\generate_text(
            contextid: $context->id,
            userid: $userid,
            prompttext: $prompt,
        );

        // Add image data to the action if supported.
        // For now, we'll include the base64 image in the prompt or use a vision-specific action.
        // This is a simplified approach - may need adjustment based on Moodle's AI API.

        $response = $manager->process_action($action);

        if (!$response->get_success()) {
            throw new \moodle_exception('aierror', 'core_ai', '', $response->get_error());
        }

        $responsedata = $response->get_response_data();
        $alttext = trim($responsedata['generatedcontent'] ?? '');

        // Limit alt text to 125 characters.
        if (strlen($alttext) > 125) {
            $alttext = substr($alttext, 0, 122) . '...';
        }

        // Replace the image in the HTML with the new alt text.
        $fixedhtml = $this->replace_image_alt_text($html, $imgsrc, $alttext);

        return $fixedhtml;
    }

    /**
     * Fix an image using vision AI with base64 image data from JavaScript.
     *
     * @param string $html The HTML content.
     * @param array $issue The issue data.
     * @param string $imagebase64 Base64-encoded image data from browser.
     * @param \context $context The context.
     * @param int $userid The user ID.
     * @return string The fixed HTML content.
     * @throws \moodle_exception
     */
    public function fix_image_with_vision_base64(string $html, array $issue, string $imagebase64, \context $context, int $userid): string {
        // Extract image src from the issue.
        $imgsrc = $issue['src'] ?? '';
        if (empty($imgsrc)) {
            throw new \moodle_exception('noimagesource', 'aiplacement_a11y');
        }

        // Check if we have base64 data.
        if (empty($imagebase64)) {
            throw new \moodle_exception('cannotaccessimage', 'aiplacement_a11y');
        }

        // Build the vision prompt.
        $prompt = "Describe this image concisely for use as alt text (under 125 characters). ";
        $prompt .= "Focus on the main subject and action. Be descriptive but brief.";

        // Get Azure OpenAI configuration from provider instance (like design_ideas block).
        $manager = \core\di::get(\core_ai\manager::class);
        $provider_instances = $manager->get_provider_instances(['provider' => 'aiprovider_azureai\\provider']);
        $provider_instance = reset($provider_instances);

        if (empty($provider_instance)) {
            throw new \moodle_exception('azurenotconfigured', 'aiplacement_a11y');
        }

        // Extract configuration from provider instance.
        $apikey = $provider_instance->config['apikey'] ?? '';
        $endpoint = $provider_instance->config['endpoint'] ?? '';
        $deployment = '';
        $apiversion = '';

        // Get deployment and API version from action config.
        foreach ($provider_instance->actionconfig as $key => $action_config) {
            if ($key == 'core_ai\aiactions\generate_text') {
                $deployment = $provider_instance->actionconfig[$key]['settings']['deployment'] ?? '';
                $apiversion = $provider_instance->actionconfig[$key]['settings']['apiversion'] ?? '2024-02-15-preview';
                break;
            }
        }

        debugging("=== AZURE CONFIG DEBUG ===", DEBUG_DEVELOPER);

        debugging("azure_endpoint: '" . $endpoint . "'", DEBUG_DEVELOPER);
        debugging("azure_api_key: '" . (empty($apikey) ? '(empty)' : 'SET (length: ' . strlen($apikey) . ')') . "'", DEBUG_DEVELOPER);
        debugging("azure_deployment: '" . $deployment . "'", DEBUG_DEVELOPER);
        debugging("azure_api_version: '" . $apiversion . "'", DEBUG_DEVELOPER);
        debugging("=== END CONFIG DEBUG ===", DEBUG_DEVELOPER);

        if (empty($endpoint) || empty($apikey) || empty($deployment)) {
            throw new \moodle_exception('azurenotconfigured', 'aiplacement_a11y');
        }

        // Construct the Azure OpenAI vision API URL.
        $url = rtrim($endpoint, '/') . '/openai/deployments/' . $deployment . '/chat/completions?api-version=' . $apiversion;

        // Check the size of base64 data - if too large, throw better error.
        $base64size = strlen($imagebase64);
        debugging("Image base64 size: " . $base64size . " bytes", DEBUG_DEVELOPER);

        // Build the request payload for vision.
        $payload = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $imagebase64,
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 150,
        ];

        // Make the HTTP request using cURL.
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $apikey,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout.

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerror = curl_error($ch);
        curl_close($ch);

        // Log the response for debugging.
        debugging("Azure API HTTP Code: " . $httpcode, DEBUG_DEVELOPER);
        if ($response) {
            debugging("Azure API Response: " . substr($response, 0, 500), DEBUG_DEVELOPER);
        }

        if ($response === false || $httpcode !== 200) {
            $errormsg = 'Azure API error: ' . ($curlerror ?: "HTTP $httpcode");
            if ($response) {
                $responsedata = json_decode($response, true);
                if (isset($responsedata['error']['message'])) {
                    $errormsg = $responsedata['error']['message'];
                }
            }
            throw new \moodle_exception('aierror', 'core_ai', '', $errormsg);
        }

        // Parse the response.
        $responsedata = json_decode($response, true);
        $alttext = trim($responsedata['choices'][0]['message']['content'] ?? '');

        if (empty($alttext)) {
            throw new \moodle_exception('aierror', 'core_ai', '', 'No alt text generated');
        }

        // Limit alt text to 125 characters.
        if (strlen($alttext) > 125) {
            $alttext = substr($alttext, 0, 122) . '...';
        }

        // Replace the image in the HTML with the new alt text.
        $fixedhtml = $this->replace_image_alt_text($html, $imgsrc, $alttext);

        return $fixedhtml;
    }

    /**
     * Fix accessibility issue using direct Azure OpenAI API call.
     * Uses the same provider instance approach as vision fixes.
     *
     * @param string $prompt The prompt to send to AI.
     * @return string The AI response.
     * @throws \moodle_exception
     */
    public function fix_with_direct_azure_call(string $prompt): string {
        // Get Azure OpenAI configuration from provider instance.
        $manager = \core\di::get(\core_ai\manager::class);
        $provider_instances = $manager->get_provider_instances(['provider' => 'aiprovider_azureai\\provider']);
        $provider_instance = reset($provider_instances);

        if (empty($provider_instance)) {
            throw new \moodle_exception('azurenotconfigured', 'aiplacement_a11y');
        }

        // Extract configuration from provider instance.
        $apikey = $provider_instance->config['apikey'] ?? '';
        $endpoint = $provider_instance->config['endpoint'] ?? '';
        $deployment = '';
        $apiversion = '';

        // Get deployment and API version from action config.
        foreach ($provider_instance->actionconfig as $key => $action_config) {
            if ($key == 'core_ai\aiactions\generate_text') {
                $deployment = $provider_instance->actionconfig[$key]['settings']['deployment'] ?? '';
                $apiversion = $provider_instance->actionconfig[$key]['settings']['apiversion'] ?? '2024-02-15-preview';
                break;
            }
        }

        if (empty($endpoint) || empty($apikey) || empty($deployment)) {
            throw new \moodle_exception('azurenotconfigured', 'aiplacement_a11y');
        }

        // Construct the Azure OpenAI API URL.
        $url = rtrim($endpoint, '/') . '/openai/deployments/' . $deployment . '/chat/completions?api-version=' . $apiversion;

        // Build the request payload.
        $payload = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => 4096,
            'temperature' => 0.7,
        ];

        // Make the HTTP request using cURL.
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $apikey,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerror = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpcode !== 200) {
            $errormsg = 'Azure API error: ' . ($curlerror ?: "HTTP $httpcode");
            if ($response) {
                $responsedata = json_decode($response, true);
                if (isset($responsedata['error']['message'])) {
                    $errormsg = $responsedata['error']['message'];
                }
            }
            throw new \moodle_exception('aierror', 'core_ai', '', $errormsg);
        }

        // Parse the response.
        $responsedata = json_decode($response, true);
        $content = trim($responsedata['choices'][0]['message']['content'] ?? '');

        if (empty($content)) {
            throw new \moodle_exception('aierror', 'core_ai', '', 'No content generated');
        }

        return $content;
    }

    /**
     * Get image as base64 encoded string.
     *
     * @param string $imgsrc The image source URL.
     * @return string|null Base64 encoded image or null if can't access.
     */
    private function get_image_as_base64(string $imgsrc): ?string {
        global $CFG;

        // Handle different types of image URLs.
        $imagedata = null;

        // Check if it's a data URI already.
        if (strpos($imgsrc, 'data:image') === 0) {
            return $imgsrc;
        }

        // Handle absolute URLs.
        if (strpos($imgsrc, 'http://') === 0 || strpos($imgsrc, 'https://') === 0) {
            // Try to fetch the image.
            $imagedata = @file_get_contents($imgsrc);
        } else if (strpos($imgsrc, '/') === 0) {
            // Relative URL from Moodle root.
            $filepath = $CFG->dirroot . $imgsrc;
            if (file_exists($filepath)) {
                $imagedata = @file_get_contents($filepath);
            }
        } else {
            // Handle pluginfile.php URLs and other Moodle-specific URLs.
            if (strpos($imgsrc, 'pluginfile.php') !== false || strpos($imgsrc, 'draftfile.php') !== false) {
                // Try to construct full URL.
                $fullurl = $CFG->wwwroot . '/' . ltrim($imgsrc, '/');
                $imagedata = @file_get_contents($fullurl);
            }
        }

        if ($imagedata === null || $imagedata === false) {
            return null;
        }

        // Detect MIME type.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimetype = $finfo->buffer($imagedata);

        // Encode to base64.
        $base64 = base64_encode($imagedata);

        return "data:{$mimetype};base64,{$base64}";
    }

    /**
     * Replace alt text for a specific image in HTML.
     *
     * @param string $html The HTML content.
     * @param string $imgsrc The image source to find.
     * @param string $alttext The new alt text.
     * @return string The updated HTML.
     */
    private function replace_image_alt_text(string $html, string $imgsrc, string $alttext): string {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_use_internal_errors(false);

        $xpath = new \DOMXPath($dom);
        $images = $xpath->query("//img[@src='{$imgsrc}']");

        foreach ($images as $img) {
            $img->setAttribute('alt', $alttext);
        }

        // Get the HTML back.
        $body = $dom->getElementsByTagName('body')->item(0);
        $fixedhtml = '';
        if ($body) {
            foreach ($body->childNodes as $node) {
                $fixedhtml .= $dom->saveHTML($node);
            }
        }

        return $fixedhtml;
    }

    /**
     * Build prompt for getting AI suggestion with reasoning.
     *
     * @param string $html The HTML content.
     * @param string $issuetype The type of issue.
     * @param array $issue The issue data.
     * @return string The prompt for AI.
     */
    public function build_suggestion_prompt(string $html, string $issuetype, array $issue): string {
        $prompt = "You are an accessibility expert. Analyze this accessibility issue and provide a detailed suggestion.\n\n";

        $prompt .= "HTML Content:\n" . $html . "\n\n";

        $prompt .= "Issue Type: " . $issuetype . "\n";
        $prompt .= "Issue Details: " . json_encode($issue) . "\n\n";

        $prompt .= "Provide your response in JSON format with these keys:\n";
        $prompt .= "1. reasoning: Explain WHY this is an accessibility problem. Reference WCAG 2.1 guidelines and explain the impact on users with disabilities.\n";
        $prompt .= "2. suggested_html: Provide ONLY the fixed HTML element (not the entire document). Make it ready to replace the problematic element.\n\n";

        $prompt .= "Return ONLY valid JSON in this exact format:\n";
        $prompt .= '{"reasoning": "explanation here", "suggested_html": "fixed html here"}';

        return $prompt;
    }

    /**
     * Get image suggestion using vision AI.
     *
     * @param string $html The HTML content.
     * @param array $issue The issue data.
     * @param string $imagebase64 Base64 image data.
     * @return array Suggestion with reasoning and suggested_html.
     */
    public function get_image_suggestion_with_vision(string $html, array $issue, string $imagebase64): array {
        // Get Azure config.
        $manager = \core\di::get(\core_ai\manager::class);
        $provider_instances = $manager->get_provider_instances(['provider' => 'aiprovider_azureai\\provider']);
        $provider_instance = reset($provider_instances);

        if (empty($provider_instance)) {
            throw new \moodle_exception('azurenotconfigured', 'aiplacement_a11y');
        }

        $apikey = $provider_instance->config['apikey'] ?? '';
        $endpoint = $provider_instance->config['endpoint'] ?? '';
        $deployment = '';
        $apiversion = '';

        foreach ($provider_instance->actionconfig as $key => $action_config) {
            if ($key == 'core_ai\aiactions\generate_text') {
                $deployment = $provider_instance->actionconfig[$key]['settings']['deployment'] ?? '';
                $apiversion = $provider_instance->actionconfig[$key]['settings']['apiversion'] ?? '2024-02-15-preview';
                break;
            }
        }

        if (empty($endpoint) || empty($apikey) || empty($deployment)) {
            throw new \moodle_exception('azurenotconfigured', 'aiplacement_a11y');
        }

        $url = rtrim($endpoint, '/') . '/openai/deployments/' . $deployment . '/chat/completions?api-version=' . $apiversion;

        $prompt = "Analyze this image and provide:\n";
        $prompt .= "1. reasoning: Explain why images need alt text for accessibility (WCAG 2.1 Level A).\n";
        $prompt .= "2. suggested_html: Describe this image in under 125 characters for alt text.\n\n";
        $prompt .= "Return JSON: {\"reasoning\": \"...\", \"suggested_html\": \"description here\"}";

        $payload = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => $imagebase64]],
                    ],
                ],
            ],
            'max_tokens' => 300,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $apikey,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpcode !== 200) {
            throw new \moodle_exception('aierror', 'core_ai', '', 'Vision API error');
        }

        $responsedata = json_decode($response, true);
        $content = trim($responsedata['choices'][0]['message']['content'] ?? '');

        $suggestion = json_decode($content, true);
        if ($suggestion === null) {
            // Fallback: extract description from text.
            $alttext = $content;
            if (strlen($alttext) > 125) {
                $alttext = substr($alttext, 0, 122) . '...';
            }
            $suggestion = [
                'reasoning' => 'Images without alt text prevent screen reader users from understanding the image content. WCAG 2.1 Level A requires all images to have alternative text.',
                'suggested_html' => $alttext,
            ];
        }

        return $suggestion;
    }

    /**
     * Parse suggestion from text response if JSON parsing fails.
     *
     * @param string $response The AI response.
     * @param string $html The original HTML.
     * @param array $issue The issue data.
     * @return array Parsed suggestion.
     */
    public function parse_suggestion_from_text(string $response, string $html, array $issue): array {
        // Try to extract JSON from text (sometimes AI wraps it in markdown).
        if (preg_match('/\{[^}]*"reasoning"[^}]*\}/s', $response, $matches)) {
            $suggestion = json_decode($matches[0], true);
            if ($suggestion !== null) {
                return $suggestion;
            }
        }

        // Fallback: treat entire response as reasoning, extract HTML from original.
        return [
            'reasoning' => $response,
            'suggested_html' => $html,
        ];
    }
}

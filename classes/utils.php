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
                $issues[] = [
                    'type' => 'missing_alt_text',
                    'element' => 'img',
                    'src' => $src,
                    'severity' => 'high',
                    'description' => "Image missing alt text: {$src}",
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
                $issues[] = [
                    'type' => 'weak_link_text',
                    'element' => 'a',
                    'href' => $href,
                    'current_text' => $text,
                    'severity' => 'high',
                    'description' => "Link has weak or missing text: '{$text}'",
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
                    $issues[] = [
                        'type' => 'contrast_issue',
                        'element' => $elem->nodeName,
                        'severity' => 'high',
                        'color' => $color,
                        'background' => $bgcolor,
                        'contrast_ratio' => round($contrast_ratio, 2),
                        'description' => sprintf(
                            'Insufficient color contrast (%.2f:1, needs 4.5:1). Color: %s on %s',
                            $contrast_ratio,
                            $color,
                            $bgcolor
                        ),
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
                    $issues[] = [
                        'type' => 'missing_form_label',
                        'element' => 'input',
                        'id' => $id,
                        'severity' => 'high',
                        'description' => "Form input '{$id}' missing associated label",
                    ];
                }
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
3. Suggest improvements for potential contrast issues
4. Add labels to form inputs
5. Ensure proper heading hierarchy

{$issue_summary}

IMPORTANT REQUIREMENTS:
- Return ONLY the fixed HTML content, no explanations
- Do not change the overall structure, just fix accessibility issues
- Keep all existing attributes and classes
- Ensure the HTML remains valid
- Use semantic HTML5 elements where appropriate
- Make alt text descriptive but concise (under 125 characters)
- Make link text descriptive and meaningful
- Preserve all existing functionality

HTML content to fix:
{$html}

Fixed HTML (complete):
PROMPT;

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
        $report = '<div class="a11y-report">';
        $report .= '<h3>' . get_string('a11yreport', 'aiplacement_a11y') . '</h3>';

        // Summary.
        $report .= '<p><strong>' . get_string('issuesfound', 'aiplacement_a11y', count($analysis['issues'])) . '</strong></p>';

        if (count($analysis['issues']) > 0) {
            $report .= '<ul>';
            foreach ($analysis['issues'] as $issue) {
                $severity_class = 'severity-' . $issue['severity'];
                $report .= '<li class="' . $severity_class . '">';
                $report .= '[' . strtoupper($issue['severity']) . '] ' . $issue['description'];
                $report .= '</li>';
            }
            $report .= '</ul>';
        }

        // Changes made.
        if (count($changes) > 0) {
            $report .= '<h4>' . get_string('changesfixed', 'aiplacement_a11y') . '</h4>';
            $report .= '<ul>';
            foreach ($changes as $change) {
                $report .= '<li>';
                $report .= htmlspecialchars($change['before']) . ' → ' . htmlspecialchars($change['after']);
                $report .= '</li>';
            }
            $report .= '</ul>';
        }

        $report .= '</div>';

        return $report;
    }
}

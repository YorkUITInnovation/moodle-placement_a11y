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

        // Check for headings (structure issue).
        $h1s = $xpath->query('//h1');
        if ($h1s->length === 0) {
            $issues[] = [
                'type' => 'missing_h1',
                'element' => 'h1',
                'severity' => 'medium',
                'description' => 'Content should have at least one H1 heading',
            ];
        }

        // Check for text that might have contrast issues (we flag all-caps text).
        $paragraphs = $xpath->query('//p | //span | //div');
        foreach ($paragraphs as $elem) {
            $text = trim($elem->textContent);
            if (!empty($text) && strlen($text) > 20 && $text === strtoupper($text)) {
                $issues[] = [
                    'type' => 'potential_contrast_issue',
                    'element' => $elem->nodeName,
                    'severity' => 'low',
                    'description' => 'Text in all caps may have contrast issues. Check styling.',
                ];
                break; // Only report once.
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
3. Add missing H1 heading if needed
4. Suggest improvements for potential contrast issues
5. Add labels to form inputs
6. Ensure proper heading hierarchy

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

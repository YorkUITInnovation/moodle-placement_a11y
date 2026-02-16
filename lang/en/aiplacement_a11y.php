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
 * Language strings for accessibility placement.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Patrick Thibaudeau, York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Patrick Thibaudeau
 */

defined('MOODLE_INTERNAL') || die();

// Plugin strings.
$string['pluginname'] = 'Accessibility Fixer';
$string['plugindescription'] = 'AI-powered tool to fix WCAG AA accessibility issues in HTML content';

// Admin settings strings.
$string['howitworks'] = 'How It Works';
$string['howitworks_desc'] = 'The Accessibility Fixer integrates directly into the HTML editor and provides a "Fix Accessibility" button. When clicked, it analyzes the content for WCAG AA compliance issues and uses the configured AI provider (typically OpenAI) to suggest fixes for:

• Missing alt text on images
• Weak or generic link text
• Missing H1 headings
• Potential color contrast issues
• Missing form labels

The fixed content is presented in a comparison view for review before being applied.';

$string['howitworks_delegation'] = 'This action uses the provider\'s Generate Text capability internally. No additional configuration is needed for this action - configure the Generate Text action in your provider settings.';

$string['requirements'] = 'Requirements';
$string['requirements_desc'] = '<strong>To use this placement plugin, you need:</strong>

1. <strong>AI Provider Configured:</strong> Go to Admin > AI Features > Providers and ensure at least one provider (e.g., OpenAI) is configured with a valid API key.

2. <strong>Generate Text Action Enabled:</strong> In the provider settings, ensure the "Generate Text" action is enabled for your chosen provider.

3. <strong>AI Tools Enabled in Courses:</strong> In course settings, ensure "Enable AI Tools" is set to Yes.

4. <strong>User Permissions:</strong> Users need the capability "aiplacement/a11y:use" to access the feature. This is granted by default to students and teachers.

<strong>No additional configuration is needed for this placement plugin itself.</strong> It automatically uses your existing AI provider configuration.';

// Feature strings.
$string['fixaccessibility'] = 'Fix Accessibility';
$string['fixaccessibility_desc'] = 'Analyzes and fixes WCAG AA accessibility issues in HTML content';

// Provider settings.
$string['aiprovider'] = 'AI Provider';
$string['aiprovider_desc'] = 'Select which AI provider to use for accessibility fixes. The plugin will automatically detect and use available providers configured in your Moodle instance. If no provider is selected, the first available provider will be used.';
$string['preferred_provider'] = 'Preferred Provider';
$string['preferred_provider_desc'] = 'Choose which AI provider to prefer. If not available, the plugin will use the first available provider in this order: Azure, OpenAI, DeepSeek, Ollama.';

// Error strings.
$string['noaccessibilityissues'] = 'No accessibility issues found. Content meets WCAG AA standards.';
$string['invalidhtml'] = 'Invalid HTML content provided';
$string['generatedinvalidhtml'] = 'Error: Generated HTML is invalid';
$string['nopermission'] = 'You do not have permission to use this feature';
$string['ainotenabledincourse'] = 'AI tools are not enabled in this course';
$string['invalidissuedata'] = 'Invalid issue data provided';
$string['noimagesource'] = 'No image source found in issue data';
$string['cannotaccessimage'] = 'Cannot access image for processing. The image may not be publicly accessible.';
$string['noaiprovidersconfigured'] = 'No AI providers are configured. Please configure at least one AI provider in Site Administration > AI Features > Providers.';
$string['providernrtconfigured'] = 'AI provider "{$a}" is not configured. Please configure it in Site Administration > AI Features > Providers.';
$string['providernrtfound'] = 'AI provider "{$a}" is not supported by this plugin.';
$string['providernotproperlyconfigured'] = 'AI provider "{$a}" is not properly configured. Please check the provider settings in Site Administration > AI Features > Providers.';
$string['azurenotconfigured'] = 'Azure OpenAI provider is not configured.';
$string['openainotconfigured'] = 'OpenAI provider is not configured.';
$string['deepseeknotconfigured'] = 'DeepSeek provider is not configured.';
$string['ollamanotconfigured'] = 'Ollama provider is not configured.';

// Report strings.
$string['a11yreport'] = 'Accessibility Analysis Report';
$string['issuesfound'] = 'Found {$a} accessibility issue(s)';
$string['changesfixed'] = 'Changes Fixed';
$string['fixedsuccessfully'] = 'Accessibility issues fixed successfully';
$string['viewhtml'] = 'View HTML';
$string['viewcode'] = 'View Code';
$string['status'] = 'Status';
$string['original'] = 'Original';
$string['fixed'] = 'Fixed';
$string['acceptchanges'] = 'Accept Changes';
$string['rejectchanges'] = 'Reject Changes';
$string['preview'] = 'Preview';
$string['showme'] = 'Show me where!';
$string['fixissue'] = 'Fix';
$string['suggestedfix'] = 'Suggested Fix';
$string['gettingsuggestion'] = 'Getting suggestion...';
$string['hidesuggestion'] = 'Hide Suggestion';
$string['whythisneedsfixing'] = 'Why this needs fixing:';
$string['suggestedfixlabel'] = 'Suggested Fix (you can edit):';
$string['fixall'] = 'Fix All Issues';
$string['fixing'] = 'Fixing...';
$string['issuesfixed'] = 'Fixed ✓';
$string['applychanges'] = 'Apply Changes';
$string['cancel'] = 'Cancel';

// Auto-check settings.
$string['autocheckheading'] = 'Auto-Check Settings';
$string['autocheckheading_desc'] = 'Configure automatic accessibility checking in the editor';
$string['autocheckdebounce'] = 'Auto-check delay (milliseconds)';
$string['autocheckdebounce_desc'] = 'Delay before automatically checking content after user stops typing. Set to 0 to disable auto-check. Default: 2000 (2 seconds). Higher values reduce server load but delay feedback.';

// Button status.
$string['accessibilityok'] = 'Accessibility: No issues found';
$string['accessibilityissues'] = 'Accessibility: {$a} issue(s) found - Click to fix';

// Table accessibility issue strings.
$string['table_missing_caption'] = 'Table missing caption';
$string['table_missing_caption_desc'] = 'Tables should have a caption element to describe their purpose and help users understand the table\'s content.';
$string['table_merged_cells'] = 'Table has merged cells';
$string['table_merged_cells_desc'] = 'Tables with merged cells (colspan/rowspan) can be confusing for screen reader users. Use proper header associations instead.';
$string['table_missing_headers'] = 'Table missing proper headers';
$string['table_missing_headers_desc'] = 'Tables should use th (table header) elements in the first row to define column headers, helping screen reader users understand the table structure.';

// Heading accessibility issue strings.
$string['heading_hierarchy_issue'] = 'Improper heading hierarchy';
$string['heading_hierarchy_issue_desc'] = 'Headings should follow a proper hierarchy (h3, h4, h5, h6) without skipping levels. The first heading must be h3. Do not jump from h3 directly to h5.';
$string['heading_too_long'] = 'Heading exceeds character limit';
$string['heading_too_long_desc'] = 'Headings should not exceed 1000 characters. Very long headings may be difficult for screen reader users to navigate and understand.';

// Unheaded content accessibility issue strings.
$string['unheaded_content'] = 'Unorganized content without headings';
$string['unheaded_content_desc'] = 'Content should be organized into logical sections using headings (h3, h4, h5, h6). Multiple paragraphs without headings can be confusing for screen reader users and makes content harder to navigate and understand.';

// Issue description strings (used in analyze_accessibility_issues).
$string['issue_missing_alt_text'] = 'Image missing alt text';
$string['issue_weak_link_text'] = 'Link has weak or missing text';
$string['issue_contrast'] = 'Insufficient color contrast';
$string['issue_missing_form_label'] = 'Form input missing label';
$string['issue_table_missing_caption'] = 'Table missing caption';
$string['issue_table_merged_cells_colspan'] = 'Table has merged cells (colspan)';
$string['issue_table_merged_cells_rowspan'] = 'Table has merged cells (rowspan)';
$string['issue_table_missing_headers'] = 'Table missing proper header row (th elements)';
$string['issue_heading_too_long'] = 'Heading contains over 1000 characters (found: {$a})';
$string['issue_heading_hierarchy_broken'] = 'Heading hierarchy broken: jumped from <h{$a->from}> to <h{$a->to}>';
$string['issue_heading_must_start_h3'] = 'Content must start with <h3> as the first heading (h1 and h2 are not used)';
$string['issue_unheaded_content'] = 'Found {$a} characters of content without a heading to organize it. Content should be grouped under appropriate headings (h3, h4, h5, or h6).';

// Severity strings.
$string['severity_high'] = 'high';
$string['severity_medium'] = 'medium';
$string['severity_low'] = 'low';

// Privacy strings.
$string['privacy:metadata:userid'] = 'The user ID of the person requesting accessibility fixes';
$string['privacy:metadata:content'] = 'The HTML content being analyzed for accessibility issues';
$string['privacy:metadata:aiprovider'] = 'Content is sent to the configured AI provider for processing';



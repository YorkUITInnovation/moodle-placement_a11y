# Accessibility Fixer (aiplacement_a11y) - Implementation Guide

## Overview

The **Accessibility Fixer** is a Moodle placement plugin that integrates AI-powered WCAG AA accessibility checking and fixing directly into the HTML text editor.

## What This Plugin Does

This plugin analyzes HTML content in the editor and uses AI to automatically fix common WCAG AA compliance issues:

- **Missing Alt Text**: Adds meaningful descriptions to images
- **Weak Link Text**: Improves link anchor text from generic phrases like "click here" to descriptive text
- **Missing H1 Heading**: Adds a primary heading if missing
- **Contrast Issues**: Flags potential color contrast problems
- **Missing Form Labels**: Identifies inputs without associated labels
- **Heading Structure**: Ensures proper heading hierarchy

## Plugin Structure

```
ai/placement/a11y/
├── classes/
│   ├── placement.php                    # Main placement class
│   ├── aiaction/
│   │   └── fix_accessibility.php        # Custom AI action definition
│   ├── external/
│   │   └── fix_accessibility.php        # Web service implementation
│   ├── utils.php                        # HTML analysis utilities
│   └── privacy/
│       └── provider.php                 # Privacy compliance
├── db/
│   ├── services.php                     # Web service definitions
│   └── access.php                       # Capability definitions
├── lang/
│   └── en/
│       └── aiplacement_a11y.php         # Language strings
├── tests/
│   ├── utils_test.php                   # Utility tests
│   └── external/
│       └── fix_accessibility_test.php   # Web service tests
├── version.php                          # Plugin metadata
└── README.md                            # This file
```

## Installation

1. **Copy plugin to correct location**:
   ```bash
   cd /path/to/moodle
   cp -r ai/placement/a11y public/ai/placement/a11y
   ```

2. **Run upgrade**:
   ```bash
   php admin/cli/upgrade.php
   ```

3. **Enable the plugin**:
   - Go to Admin > Plugins > AI Features > Placements
   - Find "Accessibility Fixer" and click Enable

4. **Configure AI Provider**:
   - Ensure you have an AI provider configured (e.g., OpenAI)
   - The plugin uses the configured provider to process accessibility fixes

## Architecture

### How It Works

```
User edits content in HTML Editor
         ↓
User clicks "Fix Accessibility" button
         ↓
JavaScript calls Web Service: aiplacement_a11y_fix_accessibility
         ↓
PHP validates permissions and HTML content
         ↓
analyze_accessibility_issues() identifies WCAG AA problems
         ↓
build_accessibility_fix_prompt() creates AI prompt
         ↓
AI provider (OpenAI) processes the prompt
         ↓
Fixed HTML content is returned
         ↓
Comparison view shows original vs. fixed
         ↓
User accepts or rejects changes
         ↓
Editor content is updated
```

### Key Components

#### 1. **placement.php**
- Extends `\core_ai\placement`
- Declares the `fix_accessibility` action
- Minimal class that acts as a registry

#### 2. **aiaction/fix_accessibility.php**
- Custom action class (not extending core_ai actions)
- Provides action metadata (name, description, ID)
- Allows independent plugin action definition

#### 3. **external/fix_accessibility.php**
- Implements the web service API
- Validates user permissions and context
- Orchestrates the analysis and fixing process
- Returns detailed results

#### 4. **utils.php**
- `is_valid_html()` - Validates HTML structure
- `analyze_accessibility_issues()` - Identifies WCAG AA problems using DOM parsing
- `build_accessibility_fix_prompt()` - Creates detailed AI prompt
- `identify_changes()` - Tracks what was modified
- `generate_report()` - Creates human-readable report

## Web Service API

### Service: `aiplacement_a11y_fix_accessibility`

**Endpoint**: AJAX accessible
**Type**: Write (modifies/creates data)
**Authentication**: Required

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `contextid` | int | Yes | The context ID (course, module, etc.) |
| `htmlcontent` | string | Yes | The HTML content to analyze and fix |

#### Return Value

```json
{
  "success": true,
  "original_content": "<img src='test.jpg'>",
  "fixed_content": "<img src='test.jpg' alt='Description'>",
  "has_issues": true,
  "issues_found": 1,
  "analysis_report": "<div>...HTML report...</div>",
  "changes_made": [
    {
      "type": "alt_text_added",
      "before": "",
      "after": "Description",
      "element": "img"
    }
  ]
}
```

## Accessibility Issues Detected

### High Severity

1. **Missing Alt Text** (`missing_alt_text`)
   - Images without alt attribute
   - Images with empty alt attribute
   - Issue: Images not accessible to screen readers

2. **Weak Link Text** (`weak_link_text`)
   - Links with no text content
   - Links with generic text: "click here", "read more", "link", "here"
   - Issue: Screen readers can't describe where link goes

3. **Missing Form Labels** (`missing_form_label`)
   - Form inputs without associated `<label>` element
   - Issue: Form fields not accessible to assistive technology

### Medium Severity

1. **Missing H1 Heading** (`missing_h1`)
   - Content has no level 1 heading
   - Issue: Page structure unclear

### Low Severity

1. **Potential Contrast Issues** (`potential_contrast_issue`)
   - Text in all caps (styling risk)
   - Issue: May indicate styling that affects contrast

## Usage Examples

### JavaScript Integration in Editor

```javascript
// Call the web service to fix accessibility
const result = await call([{
    methodname: 'aiplacement_a11y_fix_accessibility',
    args: {
        contextid: contextId,
        htmlcontent: editorContent
    }
}])[0];

if (result.success) {
    // Show comparison view
    console.log('Original:', result.original_content);
    console.log('Fixed:', result.fixed_content);
    console.log('Issues found:', result.issues_found);
    console.log('Changes:', result.changes_made);
}
```

### PHP Integration

```php
$result = \aiplacement_a11y\external\fix_accessibility::execute(
    $contextid,
    $html_content
);

if ($result['success']) {
    // Content has been fixed
    $fixed_html = $result['fixed_content'];
    // Use $fixed_html in your application
}
```

## Permissions

### Capability: `aiplacement/a11y:use`

**Context**: Course level

**Allowed for**:
- Students: Yes (by default)
- Teachers: Yes
- Editing Teachers: Yes

**Permission**: Write (can modify content)

To restrict access, modify `db/access.php` and change the archetypes.

## Privacy & Data

### Data Handling

1. **Input**: HTML content from editor
2. **Processing**: 
   - Analyzed locally with DOM parsing
   - Sent to configured AI provider (e.g., OpenAI)
3. **Output**: Fixed HTML, analysis report, change list
4. **Storage**: No permanent storage in Moodle database

### Privacy Compliance

- Implements `\core_privacy\local\metadata\provider`
- Declares that content is sent to external AI provider
- Follows Moodle privacy standards
- No user tracking beyond standard audit logs

## Configuration

### Admin Settings

Go to **Admin > Plugins > AI Features > Placements > Accessibility Fixer**

Current version doesn't require additional settings. The plugin uses:
- Configured AI provider (must be set up separately)
- Course AI tool enabling (inherited from core AI settings)

## Testing

### Run Tests

```bash
# Run all tests for this plugin
php vendor/bin/phpunit aiplacement_a11y

# Run specific test
php vendor/bin/phpunit aiplacement_a11y/tests/utils_test.php

# Run with code coverage
php vendor/bin/phpunit --coverage-html coverage aiplacement_a11y
```

### Test Files

1. **tests/utils_test.php**
   - Tests HTML validation
   - Tests accessibility analysis
   - Tests issue detection

2. **tests/external/fix_accessibility_test.php**
   - Tests web service API
   - Tests parameter validation
   - Tests return structure

## Limitations & Future Improvements

### Current Limitations

1. **Color Contrast**: Only flags potential issues, doesn't measure actual contrast ratios
2. **Complex HTML**: May struggle with deeply nested or malformed HTML
3. **AI Accuracy**: Depends on AI provider's understanding of WCAG AA standards
4. **False Positives**: May suggest unnecessary changes
5. **Language**: Currently English only

### Future Improvements

- [ ] Implement actual color contrast ratio calculation
- [ ] Add support for WCAG AAA compliance
- [ ] Multilingual support
- [ ] Content cache for performance
- [ ] Batch processing for large documents
- [ ] Integration with accessibility audit tools
- [ ] Custom prompt templates for different languages
- [ ] Preserve formatting and styling better
- [ ] Support for more HTML5 semantic elements
- [ ] Accessibility compliance scoring

## Troubleshooting

### Plugin Not Appearing

**Issue**: Accessibility Fixer doesn't show in plugin list

**Solution**:
1. Verify plugin is in correct directory: `public/ai/placement/a11y/`
2. Run: `php admin/cli/upgrade.php`
3. Clear caches: `php admin/cli/purge_caches.php`

### Web Service Returns Error

**Issue**: "aierror" message

**Possible Causes**:
- AI provider not configured
- No provider has `generate_text` action enabled
- Invalid HTML content
- User lacks permissions

**Solution**:
1. Check AI provider is configured (Admin > AI Features > Providers)
2. Enable the provider and its actions
3. Verify user has `aiplacement/a11y:use` capability
4. Check HTML is valid

### Generated HTML Invalid

**Issue**: "Generated HTML is invalid" error

**Possible Cause**: AI provider returned malformed HTML

**Solution**:
- Try with simpler HTML
- Check AI provider's model settings
- May need to refine the prompt (in utils.php)

## Performance Considerations

1. **HTML Analysis**: Uses DOM parsing which can be slow for large documents (>100KB)
2. **API Calls**: Each fix request calls the configured AI provider (typically 1-10 seconds)
3. **Response Size**: Fixed HTML can be larger if many changes were made
4. **Rate Limiting**: Respects Moodle's core AI rate limiting

## Security

1. **CSRF Protection**: All web services are protected by Moodle's CSRF tokens
2. **Permission Checking**: User context validated before processing
3. **Input Validation**: HTML is validated before sending to AI
4. **Output Validation**: Generated HTML is re-validated before returning
5. **Capability System**: Uses Moodle's capability system for access control

## Support & Contributions

For issues, feature requests, or contributions:

1. Test thoroughly before reporting issues
2. Include HTML examples that demonstrate the problem
3. Include expected vs. actual results
4. Check for similar existing issues

## License

GNU General Public License v3 or later

## Copyright

Copyright 2026 Accessibility Team

## Credits

Developed as a placement plugin extension for Moodle 5.1's AI subsystem.

---

## Quick Start Checklist

- [ ] Plugin installed in correct location
- [ ] `php admin/cli/upgrade.php` executed
- [ ] Plugin enabled in Admin > AI Features > Placements
- [ ] AI provider configured (e.g., OpenAI)
- [ ] Test with simple HTML content
- [ ] Review generated fixes before applying
- [ ] Check browser console for errors
- [ ] Run unit tests to verify installation

# Accessibility Fixer (aiplacement_a11y)

## Overview

The **Accessibility Fixer** is a Moodle 5.1 AI placement plugin that integrates WCAG AA accessibility checking and AI-powered fixing directly into the HTML text editor. It analyzes content in real-time and uses AI to automatically suggest and apply fixes for common accessibility issues.

**Version:** 1.4.0  
**Maturity:** Alpha  
**Requires:** Moodle 5.1+

## Features

### Accessibility Issues Detected

The plugin detects and can fix the following WCAG AA compliance issues:

#### High Severity
| Issue Type | Description |
|------------|-------------|
| **Missing Alt Text** | Images without alt attributes or with empty alt text |
| **Weak Link Text** | Links with generic text like "click here", "read more", "link", "here" |
| **Missing Form Labels** | Form inputs without associated `<label>` elements |
| **Insufficient Color Contrast** | Text with contrast ratio below 4.5:1 (WCAG AA requirement) |
| **Heading Hierarchy - First Heading** | Content that doesn't start with `<h3>` (h1/h2 are reserved for page structure) |

#### Medium Severity
| Issue Type | Description |
|------------|-------------|
| **Heading Hierarchy Skipping** | Headings that skip levels (e.g., h3 → h5) |
| **Heading Too Long** | Headings exceeding 1000 characters |
| **Unorganized Content** | More than 500 characters of content without headings to organize it |

#### Low Severity
| Issue Type | Description |
|------------|-------------|
| **Table Missing Caption** | Tables without `<caption>` elements |
| **Table Merged Cells** | Tables using colspan/rowspan which can confuse screen readers |
| **Table Missing Headers** | Tables without proper `<th>` header elements |

### Web Services

The plugin provides five web service endpoints:

| Service | Type | Description |
|---------|------|-------------|
| `aiplacement_a11y_fix_accessibility` | Write | Analyzes and fixes all accessibility issues using AI |
| `aiplacement_a11y_fix_single_issue` | Write | Fixes a single specific accessibility issue |
| `aiplacement_a11y_get_suggestion` | Read | Gets AI suggestion with reasoning for a specific issue |
| `aiplacement_a11y_analyze_only` | Read | Quick analysis without AI (for button status updates) |
| `aiplacement_a11y_get_settings` | Read | Retrieves plugin settings for JavaScript |

### Admin Settings

- **Auto-check Debounce**: Configurable delay (default: 2000ms) before automatically checking content after user stops typing. Set to 0 to disable auto-check.

### User Interface Features

- **Fix All Issues**: Button to fix all detected issues at once
- **Fix Individual Issues**: Fix button for each specific issue
- **Suggested Fix**: View AI reasoning and suggested HTML before applying
- **Show Me Where**: Highlight the issue location in the content preview
- **Tabbed Comparison View**: View original vs fixed content as rendered HTML or source code
- **Accept/Reject Changes**: Review changes before applying to editor

## Plugin Structure

```
ai/placement/a11y/
├── classes/
│   ├── placement.php                    # Main placement class
│   ├── utils.php                        # HTML analysis & AI prompt utilities
│   ├── hook_callbacks.php               # Hook implementations
│   ├── aiaction/
│   │   └── fix_accessibility.php        # AI action definition
│   ├── external/
│   │   ├── analyze_only.php             # Quick analysis web service
│   │   ├── fix_accessibility.php        # Full fix web service
│   │   ├── fix_single_issue.php         # Single issue fix web service
│   │   ├── get_settings.php             # Settings retrieval web service
│   │   └── get_suggestion.php           # AI suggestion web service
│   ├── form/
│   │   └── fix_accessibility_form.php   # Form definitions
│   └── privacy/
│       └── provider.php                 # Privacy API implementation
├── db/
│   ├── access.php                       # Capability definitions
│   ├── hooks.php                        # Hook registrations
│   └── services.php                     # Web service definitions
├── lang/
│   └── en/
│       └── aiplacement_a11y.php         # English language strings
├── templates/
│   ├── analysis_report.mustache         # Issue list with fix buttons
│   └── fix_accessibility_results.mustache # Comparison view template
├── amd/
│   ├── src/
│   │   └── a11y_fixer.js                # Main JavaScript module
│   └── build/
│       └── a11y_fixer.min.js            # Compiled JavaScript
├── tests/
│   ├── utils_test.php                   # Utility function tests
│   └── external/
│       └── fix_accessibility_test.php   # Web service tests
├── settings.php                         # Admin settings page
├── styles.css                           # Plugin styles
├── test_contrast.php                    # Contrast testing utility
├── version.php                          # Plugin metadata
└── README.md                            # This file
```

## Installation

1. **Copy plugin to correct location**:
   ```bash
   cp -r ai/placement/a11y /path/to/moodle/public/ai/placement/a11y
   ```

2. **Run upgrade**:
   ```bash
   php admin/cli/upgrade.php
   ```

3. **Enable the plugin**:
   - Navigate to **Admin > Plugins > AI Features > Placements**
   - Find "Accessibility Fixer" and enable it

4. **Configure AI Provider**:
   - Ensure you have an AI provider configured (e.g., OpenAI, Azure AI)
   - The plugin uses the provider's Generate Text capability

## How It Works

```
┌─────────────────────────────────────────────────────────────────┐
│                     USER IN EDITOR                               │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  1. User clicks "Fix Accessibility" button                       │
│     OR auto-check triggers after typing pause                    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  2. JavaScript calls analyze_only (quick check)                  │
│     - Returns issue count for button status                      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  3. PHP DOM Analysis (utils.php)                                 │
│     - Parses HTML with DOMDocument                               │
│     - Checks images, links, forms, tables, headings, contrast    │
│     - Returns structured issue list                              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  4. User clicks "Fix" or "Fix All"                               │
│     - Calls fix_accessibility or fix_single_issue                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  5. AI Processing                                                │
│     - Builds detailed prompt with issue context                  │
│     - Sends to configured AI provider (OpenAI/Azure)             │
│     - For images: Uses vision API to generate alt text           │
│     - Returns fixed HTML                                         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  6. Comparison View                                              │
│     - Shows original vs fixed content                            │
│     - Tabs: View HTML (rendered) / View Code (source)            │
│     - User can Accept or Reject changes                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  7. Editor Updated                                               │
│     - Fixed content applied to TinyMCE editor                    │
└─────────────────────────────────────────────────────────────────┘
```

## Web Service API

### aiplacement_a11y_fix_accessibility

Analyzes and fixes all accessibility issues in HTML content.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `contextid` | int | Yes | The Moodle context ID |
| `htmlcontent` | string | Yes | HTML content to analyze and fix |

**Returns:**
```json
{
  "success": true,
  "original_content": "<img src='photo.jpg'>",
  "fixed_content": "<img src='photo.jpg' alt='A sunset over mountains'>",
  "has_issues": true,
  "issues_found": 1,
  "analysis_report": "<div>...HTML report...</div>",
  "changes_made": "[{\"type\":\"alt_text_added\",\"before\":\"\",\"after\":\"A sunset over mountains\"}]"
}
```

### aiplacement_a11y_fix_single_issue

Fixes a single specific accessibility issue.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `contextid` | int | Yes | The Moodle context ID |
| `htmlcontent` | string | Yes | Current HTML content |
| `issuetype` | string | Yes | Type of issue (e.g., "missing_alt_text") |
| `issuedata` | string | Yes | JSON-encoded issue details |
| `imagedata` | string | No | Base64-encoded image data for vision AI |

### aiplacement_a11y_get_suggestion

Gets AI suggestion with reasoning before applying a fix.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `contextid` | int | Yes | The Moodle context ID |
| `htmlcontent` | string | Yes | Current HTML content |
| `issuetype` | string | Yes | Type of issue |
| `issuedata` | string | Yes | JSON-encoded issue details |
| `imagedata` | string | No | Base64-encoded image data |

**Returns:**
```json
{
  "success": true,
  "reasoning": "This image needs alt text to be accessible to screen reader users...",
  "suggested_html": "<img src='photo.jpg' alt='A golden retriever playing in a park'>"
}
```

### aiplacement_a11y_analyze_only

Quick analysis without AI calls (for button status).

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `contextid` | int | Yes | The Moodle context ID |
| `htmlcontent` | string | Yes | HTML content to analyze |

**Returns:**
```json
{
  "success": true,
  "has_issues": true,
  "issues_count": 5
}
```

### aiplacement_a11y_get_settings

Retrieves plugin settings for JavaScript.

**Returns:**
```json
{
  "autocheck_debounce": 2000
}
```

## Issue Types Reference

| Issue Type | Element | AI Fix Action |
|------------|---------|---------------|
| `missing_alt_text` | img | Generate descriptive alt text (uses vision AI if available) |
| `weak_link_text` | a | Replace with descriptive link text |
| `contrast_issue` | any | Adjust foreground/background colors for 4.5:1+ ratio |
| `missing_form_label` | input | Add appropriate label element |
| `table_missing_caption` | table | Add descriptive caption |
| `table_merged_cells` | table | Restructure with proper headers |
| `table_missing_headers` | table | Convert first row to th elements with scope |
| `heading_hierarchy_issue` | h3-h6 | Adjust heading levels to proper hierarchy |
| `heading_too_long` | h3-h6 | Shorten heading while maintaining meaning |
| `unheaded_content` | p/div | Add appropriate h3-h6 headings to organize content |

## Permissions

### Capability: `aiplacement/a11y:use`

- **Context**: Course level
- **Default roles**: Student, Teacher, Editing Teacher
- **Permission type**: Write

## Privacy & Data

### Data Flow
1. **Input**: HTML content from editor (not stored)
2. **Processing**: Analyzed locally with PHP DOM, then sent to AI provider
3. **Output**: Fixed HTML returned to user (not stored)

### Privacy Compliance
- Implements `\core_privacy\local\metadata\provider`
- Declares external AI provider data transmission
- No permanent data storage in Moodle database

## Requirements

1. **Moodle 5.1+** with AI subsystem enabled
2. **AI Provider configured** (OpenAI, Azure AI, etc.)
3. **Generate Text action enabled** on provider
4. **AI Tools enabled** in course settings
5. **User capability** `aiplacement/a11y:use`

## Testing

```bash
# Run all plugin tests
php vendor/bin/phpunit --testsuite aiplacement_a11y

# Run specific test file
php vendor/bin/phpunit aiplacement_a11y/tests/utils_test.php

# Run with coverage
php vendor/bin/phpunit --coverage-html coverage aiplacement_a11y
```

## Troubleshooting

### Plugin Not Appearing
1. Verify location: `public/ai/placement/a11y/`
2. Run: `php admin/cli/upgrade.php`
3. Clear caches: `php admin/cli/purge_caches.php`

### "AI not enabled in course" Error
- Enable AI Tools in course settings
- Verify AI provider is configured and enabled

### No Suggestions Generated
- Check AI provider API key is valid
- Verify Generate Text action is enabled
- Check Moodle logs for API errors

### Image Alt Text Not Generated
- Ensure AI provider supports vision capabilities
- Check image is accessible (not behind authentication)
- Verify image format is supported (JPEG, PNG, GIF, WebP)

## Heading Hierarchy Rules

This plugin enforces specific heading hierarchy rules:

1. **h1 and h2 are NOT used** - These are reserved for page-level structure
2. **Content must start with h3** - First heading in editor content must be h3
3. **No level skipping** - Cannot jump from h3 to h5 (must use h4)
4. **Headings under 1000 characters** - Long headings are flagged for review
5. **Content needs headings** - More than 500 characters without a heading triggers a warning

## License

GNU General Public License v3 or later

## Copyright

Copyright 2026 Patrick Thibaudeau, York University

## Credits

Developed as an AI placement plugin for Moodle 5.1's AI subsystem.

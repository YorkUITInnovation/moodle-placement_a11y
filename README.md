# Accessibility Fixer (aiplacement_a11y)

## Overview

The **Accessibility Fixer** is a Moodle 5.1 AI placement plugin that integrates WCAG AA accessibility checking and AI-powered fixing directly into the HTML text editor. It analyzes content in real-time and uses AI to automatically suggest and apply fixes for common accessibility issues.

**Version:** 0.8.1  
**Maturity:** Beta  
**Requires:** Moodle 5.1+

> [!IMPORTANT]
> **AI Provider Compatibility Notice**  
> Currently, only **Azure-based AI providers** have been fully tested with this plugin.  
> Support for **DeepSeek** and **OpenAI** providers is included but has **not been tested yet**.  
> **Ollama** is partially tested (connects successfully) but has **not been fully tested for fixing issues** and is **not recommended** for production use.  
> If you encounter issues with these providers, please report them on the plugin's issue tracker.

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
│   ├── privacy/
│   │   └── provider.php                 # Privacy API implementation
│   └── providers/
│       ├── AIProvider.php               # Base AI provider interface
│       ├── AzureProvider.php            # Azure AI provider (✅ Tested)
│       ├── DeepSeekProvider.php         # DeepSeek provider (⚠️ Not tested)
│       ├── OllamaProvider.php           # Ollama provider (⚠️ Partial - Not recommended)
│       ├── OpenAIProvider.php           # OpenAI provider (⚠️ Not tested)
│       └── ProviderFactory.php          # Provider factory class
├── db/
│   ├── access.php                       # Capability definitions
│   ├── hooks.php                        # Hook registrations
│   └── services.php                     # Web service definitions
├── lang/
│   ├── en/
│   │   └── aiplacement_a11y.php         # English language strings
│   └── fr/
│       └── aiplacement_a11y.php         # French language strings
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

1. **Install the TinyMCE editor plugin (required)**:
   ```bash
   cd /path/to/moodle/lib/editor/tiny/plugins
   git clone https://github.com/YorkUITInnovation/moodle-tiny_a11yfix.git a11yfix
   ```

2. **Copy AI placement plugin to correct location**:
   ```bash
   cp -r ai/placement/a11y /path/to/moodle/public/ai/placement/a11y
   ```

3. **Run upgrade**:
   ```bash
   php admin/cli/upgrade.php
   ```

4. **Enable the plugin**:
   - Navigate to **Admin > Plugins > AI Features > Placements**
   - Find "Accessibility Fixer" and enable it

5. **Configure AI Provider**:
   - Ensure you have an AI provider configured (Azure AI is recommended and fully tested)
   - Other providers (OpenAI, DeepSeek, Ollama) are supported but not yet all tested
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
│     - Sends to configured AI provider                            │
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

## Capabilities

This plugin defines a single capability:

### aiplacement/a11y:use

Allows users to use the Accessibility Fixer tool within the HTML editor.

| Property | Value |
|----------|-------|
| **Type** | Write (`captype => 'write'`) |
| **Context Level** | Course (`CONTEXT_COURSE`) |
| **Cloned From** | `moodle/course:view` |

**Default Role Assignments:**

| Role | Permission |
|------|------------|
| Student | Allowed |
| Teacher | Allowed |
| Editing Teacher | Allowed |
| Manager | Inherited (allowed via `moodle/course:view`) |
| Admin | Allowed |

**Notes:**
- This capability is granted at the course level
- Users must also have access to the course where they are editing content
- The capability inherits permissions from `moodle/course:view` by default

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
2. **TinyMCE Editor Plugin** - The **tiny_a11yfix** plugin is required to add the accessibility fixer button to the editor toolbar
   - Download from: [https://github.com/YorkUITInnovation/moodle-tiny_a11yfix](https://github.com/YorkUITInnovation/moodle-tiny_a11yfix)
   - Install to: `/lib/editor/tiny/plugins/a11yfix/`
3. **AI Provider configured** - Supported providers include:
   - **Azure AI** (✅ Tested and recommended)
   - **OpenAI** (⚠️ Not yet tested)
   - **DeepSeek** (⚠️ Not yet tested)
   - **Ollama** (⚠️ Partially tested - Connects successfully but not fully tested for fixing issues. Not recommended for production use.)
4. **Generate Text action enabled** on provider
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

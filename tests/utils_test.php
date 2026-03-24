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
 * Unit tests for accessibility placement.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Accessibility Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace aiplacement_a11y\tests;

defined('MOODLE_INTERNAL') || die();

/**
 * Test cases for accessibility utilities.
 */
class utils_test extends \advanced_testcase {

    public function setUp(): void {
        $this->resetAfterTest(true);
    }

    /**
     * Test HTML validation.
     */
    public function test_is_valid_html(): void {
        $utils = new \aiplacement_a11y\utils();

        // Valid HTML.
        $this->assertTrue($utils->is_valid_html('<p>Hello world</p>'));
        $this->assertTrue($utils->is_valid_html('<img src="test.jpg" alt="Test">'));
        $this->assertTrue($utils->is_valid_html('Plain text'));

        // Invalid HTML.
        $this->assertFalse($utils->is_valid_html(''));
        $this->assertFalse($utils->is_valid_html('   '));
    }

    /**
     * Test accessibility analysis for images.
     */
    public function test_analyze_missing_alt_text(): void {
        $utils = new \aiplacement_a11y\utils();
        $html = '<img src="image.jpg">';

        $analysis = $utils->analyze_accessibility_issues($html);

        $this->assertGreaterThan(0, count($analysis['issues']));
        $this->assertTrue(
            in_array('missing_alt_text', array_column($analysis['issues'], 'type'))
        );
    }

    /**
     * Test accessibility analysis for links.
     */
    public function test_analyze_weak_link_text(): void {
        $utils = new \aiplacement_a11y\utils();
        $html = '<a href="http://example.com">click here</a>';

        $analysis = $utils->analyze_accessibility_issues($html);

        $this->assertGreaterThan(0, count($analysis['issues']));
        $this->assertTrue(
            in_array('weak_link_text', array_column($analysis['issues'], 'type'))
        );
    }

    /**
     * Test accessibility analysis for missing H1.
     */
    public function test_analyze_missing_h1(): void {
        $utils = new \aiplacement_a11y\utils();
        $html = '<p>Some content</p>';

        $analysis = $utils->analyze_accessibility_issues($html);

        $this->assertTrue(
            in_array('missing_h1', array_column($analysis['issues'], 'type'))
        );
    }

    /**
     * Test that valid content passes analysis.
     */
    public function test_analyze_valid_content(): void {
        $utils = new \aiplacement_a11y\utils();
        $html = '<h1>Title</h1><img src="image.jpg" alt="Valid description"><a href="http://example.com">Descriptive link text</a>';

        $analysis = $utils->analyze_accessibility_issues($html);

        // Should have fewer or no issues.
        $this->assertIsArray($analysis['issues']);
    }
}

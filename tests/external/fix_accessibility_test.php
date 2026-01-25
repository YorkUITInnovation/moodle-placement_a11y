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
 * Unit tests for accessibility external API.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Accessibility Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace aiplacement_a11y\external\tests;

defined('MOODLE_INTERNAL') || die();

/**
 * Test cases for fix_accessibility external API.
 */
class fix_accessibility_test extends \advanced_testcase {

    public function setUp(): void {
        $this->resetAfterTest(true);
    }

    /**
     * Test that execute function is callable.
     */
    public function test_execute_is_callable(): void {
        $class = \aiplacement_a11y\external\fix_accessibility::class;
        $this->assertTrue(method_exists($class, 'execute'));
    }

    /**
     * Test parameter validation.
     */
    public function test_execute_parameters(): void {
        $params = \aiplacement_a11y\external\fix_accessibility::execute_parameters();
        $this->assertNotNull($params);
        $this->assertObjectHasProperty('keys', $params);
    }

    /**
     * Test return structure.
     */
    public function test_execute_returns(): void {
        $returns = \aiplacement_a11y\external\fix_accessibility::execute_returns();
        $this->assertNotNull($returns);
    }
}

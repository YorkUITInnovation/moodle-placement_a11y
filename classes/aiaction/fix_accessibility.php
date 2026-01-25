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

namespace aiplacement_a11y\aiaction;

/**
 * Base class for accessibility fix action.
 *
 * This is a custom action defined within the placement plugin.
 * It implements the required interface for core_ai action management.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Accessibility Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fix_accessibility {

    /**
     * Get the basename of the action class.
     *
     * Required by core_ai system for action management.
     * Returns the short name without namespace.
     *
     * @return string
     */
    public static function get_basename(): string {
        return 'fix_accessibility';
    }

    /**
     * Get the action display name.
     *
     * Required by core_ai system for admin display.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('fixaccessibility', 'aiplacement_a11y');
    }

    /**
     * Get the action description.
     *
     * Required by core_ai system for admin display.
     *
     * @return string
     */
    public static function get_description(): string {
        return get_string('fixaccessibility_desc', 'aiplacement_a11y');
    }
}

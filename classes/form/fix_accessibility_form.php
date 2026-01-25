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

namespace aiplacement_a11y\form;

use core_ai\form\action_settings_form;

/**
 * Action settings form for fix_accessibility action.
 *
 * This form provides configuration options for the accessibility fixing action.
 * It is used by AI providers to display settings when configuring this action.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Accessibility Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fix_accessibility_form extends action_settings_form {

    /**
     * Form definition.
     *
     * @return void
     */
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;

        // General settings header.
        $mform->addElement('header', 'generalsettingsheader', get_string('general', 'core'));

        // Description of what this action does.
        $mform->addElement('static', 'description', '', get_string('fixaccessibility_desc', 'aiplacement_a11y'));

        // Add note about delegation.
        $mform->addElement(
            'static',
            'delegationinfo',
            get_string('howitworks', 'aiplacement_a11y'),
            get_string('howitworks_delegation', 'aiplacement_a11y')
        );
    }
}

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
 * Hook callbacks for accessibility placement plugin.
 *
 * Provides action settings through the core AI hook system.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Accessibility Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Provide action settings form for generate_text action.
     *
     * This is called by the core AI system when displaying provider settings.
     * We return a form that reuses the provider's generate_text configuration.
     *
     * @param string $action The action class name
     * @param array $customdata Custom data for the form
     * @return \core_ai\form\action_settings_form|false
     */
    public static function get_action_settings_form(string $action, array $customdata = []) {
        // Check if this is the generate_text action from a core_ai placement
        if (strpos($action, 'core_ai') !== false && strpos($action, 'generate_text') !== false) {
            // Use the OpenAI provider's generate_text form
            $customdata['actionname'] = 'generate_text';
            $customdata['action'] = $action;

            try {
                return new \aiprovider_openai\form\action_generate_text_form(customdata: $customdata);
            } catch (\Throwable $e) {
                // If OpenAI provider not available, return false
                return false;
            }
        }

        return false;
    }

    /**
     * Provide action setting defaults for generate_text action.
     *
     * @param string $action The action class name
     * @return array Action setting defaults
     */
    public static function get_action_setting_defaults(string $action): array {
        // Check if this is the generate_text action from a core_ai placement
        if (strpos($action, 'core_ai') !== false && strpos($action, 'generate_text') !== false) {
            $customdata = [
                'actionname' => 'generate_text',
                'action' => $action,
                'providername' => 'aiprovider_openai',
            ];

            try {
                $form = new \aiprovider_openai\form\action_generate_text_form(customdata: $customdata);
                return $form->get_defaults();
            } catch (\Throwable $e) {
                // If OpenAI provider not available, return empty
                return [];
            }
        }

        return [];
    }
}



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

namespace aiplacement_a11y\providers;

/**
 * Provider factory for managing AI provider instances.
 *
 * Discovers available providers from Moodle's AI manager, instantiates
 * the appropriate provider classes, and manages provider selection.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Patrick Thibaudeau, York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ProviderFactory {

    /**
     * Get all available providers that have been configured in Moodle.
     *
     * @return array Array of [type => provider_instance, ...] for configured providers.
     * @throws \moodle_exception If no providers are configured.
     */
    public static function get_available_providers(): array {
        $manager = \core\di::get(\core_ai\manager::class);
        $available = [];

        // Check for Azure provider.
        $azure_instances = $manager->get_provider_instances(['provider' => 'aiprovider_azureai\\provider']);
        if (!empty($azure_instances)) {
            $available['azure'] = reset($azure_instances);
        }

        // Check for OpenAI provider.
        $openai_instances = $manager->get_provider_instances(['provider' => 'aiprovider_openai\\provider']);
        if (!empty($openai_instances)) {
            $available['openai'] = reset($openai_instances);
        }

        // Check for DeepSeek provider.
        $deepseek_instances = $manager->get_provider_instances(['provider' => 'aiprovider_deepseek\\provider']);
        if (!empty($deepseek_instances)) {
            $available['deepseek'] = reset($deepseek_instances);
        }

        // Check for Ollama provider.
        $ollama_instances = $manager->get_provider_instances(['provider' => 'aiprovider_ollama\\provider']);
        if (!empty($ollama_instances)) {
            $available['ollama'] = reset($ollama_instances);
        }

        if (empty($available)) {
            throw new \moodle_exception('noaiprovidersconfigured', 'aiplacement_a11y');
        }

        return $available;
    }

    /**
     * Get a specific provider instance.
     *
     * @param string $type The provider type ('azure', 'openai', 'deepseek', 'ollama').
     * @return AIProvider The instantiated provider.
     * @throws \moodle_exception If provider is not configured.
     */
    public static function get_provider(string $type): AIProvider {
        $available = self::get_available_providers();

        if (!isset($available[$type])) {
            throw new \moodle_exception('providernrtconfigured', 'aiplacement_a11y', '', $type);
        }

        $provider_instance = $available[$type];

        // Instantiate the appropriate provider class.
        $class_name = 'aiplacement_a11y\\providers\\' . ucfirst($type) . 'Provider';

        if (!class_exists($class_name)) {
            throw new \moodle_exception('providernrtfound', 'aiplacement_a11y', '', $type);
        }

        try {
            $provider = new $class_name($provider_instance);
        } catch (\Exception $e) {
            $error_details = $type . ': ' . $e->getMessage();
            throw new \moodle_exception('providernotproperlyconfigured', 'aiplacement_a11y', '', $error_details);
        }

        if (!$provider->is_configured()) {
            throw new \moodle_exception('providernotproperlyconfigured', 'aiplacement_a11y', '', $provider->get_name());
        }

        return $provider;
    }

    /**
     * Get the selected provider based on plugin settings.
     *
     * Falls back to first available provider if setting is not set or selected provider is unavailable.
     *
     * @return AIProvider The selected or default provider.
     * @throws \moodle_exception If no providers are available.
     */
    public static function get_selected_provider(): AIProvider {
        $available = self::get_available_providers();

        // Get plugin setting for preferred provider.
        $preferred_type = get_config('aiplacement_a11y', 'preferred_provider');

        // If preference is set and available, use it.
        if (!empty($preferred_type) && isset($available[$preferred_type])) {
            return self::get_provider($preferred_type);
        }

        // Fall back to first available provider (in preferred order).
        $preference_order = ['azure', 'openai', 'deepseek', 'ollama'];

        foreach ($preference_order as $type) {
            if (isset($available[$type])) {
                return self::get_provider($type);
            }
        }

        // Should never reach here due to get_available_providers() validation.
        throw new \moodle_exception('noaiprovidersconfigured', 'aiplacement_a11y');
    }

    /**
     * Get list of available provider types for settings dropdown.
     *
     * @return array Array of [type => name] for available providers.
     */
    public static function get_available_provider_list(): array {
        try {
            $available = self::get_available_providers();
        } catch (\moodle_exception $e) {
            return [];
        }

        $list = [];

        foreach ($available as $type => $instance) {
            // Determine class name.
            $class_name = 'aiplacement_a11y\\providers\\' . ucfirst($type) . 'Provider';

            if (class_exists($class_name)) {
                $provider = new $class_name($instance);
                $list[$type] = $provider->get_name();
            }
        }

        return $list;
    }
}


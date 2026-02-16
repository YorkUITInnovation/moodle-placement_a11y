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
 * Abstract base class for AI provider implementations.
 *
 * Defines the interface that all AI providers must implement.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Patrick Thibaudeau, York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class AIProvider {

    /**
     * Provider instance from Moodle's AI manager.
     *
     * @var object
     */
    protected $provider_instance;

    /**
     * Constructor.
     *
     * @param object $provider_instance The Moodle AI provider instance.
     */
    public function __construct($provider_instance) {
        $this->provider_instance = $provider_instance;
    }

    /**
     * Make an API call to generate text based on a prompt.
     *
     * @param string $prompt The prompt to send to the AI.
     * @return string The generated content.
     * @throws \moodle_exception
     */
    abstract public function call(string $prompt): string;

    /**
     * Make an API call with vision capabilities (image analysis).
     *
     * @param string $prompt The prompt to send to the AI.
     * @param string $image_base64 Base64-encoded image data with data URI scheme.
     * @return string The generated content.
     * @throws \moodle_exception
     */
    abstract public function call_with_vision(string $prompt, string $image_base64): string;

    /**
     * Validate that the provider is properly configured.
     *
     * @return bool True if valid, false otherwise.
     */
    abstract public function is_configured(): bool;

    /**
     * Get the provider name.
     *
     * @return string The human-readable provider name.
     */
    abstract public function get_name(): string;

    /**
     * Get the provider type identifier.
     *
     * @return string The provider type (e.g., 'azure', 'openai', 'deepseek', 'ollama').
     */
    abstract public function get_type(): string;
}


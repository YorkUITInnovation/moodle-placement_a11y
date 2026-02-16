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
 * Ollama provider implementation.
 *
 * Handles communication with Ollama (local, self-hosted AI models) using direct cURL calls.
 * Note: Ollama typically doesn't require API keys but requires endpoint configuration.
 * Vision support depends on the model being used.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Patrick Thibaudeau, York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class OllamaProvider extends AIProvider {

    /**
     * Make an API call to Ollama.
     *
     * @param string $prompt The prompt to send to the AI.
     * @return string The generated content.
     * @throws \moodle_exception
     */
    public function call(string $prompt): string {
        if (!$this->is_configured()) {
            throw new \moodle_exception('ollamanotconfigured', 'aiplacement_a11y');
        }

        $endpoint = $this->get_endpoint();
        $model = $this->get_model();

        if (empty($endpoint) || empty($model)) {
            throw new \moodle_exception('ollamanotconfigured', 'aiplacement_a11y');
        }

        // Ollama API endpoint.
        $url = rtrim($endpoint, '/') . '/api/chat';

        // Build the request payload.
        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'stream' => false,
        ];

        // Make the HTTP request using cURL.
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Ollama can be slow on first request.

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerror = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpcode !== 200) {
            $errormsg = 'Ollama API error: ' . ($curlerror ?: "HTTP $httpcode");
            if ($response) {
                $responsedata = json_decode($response, true);
                if (isset($responsedata['error'])) {
                    $errormsg = $responsedata['error'];
                }
            }
            throw new \moodle_exception('aierror', 'core_ai', '', $errormsg);
        }

        // Parse the response.
        $responsedata = json_decode($response, true);
        $content = trim($responsedata['message']['content'] ?? '');

        if (empty($content)) {
            throw new \moodle_exception('aierror', 'core_ai', '', 'No content generated');
        }

        return $content;
    }

    /**
     * Make an API call to Ollama with vision capabilities.
     *
     * Note: Vision support in Ollama depends on the model being used.
     * Models like llava support vision capabilities.
     *
     * @param string $prompt The prompt to send to the AI.
     * @param string $image_base64 Base64-encoded image data with data URI scheme.
     * @return string The generated content.
     * @throws \moodle_exception
     */
    public function call_with_vision(string $prompt, string $image_base64): string {
        if (!$this->is_configured()) {
            throw new \moodle_exception('ollamanotconfigured', 'aiplacement_a11y');
        }

        $endpoint = $this->get_endpoint();
        $model = $this->get_model();

        if (empty($endpoint) || empty($model)) {
            throw new \moodle_exception('ollamanotconfigured', 'aiplacement_a11y');
        }

        // Extract base64 data from data URI if present.
        $image_data = $image_base64;
        if (strpos($image_base64, 'base64,') !== false) {
            // Extract just the base64 part after 'base64,'
            $parts = explode('base64,', $image_base64);
            $image_data = $parts[1] ?? $image_base64;
        }

        // Ollama API endpoint.
        $url = rtrim($endpoint, '/') . '/api/chat';

        // Build the request payload for vision.
        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                    'images' => [$image_data], // Ollama uses 'images' array with base64 strings.
                ],
            ],
            'stream' => false,
        ];

        // Make the HTTP request using cURL.
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerror = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpcode !== 200) {
            $errormsg = 'Ollama API error: ' . ($curlerror ?: "HTTP $httpcode");
            if ($response) {
                $responsedata = json_decode($response, true);
                if (isset($responsedata['error'])) {
                    $errormsg = $responsedata['error'];
                }
            }
            throw new \moodle_exception('aierror', 'core_ai', '', $errormsg);
        }

        // Parse the response.
        $responsedata = json_decode($response, true);
        $content = trim($responsedata['message']['content'] ?? '');

        if (empty($content)) {
            throw new \moodle_exception('aierror', 'core_ai', '', 'No content generated');
        }

        return $content;
    }

    /**
     * Validate that Ollama provider is properly configured.
     *
     * @return bool True if valid, false otherwise.
     */
    public function is_configured(): bool {
        if (empty($this->provider_instance)) {
            return false;
        }

        $endpoint = $this->get_endpoint();

        if (empty($endpoint)) {
            return false;
        }

        return !empty($this->get_model());
    }

    /**
     * Get the Ollama endpoint URL from configuration.
     *
     * @return string The Ollama endpoint URL (e.g., 'http://localhost:11434').
     */
    private function get_endpoint(): string {
        // Try to get endpoint from config.
        if (isset($this->provider_instance->config) && is_array($this->provider_instance->config)) {
            if (!empty($this->provider_instance->config['endpoint'])) {
                return $this->provider_instance->config['endpoint'];
            }
        }

        // Try to get from action config as fallback.
        if (isset($this->provider_instance->actionconfig) && is_array($this->provider_instance->actionconfig)) {
            foreach ($this->provider_instance->actionconfig as $key => $action_config) {
                if ($key == 'core_ai\aiactions\generate_text') {
                    $endpoint = $action_config['settings']['endpoint'] ?? '';
                    if (!empty($endpoint)) {
                        return $endpoint;
                    }
                }
            }
        }

        // Fallback to common Ollama local endpoint.
        return 'http://localhost:11434';
    }

    /**
     * Get the model name from configuration.
     *
     * @return string The model name (e.g., 'llama2', 'mistral', 'llava').
     */
    private function get_model(): string {
        // Try to get model from action config.
        if (isset($this->provider_instance->actionconfig) && is_array($this->provider_instance->actionconfig)) {
            foreach ($this->provider_instance->actionconfig as $key => $action_config) {
                if ($key == 'core_ai\aiactions\generate_text') {
                    $model = $action_config['settings']['model'] ?? '';
                    if (!empty($model)) {
                        return $model;
                    }
                }
            }
        }

        // Return empty string - is_configured() will return false
        return '';
    }

    /**
     * Get the provider name.
     *
     * @return string The human-readable provider name.
     */
    public function get_name(): string {
        return 'Ollama (Local)';
    }

    /**
     * Get the provider type.
     *
     * @return string The provider type identifier.
     */
    public function get_type(): string {
        return 'ollama';
    }
}


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
 * OpenAI provider implementation.
 *
 * Handles communication with OpenAI API (ChatGPT, GPT-4, etc.) using direct cURL calls
 * to support advanced features like vision and custom prompting.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Patrick Thibaudeau, York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class OpenAIProvider extends AIProvider {

    /**
     * Make an API call to OpenAI.
     *
     * @param string $prompt The prompt to send to the AI.
     * @return string The generated content.
     * @throws \moodle_exception
     */
    public function call(string $prompt): string {
        if (!$this->is_configured()) {
            throw new \moodle_exception('openainotconfigured', 'aiplacement_a11y');
        }

        $apikey = $this->provider_instance->config['apikey'] ?? '';
        $model = $this->get_model();

        if (empty($apikey) || empty($model)) {
            throw new \moodle_exception('openainotconfigured', 'aiplacement_a11y');
        }

        // OpenAI API endpoint.
        $url = 'https://api.openai.com/v1/chat/completions';

        // Build the request payload.
        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => 4096,
            'temperature' => 0.7,
        ];

        // Make the HTTP request using cURL.
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerror = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpcode !== 200) {
            $errormsg = 'OpenAI API error: ' . ($curlerror ?: "HTTP $httpcode");
            if ($response) {
                $responsedata = json_decode($response, true);
                if (isset($responsedata['error']['message'])) {
                    $errormsg = $responsedata['error']['message'];
                }
            }
            throw new \moodle_exception('aierror', 'core_ai', '', $errormsg);
        }

        // Parse the response.
        $responsedata = json_decode($response, true);
        $content = trim($responsedata['choices'][0]['message']['content'] ?? '');

        if (empty($content)) {
            throw new \moodle_exception('aierror', 'core_ai', '', 'No content generated');
        }

        return $content;
    }

    /**
     * Make an API call to OpenAI with vision capabilities.
     *
     * @param string $prompt The prompt to send to the AI.
     * @param string $image_base64 Base64-encoded image data with data URI scheme.
     * @return string The generated content.
     * @throws \moodle_exception
     */
    public function call_with_vision(string $prompt, string $image_base64): string {
        if (!$this->is_configured()) {
            throw new \moodle_exception('openainotconfigured', 'aiplacement_a11y');
        }

        $apikey = $this->provider_instance->config['apikey'] ?? '';
        $model = $this->get_model();

        if (empty($apikey) || empty($model)) {
            throw new \moodle_exception('openainotconfigured', 'aiplacement_a11y');
        }

        // OpenAI API endpoint.
        $url = 'https://api.openai.com/v1/chat/completions';

        // Build the request payload for vision.
        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $image_base64,
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 150,
        ];

        // Make the HTTP request using cURL.
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerror = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpcode !== 200) {
            $errormsg = 'OpenAI API error: ' . ($curlerror ?: "HTTP $httpcode");
            if ($response) {
                $responsedata = json_decode($response, true);
                if (isset($responsedata['error']['message'])) {
                    $errormsg = $responsedata['error']['message'];
                }
            }
            throw new \moodle_exception('aierror', 'core_ai', '', $errormsg);
        }

        // Parse the response.
        $responsedata = json_decode($response, true);
        $content = trim($responsedata['choices'][0]['message']['content'] ?? '');

        if (empty($content)) {
            throw new \moodle_exception('aierror', 'core_ai', '', 'No content generated');
        }

        return $content;
    }

    /**
     * Validate that OpenAI provider is properly configured.
     *
     * @return bool True if valid, false otherwise.
     */
    public function is_configured(): bool {
        if (empty($this->provider_instance)) {
            return false;
        }

        $apikey = $this->provider_instance->config['apikey'] ?? '';

        if (empty($apikey)) {
            return false;
        }

        return !empty($this->get_model());
    }

    /**
     * Get the model name from provider configuration.
     *
     * @return string The model name (e.g., 'gpt-4-turbo', 'gpt-4-vision-preview').
     */
    private function get_model(): string {
        // Try to get model from action config.
        foreach ($this->provider_instance->actionconfig as $key => $action_config) {
            if ($key == 'core_ai\aiactions\generate_text') {
                $model = $this->provider_instance->actionconfig[$key]['settings']['model'] ?? '';
                if (!empty($model)) {
                    return $model;
                }
            }
        }

        // Fallback to common model names.
        return 'gpt-4-turbo';
    }

    /**
     * Get the provider name.
     *
     * @return string The human-readable provider name.
     */
    public function get_name(): string {
        return 'OpenAI';
    }

    /**
     * Get the provider type.
     *
     * @return string The provider type identifier.
     */
    public function get_type(): string {
        return 'openai';
    }
}


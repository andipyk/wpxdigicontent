<?php

declare(strict_types=1);

namespace DigiContent\Core;

class AIGenerator {
    private string $anthropic_key;
    private string $openai_key;
    private array $settings;

    public function __construct() {
        $this->anthropic_key = get_option('digicontent_anthropic_key', '');
        $this->openai_key = get_option('digicontent_openai_key', '');
        $this->settings = (array) get_option('digicontent_settings', [
            'default_model' => 'gpt-4-turbo-preview',
            'max_tokens' => 1000,
            'temperature' => 0.7
        ]);
    }

    public function generate(string $prompt, string $model): string {
        if (empty($prompt)) {
            throw new \Exception(__('Prompt cannot be empty', 'digicontent'));
        }

        // Sanitize prompt
        $prompt = sanitize_text_field($prompt);
        
        // Validate model first
        $model = $this->validate_model($model);
        
        try {
            // Check if using Claude model
            if (strpos($model, 'claude') === 0) {
                $decrypted_key = Encryption::decrypt($this->anthropic_key);
                if (empty($decrypted_key)) {
                    throw new \Exception(__('Anthropic API key is not configured. Please add your API key in the settings.', 'digicontent'));
                }
                return $this->generate_with_anthropic($prompt, $model);
            }
            
            // If not Claude, then it's OpenAI model
            $decrypted_key = Encryption::decrypt($this->openai_key);
            if (empty($decrypted_key)) {
                throw new \Exception(__('OpenAI API key is not configured. Please add your API key in the settings.', 'digicontent'));
            }
            return $this->generate_with_openai($prompt, $model);
        } catch (\Exception $e) {
            error_log(sprintf('[DigiContent] Error in generate method: %s', $e->getMessage()));
            throw $e;
        }
    }

    private function validate_model(string $model): string {
        $valid_anthropic_models = ['claude-3-sonnet-20240229', 'claude-3-opus-20240229', 'claude-3-haiku-20240307'];
        $valid_openai_models = ['gpt-4-turbo-preview', 'gpt-4', 'gpt-3.5-turbo'];

        // Check if it's a valid Claude model first
        if (in_array($model, $valid_anthropic_models)) {
            return $model;
        }

        // Then check if it's a valid OpenAI model
        if (in_array($model, $valid_openai_models)) {
            return $model;
        }

        // If model starts with 'claude', return the default Claude model
        if (strpos($model, 'claude') === 0) {
            return 'claude-3-sonnet-20240229';
        }

        // Default to GPT-4 Turbo if not a Claude model
        return 'gpt-4-turbo-preview';
    }

    private function generate_with_anthropic(string $prompt, string $model): string {
        try {
            $decrypted_key = Encryption::decrypt($this->anthropic_key);
            if (empty($decrypted_key)) {
                throw new \Exception(__('Anthropic API key is not configured. Please add your API key in the settings.', 'digicontent'));
            }
            $this->anthropic_key = $decrypted_key;

            $request_body = [
                'model' => $model,
                'max_tokens' => $this->settings['max_tokens'] ?? 1000,
                'messages' => [['role' => 'user', 'content' => $prompt]]
            ];

            $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->anthropic_key,
                    'anthropic-version' => '2023-06-01'
                ],
                'body' => json_encode($request_body),
                'timeout' => 60,
                'sslverify' => true
            ]);

            if (is_wp_error($response)) {
                error_log(sprintf('[DigiContent] Anthropic API error: %s', $response->get_error_message()));
                throw new \Exception(__('Failed to connect to Anthropic API. Please try again later.', 'digicontent'));
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                $error_message = wp_remote_retrieve_response_message($response);
                error_log(sprintf('[DigiContent] Anthropic API error: %s (HTTP %d)', $error_message, $response_code));
                throw new \Exception(__('Anthropic API request failed. Please try again later.', 'digicontent'));
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (empty($body['content'][0]['text'])) {
                error_log('[DigiContent] Invalid response format from Anthropic API');
                throw new \Exception(__('Invalid response from Anthropic API. Please try again later.', 'digicontent'));
            }

            return $body['content'][0]['text'];
        } catch (\Exception $e) {
            error_log(sprintf('[DigiContent] Error in generate_with_anthropic: %s', $e->getMessage()));
            throw $e;
        }
    }

    private function generate_with_openai(string $prompt, string $model): string {
        try {
            $decrypted_key = Encryption::decrypt($this->openai_key);
            if (empty($decrypted_key)) {
                throw new \Exception(__('OpenAI API key is not configured. Please add your API key in the settings.', 'digicontent'));
            }
            $this->openai_key = $decrypted_key;

            $request_body = [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => $this->settings['max_tokens'] ?? 1000,
                'temperature' => $this->settings['temperature'] ?? 0.7
            ];

            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->openai_key
                ],
                'body' => json_encode($request_body),
                'timeout' => 60,
                'sslverify' => true
            ]);

            if (is_wp_error($response)) {
                error_log(sprintf('[DigiContent] OpenAI API error: %s', $response->get_error_message()));
                throw new \Exception(__('Failed to connect to OpenAI API. Please try again later.', 'digicontent'));
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                $error_message = wp_remote_retrieve_response_message($response);
                error_log(sprintf('[DigiContent] OpenAI API error: %s (HTTP %d)', $error_message, $response_code));
                throw new \Exception(__('OpenAI API request failed. Please try again later.', 'digicontent'));
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (empty($body['choices'][0]['message']['content'])) {
                error_log('[DigiContent] Invalid response format from OpenAI API');
                throw new \Exception(__('Invalid response from OpenAI API. Please try again later.', 'digicontent'));
            }

            return $body['choices'][0]['message']['content'];
        } catch (\Exception $e) {
            error_log(sprintf('[DigiContent] Error in generate_with_openai: %s', $e->getMessage()));
            throw $e;
        }
    }
}
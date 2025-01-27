<?php

declare(strict_types=1);

namespace DigiContent\Core;

use DigiContent\Core\Services\LoggerService;
use DigiContent\Core\Services\EncryptionService;

/**
 * Handles AI content generation using various models.
 *
 * @since 1.0.0
 */
final class AIGenerator {
    private const ANTHROPIC_API_URL = 'https://api.anthropic.com/v1/messages';
    private const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';
    
    private const MODELS = [
        'claude-3-sonnet' => [
            'provider' => 'anthropic',
            'max_tokens' => 4096,
        ],
        'gpt-4-turbo-preview' => [
            'provider' => 'openai',
            'max_tokens' => 4096,
        ],
    ];

    private LoggerService $logger;
    private ?string $anthropic_key;
    private ?string $openai_key;
    private EncryptionService $encryption;

    /**
     * Initialize AI generator with API keys.
     */
    public function __construct(LoggerService $logger, EncryptionService $encryption) 
    {
        $this->logger = $logger;
        $this->encryption = $encryption;
        
        $anthropic_key = get_option('digicontent_anthropic_key');
        $openai_key = get_option('digicontent_openai_key');
        
        $this->anthropic_key = !empty($anthropic_key) 
            ? $this->encryption->decrypt($anthropic_key) 
            : null;

        $this->openai_key = !empty($openai_key) 
            ? $this->encryption->decrypt($openai_key) 
            : null;
    }

    /**
     * Generate content using specified model.
     *
     * @param string $prompt Content generation prompt.
     * @param string $model AI model to use.
     * @return string Generated content.
     * @throws \RuntimeException If content generation fails.
     */
    public function generate(string $prompt, string $model = 'claude-3-sonnet'): string 
    {
        if (empty($prompt)) {
            throw new \RuntimeException('Prompt cannot be empty');
        }

        $model = $this->validate_model($model);
        $sanitized_prompt = $this->sanitize_prompt($prompt);

        $this->logger->info('Generating content', [
            'model' => $model,
            'prompt_length' => strlen($sanitized_prompt),
        ]);

        try {
            return match (self::MODELS[$model]['provider']) {
                'anthropic' => $this->generate_with_anthropic($sanitized_prompt, $model),
                'openai' => $this->generate_with_openai($sanitized_prompt, $model),
                default => throw new \RuntimeException('Unsupported AI provider'),
            };
        } catch (\Exception $e) {
            $this->logger->error('Content generation failed', [
                'error' => $e->getMessage(),
                'model' => $model,
            ]);
            throw new \RuntimeException('Failed to generate content: ' . $e->getMessage());
        }
    }

    /**
     * Generate content using Anthropic's Claude model.
     *
     * @param string $prompt Sanitized prompt.
     * @param string $model Model identifier.
     * @return string Generated content.
     * @throws \RuntimeException If API request fails.
     */
    private function generate_with_anthropic(string $prompt, string $model): string 
    {
        if (empty($this->anthropic_key)) {
            throw new \RuntimeException('Anthropic API key not configured');
        }

        $response = wp_remote_post(
            self::ANTHROPIC_API_URL,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->anthropic_key,
                    'anthropic-version' => '2023-06-01',
                ],
                'body' => wp_json_encode([
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => self::MODELS[$model]['max_tokens'],
                ]),
                'timeout' => 60,
            ]
        );

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['content'][0]['text'])) {
            throw new \RuntimeException('Invalid response from Anthropic API');
        }

        return $body['content'][0]['text'];
    }

    /**
     * Generate content using OpenAI's GPT model.
     *
     * @param string $prompt Sanitized prompt.
     * @param string $model Model identifier.
     * @return string Generated content.
     * @throws \RuntimeException If API request fails.
     */
    private function generate_with_openai(string $prompt, string $model): string 
    {
        if (empty($this->openai_key)) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        $response = wp_remote_post(
            self::OPENAI_API_URL,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->openai_key,
                ],
                'body' => wp_json_encode([
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => self::MODELS[$model]['max_tokens'],
                ]),
                'timeout' => 60,
            ]
        );

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Invalid response from OpenAI API');
        }

        return $body['choices'][0]['message']['content'];
    }

    /**
     * Validate and normalize model selection.
     *
     * @param string $model Requested model.
     * @return string Validated model identifier.
     * @throws \RuntimeException If model is invalid.
     */
    private function validate_model(string $model): string 
    {
        if (!isset(self::MODELS[$model])) {
            $this->logger->info('Invalid model requested, using default', ['model' => $model]);
            return 'claude-3-sonnet';
        }
        return $model;
    }

    /**
     * Sanitize prompt for API submission.
     *
     * @param string $prompt Raw prompt.
     * @return string Sanitized prompt.
     */
    private function sanitize_prompt(string $prompt): string 
    {
        return wp_strip_all_tags(trim($prompt));
    }
}
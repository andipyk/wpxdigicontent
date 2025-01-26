<?php
declare(strict_types=1);

namespace DigiContent\Core;

class TemplateManager {
    private $template_repository;
    private const CATEGORIES = [
        'blog_post' => 'Blog Post',
        'product_description' => 'Product Description',
        'news_article' => 'News Article',
        'social_media' => 'Social Media Post',
        'email' => 'Email Template',
        'seo' => 'SEO Content'
    ];
    private const CACHE_EXPIRATION = 3600; // 1 hour in seconds
    private const MAX_VERSIONS = 5;

    public function __construct($template_repository) {
        $this->template_repository = $template_repository;
        add_action('init', [$this, 'init']);
    }

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints(): void {
        register_rest_route('digicontent/v1', '/templates', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_templates'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'category' => [
                        'type' => 'string',
                        'enum' => array_keys(self::CATEGORIES),
                        'required' => false,
                    ],
                    'search' => [
                        'type' => 'string',
                        'required' => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ]
                ]
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_template'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'name' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'category' => [
                        'required' => true,
                        'type' => 'string',
                        'enum' => array_keys(self::CATEGORIES),
                    ],
                    'prompt' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'wp_kses_post',
                    ],
                    'variables' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'default' => [],
                        'sanitize_callback' => function($variables) {
                            return array_map('sanitize_text_field', $variables);
                        },
                    ],
                ],
            ]
        ]);

        register_rest_route('digicontent/v1', '/templates/(?P<id>[\w-]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_template_versions'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_template'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_template'],
                'permission_callback' => [$this, 'check_permission'],
            ]
        ]);
    }

    public function check_permission(): bool {
        return current_user_can('edit_posts');
    }

    public function get_templates(\WP_REST_Request $request): \WP_REST_Response {
        try {
            $category = $request->get_param('category');
            $args = ['category' => $category];
            $templates = $this->template_repository->get_all($args);
            return new \WP_REST_Response($templates);
        } catch (\Exception $e) {
            return new \WP_REST_Response(
                ['message' => 'Error retrieving templates: ' . $e->getMessage()],
                500
            );
        }
    }

    public function get_template_versions(\WP_REST_Request $request): \WP_REST_Response {
        try {
            $template_id = $request['id'];
            $templates = get_option(self::TEMPLATE_OPTION_KEY, []);
            
            $template = current(array_filter($templates, fn($t) => $t['id'] === $template_id));
            
            if (!$template) {
                return new \WP_REST_Response(['message' => 'Template not found'], 404);
            }
            
            return new \WP_REST_Response($template);
        } catch (\Exception $e) {
            return new \WP_REST_Response(
                ['message' => 'Error retrieving template versions: ' . $e->getMessage()],
                500
            );
        }
    }

    public function update_template(\WP_REST_Request $request): \WP_REST_Response {
        try {
            $template_id = $request['id'];
            $body = $request->get_json_params();
            $templates = get_option(self::TEMPLATE_OPTION_KEY, []);
            
            $template_index = array_search($template_id, array_column($templates, 'id'));
            
            if ($template_index === false) {
                return new \WP_REST_Response(['message' => 'Template not found'], 404);
            }
            
            $current_template = $templates[$template_index];
            $new_version = $current_template['version'] + 1;
            
            $updated_template = [
                'id' => $template_id,
                'name' => sanitize_text_field($body['name'] ?? $current_template['name']),
                'category' => sanitize_text_field($body['category'] ?? $current_template['category']),
                'prompt' => wp_kses_post($body['prompt'] ?? $current_template['prompt']),
                'variables' => isset($body['variables']) 
                    ? array_map('sanitize_text_field', $body['variables']) 
                    : $current_template['variables'],
                'updated_at' => current_time('mysql'),
                'version' => $new_version
            ];
            
            $templates[$template_index] = $updated_template;
            update_option(self::TEMPLATE_OPTION_KEY, $templates);
            delete_transient('digicontent_templates_cache');
            
            return new \WP_REST_Response($updated_template);
        } catch (\Exception $e) {
            return new \WP_REST_Response(
                ['message' => 'Error updating template: ' . $e->getMessage()],
                500
            );
        }
    }

    public function save_template(\WP_REST_Request $request): \WP_REST_Response {
        try {
            $body = $request->get_json_params();

            if (!array_key_exists($body['category'], self::CATEGORIES)) {
                return new \WP_REST_Response(
                    ['message' => 'Invalid category'],
                    400
                );
            }

            $template_data = [
                'name' => sanitize_text_field($body['name']),
                'category' => sanitize_text_field($body['category']),
                'prompt' => wp_kses_post($body['prompt']),
                'variables' => maybe_serialize(array_map('sanitize_text_field', $body['variables'] ?? []))
            ];

            $template_id = $this->template_repository->create($template_data);
            
            if ($template_id === false) {
                return new \WP_REST_Response(
                    ['message' => 'Failed to create template'],
                    500
                );
            }
            
            $template = $this->template_repository->get($template_id);
            return new \WP_REST_Response($template, 201);
        } catch (\Exception $e) {
            return new \WP_REST_Response(
                ['message' => 'Error saving template: ' . $e->getMessage()],
                500
            );
        }
    }

    public function delete_template(\WP_REST_Request $request): \WP_REST_Response {
        try {
            $template_id = $request['id'];
            $templates = get_option(self::TEMPLATE_OPTION_KEY, []);

            $template_exists = false;
            $templates = array_filter($templates, function($t) use ($template_id, &$template_exists) {
                if ($t['id'] === $template_id) {
                    $template_exists = true;
                    return false;
                }
                return true;
            });

            if (!$template_exists) {
                return new \WP_REST_Response(
                    ['message' => 'Template not found'],
                    404
                );
            }

            update_option(self::TEMPLATE_OPTION_KEY, array_values($templates));
            delete_transient('digicontent_templates_cache');

            return new \WP_REST_Response(null, 204);
        } catch (\Exception $e) {
            return new \WP_REST_Response(
                ['message' => 'Error deleting template: ' . $e->getMessage()],
                500
            );
        }
    }

    public function get_categories(): array {
        return self::CATEGORIES;
    }

    public function process_template(string $template_content, array $variables): string {
        try {
            return preg_replace_callback('/\{\{([\w-]+)\}\}/', function($matches) use ($variables) {
                $var_name = $matches[1];
                return $variables[$var_name] ?? $matches[0];
            }, $template_content);
        } catch (\Exception $e) {
            error_log('Template processing error: ' . $e->getMessage());
            return $template_content;
        }
    }
}
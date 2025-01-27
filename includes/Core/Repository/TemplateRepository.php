<?php

namespace DigiContent\Core\Repository;

use DigiContent\Core\Database;

class TemplateRepository {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getTableName(): string {
        return $this->db->get_table_name('templates');
    }

    /**
     * Create a new template
     *
     * @param array $data Template data
     * @return int|false Template ID on success, false on failure
     */
    public function create($data) {
        global $wpdb;
        $table = $this->getTableName();

        $result = $wpdb->insert(
            $table,
            [
                'name' => $data['name'],
                'category' => $data['category'],
                'prompt' => $data['prompt'],
                'variables' => maybe_serialize($data['variables'])
            ],
            ['%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            $this->db->log('error', 'Failed to create template', [
                'data' => $data,
                'error' => $wpdb->last_error
            ]);
            return false;
        }

        wp_cache_delete('digicontent_templates');
        return $wpdb->insert_id;
    }

    /**
     * Get a template by ID
     *
     * @param int $id Template ID
     * @return object|null Template object or null if not found
     */
    public function get($id) {
        $cache_key = 'digicontent_template_' . $id;
        $cached = wp_cache_get($cache_key);
        
        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        $table = $this->getTableName();

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $id
        );
        
        $template = $wpdb->get_row($sql);

        if ($template) {
            $template->variables = maybe_unserialize($template->variables);
            wp_cache_set($cache_key, $template);
        }

        return $template;
    }

    /**
     * Get all templates with optional filtering
     *
     * @param array $args Query arguments
     * @return array Array of template objects
     */
    public function get_all($args = []) {
        $cache_key = 'digicontent_templates_' . md5(serialize($args));
        $cached = wp_cache_get($cache_key);
        
        if (false !== $cached) {
            return $cached;
        }

        global $wpdb;
        $table = $this->getTableName();

        $defaults = [
            'category' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        ];

        $args = wp_parse_args($args, $defaults);
        $where = '';
        $query_args = [];

        // Validate and sanitize orderby
        $allowed_orderby = ['created_at', 'name', 'category'];
        $args['orderby'] = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        
        // Validate and sanitize order
        $args['order'] = in_array(strtoupper($args['order']), ['ASC', 'DESC']) ? strtoupper($args['order']) : 'DESC';

        if (!empty($args['category'])) {
            $where = ' WHERE category = %s';
            $query_args[] = $args['category'];
        }

        $query_args[] = (int) $args['limit'];
        $query_args[] = (int) $args['offset'];

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}{$where} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            ...$query_args
        );

        $templates = $wpdb->get_results($sql);

        foreach ($templates as $template) {
            $template->variables = maybe_unserialize($template->variables);
        }

        wp_cache_set($cache_key, $templates);
        return $templates;
    }

    /**
     * Update a template
     *
     * @param int $id Template ID
     * @param array $data Template data
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        global $wpdb;
        $table = $this->getTableName();

        $result = $wpdb->update(
            $table,
            [
                'name' => $data['name'],
                'category' => $data['category'],
                'prompt' => $data['prompt'],
                'variables' => maybe_serialize($data['variables'])
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            $this->db->log('error', 'Failed to update template', [
                'id' => $id,
                'data' => $data,
                'error' => $wpdb->last_error
            ]);
            return false;
        }

        wp_cache_delete('digicontent_template_' . $id);
        wp_cache_delete('digicontent_templates');
        return true;
    }

    /**
     * Delete a template
     *
     * @param int $id Template ID
     * @return bool True on success, false on failure
     */
    public function delete($id) {
        global $wpdb;
        $table = $this->getTableName();

        $result = $wpdb->delete(
            $table,
            ['id' => $id],
            ['%d']
        );

        if ($result === false) {
            $this->db->log('error', 'Failed to delete template', [
                'id' => $id,
                'error' => $wpdb->last_error
            ]);
            return false;
        }

        wp_cache_delete('digicontent_template_' . $id);
        wp_cache_delete('digicontent_templates');
        return true;
    }
}
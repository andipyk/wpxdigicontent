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

        return $wpdb->insert_id;
    }

    /**
     * Get a template by ID
     *
     * @param int $id Template ID
     * @return object|null Template object or null if not found
     */
    public function get($id) {
        global $wpdb;
        $table = $this->getTableName();

        $template = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id)
        );

        if ($template) {
            $template->variables = maybe_unserialize($template->variables);
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

        if (!empty($args['category'])) {
            $where = $wpdb->prepare(' WHERE category = %s', $args['category']);
        }

        $sql = "SELECT * FROM $table$where
                ORDER BY {$args['orderby']} {$args['order']}
                LIMIT %d OFFSET %d";

        $templates = $wpdb->get_results(
            $wpdb->prepare($sql, $args['limit'], $args['offset'])
        );

        foreach ($templates as $template) {
            $template->variables = maybe_unserialize($template->variables);
        }

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

        return true;
    }
}
<?php

namespace DigiContent\Core;

class Database {
    private $wpdb;
    private $table_prefix;
    private $tables;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix . 'digicontent_';
        $this->tables = [
            'templates' => $this->table_prefix . 'templates',
            'logs' => $this->table_prefix . 'logs',
            'settings' => $this->table_prefix . 'settings'
        ];
    }

    /**
     * Initialize database tables
     *
     * @return void
     */
    public function init() {
        $this->create_tables();
    }

    /**
     * Create required database tables
     *
     * @return void
     */
    private function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $this->wpdb->get_charset_collate();

        // Templates table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tables['templates']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            category varchar(50) NOT NULL,
            prompt text NOT NULL,
            variables text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql);

        // Logs table for debugging
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tables['logs']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql);

        // Settings table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tables['settings']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Get table name
     *
     * @param string $table Table identifier
     * @return string Full table name
     */
    public function get_table_name($table) {
        return isset($this->tables[$table]) ? $this->tables[$table] : '';
    }

    /**
     * Log message for debugging
     *
     * @param string $level Log level (info, warning, error)
     * @param string $message Log message
     * @param array $context Additional context
     * @return bool|int False on failure, number of rows affected on success
     */
    public function log($level, $message, $context = []) {
        return $this->wpdb->insert(
            $this->tables['logs'],
            [
                'level' => $level,
                'message' => $message,
                'context' => maybe_serialize($context)
            ],
            ['%s', '%s', '%s']
        );
    }

    /**
     * Get logs for debugging
     *
     * @param array $args Query arguments
     * @return array Array of log entries
     */
    public function get_logs($args = []) {
        $defaults = [
            'level' => '',
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);
        $where = '';

        if (!empty($args['level'])) {
            $where = $this->wpdb->prepare(' WHERE level = %s', $args['level']);
        }

        $sql = "SELECT * FROM {$this->tables['logs']}{$where}
                ORDER BY {$args['orderby']} {$args['order']}
                LIMIT %d OFFSET %d";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $args['limit'], $args['offset'])
        );
    }

    /**
     * Clear logs
     *
     * @param string $level Optional log level to clear
     * @return bool|int False on failure, number of rows affected on success
     */
    public function clear_logs($level = '') {
        $where = '';
        $where_values = [];

        if (!empty($level)) {
            $where = ' WHERE level = %s';
            $where_values[] = $level;
        }

        $sql = "DELETE FROM {$this->tables['logs']}{$where}";

        if (!empty($where_values)) {
            $sql = $this->wpdb->prepare($sql, ...$where_values);
        }

        return $this->wpdb->query($sql);
    }
}
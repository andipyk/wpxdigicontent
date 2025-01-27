<?php

declare(strict_types=1);

namespace DigiContent\Core;

use DigiContent\Core\Services\LoggerService;

/**
 * Handles database operations for the DigiContent plugin.
 *
 * @since 1.0.0
 */
final class Database {
    private const TABLES = [
        'templates' => [
            'id' => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
            'name' => 'varchar(255) NOT NULL',
            'category' => 'varchar(50) NOT NULL',
            'prompt' => 'text NOT NULL',
            'variables' => 'text DEFAULT NULL CHECK (variables IS NULL OR JSON_VALID(variables))',
            'created_at' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'PRIMARY KEY' => '(id)',
            'KEY' => 'category (category)',
        ],
        'logs' => [
            'id' => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
            'level' => "enum('info','error','debug') NOT NULL DEFAULT 'info'",
            'message' => 'text NOT NULL',
            'context' => 'text DEFAULT NULL',
            'created_at' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'PRIMARY KEY' => '(id)',
            'KEY' => 'level (level)',
        ],
        'settings' => [
            'id' => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
            'option_name' => 'varchar(255) NOT NULL',
            'option_value' => 'longtext DEFAULT NULL',
            'created_at' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'PRIMARY KEY' => '(id)',
            'UNIQUE KEY' => 'option_name (option_name)',
        ],
    ];

    private \wpdb $wpdb;
    private LoggerService $logger;
    private string $charset_collate;
    private string $table_prefix;

    /**
     * Initialize database handler.
     */
    public function __construct(LoggerService $logger) 
    {
        global $wpdb;
        
        $this->wpdb = $wpdb;
        $this->logger = $logger;
        $this->charset_collate = $this->wpdb->get_charset_collate();
        $this->table_prefix = $this->wpdb->prefix . 'digicontent_';
    }

    /**
     * Create database tables.
     */
    public function create_tables(): void 
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        try {
            $wpdb->query('START TRANSACTION');

            foreach (self::TABLES as $table => $schema) {
                $table_name = $this->get_table_name($table);
                $columns = [];
                
                foreach ($schema as $column => $definition) {
                    if (in_array($column, ['PRIMARY KEY', 'KEY', 'UNIQUE KEY'])) {
                        $columns[] = "$column $definition";
                    } else {
                        $columns[] = "`$column` $definition";
                    }
                }
                
                $sql = sprintf(
                    "CREATE TABLE IF NOT EXISTS `%s` (\n%s\n) %s",
                    $table_name,
                    implode(",\n", $columns),
                    $this->charset_collate
                );

                dbDelta($sql);

                if (!$this->table_exists($table_name)) {
                    throw new \RuntimeException(sprintf('Failed to create table: %s', $table_name));
                }
            }

            $wpdb->query('COMMIT');
            $this->logger->info('Database tables created successfully');

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            $this->logger->error('Failed to create database tables', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Check if a table exists.
     *
     * @param string $table_name Full table name
     * @return bool Whether table exists
     */
    private function table_exists(string $table_name): bool 
    {
        global $wpdb;
        $sql = sprintf(
            "SHOW TABLES LIKE '%s'",
            $wpdb->_real_escape($table_name)
        );
        return (bool) $wpdb->get_var($sql);
    }

    /**
     * Drop database tables.
     */
    public function drop_tables(): void 
    {
        global $wpdb;

        try {
            $wpdb->query('START TRANSACTION');

            foreach (array_keys(self::TABLES) as $table) {
                $table_name = $this->get_table_name($table);
                $sql = $wpdb->prepare("DROP TABLE IF EXISTS %i", $table_name);
                $wpdb->query($sql);
            }

            $wpdb->query('COMMIT');
            $this->logger->info('Database tables dropped successfully');

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            $this->logger->error('Failed to drop database tables', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get full table name with prefix.
     *
     * @param string $table Base table name.
     * @return string Full table name.
     */
    public function get_table_name(string $table): string 
    {
        return $this->table_prefix . $table;
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
     * Log message for debugging
     *
     * @param string $level Log level (info, warning, error)
     * @param string $message Log message
     * @param array $context Additional context
     * @return bool|int False on failure, number of rows affected on success
     */
    public function log($level, $message, $context = []) {
        return $this->wpdb->insert(
            $this->get_table_name('logs'),
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
        $cache_key = 'digicontent_logs_' . md5(serialize($args));
        $cached = wp_cache_get($cache_key);
        
        if (false !== $cached) {
            return $cached;
        }

        $defaults = [
            'level' => '',
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);
        $where = '';
        $query_args = [];

        // Validate and sanitize orderby
        $allowed_orderby = ['created_at', 'level', 'message'];
        $args['orderby'] = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        
        // Validate and sanitize order
        $args['order'] = in_array(strtoupper($args['order']), ['ASC', 'DESC']) ? strtoupper($args['order']) : 'DESC';

        if (!empty($args['level'])) {
            $where = ' WHERE level = %s';
            $query_args[] = $args['level'];
        }

        $query_args[] = (int) $args['limit'];
        $query_args[] = (int) $args['offset'];

        $table_name = $this->get_table_name('logs');
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$table_name}{$where} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            ...$query_args
        );

        $logs = $this->wpdb->get_results($sql);
        
        foreach ($logs as $log) {
            $log->context = maybe_unserialize($log->context);
        }

        wp_cache_set($cache_key, $logs, '', 300); // Cache for 5 minutes
        return $logs;
    }

    /**
     * Clear logs
     *
     * @param string $level Optional log level to clear
     * @return bool|int False on failure, number of rows affected on success
     */
    public function clear_logs($level = ''): bool|int {
        $table_name = $this->get_table_name('logs');
        $where = '';
        $query_args = [];

        if (!empty($level)) {
            $where = ' WHERE level = %s';
            $query_args[] = $level;
        }

        $sql = $this->wpdb->prepare(
            "DELETE FROM {$table_name}{$where}",
            ...$query_args
        );

        $result = $this->wpdb->query($sql);
        
        if ($result !== false) {
            wp_cache_delete('digicontent_logs');
        }
        
        return $result;
    }

    /**
     * Check if required database tables exist
     *
     * @return bool True if all required tables exist, false otherwise
     */
    public function check_tables_exist(): bool {
        try {
            global $wpdb;
            foreach (self::TABLES as $table => $schema) {
                $table_name = $wpdb->prefix . $table;
                $sql = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
                if (!$wpdb->get_var($sql)) {
                    $this->logger->error('Required table does not exist', ['table' => $table_name]);
                    return false;
                }
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error checking tables', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function drop_table(string $table_name): void {
        $sql = $this->wpdb->prepare("DROP TABLE IF EXISTS %i", $table_name);
        $this->wpdb->query($sql);
    }
}
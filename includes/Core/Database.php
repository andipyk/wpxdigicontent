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
            'variables' => 'text DEFAULT NULL',
            'created_at' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'PRIMARY KEY' => '(id)',
            'KEY' => 'category (category)',
        ],
        'logs' => [
            'id' => 'bigint(20) unsigned NOT NULL AUTO_INCREMENT',
            'level' => 'varchar(20) NOT NULL',
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
     * Create plugin database tables.
     *
     * @throws \RuntimeException If table creation fails.
     */
    public function create_tables(): void 
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach (self::TABLES as $table => $columns) {
            $table_name = $this->get_table_name($table);
            $sql = $this->build_create_table_sql($table_name, $columns);

            if (!$this->execute_table_creation($sql)) {
                throw new \RuntimeException(
                    sprintf('Failed to create table: %s', $table_name)
                );
            }

            $this->logger->info(
                sprintf('Created table: %s', $table_name)
            );
        }
    }

    /**
     * Drop plugin database tables.
     */
    public function drop_tables(): void 
    {
        foreach (array_keys(self::TABLES) as $table) {
            $table_name = $this->get_table_name($table);
            
            $this->wpdb->query("DROP TABLE IF EXISTS {$table_name}");
            
            $this->logger->info(
                sprintf('Dropped table: %s', $table_name)
            );
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
     * Check if a table exists.
     *
     * @param string $table Base table name.
     * @return bool True if table exists, false otherwise.
     */
    public function table_exists(string $table): bool 
    {
        $table_name = $this->get_table_name($table);
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $table_name
            )
        );

        return $result === $table_name;
    }

    /**
     * Build CREATE TABLE SQL statement.
     *
     * @param string $table_name Full table name.
     * @param array<string, string> $columns Table columns and their definitions.
     * @return string SQL statement.
     */
    private function build_create_table_sql(string $table_name, array $columns): string 
    {
        $sql = "CREATE TABLE {$table_name} (\n";
        
        foreach ($columns as $column => $definition) {
            if (in_array($column, ['PRIMARY KEY', 'UNIQUE KEY', 'KEY'], true)) {
                $sql .= "\t{$column} {$definition},\n";
            } else {
                $sql .= "\t{$column} {$definition},\n";
            }
        }
        
        $sql = rtrim($sql, ",\n");
        $sql .= "\n) {$this->charset_collate};";
        
        return $sql;
    }

    /**
     * Execute table creation SQL.
     *
     * @param string $sql CREATE TABLE SQL statement.
     * @return bool True on success, false on failure.
     */
    private function execute_table_creation(string $sql): bool 
    {
        $result = dbDelta($sql);
        
        if (empty($result)) {
            return false;
        }

        foreach ($result as $table => $message) {
            if (str_contains($message, 'Created table')) {
                $this->logger->info($message);
            } elseif (str_contains($message, 'Modified table')) {
                $this->logger->info($message);
            } else {
                $this->logger->error($message);
                return false;
            }
        }

        return true;
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

        $sql = "SELECT * FROM {$this->get_table_name('logs')}{$where}
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

        $sql = "DELETE FROM {$this->get_table_name('logs')}{$where}";

        if (!empty($where_values)) {
            $sql = $this->wpdb->prepare($sql, ...$where_values);
        }

        return $this->wpdb->query($sql);
    }
}
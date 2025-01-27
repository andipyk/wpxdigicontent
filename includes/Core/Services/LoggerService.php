<?php

declare(strict_types=1);

namespace DigiContent\Core\Services;

/**
 * Handles logging functionality for the DigiContent plugin.
 */
final class LoggerService {
    private const LOG_FILE = 'digicontent-debug.log';
    
    private $log_dir;

    /**
     * Initialize logger and create log directory if needed.
     */
    public function __construct() 
    {
        $this->log_dir = WP_CONTENT_DIR . '/digicontent-logs';
        
        if (!$this->ensure_log_directory()) {
            throw new \RuntimeException('Failed to create log directory');
        }
    }

    /**
     * Ensure log directory exists and is writable.
     */
    private function ensure_log_directory(): bool 
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        if (!$wp_filesystem->exists($this->log_dir)) {
            if (!$wp_filesystem->mkdir($this->log_dir, 0755)) {
                return false;
            }

            // Create .htaccess to prevent direct access
            $htaccess = "Order deny,allow\nDeny from all";
            $wp_filesystem->put_contents(
                $this->log_dir . '/.htaccess',
                $htaccess,
                FS_CHMOD_FILE
            );
        }

        return $wp_filesystem->is_writable($this->log_dir);
    }

    /**
     * Write log entry to file.
     */
    private function write_log(string $level, string $message, array $context = []): void 
    {
        $timestamp = date('Y-m-d H:i:s');
        $log_file = $this->log_dir . '/digicontent-' . date('Y-m-d') . '.log';
        
        $entry = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            strtoupper($level),
            $message,
            !empty($context) ? wp_json_encode($context) : ''
        );

        if (file_exists($log_file)) {
            file_put_contents($log_file, $entry, FILE_APPEND);
        } else {
            file_put_contents($log_file, $entry);
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('DigiContent: %s', $entry));
        }
    }

    /**
     * Log a message with the specified level.
     *
     * @param string $level   The log level (debug, info, error).
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     *
     * @throws \RuntimeException If log directory cannot be created.
     */
    public function log(string $level, string $message, array $context = []): void {
        // Check if debug logging is enabled
        if ($level === 'debug' && !get_option('digicontent_debug_enabled', false)) {
            return;
        }

        try {
            $this->write_log($level, $message, $context);
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }
    
    public function debug(string $message, array $context = []): void {
        $this->log('debug', $message, $context);
    }
    
    public function error(string $message, array $context = []): void {
        $this->log('error', $message, $context);
    }
    
    public function info(string $message, array $context = []): void {
        $this->log('info', $message, $context);
    }
}
<?php

declare(strict_types=1);

namespace DigiContent\Core\Services;

/**
 * Handles logging functionality for the DigiContent plugin.
 */
final class LoggerService {
    private const LOG_FILE = 'digicontent-debug.log';
    
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
            if (!is_dir(WP_CONTENT_DIR . '/logs')) {
                $created = mkdir(WP_CONTENT_DIR . '/logs', 0755, true);
                if (!$created) {
                    throw new \RuntimeException('Failed to create logs directory');
                }
            }
            
            $log_entry = sprintf(
                "[%s] %s: %s %s\n",
                current_time('Y-m-d H:i:s'),
                strtoupper($level),
                $message,
                !empty($context) ? json_encode($context) : ''
            );
            
            error_log($log_entry, 3, WP_CONTENT_DIR . '/logs/' . self::LOG_FILE);
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
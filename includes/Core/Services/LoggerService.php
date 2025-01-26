<?php
declare(strict_types=1);

namespace DigiContent\Core\Services;

class LoggerService {
    private const LOG_FILE = 'digicontent-debug.log';
    
    public function log(string $level, string $message, array $context = []): void {
        if (!is_dir(WP_CONTENT_DIR . '/logs')) {
            mkdir(WP_CONTENT_DIR . '/logs', 0755, true);
        }
        
        $log_entry = sprintf(
            "[%s] %s: %s %s\n",
            current_time('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        error_log($log_entry, 3, WP_CONTENT_DIR . '/logs/' . self::LOG_FILE);
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
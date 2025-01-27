<?php
declare(strict_types=1);

namespace DigiContent\Core\Services;

class EncryptionService {
    private const CIPHER = "aes-256-cbc";
    private const HASH_ALGO = "sha256";
    private const ITERATIONS = 100000;
    private const KEY_LENGTH = 32;
    private const DELIMITER = ':';
    private LoggerService $logger;
    
    public function __construct(LoggerService $logger) {
        $this->logger = $logger;
    }
    
    public function encrypt(string $value): string {
        if (empty($value)) {
            return '';
        }
        
        try {
            $ivlen = openssl_cipher_iv_length(self::CIPHER);
            $iv = random_bytes($ivlen);
            $salt = random_bytes(32);
            $key = $this->generateKey($salt);
            
            $encrypted = openssl_encrypt(
                $value,
                self::CIPHER,
                $key,
                0,
                $iv
            );
            
            if ($encrypted === false) {
                throw new \Exception('Encryption failed');
            }
            
            return base64_encode(
                bin2hex($iv) . self::DELIMITER . 
                bin2hex($salt) . self::DELIMITER . 
                $encrypted
            );
            
        } catch (\Exception $e) {
            $this->logger->error('Encryption failed', ['error' => $e->getMessage()]);
            return '';
        }
    }
    
    public function decrypt(string $encrypted): string {
        if (empty($encrypted)) {
            return '';
        }
        
        try {
            $decoded = base64_decode($encrypted);
            if ($decoded === false) {
                throw new \Exception('Invalid base64 encoding');
            }
            
            $parts = explode(self::DELIMITER, $decoded);
            if (count($parts) !== 3) {
                throw new \Exception('Invalid encrypted data format');
            }
            
            [$iv, $salt, $ciphertext] = $parts;
            
            $iv = hex2bin($iv);
            $salt = hex2bin($salt);
            
            if ($iv === false || $salt === false) {
                throw new \Exception('Invalid hex encoding');
            }
            
            $key = $this->generateKey($salt);
            
            $decrypted = openssl_decrypt(
                $ciphertext,
                self::CIPHER,
                $key,
                0,
                $iv
            );
            
            if ($decrypted === false) {
                throw new \Exception('Decryption failed');
            }
            
            return $decrypted;
            
        } catch (\Exception $e) {
            $this->logger->error('Decryption failed', ['error' => $e->getMessage()]);
            return '';
        }
    }
    
    private function generateKey(string $salt): string {
        return hash_pbkdf2(
            self::HASH_ALGO,
            wp_salt('auth'),
            $salt,
            self::ITERATIONS,
            self::KEY_LENGTH,
            true
        );
    }
} 
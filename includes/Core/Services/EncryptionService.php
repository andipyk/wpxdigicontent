<?php
declare(strict_types=1);

namespace DigiContent\Core\Services;

class EncryptionService {
    private const CIPHER = "aes-256-cbc";
    private const HASH_ALGO = "sha256";
    private const ITERATIONS = 1000;
    private const KEY_LENGTH = 32;
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
            $iv = openssl_random_pseudo_bytes($ivlen);
            $salt = wp_generate_password(64, true, true);
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
            
            return base64_encode($iv . $salt . $encrypted);
            
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
            $encrypted = base64_decode($encrypted);
            if ($encrypted === false) {
                throw new \Exception('Invalid base64 encoding');
            }
            
            $ivlen = openssl_cipher_iv_length(self::CIPHER);
            $iv = substr($encrypted, 0, $ivlen);
            $salt = substr($encrypted, $ivlen, 64);
            $ciphertext = substr($encrypted, $ivlen + 64);
            
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
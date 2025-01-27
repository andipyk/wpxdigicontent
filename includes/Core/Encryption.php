<?php

declare(strict_types=1);

namespace DigiContent\Core;

use DigiContent\Core\Services\LoggerService;

/**
 * Handles encryption and decryption of sensitive data.
 *
 * @since 1.0.0
 */
final class Encryption {
    private const CIPHER_ALGO = 'aes-256-cbc';
    private const HASH_ALGO = 'sha256';
    private const KEY_LENGTH = 32;
    private const IV_LENGTH = 16;
    private const ITERATIONS = 10000;

    private LoggerService $logger;

    /**
     * Initialize encryption service.
     *
     * @param LoggerService $logger Logger service instance
     */
    public function __construct(LoggerService $logger) 
    {
        $this->logger = $logger;
    }

    /**
     * Encrypt a value.
     *
     * @param string $value Value to encrypt
     * @return string Encrypted value
     */
    public function encrypt(string $value): string 
    {
        try {
            if (empty($value)) {
                return '';
            }

            $key = $this->generate_key();
            $iv = random_bytes(self::IV_LENGTH);
            $salt = random_bytes(self::KEY_LENGTH);
            
            $key_derived = hash_pbkdf2(
                self::HASH_ALGO,
                $key,
                $salt,
                self::ITERATIONS,
                self::KEY_LENGTH,
                true
            );
            
            $encrypted = openssl_encrypt(
                $value,
                self::CIPHER_ALGO,
                $key_derived,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($encrypted === false) {
                throw new \RuntimeException('Encryption failed');
            }
            
            $encoded = base64_encode($iv . $salt . $encrypted);
            return $encoded;
            
        } catch (\Exception $e) {
            $this->logger->error('Encryption failed', ['error' => esc_html($e->getMessage())]);
            return '';
        }
    }

    /**
     * Decrypt a value.
     *
     * @param string $encrypted_value Value to decrypt
     * @return string Decrypted value
     */
    public function decrypt(string $encrypted_value): string 
    {
        try {
            if (empty($encrypted_value)) {
                return '';
            }
            
            $decoded = base64_decode($encrypted_value);
            if ($decoded === false) {
                throw new \RuntimeException('Invalid base64 encoding');
            }
            
            $iv = substr($decoded, 0, self::IV_LENGTH);
            $salt = substr($decoded, self::IV_LENGTH, self::KEY_LENGTH);
            $ciphertext = substr($decoded, self::IV_LENGTH + self::KEY_LENGTH);
            
            if (strlen($iv) !== self::IV_LENGTH || strlen($salt) !== self::KEY_LENGTH) {
                throw new \RuntimeException('Invalid encrypted data format');
            }
            
            $key = $this->generate_key();
            $key_derived = hash_pbkdf2(
                self::HASH_ALGO,
                $key,
                $salt,
                self::ITERATIONS,
                self::KEY_LENGTH,
                true
            );
            
            $decrypted = openssl_decrypt(
                $ciphertext,
                self::CIPHER_ALGO,
                $key_derived,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($decrypted === false) {
                throw new \RuntimeException('Decryption failed');
            }
            
            return $decrypted;
            
        } catch (\Exception $e) {
            $this->logger->error('Decryption failed', ['error' => esc_html($e->getMessage())]);
            return '';
        }
    }

    /**
     * Generate encryption key from WordPress salt keys.
     *
     * @return string Generated key.
     */
    private function generate_key(): string 
    {
        $salt = AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY;
        return substr(hash(self::HASH_ALGO, $salt), 0, self::KEY_LENGTH);
    }
}
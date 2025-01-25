<?php

declare(strict_types=1);

namespace DigiContent\Core;

class Encryption {
    private static ?string $salt = null;

    public static function encrypt(string $value): string {
        if (empty($value)) {
            return '';
        }

        if (self::$salt === null) {
            self::$salt = wp_salt('auth');
        }

        $nonce = wp_create_nonce('digicontent_encrypt');
        $encrypted = openssl_encrypt(
            $value,
            'AES-256-CBC',
            self::$salt,
            0,
            substr(self::$salt, 0, 16)
        );

        if ($encrypted === false) {
            throw new \Exception('Encryption failed');
        }

        return base64_encode($encrypted);
    }

    public static function decrypt(string $encrypted_value): string {
        if (empty($encrypted_value)) {
            return '';
        }

        if (self::$salt === null) {
            self::$salt = wp_salt('auth');
        }

        $encrypted = base64_decode($encrypted_value);
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            self::$salt,
            0,
            substr(self::$salt, 0, 16)
        );

        if ($decrypted === false) {
            throw new \Exception('Decryption failed');
        }

        return $decrypted;
    }
}
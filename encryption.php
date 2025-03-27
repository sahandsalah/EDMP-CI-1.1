<?php
/**
 * Secure password encryption and decryption functions
 * Uses OpenSSL for strong encryption
 */

// Generate a secure encryption key and store it in a secure location outside web root
// This should be done once and saved securely
function generateEncryptionKey() {
    return base64_encode(openssl_random_pseudo_bytes(32));
}

// Encrypt a password
function encryptPassword($plainPassword) {
    // Your encryption key - store this securely, ideally in environment variables
    $encryptionKey = getenv('ENCRYPTION_KEY') ?: 'YOUR_SECURE_KEY_HERE';
    $key = base64_decode($encryptionKey);
    
    // Generate an initialization vector
    $ivSize = openssl_cipher_iv_length('AES-256-CBC');
    $iv = openssl_random_pseudo_bytes($ivSize);
    
    // Encrypt the password
    $encrypted = openssl_encrypt(
        $plainPassword,
        'AES-256-CBC',
        $key,
        0,
        $iv
    );
    
    // Combine the IV and encrypted data for storage
    $encryptedWithIv = base64_encode($iv . $encrypted);
    
    return $encryptedWithIv;
}

// Decrypt a password
function decryptPassword($encryptedPassword) {
    // Your encryption key - same as above
    $encryptionKey = getenv('ENCRYPTION_KEY') ?: 'YOUR_SECURE_KEY_HERE';
    $key = base64_decode($encryptionKey);
    
    // Decode from base64
    $data = base64_decode($encryptedPassword);
    
    // Extract the initialization vector and encrypted data
    $ivSize = openssl_cipher_iv_length('AES-256-CBC');
    $iv = substr($data, 0, $ivSize);
    $encrypted = substr($data, $ivSize);
    
    // Decrypt the password
    $decrypted = openssl_decrypt(
        $encrypted,
        'AES-256-CBC',
        $key,
        0,
        $iv
    );
    
    return $decrypted;
}

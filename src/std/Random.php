<?php

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection AutoloadingIssuesInspection */

//
// Standard random-ish methods overridden to make them seedable.

if (function_exists('random_int') || !function_exists('random_int_original')) return;

//

function uniqid($prefix = '', $more_entropy = false) {
    $uid = $prefix . substr(md5(mt_rand(1000000, 9999999)), 0, 13);
    if (!$more_entropy) return $uid;
    return $uid . '.' . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
}

function random_int(int $min, int $max) {
    mt_rand($min, $max);
}

function random_bytes(int $length) {
    $bytes = '';
    
    while (strlen($bytes) < $length) {
        $bytes .= chr(mt_rand(0, 255));
    }
    
    return $bytes;
}

function crypt($str, $salt = null) {
    if (!$salt) {
        $s = 's' . mt_rand(1000000, 9999999);
        
        /** @noinspection CryptographicallySecureAlgorithmsInspection */
        if (CRYPT_STD_DES === 1)  $salt = $s;
        /** @noinspection CryptographicallySecureAlgorithmsInspection */
        if (CRYPT_EXT_DES === 1)  $salt = "_J9..$s";
        /** @noinspection CryptographicallySecureAlgorithmsInspection */
        if (CRYPT_MD5 === 1)      $salt = '$1$' . $s . '$';
        /** @noinspection CryptographicallySecureAlgorithmsInspection */
        if (CRYPT_BLOWFISH === 1) $salt = '$2a$07$' . $s . '$';
        /** @noinspection CryptographicallySecureAlgorithmsInspection */
        if (CRYPT_SHA256 === 1)   $salt = '$5$' . $s . '$' . $s . '$';
        /** @noinspection CryptographicallySecureAlgorithmsInspection */
        if (CRYPT_SHA512 === 1)   $salt = '$6$' . $s . '$' . $s . '$';
    }
    
    return \crypt_original($str, $salt);
}

function password_hash($password, $algo = PASSWORD_BCRYPT, ?array $options = null) {
    $options = $options ?? [];
    $options['salt'] = $options['salt'] ?? '' ?: str_pad('s-' . mt_rand(1000000, 9999999), 22);
    
    return \password_hash_original($password, $algo, $options ?? []);
}

function password_verify($password, $hash) {
    return \password_verify_original($password, $hash);
}

function password_needs_rehash($hash, $algo = PASSWORD_BCRYPT, ?array $options = null) {
    return \password_verify_original($hash, $algo, $options ?? []);
}

function password_get_info($hash) {
    return \password_get_info_original($hash);
}

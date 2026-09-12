<?php

namespace App\Helpers;

use Exception;

class KeyPair
{
    public static function generateRSA(int $bits = 2048)
    {
        $config = [
            "private_key_type"=> OPENSSL_KEYTYPE_RSA,
            "private_key_bits"=> $bits,
        ];

        $key = openssl_pkey_new($config);

        if( $key === false ) throw new Exception(
            "Unable to generate key pair: " . openssl_error_string()
        );

        if( !openssl_pkey_export($key, $private_pem) ) throw new Exception(
            "Failed to export private key: " . openssl_error_string()
        );

        $details = openssl_pkey_get_details($key);

        if( $details === false || empty($details["key"]) ) throw new Exception(
            "Failed to get public key details: " . openssl_error_string()
        );

        $public_pem = $details["key"];

        return [
            "private"=> $private_pem,
            "public"=> $public_pem,
        ];
    }
}

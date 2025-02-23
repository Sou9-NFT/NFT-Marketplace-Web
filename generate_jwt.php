<?php
require 'vendor/autoload.php';

use Firebase\JWT\JWT;

$secretKey = "Souk NFT"; // Replace this with a securely generated key
$payload = [
    "mercure" => [
        "publish" => ["*"], // Allows publishing to all topics
    ],
    "exp" => time() + 3600 // Expires in 1 hour
];

$jwt = JWT::encode($payload, $secretKey, 'HS256');

echo "MERCURE_PUBLISHER_JWT_KEY: " . $jwt . PHP_EOL;
?>

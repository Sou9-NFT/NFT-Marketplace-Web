<?php

require __DIR__ . '/../../vendor/autoload.php'; // Adjust the path as needed

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

// Define the secret directly in the code
$secret = 's3cUr3R@nd0mStr1ngTh@tIsV3ryL0ngAndH@rdToGu3ss';

// Create a configuration object
$config = Configuration::forSymmetricSigner(
    new Sha256(),
    InMemory::plainText($secret)
);

// Create a token for the publisher
$now = new DateTimeImmutable();
$publisherToken = $config->builder()
    ->issuedBy('https://example.com') // Configures the issuer (iss claim)
    ->withClaim('mercure', ['publish' => ['*']])
    ->issuedAt($now)
    ->getToken($config->signer(), $config->signingKey());

// Create a token for the subscriber
$subscriberToken = $config->builder()
    ->issuedBy('https://example.com') // Configures the issuer (iss claim)
    ->withClaim('mercure', ['subscribe' => ['*']])
    ->issuedAt($now)
    ->getToken($config->signer(), $config->signingKey());

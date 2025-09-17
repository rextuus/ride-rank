#!/usr/bin/env php
<?php

use App\Kernel;
use App\Service\Rcdb\Crawler;
use Symfony\Component\HttpClient\HttpClient;

if (!is_dir(dirname(__DIR__).'/vendor')) {
    throw new LogicException('Dependencies are missing. Try running "composer install".');
}

if (!is_file(dirname(__DIR__).'/vendor/autoload_runtime.php')) {
    throw new LogicException('Symfony Runtime is missing. Try running "composer require symfony/runtime".');
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();

    // Create HTTP client directly
    $httpClient = HttpClient::create();

    // Create crawler
    $crawler = new Crawler($httpClient);

    try {
        // Fetch roller coaster with ID 4 (Shockwave from the example)
        $data = $crawler->fetchRollerCoaster(4);

        // Display the results
        echo "Roller Coaster Information:\n";
        echo "==========================\n\n";

        // Pretty print the data
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    return 0;
};

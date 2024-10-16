<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;

\it('Test method', function ($server, $method) {
    $httpFactory = new HttpFactory();
    $sr = (new ServerRequestWizard(
        $httpFactory,
        $httpFactory,
        $httpFactory,
        $httpFactory
    ))->fromParams(serverParams: $server);

    \expect($sr->getMethod())->toBe($method);
})->with([
    'Method get' => [
        [
            'SERVER_NAME' => '127.0.0.1',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET',
            'PHP_SELF' => '/index.php',
        ],
        'GET',
    ],
    'Method post' => [
        [
            'SERVER_NAME' => '127.0.0.1',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'POST',
            'PHP_SELF' => '/index.php',
        ],
        'POST',
    ],
    'Method not set' => [
        [],
        'GET',
    ],
])
    ->covers(ServerRequestWizard::class)
;

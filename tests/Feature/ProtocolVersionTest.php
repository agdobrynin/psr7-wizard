<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;

\it('Test protocol version', function ($server, $protocol) {
    $httpFactory = new HttpFactory();
    $sr = (new ServerRequestWizard(
        $httpFactory,
        $httpFactory,
        $httpFactory,
        $httpFactory
    ))->fromParams(serverParams: $server);

    \expect($sr->getProtocolVersion())->toBe($protocol);
})
    ->with([
        'Not set protocol' => [
            [],
            '1.1',
        ],
        'Has version 1.1' => [
            [
                'DOCUMENT_ROOT' => '/home/slider/tmp',
                'REMOTE_ADDR' => '127.0.0.1',
                'REMOTE_PORT' => '40792',
                'SERVER_SOFTWARE' => 'PHP 8.2.14 Development Server',
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'SERVER_NAME' => '127.0.0.1',
                'SERVER_PORT' => '8080',
                'REQUEST_URI' => '/index.php?list=ok',
                'REQUEST_METHOD' => 'GET',
                'SCRIPT_NAME' => '/index.php',
            ],
            '1.1',
        ],
        'Has version 1.0' => [
            [
                'DOCUMENT_ROOT' => '/home/slider/tmp',
                'REMOTE_ADDR' => '127.0.0.1',
                'REMOTE_PORT' => '40792',
                'SERVER_SOFTWARE' => 'PHP 8.2.14 Development Server',
                'SERVER_PROTOCOL' => 'HTTP/1.0',
                'SERVER_NAME' => '127.0.0.1',
                'SERVER_PORT' => '8080',
                'REQUEST_URI' => '/index.php?list=ok',
                'REQUEST_METHOD' => 'GET',
                'SCRIPT_NAME' => '/index.php',
            ],
            '1.0',
        ],
    ])
    ->covers(ServerRequestWizard::class)
;

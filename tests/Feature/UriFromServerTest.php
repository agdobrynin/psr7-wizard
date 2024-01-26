<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;
use Psr\Http\Message\ServerRequestInterface;

\test('example', function (array $server, string $expectUri) {
    $httpFactory = new HttpFactory();
    $sr = (new ServerRequestWizard(
        $httpFactory,
        $httpFactory,
        $httpFactory,
        $httpFactory
    ))->fromGlobals(serverParams: $server);

    \expect($sr)->toBeInstanceOf(ServerRequestInterface::class)
        ->and((string) $sr->getUri())->toBe($expectUri)
    ;
})
    ->with('server_request', [
        'empty' => [
            'server' => [],
            'expectUri' => '',
        ],
    ])
    ->covers(ServerRequestWizard::class);

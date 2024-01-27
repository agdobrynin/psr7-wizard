<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;

\describe('Test parsed body', function () {
    \it('from POST', function () {
        $_POST = ['list' => 'ok', 0 => 'true', 'desc' => true];

        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromGlobals();

        \expect($sr->getParsedBody())->toBe($_POST);
    });

    \it('post is empty', function () {
        $_POST = [];

        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromGlobals();

        \expect($sr->getParsedBody())->toBeNull();
    });
})
    ->covers(ServerRequestWizard::class)
;

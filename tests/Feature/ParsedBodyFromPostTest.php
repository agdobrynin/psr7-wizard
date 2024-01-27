<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;

\describe('Test parsed body', function () {
    \it('from POST', function () {
        $exist = $_POST;
        $_POST = ['list' => 'ok', 0 => 'true', 'desc' => true];

        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromGlobals();

        \expect($sr->getParsedBody())->toBe($_POST);

        $_POST = $exist;
    });

    \it('post is empty', function () {
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

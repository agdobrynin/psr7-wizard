<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;

\it('Test query params', function () {
    $exist = $_GET;
    $_GET = ['list' => 'ok', 0 => 'true', 'desc' => true];

    $httpFactory = new HttpFactory();
    $sr = (new ServerRequestWizard(
        $httpFactory,
        $httpFactory,
        $httpFactory,
        $httpFactory
    ))->fromGlobals();

    \expect($sr->getQueryParams())->toBe($_GET);
    $_GET = $exist;
})
    ->covers(ServerRequestWizard::class)
;

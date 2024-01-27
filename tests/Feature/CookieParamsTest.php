<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;

\it('Test cookie params', function () {
    $_COOKIE = ['store' => 'ok', 'live' => 'no'];

    $httpFactory = new HttpFactory();
    $sr = (new ServerRequestWizard(
        $httpFactory,
        $httpFactory,
        $httpFactory,
        $httpFactory
    ))->fromGlobals();

    \expect($sr->getCookieParams())->toBe($_COOKIE);
})
    ->covers(ServerRequestWizard::class)
;

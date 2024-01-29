<?php

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;

describe('Test parsing headers', function () {
    \it('content type convert', function ($server, $expect) {
        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromParams($server);

        \expect($sr->getHeaders())->toBe($expect);
    })
        ->with([
            'content type test' => [
                'server' => [

                ],
                'expect' => []
            ]
        ]);
})
    ->covers(ServerRequestWizard::class)
;

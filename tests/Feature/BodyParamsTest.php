<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;

\describe('Test Body by params', function () {
    \it('with string ', function () {
        $httpFactory = new HttpFactory();

        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromParams(serverParams: [], body: 'Hello world 😎');

        \expect((string) $sr->getBody())->toBe('Hello world 😎');
    });

    \it('with StreamInterface', function () {
        $httpFactory = new HttpFactory();

        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromParams(serverParams: [], body: $httpFactory->createStream('🎨 Hello world'));

        \expect((string) $sr->getBody())->toBe('🎨 Hello world');
    });
})
    ->covers(ServerRequestWizard::class)
;

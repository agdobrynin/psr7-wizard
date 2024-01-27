<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\HttpMessage\Stream\PhpTempStream;
use Kaspi\Psr7Wizard\ServerRequestWizard;

\it('Test Body by params ', function (PhpTempStream|string $body, $expect) {
    $httpFactory = new HttpFactory();

    $sr = (new ServerRequestWizard(
        $httpFactory,
        $httpFactory,
        $httpFactory,
        $httpFactory
    ))->fromParams(serverParams: [], body: $body);

    \expect((string) $sr->getBody())->toBe($expect);
})
    ->with([
        'from string' => [
            'body' => static fn () => 'Hello world 😎',
            'expect' => 'Hello world 😎',
        ],
        'from Stream' => [
            'body' => static function () {
                $s = new PhpTempStream();
                $s->write('😋!');

                return $s;
            },
            'expect' => '😋!',
        ],
    ])
    ->covers(ServerRequestWizard::class)
;

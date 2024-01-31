<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;
use Psr\Http\Message\ServerRequestInterface;

\describe('Creating URI from server headers', function () {
    \test('header', function (array $server, string $expectUri) {
        $exist = $_SERVER;
        $_SERVER = $server;

        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromGlobals();

        \expect($sr)->toBeInstanceOf(ServerRequestInterface::class)
            ->and((string) $sr->getUri())->toBe($expectUri)
        ;

        $_SERVER = $exist;
    })
        ->with([
            'empty' => [
                'server' => [],
                'expectUri' => '',
            ],
            'with QUERY_STRING different REQUEST_URI' => [
                'server' => [
                    'SERVER_NAME' => '127.0.0.1',
                    'SERVER_PORT' => '8080',
                    'REQUEST_URI' => '/index.php?list=ok',
                    'REQUEST_METHOD' => 'GET',
                    'PHP_SELF' => '/index.php',
                    'QUERY_STRING' => 'sort=false',
                    'HTTP_HOST' => 'hello.st:8080',
                ],
                'expect' => 'http://hello.st:8080/index.php?list=ok',
            ],
            'has HTTP_X_FORWARDED_PROTO' => [
                'server' => [
                    'HTTP_X_FORWARDED_PROTO' => 'https',
                    'SERVER_NAME' => '127.0.0.1',
                    'SERVER_PORT' => '8080',
                    'REQUEST_URI' => '/',
                    'REQUEST_METHOD' => 'GET',
                    'PHP_SELF' => '/index.php',
                    'QUERY_STRING' => 'list=ok',
                ],
                'expect' => 'https://127.0.0.1:8080/',
            ],
            'has HTTPS' => [
                'server' => [
                    'SERVER_NAME' => '127.0.0.1',
                    'SERVER_PORT' => '8080',
                    'REQUEST_URI' => '/',
                    'REQUEST_METHOD' => 'GET',
                    'PHP_SELF' => '/index.php',
                    'QUERY_STRING' => 'list=ok',
                    'HTTPS' => 'on',
                ],
                'expect' => 'https://127.0.0.1:8080/',
            ],
            'has HTTPS off' => [
                'server' => [
                    'SERVER_NAME' => '127.0.0.1',
                    'SERVER_PORT' => '8080',
                    'REQUEST_URI' => '/',
                    'REQUEST_METHOD' => 'GET',
                    'PHP_SELF' => '/index.php',
                    'QUERY_STRING' => 'list=ok',
                    'HTTPS' => '1',
                ],
                'expect' => 'https://127.0.0.1:8080/',
            ],
            'not has HTTP_HOST' => [
                'server' => [
                    'SERVER_NAME' => '127.0.0.1',
                    'SERVER_PORT' => '8080',
                    'REQUEST_URI' => '/index.php?list=ok',
                    'REQUEST_METHOD' => 'GET',
                    'PHP_SELF' => '/index.php',
                    'QUERY_STRING' => 'list=ok',
                ],
                'expect' => 'http://127.0.0.1:8080/index.php?list=ok',
            ],
            'without HTTP_HOST and SERVER_NAME' => [
                'server' => [
                    'SERVER_PORT' => '8080',
                    'REQUEST_URI' => '/index.php?list=ok',
                    'REQUEST_METHOD' => 'GET',
                    'PHP_SELF' => '/index.php',
                    'QUERY_STRING' => 'list=ok',
                ],
                'expect' => '',
            ],
            'without HTTP_HOST header but has non standard port SERVER_PORT' => [
                'server' => [
                    'SERVER_NAME' => '[::1]',
                    'SERVER_PORT' => '8080',
                    'REQUEST_URI' => '/index.php?list=ok',
                    'REQUEST_METHOD' => 'GET',
                    'PHP_SELF' => '/index.php',
                    'QUERY_STRING' => 'list=ok',
                ],
                'expect' => 'http://[::1]:8080/index.php?list=ok',
            ],
            'without REQUEST_URI' => [
                'server' => [
                    'SERVER_NAME' => '127.0.0.1',
                    'SERVER_PORT' => '8080',
                    'REQUEST_METHOD' => 'GET',
                    'ORIG_PATH_INFO' => '/index.php',
                    'QUERY_STRING' => 'list=ok',
                    'HTTP_HOST' => 'hello.st:8080',
                ],
                'expect' => 'http://hello.st:8080/index.php?list=ok',
            ],
            'without REQUEST_URI and QUERY_STRING is "0"' => [
                'server' => [
                    'SERVER_NAME' => '127.0.0.1',
                    'SERVER_PORT' => '80',
                    'REQUEST_METHOD' => 'GET',
                    'ORIG_PATH_INFO' => '/index.php',
                    'QUERY_STRING' => '0',
                ],
                'expect' => 'http://127.0.0.1/index.php?0',
            ],
            'without REQUEST_URI and ORIG_PATH_INFO but has QUERY_STRING' => [
                'server' => [
                    'SERVER_NAME' => '127.0.0.1',
                    'SERVER_PORT' => '80',
                    'REQUEST_METHOD' => 'GET',
                    'QUERY_STRING' => 'list=ok',
                ],
                'expect' => 'http://127.0.0.1/?list=ok',
            ],
        ])
    ;
})

    ->covers(ServerRequestWizard::class)
;

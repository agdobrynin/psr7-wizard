<?php

declare(strict_types=1);

use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;

\describe('Test parsing headers', function () {
    $simpleServerParams = [
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
        'SCRIPT_FILENAME' => '/home/slider/tmp/index.php',
        'PHP_SELF' => '/index.php',
        'QUERY_STRING' => 'list=ok',
        'HTTP_HOST' => 'hello.st:8080',
        'HTTP_CONNECTION' => 'keep-alive',
        'HTTP_DNT' => '1',
        'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
        'HTTP_ACCEPT_LANGUAGE' => 'ru,ru-RU;q=0.9,en-US;q=0.8,en;q=0.7,de;q=0.6,it;q=0.5',
        'REQUEST_TIME_FLOAT' => 1706209958.31321,
        'REQUEST_TIME' => 1706209958,
        'HTTP_ORIGIN' => 'http://127.0.0.1:8080',
    ];

    \it('content type convert', function ($server, $expect) {
        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromParams($server);

        \expect(\array_intersect_key($sr->getHeaders(), $expect))
            ->toBe($expect)
        ;
    })
        ->with([
            'content type test' => [
                'server' => \array_merge(
                    $simpleServerParams,
                    [
                        'CONTENT_LENGTH' => '363715',
                        'HTTP_CONTENT_LENGTH' => '363715',
                        'CONTENT_TYPE' => 'multipart/form-data; boundary=----WebKitFormBoundaryIRB3O6SZxfL5m4Lt',
                        'HTTP_CONTENT_TYPE' => 'multipart/form-data; boundary=----WebKitFormBoundaryIRB3O6SZxfL5m4Lt',
                    ]
                ),
                'expect' => [
                    'Content-Length' => ['363715'],
                    'Content-Type' => ['multipart/form-data; boundary=----WebKitFormBoundaryIRB3O6SZxfL5m4Lt'],
                ],
            ],
            'headers with numeric' => [
                'server' => \array_merge(
                    $simpleServerParams,
                    [
                        'HTTP_0' => 'zero header',
                        'HTTP_1234' => 'numeric header',
                    ]
                ),
                'expect' => [
                    '0' => ['zero header'],
                    '1234' => ['numeric header'],
                ],
            ],
            'authorization header' => [
                'server' => \array_merge(
                    $simpleServerParams,
                    ['REDIRECT_HTTP_AUTHORIZATION' => 'auth-token']
                ),
                'expect' => ['Authorization' => ['auth-token']],
            ],
            'authorization header php_auth_digest' => [
                'server' => \array_merge(
                    $simpleServerParams,
                    ['PHP_AUTH_DIGEST' => 'value']
                ),
                'expect' => ['Authorization' => ['value']],
            ],
            'authorization php_auth_user' => [
                'server' => \array_merge(
                    $simpleServerParams,
                    ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'pass']
                ),
                'expect' => ['Authorization' => ['Basic '.\base64_encode('admin:pass')]],
            ],
        ])
    ;
})
    ->covers(ServerRequestWizard::class)
;

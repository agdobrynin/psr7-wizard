<?php

declare(strict_types=1);

namespace Tests\Kaspi\Psr7Wizard;

use Generator;
use Kaspi\HttpMessage\HttpFactory;
use Kaspi\Psr7Wizard\ServerRequestWizard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_intersect_key;
use function array_merge;
use function base64_encode;

/**
 * @internal
 */
#[CoversClass(ServerRequestWizard::class)]
class HeadersTest extends TestCase
{
    #[DataProvider('parsingHeadersContentTypeDataProvider')]
    public function testParsingHeadersContentTypeConvert($srvArgs, $expect): void
    {
        $httpFactory = new HttpFactory();
        $sr = (new ServerRequestWizard(
            $httpFactory,
            $httpFactory,
            $httpFactory,
            $httpFactory
        ))->fromParams($srvArgs);

        self::assertEquals($expect, array_intersect_key($sr->getHeaders(), $expect));
    }

    public static function parsingHeadersContentTypeDataProvider(): Generator
    {
        yield 'content type test' => [
            array_merge(
                [...Dataset::simple_server_params()],
                [
                    'CONTENT_LENGTH' => '363715',
                    'HTTP_CONTENT_LENGTH' => '363715',
                    'CONTENT_TYPE' => 'multipart/form-data; boundary=----WebKitFormBoundaryIRB3O6SZxfL5m4Lt',
                    'HTTP_CONTENT_TYPE' => 'multipart/form-data; boundary=----WebKitFormBoundaryIRB3O6SZxfL5m4Lt',
                ]
            ),
            [
                'Content-Length' => ['363715'],
                'Content-Type' => ['multipart/form-data; boundary=----WebKitFormBoundaryIRB3O6SZxfL5m4Lt'],
            ],
        ];

        yield 'headers with numeric' => [
            array_merge(
                [...Dataset::simple_server_params()],
                [
                    'HTTP_0' => 'zero header',
                    'HTTP_1234' => 'numeric header',
                ]
            ),
            [
                '0' => ['zero header'],
                '1234' => ['numeric header'],
            ],
        ];

        yield 'authorization header' => [
            array_merge(
                [...Dataset::simple_server_params()],
                ['REDIRECT_HTTP_AUTHORIZATION' => 'auth-token']
            ),
            ['Authorization' => ['auth-token']],
        ];

        yield 'authorization header php_auth_digest' => [
            array_merge(
                [...Dataset::simple_server_params()],
                ['PHP_AUTH_DIGEST' => 'value']
            ),
            ['Authorization' => ['value']],
        ];

        yield 'authorization php_auth_user' => [
            array_merge(
                [...Dataset::simple_server_params()],
                ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'pass']
            ),
            ['Authorization' => ['Basic '.base64_encode('admin:pass')]],
        ];
    }
}

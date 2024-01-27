<?php

declare(strict_types=1);

namespace Kaspi\Psr7Wizard;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

use function function_exists;
use function getallheaders;
use function in_array;
use function preg_match;
use function str_starts_with;
use function strtolower;
use function strtr;
use function substr;
use function ucwords;

class ServerRequestWizard
{
    public function __construct(
        private readonly ServerRequestFactoryInterface $serverRequestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UploadedFileFactoryInterface $uploadedFileFactory,
        private readonly UriFactoryInterface $uriFactory,
    ) {}

    public function fromGlobals(
        array $serverParams = null,
        array $queryParams = null,
        array $cookieParams = null,
        array $uploadedFiles = null,
        array $parsedBody = null
    ): ServerRequestInterface {
        $serverParams ??= $_SERVER;
        $queryParams ??= $_GET;
        $cookieParams ??= $_COOKIE;
        $uploadedFiles ??= $_FILES;
        $parsedBody ??= $_POST;

        $httpProtocol = isset($serverParams['SERVER_PROTOCOL']) ? strtr($serverParams['SERVER_PROTOCOL'], 'HTTP/', '') : '1.1';
        $headers = function_exists('getallheaders') ? getallheaders() : static::getHttpHeaders($serverParams);

        $serverRequest = $this->serverRequestFactory
            ->createServerRequest(
                method: $serverParams['REQUEST_METHOD'] ?? 'GET',
                uri: $this->createUriFromServer($serverParams),
                serverParams: $serverParams
            )->withProtocolVersion($httpProtocol)
            ->withQueryParams($queryParams)
            ->withCookieParams($cookieParams)
        ;

        foreach ($headers as $header => $value) {
            $serverRequest = $serverRequest->withAddedHeader($header, $value);
        }

        return $serverRequest;
    }

    public static function getHttpHeaders(array $serverParams): array
    {
        $headers = [];

        $otherHeader = [
            'CONTENT_TYPE' => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5' => 'Content-Md5',
        ];

        foreach ($serverParams as $headerOrig => $value) {
            if (str_starts_with($headerOrig, 'HTTP_')) {
                $header = substr($headerOrig, 5);
                $normalizedHeader = strtr(ucwords(strtr(strtolower($header), '_', ' ')), ' ', '-');

                if (!isset($headers[$normalizedHeader])) {
                    $headers[$normalizedHeader] = $value;
                }
            } elseif (isset($otherHeader[$headerOrig])) {
                $headers[$otherHeader[$headerOrig]] = $value;
            }
        }

        return $headers;
    }

    private function createUriFromServer(array $serverParams): UriInterface
    {
        $scheme = $serverParams['HTTP_X_FORWARDED_PROTO']
            ?? $serverParams['REQUEST_SCHEME']
            ?? in_array($serverParams['HTTPS'] ?? null, ['on', '1'], true)
            ? 'https'
            : 'http';

        $host = $serverParams['HTTP_HOST']
            ?? $serverParams['SERVER_NAME']
            ?? '';

        if ('' === $host) {
            return $this->uriFactory->createUri();
        }

        if ('' !== ($port = $serverParams['SERVER_PORT'] ?? '')
            && 1 !== preg_match('/:(\d+)$/', $host)) {
            $host .= ':'.$port;
        }

        if ('' !== ($requestUriWithQuery = $serverParams['REQUEST_URI'] ?? '')) {
            $requestUri = $requestUriWithQuery;
        } elseif ('' !== ($phpSelf = $serverParams['PHP_SELF'] ?? '')) {
            $requestUri = $phpSelf
                .('' !== ($query = $serverParams['QUERY_STRING'] ?? '')
                    ? '?'.$query
                    : '');
        }

        $uriString = $scheme.'://'.$host.($requestUri ?? '');

        return $this->uriFactory->createUri($uriString);
    }
}

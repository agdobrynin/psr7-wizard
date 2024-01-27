<?php

declare(strict_types=1);

namespace Kaspi\Psr7Wizard;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

use function apache_request_headers;
use function fopen;
use function function_exists;
use function in_array;
use function preg_match;
use function str_replace;
use function str_starts_with;
use function strtolower;
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

    public function fromGlobals(): ServerRequestInterface
    {
        return $this->fromParams($_SERVER, $_GET, $_COOKIE, $_FILES, $_POST);
    }

    public function fromParams(
        array $serverParams,
        array $queryParams = [],
        array $cookieParams = [],
        array $files = [],
        array $parsedBody = [],
    ): ServerRequestInterface {
        $requestMethod = $serverParams['REQUEST_METHOD'] ?? 'GET';
        $httpProtocol = 1 === preg_match('/(\d\.\d)$/', $serverParams['SERVER_PROTOCOL'] ?? '', $matches)
            ? $matches[0]
            : '1.1';
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : static::getHttpHeaders($_SERVER);

        $serverRequest = $this->serverRequestFactory
            ->createServerRequest(
                method: $requestMethod,
                uri: $this->createUriFromServer($serverParams),
                serverParams: $serverParams
            )->withProtocolVersion($httpProtocol)
            ->withQueryParams($queryParams)
            ->withCookieParams($cookieParams)
            ->withParsedBody(!empty($parsedBody) ? $parsedBody : null)
            ->withUploadedFiles($this->prepareUploadedFiles($files))
        ;

        foreach ($headers as $header => $value) {
            $serverRequest = $serverRequest->withAddedHeader($header, $value);
        }

        if (false !== ($resource = @fopen('php://input', 'rb'))) {
            $serverRequest = $serverRequest->withBody(
                $this->streamFactory->createStreamFromResource($resource)
            );
        }

        return $serverRequest;
    }

    public static function getHttpHeaders(array $serverParams): array
    {
        $headers = [];

        $otherHeader = [
            'CONTENT_TYPE' => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5' => 'Content-MD5',
        ];

        foreach ($serverParams as $headerOrig => $value) {
            if (str_starts_with($headerOrig, 'HTTP_')) {
                $header = substr($headerOrig, 5);
                $normalizedHeader = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($header))));

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

    private function prepareUploadedFiles(array $files): array
    {
        return [];
    }
}

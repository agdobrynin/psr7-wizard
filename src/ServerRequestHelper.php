<?php

declare(strict_types=1);

namespace Kaspi\Psr7Globals;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class ServerRequestHelper
{
    public function __construct(
        private readonly ServerRequestFactoryInterface $serverRequestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UploadedFileFactoryInterface $uploadedFileFactory,
        private readonly UriFactoryInterface $uriFactory,
    ) {}

    public function fromGlobals(
        ?array $serverParams = null,
        ?array $queryParams = null,
        ?array $cookieParams = null,
        ?array $uploadedFiles = null,
        ?array $parsedBody = null
    ): ServerRequestInterface {
        $serverParams ??= $_SERVER;
        $queryParams ??= $_GET;
        $cookieParams ??= $_COOKIE;
        $uploadedFiles ??= $_FILES;
        $parsedBody ??= $_POST;

        $scheme = ('off' !== $serverParams['HTTPS'] ?? null) ? 'https' : 'http';
        $host = $serverParams['SERVER_NAME'] ?? 'localhost';

        if (($port = $serverParams['SERVER_PORT']) !== '') {
            $host.=':'.$port;
        }

        $requestUri = $serverParams['REQUEST_URI'] ?? $serverParams['PHP_SELF'] ?? '';

        if ('' !== $requestUri &&  '' !== ($serverParams['QUERY_STRING'] ?? '')) {
            $requestUri.= '?'.$serverParams['QUERY_STRING'];
        }

        $uriString = $scheme.'://'.$host.($requestUri !== null ? $requestUri : '/');

        return $this->serverRequestFactory
            ->createServerRequest(
                method: $serverParams['REQUEST_METHOD'] ?? 'GET',
                uri: $this->uriFactory->createUri($uriString),
                serverParams: $serverParams
            )
            ;
    }
}

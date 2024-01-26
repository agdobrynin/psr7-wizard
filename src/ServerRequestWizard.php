<?php

declare(strict_types=1);

namespace Kaspi\Psr7Wizard;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

use function preg_match;

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

        return $this->serverRequestFactory
            ->createServerRequest(
                method: $serverParams['REQUEST_METHOD'] ?? 'GET',
                uri: $this->createUriFromServer($serverParams),
                serverParams: $serverParams
            )
        ;
    }

    private function createUriFromServer(array $serverParams): UriInterface
    {
        $scheme = $serverParams['HTTP_X_FORWARDED_PROTO']
            ?? $serverParams['REQUEST_SCHEME']
            ?? \in_array(($serverParams['HTTPS'] ?? null), ['on', '1'], true)
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

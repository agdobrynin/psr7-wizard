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

    public function fromGlobals(): ServerRequestInterface
    {
        $method = 'GET';
        $uri = 'https://www.example.com/index.php?query=1#frag0';
        $serverParams = $_SERVER ?? [];

        return $this->serverRequestFactory->createServerRequest(
            $method,
            $uri,
            $serverParams,
        );
    }
}

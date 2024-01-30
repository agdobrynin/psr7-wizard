<?php

declare(strict_types=1);

namespace Kaspi\Psr7Wizard;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

interface ServerRequestWizardInterface
{
    public function fromGlobals(): ServerRequestInterface;

    /**
     * @param array                       $serverParams retrieve from $_SERVER or similar structure from web server
     * @param array                       $queryParams  retrieve from $_GET or similar structure related to <QUERY_STRING> from web server
     * @param array                       $cookieParams retrieve from $_COOKIE or similar structure from browser cookies
     * @param array                       $files        retrieve from $_FILES or similar structure
     * @param array                       $parsedBody   retrieve from $_POST or similar structure, parsed request body
     * @param null|StreamInterface|string $body         retrieve from <php://input> or similar input, raw data from a client
     */
    public function fromParams(
        array $serverParams,
        array $queryParams = [],
        array $cookieParams = [],
        array $files = [],
        array $parsedBody = [],
        StreamInterface|string $body = null,
    ): ServerRequestInterface;
}

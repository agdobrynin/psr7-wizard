<?php

declare(strict_types=1);

namespace Kaspi\Psr7Wizard;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

use function apache_request_headers;
use function base64_encode;
use function fopen;
use function function_exists;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function preg_match;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function substr;
use function ucwords;

final class ServerRequestWizard implements ServerRequestWizardInterface
{
    public function __construct(
        private readonly ServerRequestFactoryInterface $serverRequestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UploadedFileFactoryInterface $uploadedFileFactory,
        private readonly UriFactoryInterface $uriFactory,
    ) {}

    public function fromGlobals(): ServerRequestInterface
    {
        $body = (false !== ($resource = @fopen('php://input', 'rb')))
            ? $this->streamFactory->createStreamFromResource($resource)
         // @codeCoverageIgnoreStart
            : null;
        // @codeCoverageIgnoreEnd

        return $this->fromParams(
            $_SERVER,
            $_GET,
            $_COOKIE,
            $_FILES,
            $_POST,
            $body
        );
    }

    public function fromParams(
        array $serverParams,
        array $queryParams = [],
        array $cookieParams = [],
        array $files = [],
        array $parsedBody = [],
        StreamInterface|string|null $body = null,
    ): ServerRequestInterface {
        $requestMethod = $serverParams['REQUEST_METHOD'] ?? 'GET';
        $httpProtocol = 1 === preg_match('/(\d\.\d)$/', $serverParams['SERVER_PROTOCOL'] ?? '', $matches)
            ? $matches[0]
            : '1.1';
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : self::getHttpHeaders($serverParams);

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
            $serverRequest = $serverRequest->withAddedHeader(
                is_int($header) ? (string) $header : $header,
                $value
            );
        }

        if (null === $body) {
            return $serverRequest;
        }

        return $serverRequest->withBody(
            is_string($body)
                ? $this->streamFactory->createStream($body)
                : $body
        );
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

        // Authorization
        if (!isset($headers['Authorization'])) {
            if (null !== ($authorization = $serverParams['REDIRECT_HTTP_AUTHORIZATION'] ?? null)) {
                $headers['Authorization'] = $authorization;
            } elseif (null !== ($user = $serverParams['PHP_AUTH_USER'] ?? null)) {
                $headers['Authorization'] = 'Basic '.base64_encode($user.':'.$serverParams['PHP_AUTH_PW'] ?? '');
            } elseif (null !== ($authorization = $serverParams['PHP_AUTH_DIGEST'] ?? null)) {
                $headers['Authorization'] = $authorization;
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
        } elseif ('' !== ($originPath = $serverParams['ORIG_PATH_INFO'] ?? '')) {
            $requestUri = $originPath
                .('' !== ($query = $serverParams['QUERY_STRING'] ?? '')
                    ? '?'.$query
                    : '');
        } else {
            $requestUri = '/'.('' !== ($query = $serverParams['QUERY_STRING'] ?? '')
                    ? '?'.$query
                    : '');
        }

        $uriString = $scheme.'://'.$host.$requestUri;

        return $this->uriFactory->createUri($uriString);
    }

    private function prepareUploadedFiles(array $files): array
    {
        if ([] === $files) {
            return [];
        }

        $createUploadedFileItem = function (array $fileItem): UploadedFileInterface {
            if (UPLOAD_ERR_OK !== $fileItem['error']) {
                $stream = $this->streamFactory->createStream();
            } else {
                try {
                    $stream = $this->streamFactory->createStreamFromFile($fileItem['tmp_name']);
                } catch (RuntimeException) {
                    $stream = $this->streamFactory->createStream();
                }
            }

            return $this->uploadedFileFactory->createUploadedFile(
                $stream,
                $fileItem['size'] ?? null,
                $fileItem['error'],
                $fileItem['name'] ?? null,
                $fileItem['type'] ?? null
            );
        };

        $rebuildTree = static function (
            array $tmpNamesTree,
            array $errorsTree,
            ?array $namesTree,
            ?array $sizesTree,
            ?array $typesTree,
        ) use (&$rebuildTree, $createUploadedFileItem) {
            $rebuild = [];

            foreach ($tmpNamesTree as $key => $value) {
                if (!isset($errorsTree[$key])) {
                    throw new InvalidArgumentException(
                        "Uploaded file \"{$value}\" must be provide \"error\" key"
                    );
                }

                if (is_string($value)) {
                    $rebuild[$key] = $createUploadedFileItem(
                        [
                            'tmp_name' => $value,
                            'error' => $errorsTree[$key],
                            'name' => $namesTree[$key] ?? null,
                            'size' => $sizesTree[$key] ?? null,
                            'type' => $typesTree[$key] ?? null,
                        ]
                    );
                } elseif (is_array($value)) {
                    $rebuild[$key] = $rebuildTree(
                        $value,
                        $errorsTree[$key],
                        $namesTree[$key] ?? null,
                        $sizesTree[$key] ?? null,
                        $typesTree[$key] ?? null,
                    );
                }
            }

            return $rebuild;
        };

        $uploadedFiles = [];

        foreach ($files as $key => $value) {
            if (!($value instanceof UploadedFileInterface)
                && !isset($value['tmp_name'], $value['error'])) {
                throw new InvalidArgumentException(
                    __FUNCTION__.' : Items in parameter $files must be provide keys "tmp_name", "error" in "'.$key.'" field '
                    .'or item must be '.UploadedFileInterface::class
                );
            }

            if ($value instanceof UploadedFileInterface) {
                $uploadedFiles[$key] = $value;
            } elseif (is_array($value) && is_array($value['tmp_name'])) {
                $uploadedFiles[$key] = $rebuildTree(
                    $value['tmp_name'],
                    $value['error'],
                    $value['name'] ?? null,
                    $value['size'] ?? null,
                    $value['type'] ?? null
                );
            } elseif (is_string($value['tmp_name'])) {
                $uploadedFiles[$key] = $createUploadedFileItem($value);
            } else {
                throw new InvalidArgumentException('Wrong structure for uploaded files');
            }
        }

        return $uploadedFiles;
    }
}

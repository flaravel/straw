<?php

namespace Straw\Core\Http\Factory;

use Psr\Http\Message\UriInterface;
use Straw\Core\Http\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

class ServerRequestFactory implements ServerRequestFactoryInterface
{

    /**
     * Create a new server request.
     *
     * Note that server-params are taken precisely as given - no parsing/processing
     * of the given values is performed, and, in particular, no attempt is made to
     * determine the HTTP method or URI, which must be provided explicitly.
     *
     * @param string $method The HTTP method associated with the request.
     * @param UriInterface|string $uri The URI associated with the request. If
     *     the value is a string, the factory MUST create a UriInterface
     *     instance based on it.
     * @param array $serverParams Array of SAPI parameters with which to seed
     *     the generated request instance.
     *
     * @return ServerRequestInterface
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (is_string($uri)) {
            $uri = (new UriFactory())->createUri($uri);
        }
        return new ServerRequest($method, $uri, $serverParams);
    }


    /**
     * @return ServerRequestInterface
     */
    public function createServerRequestFormBase(): ServerRequestInterface
    {

        [$scheme, $protocolVersion] = explode('/', $_SERVER['SERVER_PROTOCOL']);

        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $scheme = strtolower($scheme);
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $requestsUri = $_SERVER['REQUEST_URI'] ?? '';
        $queryString = $_SERVER['QUERY_STRING'] ?? '';

        $uri = $scheme . '://' . $host . $requestsUri . ($queryString ? "?{$queryString}" : '');
        $serverParams = $_SERVER;

        $serverRequest = $this->createServerRequest($method, $uri, $serverParams);

        $serverRequest->withProtocolVersion($protocolVersion);
        $serverRequest->withRequestTarget($uri);

        $headers = array_change_key_case(getallheaders(), CASE_LOWER);

        foreach ($headers as $name => $value) {
            $serverRequest->withHeader($name, $value);
        }

        $body = (new StreamFactory())->createStream();
        $serverRequest->withBody($body);

        $serverRequest->withCookieParams($_COOKIE);
        $serverRequest->withParsedBody($_POST);

        return $serverRequest;
    }
}
<?php

namespace Straw\Core\Http\Factory;

use RuntimeException;
use Straw\Core\Http\Uri;
use Straw\Core\Http\Stream;
use Straw\Core\Http\Request;
use InvalidArgumentException;
use Straw\Core\Http\UploadedFile;
use Psr\Http\Message\UriInterface;
use Straw\Core\Http\ServerRequest;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

class Psr17Factory implements
    RequestFactoryInterface,
    ResponseFactoryInterface,
    ServerRequestFactoryInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
    UriFactoryInterface
{

    /**
     * 创建一个新的请求
     *
     * @param string $method 请求使用的 HTTP 方法。
     * @param UriInterface|string $uri 请求关联的 URI。
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }

    /**
     * 创建一个服务端请求。
     *
     * 注意服务器参数要精确的按给定的方式获取 - 不执行给定值的解析或处理。
     * 尤其是不要从中尝试获取 HTTP 方法或 URI，这两个信息一定要通过函数参数明确给出。
     *
     * @param string $method 与请求关联的 HTTP 方法。
     * @param UriInterface|string $uri 与请求关联的 URI。
     * @param array $serverParams 用来生成请求实例的 SAPI 参数。
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, $serverParams);
    }

    /**
     * 创建一个响应对象。
     *
     * @param int $code HTTP 状态码，默认值为 200。
     * @param string $reasonPhrase 与状态码关联的原因短语。如果未提供，实现 **可能** 使用 HTTP 规范中建议的值。
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        // TODO: Implement createResponse() method.
    }


    /**
     * 创建 Server Request
     *
     * @return ServerRequestInterface
     */
    public function createServerRequestFromGlobals():ServerRequestInterface
    {

        // 获取请求方式
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // 获取系统Server数据
        $serverParams = $_SERVER;

        // 创建Uri对象
        $uri = $this->createUriFromArray($serverParams);

        // 创建Server Request
        $serverRequest = $this->createServerRequest($method, $uri, $serverParams);

        // 获取headers头信息
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);

        // 设置信息头
        foreach ($headers as $name => $value) {
            $serverRequest->withHeader($name, $value);
        }

        $protocol = isset($server['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $server['SERVER_PROTOCOL']) : '1.1';

        // 设置http版本号, $_COOKIE,$_GET,$_POST,$_FILES php://input
        $serverRequest = $serverRequest
            ->withProtocolVersion($protocol)
            ->withRequestTarget((string)$uri)
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles($this->getUploadedFiles($_FILES));

        $body = fopen('php://input', 'r');

        if ($body && is_resource($body)) {
            $serverRequest = $serverRequest->withBody($this->createStreamFromResource($body));
        }
        return $serverRequest;
    }

    /**
     * 创建Uri
     *
     * @param array $server
     *
     * @return UriInterface
     */
    private function createUriFromArray(array $server): UriInterface
    {
        $uri = $this->createUri();
        [$scheme] = explode('/', $server['SERVER_PROTOCOL']);                  // 获取 scheme
        return $uri->withScheme(strtolower($scheme))->withPort($server['SERVER_PORT'])  // 获取请求端口
                ->withHost($server['SERVER_NAME'])                                      // 获取url host 域名/ip
                ->withPath(current(explode('?', $server['REQUEST_URI'])))      // 获取url path
                ->withQuery($server['QUERY_STRING'] ?? '');                       // 获取url query
    }

    /**
     * 根据请求的文件资源转换为 uploadFile 对象
     *
     * @param array $files
     *
     * @return array
     */
    private function getUploadedFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $value) {
            if (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = $this->createUploadedFileFromSpec($value);
            } else {
                throw new InvalidArgumentException('文件规范中的值无效');
            }
        }
        return $normalized;
    }

    /**
     * @param array $value
     *
     * @return array|UploadedFileInterface
     */
    public function createUploadedFileFromSpec(array $value): array|UploadedFileInterface
    {
        // 如果tmp_name是数组格式，那么客户端传的文件是数组 file[]
        // 如果tmp_name不是数组，那么客户端传的文件是单文件 file
        if (is_array($value['tmp_name'])) {
            return $this->normalizeNestedFileSpec($value);
        }

        if (UPLOAD_ERR_OK !== $value['error']) {
            $stream = $this->createStream();
        } else {
            try {
                $stream = $this->createStreamFromFile($value['tmp_name']);
            } catch (RuntimeException) {
                $stream = $this->createStream();
            }
        }
        return $this->createUploadedFile(
            $stream,
            (int) $value['size'],
            (int) $value['error'],
            $value['name'],
            $value['type']
        );
    }

    /**
     * 处理数组上传的文件
     *
     * @param array $files
     *
     * @return array
     */
    private function normalizeNestedFileSpec(array $files = []): array
    {
        $normalizedFiles = [];

        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
            ];
            $normalizedFiles[$key] = $this->createUploadedFileFromSpec($spec);
        }

        return $normalizedFiles;
    }

    /**
     * 从字符串创建一个流。
     *
     * 流 **应该** 使用临时资源来创建。
     *
     * @param string $content 用于填充流的字符串内容。
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return Stream::create();
    }

    /**
     * 通过现有资源创建一个流。
     *
     * 流必须是可读的并且可能是可写的。
     *
     * @param resource $resource 用作流的基础的 PHP 资源。
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return Stream::create($resource);
    }

    /**
     * 通过现有文件创建一个流。
     *
     * 文件 **必须** 用给定的模式打开文件，该模式可以是 `fopen` 函数支持的任意模式。
     *
     * `$filename` **可能** 是任意被 `fopen()` 函数支持的字符串。
     *
     * @param string $filename 用作流基础的文件名或 URI。
     * @param string $mode 用于打开基础文件名或流的模式。
     *
     * @throws RuntimeException 如果文件无法被打开时抛出。
     * @throws InvalidArgumentException 如果模式无效会被抛出。
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (empty($filename)) {
            throw new RuntimeException('文件路径不能为空');
        }
        if (false === $resource = @fopen($filename, $mode)) {
            throw new RuntimeException(sprintf('createStreamFromFile 无法打开文件: %s', $filename));
        }
        return Stream::create($resource);
    }

    /**
     * 创建一个上传文件接口的对象。
     *
     * 如果未提供大小，将通过检查流的大小来确定。
     *
     * @link http://php.net/manual/features.file-upload.post-method.php
     * @link http://php.net/manual/features.file-upload.errors.php
     *
     * @param StreamInterface $stream 表示上传文件内容的流。
     * @param int|null $size 文件的大小，以字节为单位。
     * @param int $error PHP 上传文件的错误码。
     * @param string|null $clientFilename 如果存在，客户端提供的文件名。
     * @param string|null $clientMediaType 如果存在，客户端提供的媒体类型。
     *
     * @return UploadedFileInterface
     */
    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface {

        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }


    /**
     * 创建一个 URI。
     *
     * @param string $uri 要解析的 URI。
     *
     * @throws InvalidArgumentException 如果给定的 URI 无法被解析时抛出。
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}

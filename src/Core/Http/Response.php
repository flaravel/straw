<?php

namespace Straw\Core\Http;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

/**
 * 表示服务器返回的响应消息。
 *
 * 根据 HTTP 规范，此接口包含以下各项的属性：
 *
 * - 协议版本
 * - 状态码和原因短语
 * - 报头
 * - 消息体
 *
 * HTTP 响应是被视为无法修改的，所有能修改状态的方法，都 **必须** 有一套机制，在内部保
 * 持好原有的内容，然后把修改状态后的，新的 HTTP 响应实例返回。
 */
class Response extends Message implements ResponseInterface
{

    /**
     * 标准HTTP状态代码/原因短语目录（laravel中的Response）
     *
     * @var array|string[]
     */
    public static array $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large',                                           // RFC-ietf-httpbis-semantics
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        421 => 'Misdirected Request',                                         // RFC7540
        422 => 'Unprocessable Content',                                       // RFC-ietf-httpbis-semantics
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Too Early',                                                   // RFC-ietf-httpbis-replay-04
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        451 => 'Unavailable For Legal Reasons',                               // RFC7725
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',                                     // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    ];

    /**
     * @var string
     */
    private string $reasonPhrase = '';

    /**
     * @var int
     */
    private int $statusCode;


    public function __construct(
        int $status = 200,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        string $reason = null
    ) {
        if ($body !== null && $body !== '') {
            $this->body = Stream::create($body);
        }

        $this->statusCode = $status;

        $this->setHeaders($headers);

        if (null === $reason && isset(self::$statusTexts[$this->statusCode])) {
            $this->reasonPhrase = self::$statusTexts[$status];
        } else {
            $this->reasonPhrase = $reason ?? '';
        }
        $this->protocolVersion = $version;
    }

    /**
     * 获取响应状态码。
     *
     * 状态码是一个三位整数，用于理解请求。
     *
     * @return int 状态码。
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 返回具有指定状态码和原因短语（可选）的实例。
     *
     * 如果未指定原因短语，实现代码 **可能** 选择 RFC7231 或 IANA 为状态码推荐的原因短语。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 消息实例，然后返回
     * 一个新的修改过的 HTTP 消息实例。
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code 三位整数的状态码。
     * @param string $reasonPhrase 为状态码提供的原因短语；如果未提供，实现代码可以使用 HTTP 规范建议的默认代码。
     * @return self
     * @throws InvalidArgumentException 如果传入无效的状态码，则抛出。
     */
    public function withStatus($code, $reasonPhrase = ''): Response
    {
        if (!is_int($code) && !is_string($code)) {
            throw new InvalidArgumentException('状态代码必须是整数');
        }
        $code = (int) $code;
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException(\sprintf('状态代码必须是100到599之间的整数。状态代码为 %d', $code));
        }

        $new = clone $this;
        $new->statusCode = $code;
        if (($reasonPhrase == '' || $reasonPhrase == null) && isset(self::$statusTexts[$new->statusCode])) {
            $reasonPhrase = self::$statusTexts[$new->statusCode];
        }
        $new->reasonPhrase = $reasonPhrase;

        return $new;
    }

    /**
     * 获取与响应状态码关联的响应原因短语。
     *
     * 因为原因短语不是响应状态行中的必需元素，所以原因短语 **可能** 是空。
     * 实现代码可以选择返回响应的状态代码的默认 RFC 7231 推荐原因短语（或 IANA HTTP 状态码注册表中列出的原因短语）。
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string 原因短语；如果不存在，则 **必须** 返回空字符串。
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }


    /**
     * @return $this
     */
    public function send(): static
    {
        $this->sendHeaders();
        $this->sendContent();

        if (\function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (\function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        } elseif (!\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            static::closeOutputBuffers(0, true);
        }

        return $this;
    }

    public static function closeOutputBuffers(int $targetLevel, bool $flush): void
    {
        $status = ob_get_status(true);
        $level = \count($status);
        $flags = \PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? \PHP_OUTPUT_HANDLER_FLUSHABLE : \PHP_OUTPUT_HANDLER_CLEANABLE);

        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }

    public function sendHeaders(): static
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return $this;
        }

        // headers
        foreach ($this->headers as $name => $values) {
            $replace = 0 === strcasecmp($name, 'Content-Type');
            foreach ($values as $value) {
                header($name.': '.$value, $replace, $this->statusCode);
            }
        }

        // status
        header(sprintf('HTTP/%s %s %s', $this->protocolVersion, $this->statusCode, $this->reasonPhrase), true, $this->statusCode);

        return $this;
    }

    public function sendContent(): static
    {
        echo $this->body;

        return $this;
    }
}

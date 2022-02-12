<?php

namespace Straw\Core\Http;

use JetBrains\PhpStorm\Pure;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;

/**
 * 代表客户端向服务器发起请求的 HTTP 消息对象。
 *
 * 根据 HTTP 规范，此接口包含以下属性：
 *
 * - HTTP 协议版本号
 * - HTTP 请求方法
 * - URI
 * - 报头信息
 * - 消息内容
 *
 * 在构造 HTTP 请求对象的时候，如果没有提供 Host 信息，
 * 实现类库 **必须** 从给出的 URI 中去提取 Host 信息。
 *
 * HTTP 请求是被视为无法修改的，所有能修改状态的方法，都 **必须** 有一套机制，在内部保
 * 持好原有的内容，然后把修改状态后的新的 HTTP 请求实例返回。
 */
class Request extends Message implements RequestInterface
{

    /**
     * @var string|null
     */
    protected ?string $requestTarget = null;


    /**
     * @var string
     */
    protected string $method = '';


    /**
     * @var UriInterface|string
     */
    protected mixed $uri;


    /**
     * @param string $method            请求方法
     * @param string|UriInterface $uri  URI实现类
     */
    public function __construct(string $method, UriInterface|string $uri, array $headers = [], $body = null, string $version = '1.1')
    {
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }
        $this->uri = $uri;
        $this->method = $method;

        foreach ($headers as $name => $value) {
            $this->withHeader($name, $value);
        }

        if ($body != null || $body != '') {
            $this->body = Stream::create($body);
        }
        $this->withProtocolVersion($version);
    }


    /**
     * 获取消息的请求目标。
     *
     * 获取消息的请求目标的使用场景，可能是在客户端，也可能是在服务器端，也可能是在指定信息的时候
     * （参阅下方的 `withRequestTarget()`）。
     *
     * 在大部分情况下，此方法会返回组合 URI 的原始形式，除非被指定过（参阅下方的 `withRequestTarget()`）。
     *
     * 如果没有可用的 URI，并且没有设置过请求目标，此方法 **必须** 返回 「/」。
     *
     * @return string
     */
    #[Pure] public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }
        if (($target = $this->uri->getPath()) == '') {
            $target = '/';
        }
        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }
        return $target;
    }

    /**
     * 返回一个指定目标的请求实例。
     *
     * 如果请求需要非原始形式的请求目标——例如指定绝对形式、认证形式或星号形式——则此方法
     * 可用于创建指定请求目标的实例。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 请求实例，然后返回
     * 一个新的修改过的 HTTP 请求实例。
     *
     * @see [http://tools.ietf.org/html/rfc7230#section-2.7](http://tools.ietf.org/html/rfc7230#section-2.7)
     * （关于请求目标的各种允许的格式）
     *
     * @param mixed $requestTarget
     * @return self
     */
    public function withRequestTarget($requestTarget): static
    {
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    /**
     * 获取当前请求使用的 HTTP 方法
     *
     * @return string HTTP 方法字符串
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * 返回更改了请求方法的消息实例。
     *
     * 虽然，在大部分情况下，HTTP 请求方法都是使用大写字母来标示的，但是，实现类库 **不应该**
     * 修改用户传参的大小格式。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 请求实例，然后返回
     * 一个新的修改过的 HTTP 请求实例。
     *
     * @param string $method 大小写敏感的方法名
     * @return self
     * @throws InvalidArgumentException 当非法的 HTTP 方法名传入时会抛出异常。
     */
    public function withMethod($method): static
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException('Method must be a string');
        }
        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    /**
     * 获取 URI 实例。
     *
     * 此方法 **必须** 返回 `UriInterface` 的 URI 实例。
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface 返回与当前请求相关的 `UriInterface` 类型的 URI 实例。
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * 返回修改了 URI 的消息实例。
     *
     * 当传入的 URI 包含有 HOST 信息时，此方法 **必须** 更新 HOST 信息。如果 URI
     * 实例没有附带 HOST 信息，任何之前存在的 HOST 信息 **必须** 作为候补，应用
     * 更改到返回的消息实例里。
     *
     * 你可以通过传入第二个参数来，来干预方法的处理，当 `$preserveHost` 设置为 `true`
     * 的时候，会保留原来的 HOST 信息。当 `$preserveHost` 设置为 `true` 时，此方法
     * 会如下处理 HOST 信息：
     *
     * - 如果 HOST 信息不存在或为空，并且新 URI 包含 HOST 信息，则此方法 **必须** 更新返回请求中的 HOST 信息。
     * - 如果 HOST 信息不存在或为空，并且新 URI 不包含 HOST 信息，则此方法 **不得** 更新返回请求中的 HOST 信息。
     * - 如果HOST 信息存在且不为空，则此方法 **不得** 更新返回请求中的 HOST 信息。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 请求实例，然后返回
     * 一个新的修改过的 HTTP 请求实例。
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri `UriInterface` 新的 URI 实例
     * @param bool $preserveHost 是否保留原有的 HOST 头信息
     * @return self
     */
    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        $new = clone $this;
        $new->uri = $uri;

        return $new;
    }
}

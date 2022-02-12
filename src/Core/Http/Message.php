<?php

namespace Straw\Core\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\MessageInterface;

/**
 *
 * HTTP 消息包括客户端向服务器发起的「请求」和服务器端返回给客户端的「响应」。
 * 此接口定义了他们通用的方法。
 *
 * HTTP 消息是被视为无法修改的，所有能修改状态的方法，都 **必须** 有一套
 * 机制，在内部保持好原有的内容，然后把修改状态后的信息返回。
 *
 * @see http://www.ietf.org/rfc/rfc7230.txt
 * @see http://www.ietf.org/rfc/rfc7231.txt
 *
 * @see https://learnku.com/docs/psr/psr-7-http-message/1616 中文翻译
 */
class Message implements MessageInterface
{

    /**
     * @var string
     */
    protected string $protocolVersion = '';

    /**
     * @var string[][]
     */
    protected array $headers = [];

    /**
     * @var array
     */
    protected array $headerNames = [];

    /**
     * @var ?StreamInterface
     */
    protected ?StreamInterface $body = null;

    /**
     * 获取字符串形式的 HTTP 协议版本信息。
     *
     * 字符串 **必须** 包含 HTTP 版本数字（如：「1.1」, 「1.0」）。
     *
     * @return string HTTP 协议版本
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * 返回指定 HTTP 版本号的消息实例。
     *
     * 传参的版本号只 **必须** 包含 HTTP 版本数字，如："1.1", "1.0"。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 消息对象，然后返回
     * 一个新的带有传参进去的 HTTP 版本的实例
     *
     * @param string $version HTTP 版本信息
     * @return self
     */
    public function withProtocolVersion($version): static
    {
        $this->protocolVersion = $version;
        return $this;
    }

    /**
     * 获取所有的报头信息
     *
     * 返回的二维数组中，第一维数组的「键」代表单条报头信息的名字，「值」是
     * 以数组形式返回的，见以下实例：
     *
     *     // 把「值」的数据当成字串打印出来
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ': ' . implode(', ', $values);
     *     }
     *
     *     // 迭代的循环二维数组
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * 虽然报头信息是没有大小写之分，但是使用 `getHeaders()` 会返回保留了原本
     * 大小写形式的内容。
     *
     * @return string[][] 返回一个两维数组，第一维数组的「键」 **必须** 为单条报头信息的
     *     名称，对应的是由字串组成的数组，请注意，对应的「值」 **必须** 是数组形式的。
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * 检查是否报头信息中包含有此名称的值，不区分大小写
     *
     * @param string $name 不区分大小写的报头信息名称
     * @return bool 找到返回 true，未找到返回 false
     */
    public function hasHeader($name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * 根据给定的名称，获取一条报头信息，不区分大小写，以数组形式返回
     *
     * 此方法以数组形式返回对应名称的报头信息。
     *
     * 如果没有对应的报头信息，**必须** 返回一个空数组。
     *
     * @param string $name 不区分大小写的报头字段名称。
     * @return string[] 返回报头信息中，对应名称的，由字符串组成的数组值，如果没有对应
     *     的内容，**必须** 返回空数组。
     */
    public function getHeader($name): array
    {
        $header = strtolower($name);

        if (!isset($this->headerNames[$header])) {
            return [];
        }
        $header = $this->headerNames[$header];

        return $this->headers[$header];
    }

    /**
     * 根据给定的名称，获取一条报头信息，不区分大小写，以逗号分隔的形式返回
     *
     * 此方法返回所有对应的报头信息，并将其使用逗号分隔的方法拼接起来。
     *
     * 注意：不是所有的报头信息都可使用逗号分隔的方法来拼接，对于那些报头信息，请使用
     * `getHeader()` 方法来获取。
     *
     * 如果没有对应的报头信息，此方法 **必须** 返回一个空字符串。
     *
     * @param string $name 不区分大小写的报头字段名称。
     * @return string 返回报头信息中，对应名称的，由逗号分隔组成的字串，如果没有对应
     *     的内容，**必须** 返回空字符串。
     */
    public function getHeaderLine($name): string
    {
        if ($this->hasHeader($name)) {
            return implode(', ', $this->getHeader($name));
        }

        return '';
    }

    /**
     * 返回替换指定报头信息「键/值」对的消息实例。
     *
     * 虽然报头信息是不区分大小写的，但是此方法必须保留其传参时的大小写状态，并能够在
     * 调用 `getHeaders()` 的时候被取出。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 消息对象，然后返回
     * 一个更新后带有传参进去报头信息的实例
     *
     * @param string $name 不区分大小写的报头字段名称。
     * @param string|string[] $value 报头信息或报头信息数组。
     * @return self
     * @throws InvalidArgumentException 无效的报头字段或报头信息时抛出
     */
    public function withHeader($name, $value): static
    {
        $value = $this->validateAndTrimHeader($name, $value);
        $normalized = strtolower($name);

        $new = clone $this;
        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }
        $new->headerNames[$normalized] = $name;
        $new->headers[$name] = $value;
        return $new;
    }

    /**
     * 返回一个报头信息增量的 HTTP 消息实例。
     *
     * 原有的报头信息会被保留，新的值会作为增量加上，如果报头信息不存在的话，字段会被加上。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 消息对象，然后返回
     * 一个新的修改过的 HTTP 消息实例。
     *
     * @param string $name 不区分大小写的报头字段名称。
     * @param string|string[] $value 报头信息或报头信息数组。
     *
     * @return self
     * @throws InvalidArgumentException 报头字段名称非法时会被抛出。
     * @throws InvalidArgumentException 报头头信息的值非法的时候会被抛出。
     */
    public function withAddedHeader($name, $value): static
    {
        $new = clone $this;
        $new->setHeaders([$name => $value]);

        return $new;
    }


    protected function setHeaders(array $headers): void
    {
        foreach ($headers as $header => $value) {
            if (\is_int($header)) {
                $header = (string) $header;
            }
            $value = $this->validateAndTrimHeader($header, $value);
            $normalized = strtolower($header);
            if (isset($this->headerNames[$normalized])) {
                $header = $this->headerNames[$normalized];
                // 如果当前头存在就继续往该头信息添加数据
                $this->headers[$header] = array_merge($this->headers[$header], $value);
            } else {
                $this->headerNames[$normalized] = $header;
                $this->headers[$header] = $value;
            }
        }
    }

    private function validateAndTrimHeader($header, $values): array
    {
        if (!is_string($header) || 1 !== preg_match("@^[!#$%&'*+.^_`|~0-9A-Za-z-]+$@", $header)) {
            throw new \InvalidArgumentException('Header name must be an RFC 7230 compatible string.');
        }

        if (!is_array($values)) {
            // This is simple, just one value.
            if ((!is_numeric($values) && !\is_string($values)) || 1 !== preg_match("@^[ \t\x21-\x7E\x80-\xFF]*$@", (string) $values)) {
                throw new \InvalidArgumentException('Header values must be RFC 7230 compatible strings.');
            }

            return [\trim((string) $values, " \t")];
        }

        if (empty($values)) {
            throw new \InvalidArgumentException('Header values must be a string or an array of strings, empty array given.');
        }

        // Assert Non empty array
        $returnValues = [];
        foreach ($values as $v) {
            if ((!\is_numeric($v) && !\is_string($v)) || 1 !== \preg_match("@^[ \t\x21-\x7E\x80-\xFF]*$@", (string) $v)) {
                throw new \InvalidArgumentException('Header values must be RFC 7230 compatible strings.');
            }

            $returnValues[] = \trim((string) $v, " \t");
        }

        return $returnValues;
    }

    /**
     * 返回被移除掉指定报头信息的 HTTP 消息实例。
     *
     * 报头信息字段在解析的时候，**必须** 保证是不区分大小写的。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 消息对象，然后返回
     * 一个新的修改过的 HTTP 消息实例。
     *
     * @param string $name 不区分大小写的头部字段名称。
     * @return self
     */
    public function withoutHeader($name): static
    {
        $normalized = strtolower($name);
        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }
        $header = $this->headerNames[$normalized];
        $new = clone $this;
        unset($new->headers[$header], $new->headerNames[$normalized]);
        return $new;
    }

    /**
     * 获取 HTTP 消息的内容。
     *
     * @return StreamInterface 以数据流的形式返回。
     */
    public function getBody(): StreamInterface
    {
        if ($this->body == null) {
            $this->body = Stream::create();
        }
        return $this->body;
    }

    /**
     * 返回指定内容的 HTTP 消息实例。
     *
     * 内容 **必须** 是 `StreamInterface` 接口的实例。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 消息对象，然后返回
     * 一个新的修改过的 HTTP 消息实例。
     *
     * @param StreamInterface $body 数据流形式的内容。
     * @return self
     * @throws InvalidArgumentException 当消息内容不正确的时候抛出。
     */
    public function withBody(StreamInterface $body): static
    {
        if ($this->body === $body) {
            return $this;
        }
        $new = clone $this;
        $new->body = $body;

        return $new;
    }
}

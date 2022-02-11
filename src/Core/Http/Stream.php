<?php

namespace Straw\Core\Http;

use Exception;
use RuntimeException;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;
use function error_get_last;
use function stream_get_contents;
use function stream_get_meta_data;

/**
 * 描述数据流。
 *
 * 通常，实例将包装PHP流; 此接口提供了最常见操作的包装，包括将整个流序列化为字符串。
 */
class Stream implements StreamInterface
{
    /**
     * 流信息
     *
     * @var resource
     */
    private $stream;

    /**
     * 是否可以在当前流中搜索。
     *
     * @var bool
     */
    private bool $seekable;


    /**
     * 是否可以写
     *
     * @var bool
     */
    private bool $writable;

    /**
     * 是否可以读
     *
     * @var bool
     */
    private bool $readable;


    /**
     * 流数据大小
     *
     * @var int
     */
    private int $size = 0;


    // 读与写的规则
    private const READ_WRITE_HASH = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true,
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true,
        ],
    ];

    private function __construct()
    {
    }


    /**
     * @param string|StreamInterface|resource $body
     *
     * @return StreamInterface
     */
    public static function create($body = ''): StreamInterface
    {
        if ($body instanceof StreamInterface) {
            return $body;
        }

        // 如果是字符串就写入临时resource
        if (is_string($body)) {
            $resource = \fopen('php://temp', 'rw+');
            \fwrite($resource, $body);
            $body = $resource;
        }
        if (\is_resource($body)) {
            $new = new self();
            $new->stream = $body;
            // 从流信息检索元数据
            $meta = stream_get_meta_data($new->stream);
            $new->seekable = $meta['seekable'];
            $new->readable = isset(self::READ_WRITE_HASH['read'][$meta['mode']]);
            $new->writable = isset(self::READ_WRITE_HASH['write'][$meta['mode']]);
            return $new;
        }
        throw new InvalidArgumentException('Stream:：create（）的第一个参数必须是字符串、资源或StreamInterface');
    }

    /**
     * 从头到尾将流中的所有数据读取到字符串。
     *
     * 这个方法 **必须** 在开始读数据前定位到流的开头，并读取出所有的数据。
     *
     * 警告：这可能会尝试将大量数据加载到内存中。
     *
     * 这个方法 **不得** 抛出异常以符合 PHP 的字符串转换操作。
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        try {
            if ($this->isSeekable()) {
                $this->seek(0); // 从头开始获取流数据
            }
            return $this->getContents();
        } catch (Exception) {
            return '';
        }
    }


    /**
     * Closes the stream when the destructed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 关闭流和任何底层资源。
     *
     * @return void
     */
    public function close()
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    /**
     * 从流中分离任何底层资源。
     *
     * 分离之后，流处于不可用状态。
     *
     * @return resource|null 如果存在的话，返回底层 PHP 流。
     */
    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }
        $result = $this->stream;
        unset($this->stream);
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    /**
     * 如果可知，获取流的数据大小。
     *
     * @return int|null 如果可知，返回以字节为单位的大小，如果未知返回 `null`。
     */
    public function getSize(): ?int
    {
        if ($this->size > 0) {
            return $this->size;
        }
        if (!$this->stream) {
            return null;
        }
        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];

            return $this->size;
        }

        return null;
    }

    /**
     * 返回当前读/写的指针位置。
     *
     * @return int 指针位置。
     * @throws RuntimeException 产生错误时抛出。
     */
    public function tell(): int
    {
        if (false === $result = @ftell($this->stream)) {
            throw new RuntimeException('无法确定流的位置: ' . (error_get_last()['message'] ?? ''));
        }

        return $result;
    }

    /**
     * 返回是否位于流的末尾。
     *
     * @return bool
     */
    public function eof(): bool
    {
        return !isset($this->stream) || feof($this->stream);
    }

    /**
     * 返回流是否可随机读取。
     *
     * @return bool
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * 定位流中的指定位置。
     *
     * @see http://www.php.net/manual/en/function.fseek.php
     * @param int $offset 要定位的流的偏移量。
     * @param int $whence 指定如何根据偏移量计算光标位置。有效值与 PHP 内置函数 `fseek()` 相同。
     *     SEEK_SET：设定位置等于 $offset 字节。默认。
     *     SEEK_CUR：设定位置为当前位置加上 $offset。
     *     SEEK_END：设定位置为文件末尾加上 $offset （要移动到文件尾之前的位置，offset 必须是一个负值）。
     * @throws RuntimeException 失败时抛出。
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable) {
            throw new RuntimeException('当前流信息不可以被搜索');
        }

        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException('当前流信息无法被重置');
        }
    }

    /**
     * 定位流的起始位置。
     *
     * 如果流不可以随机访问，此方法将引发异常；否则将执行 seek(0)。
     *
     * @see seek()
     * @see http://www.php.net/manual/en/function.fseek.php
     * @throws RuntimeException 失败时抛出。
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * 返回流是否可写。
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * 向流中写数据。
     *
     * @param string $string 要写入流的数据。
     * @return int 返回写入流的字节数。
     * @throws RuntimeException 失败时抛出。
     */
    public function write($string): int
    {
        if (!$this->isWritable()) {
            throw new RuntimeException('无法从不可写的流中写入信息');
        }

        if (false === $result = @fwrite($this->stream, $string)) {
            throw new RuntimeException('无法写入流信息' . (error_get_last()['message'] ?? ''));
        }

        return $result;
    }

    /**
     * 返回流是否可读。
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * 从流中读取数据。
     *
     * @param int $length 从流中读取最多 $length 字节的数据并返回。如果数据不足，则可能返回少于
     *     $length 字节的数据。
     * @return string 返回从流中读取的数据，如果没有可用的数据则返回空字符串。
     * @throws RuntimeException 失败时抛出。
     */
    public function read($length): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('无法从不可读的流中读取信息');
        }
        if (false === $result = @fread($this->stream, $length)) {
            throw new RuntimeException('无法读取流信息: ' . (error_get_last()['message'] ?? ''));
        }

        return $result;
    }

    /**
     * 返回资源流内容。
     *
     * @return string
     * @throws RuntimeException 如果无法读取则抛出异常。
     * @throws RuntimeException 如果在读取时发生错误则抛出异常。
     */
    public function getContents(): string
    {
        if (false === $contents = @stream_get_contents($this->stream)) {
            throw new RuntimeException('无法读取流内容: ' . (error_get_last()['message'] ?? ''));
        }
        return $contents;
    }

    /**
     * 获取流中的元数据作为关联数组，或者检索指定的键。
     *
     * 返回的键与从 PHP 的 stream_get_meta_data() 函数返回的键相同。
     *
     * @see http://php.net/manual/en/function.stream-get-meta-data.php
     *
     * @param null $key 要检索的特定元数据。
     *
     * @return mixed 如果没有键，则返回关联数组。如果提供了键并且找到值，
     *     则返回特定键值；如果未找到键，则返回 null。
     */
    public function getMetadata($key = null): mixed
    {
        $meta = stream_get_meta_data($this->stream);

        if ($key == null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}

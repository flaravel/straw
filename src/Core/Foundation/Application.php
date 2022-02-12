<?php

namespace Straw\Core\Foundation;

use Straw\Core\Container\Container;

class Application extends Container
{
    /**
     * @var string|null
     */
    protected ?string $basePath;

    public function __construct(?string $basePath = null)
    {
        if ($basePath) {
            // 注册项目目录路径
            $this->setBasePath($basePath);
        }
        // 注册初始服务
        $this->registerBaseBindings();
    }

    /**
     * 设置项目路径
     *
     * @param string $basePath
     *
     * @return Application
     */
    public function setBasePath(string $basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }


    protected function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Container::class, $this);
    }

    /**
     * 注册项目目录
     */
    protected function bindPathsInContainer()
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.database', $this->databasePath());
    }


    /**
     * app目录
     *
     * @return string
     */
    public function path(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'app';
    }

    /**
     * 获取跟目录
     *
     * @return string
     */
    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * 获取配置文件目录
     *
     * @return string
     */
    public function configPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'config';
    }


    /**
     * 获取数据库目录
     *
     * @return string
     */
    public function databasePath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'database';
    }
}

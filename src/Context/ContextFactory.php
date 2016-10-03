<?php

namespace Bicycle\FilesManager\Context;

use Illuminate\Contracts\Foundation\Application;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Exceptions;
use Bicycle\FilesManager\Helpers;

/**
 * ContextFactory builds contexts instances.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class ContextFactory implements Contracts\ContextFactory
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Contracts\Manager
     */
    protected $manager;

    /**
     * Extended configs of contexts.
     * 
     * @var array[] in `name` => `config` format.
     */
    protected $configs = [];

    /**
     * Extended configs of types.
     * 
     * @var array[] in `type` => `config` format.
     */
    protected $types = [];

    /**
     * @param Application $app
     * @param Contracts\Manager $manager
     */
    public function __construct(Application $app, Contracts\Manager $manager)
    {
        $this->app = $app;
        $this->manager = $manager;
    }

    /**
     * @inheritdoc
     * @throws Exceptions\InvalidConfigException
     */
    public function make($name, array $config = [])
    {
        $type = isset($config['type']) ? $config['type'] : $this->app['config']['filemanager.default_type'];
        unset($config['type']);
        $resultConfig = Helpers\Config::merge($this->resolveTypeConfig($type), $config);

        $class = isset($resultConfig['class']) ? $resultConfig['class'] : Context::class;
        unset($resultConfig['class']);

        $result = $this->app->make($class, [
            'name' => $name,
            'manager' => $this->manager,
            'config' => $resultConfig,
        ]);
        if (!$result instanceof Contracts\Context) {
            throw new Exceptions\InvalidConfigException("Bad configuration of '$name' context. It should be an instance of '" . Contracts\Context::class . "' interface.");
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function resolve($name)
    {
        if (!$this->has($name) && strpos($name, '@') !== false) {
            list($class, $attribute) = explode('@', $name);
            $model = $this->app->make($class);
            /* @var $model \Bicycle\FilesManager\ModelFilesTrait */
            return $model->getFileAttributeContext($attribute);
        }

        $config = $this->getContextConfig($name);
        return $this->make($name, $config);
    }

    /**
     * Fetches config for the context by name from application config.
     * 
     * @param string $contextName
     * @return array
     * @throws Exceptions\ContextNotFoundException
     */
    protected function getContextConfig($contextName)
    {
        if (isset($this->configs[$contextName])) {
            return $this->configs[$contextName];
        }

        $config = $this->app['config']["filecontexts.$contextName"];
        if (is_array($config)) {
            return $config;
        }

        throw new Exceptions\ContextNotFoundException($contextName);
    }

    /**
     * @inheritdoc
     */
    public function extend($name, array $config)
    {
        $this->configs[$name] = $config;
    }

    /**
     * @inheritdoc
     */
    public function has($name)
    {
        try {
            $this->getContextConfig($name);
            return true;
        } catch (Exceptions\ContextNotFoundException $ex) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function names()
    {
        $names = array_keys($this->configs);

        $configs = $this->app['config']['filecontexts'];
        if ($configs) {
            $names = array_merge($names, array_keys($configs));
        }

        return array_values(array_unique($names));
    }

    /**
     * @inheritdoc
     */
    public function configureType($type, array $config)
    {
        $this->types[$type] = $config;
    }

    /**
     * @param string $type
     * @return array
     * @throws Exceptions\UnknownContextTypeException
     */
    protected function resolveTypeConfig($type)
    {
        $appGlobalConfig = $this->app['config']['filemanager.global'];
        $appTypeConfig = $this->app['config']["filemanager.types.$type"];

        if (!is_array($appTypeConfig) && !isset($this->types[$type])) {
            throw new Exceptions\UnknownContextTypeException($type);
        }

        return Helpers\Config::merge(
            $appGlobalConfig ?: [],
            $appTypeConfig ?: [],
            isset($this->types[$type]) ? $this->types[$type] : []
        );
    }
}

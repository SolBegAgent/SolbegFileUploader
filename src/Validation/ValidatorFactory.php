<?php

namespace Bicycle\FilesManager\Validation;

use Illuminate\Contracts\Container\Container;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Exceptions;
use Bicycle\FilesManager\Helpers;

/**
 * ValidatorFactory builds validators instances.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class ValidatorFactory implements Contracts\ValidatorFactory
{
    /**
     * @var array
     */
    protected $aliases = [
        'extensions' => ExtensionValidator::class,
        'types' => MimeTypeValidator::class,
        'size' => SizeValidator::class,
    ];

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function build(Contracts\Context $context, $name, $config)
    {
        $class = $name;
        while (isset($this->aliases[$class])) {
            $class = $this->aliases[$class];
        }

        $result = $this->getContainer()->make($class, [
            'name' => $name,
            'context' => $context,
            'config' => $this->parseConfig($config),
            'factory' => $this,
        ]);
        if (!$result instanceof Contracts\Validator) {
            throw new Exceptions\InvalidConfigException("Invalid validator class '$class', it must implement '" . Contracts\Validator::class . '\' interface.');
        }
        return $result;
    }

    /**
     * @param mixed $config
     * @return mixed
     */
    protected function parseConfig($config)
    {
        if (!is_string($config) || mb_strpos($config, '=', 0, 'UTF-8') === false) {
            return $config;
        } else {
            return Helpers\Config::parse($config);
        }
    }

    /**
     * @inheritdoc
     */
    public function alias($alias, $class)
    {
        $this->aliases[$alias] = $class;
        return $this;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}

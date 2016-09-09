<?php

namespace Bicycle\FilesManager\Formatters;

use Illuminate\Contracts\Container\Container;

use Bicycle\FilesManager\Contracts\Context as ContextInterface;
use Bicycle\FilesManager\Contracts\Formatter as FormatterInterface;
use Bicycle\FilesManager\Contracts\FormatterFactory as FormatterFactoryInterface;
use Bicycle\FilesManager\Exceptions\InvalidConfigException;
use Bicycle\FilesManager\Helpers;

/**
 * FormatterFactory builds formatters instances.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class FormatterFactory implements FormatterFactoryInterface
{
    /**
     * @var array
     */
    protected $aliases = [
        'from' => FromFormatter::class,
        'inline' => InlineFormatter::class,

        'image/fit' => Image\FitFormatter::class,
        'image/resize' => Image\ResizeFormatter::class,
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
    public function build(ContextInterface $context, $name, $config)
    {
        if ($config instanceof \Closure) {
            return $this->inline($context, $name, $config);
        } elseif (is_string($config)) {
            $config = $this->parseStringConfig($config);
        }

        if (!isset($config[0])) {
            throw new InvalidConfigException('Each formatter must have the name of class as the first argument.');
        }
        $class = $config[0];
        unset($config[0]);

        return $this->make($context, $name, $class, $config);
    }

    /**
     * @param ContextInterface $context
     * @param string $name
     * @param callable $closure
     * @return InlineFormatter
     */
    public function inline(ContextInterface $context, $name, callable $closure)
    {
        return $this->make($context, $name, 'inline', [
            'closure' => $closure,
        ]);
    }

    /**
     * @param ContextInterface $context
     * @param string $name
     * @param string $class
     * @param array $config
     * @return FormatterInterface
     */
    public function make(ContextInterface $context, $name, $class, array $config = [])
    {
        while (isset($this->aliases[$class])) {
            $class = $this->aliases[$class];
        }

        $result = $this->getContainer()->make($class, array_merge($config, [
            'name' => $name,
            'context' => $context,
            'config' => $config,
            'factory' => $this,
        ]));
        if (!$result instanceof FormatterInterface) {
            throw new InvalidConfigException("Invalid formatter class '$class', it must implement '" . FormatterInterface::class . '\' interface.');
        }
        return $result;
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
     * @param string $string
     * @return array
     */
    protected function parseStringConfig($string)
    {
        if (false !== $pos = mb_strpos($string, ':', 0, 'UTF-8')) {
            $class = trim(mb_substr($string, 0, $pos, 'UTF-8'));
            $paramsString = ltrim(mb_substr($string, $pos + 1, null, 'UTF-8'));
        } else {
            list($class, $paramsString) = [trim($string), ''];
        }
        return array_merge([$class], Helpers\Config::parse($paramsString));
    }

    /**
     * @inheritdoc
     */
    public function parse(ContextInterface $context, $name)
    {
        return null;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}

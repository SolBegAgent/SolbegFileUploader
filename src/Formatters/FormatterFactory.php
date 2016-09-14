<?php

namespace Bicycle\FilesManager\Formatters;

use Illuminate\Contracts\Foundation\Application;

use Bicycle\FilesManager\Contracts\Context as ContextInterface;
use Bicycle\FilesManager\Contracts\Formatter as FormatterInterface;
use Bicycle\FilesManager\Contracts\FormatterFactory as FormatterFactoryInterface;
use Bicycle\FilesManager\Contracts\FormatterParser as ParserInterface;
use Bicycle\FilesManager\Exceptions\InvalidConfigException;
use Bicycle\FilesManager\Exceptions\FormatterParserNotFoundException;
use Bicycle\FilesManager\Helpers;

/**
 * FormatterFactory builds formatters instances.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class FormatterFactory implements FormatterFactoryInterface
{
    const ALIAS_FROM = 'from';
    const ALIAS_INLINE = 'inline';
    const ALIAS_IMAGE_FIT = 'image/fit';
    const ALIAS_IMAGE_RESIZE = 'image/resize';
    const ALIAS_IMAGE_THUMB = 'image/thumb';

    /**
     * @var array
     */
    protected $aliases = [
        self::ALIAS_FROM => FromFormatter::class,
        self::ALIAS_INLINE => InlineFormatter::class,

        self::ALIAS_IMAGE_FIT => Image\FitFormatter::class,
        self::ALIAS_IMAGE_RESIZE => Image\ResizeFormatter::class,
        self::ALIAS_IMAGE_THUMB => Image\ThumbnailFormatter::class,
    ];

    /**
     * @var array
     */
    protected $parsers = [
        'num' => Parsers\NumParser::class,
        'num_x_num' => Parsers\NumXNumParser::class,
    ];

    /**
     * @var Application
     */
    private $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $configParsers = $app['config']['filemanager.format_parsers'];
        if (is_array($configParsers)) {
            $this->parsers = $configParsers;
        }
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
        } elseif ($config instanceof FormatterInterface) {
            return $config;
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
     * @return static $this
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
     * @return static $this
     */
    public function parsers(array $parsers, $replaceAll = true)
    {
        if (!$replaceAll) {
            $parsers = array_merge($this->parsers, $parsers);
        }
        foreach ($parsers as $key => $parser) {
            if ($parser === null) {
                unset($parsers[$key]);
            }
        }
        $this->parsers = $parsers;
        return $this;
    }

    /**
     * @param string $name
     * @return ParserInterface|Parsers\AbstractParser
     * @throws FormatterParserNotFoundException
     */
    public function parser($name)
    {
        if (!isset($this->parsers[$name])) {
            throw new FormatterParserNotFoundException($name);
        }
        $parser = $this->parsers[$name];
        if (!$parser instanceof ParserInterface) {
            $this->parsers[$name] = $parser = $this->createParser($parser);
        }
        return $parser;
    }

    /**
     * @inheritdoc
     */
    public function parse(ContextInterface $context, $name)
    {
        foreach (array_keys($this->parsers) as $key) {
            $parsed = $this->parser($key)->parse($name, $context);
            if ($parsed !== null) {
                return $this->build($context, $name, $parsed);
            }
        }
        return null;
    }

    /**
     * @param mixed $config
     * @return ParserInterface
     * @throws InvalidConfigException
     */
    protected function createParser($config)
    {
        if ($config instanceof \Closure) {
            list($class, $config) = [Parsers\InlineParser::class, ['callback' => $config]];
        } elseif (is_string($config)) {
            list($class, $config) = [$config, []];
        } elseif (!is_array($config) || !isset($config[0])) {
            throw new InvalidConfigException('Invalid config of parser. The first element of an array required and must keep the name of parser class.');
        } else {
            $class = $config[0];
            unset($config[0]);
        }

        $result = $this->getContainer()->make($class, [
            'factory' => $this,
            'config' => $config,
        ]);
        if (!$result instanceof ParserInterface) {
            throw new InvalidConfigException("Invalid config of parser '$class'. It must implement '" . ParserInterface::class . '\' interface.');
        }
        return $result;
    }

    /**
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer()
    {
        return $this->app;
    }
}

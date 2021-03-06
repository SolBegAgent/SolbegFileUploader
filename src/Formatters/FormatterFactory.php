<?php

namespace Solbeg\FilesManager\Formatters;

use Illuminate\Contracts\Foundation\Application;

use Intervention\Image\Image as InterventionImage;

use Solbeg\FilesManager\Contracts\Context as ContextInterface;
use Solbeg\FilesManager\Contracts\Formatter as FormatterInterface;
use Solbeg\FilesManager\Contracts\FormatterFactory as FormatterFactoryInterface;
use Solbeg\FilesManager\Contracts\FormatterParser as ParserInterface;
use Solbeg\FilesManager\Exceptions\InvalidConfigException;
use Solbeg\FilesManager\Exceptions\FormatterParserNotFoundException;
use Solbeg\FilesManager\Helpers;

/**
 * FormatterFactory builds formatters instances.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class FormatterFactory implements FormatterFactoryInterface
{
    const ALIAS_CHAIN = 'chain';
    const ALIAS_FROM = 'from';
    const ALIAS_INLINE = 'inline';

    const ALIAS_IMAGE_INLINE = 'image/inline';
    const ALIAS_IMAGE_FIT = 'image/fit';
    const ALIAS_IMAGE_RESIZE = 'image/resize';
    const ALIAS_IMAGE_THUMB = 'image/thumb';
    const ALIAS_IMAGE_WATERMARK = 'image/watermark';

    /**
     * @var array
     */
    protected $aliases = [
        self::ALIAS_CHAIN => ChainFormatter::class,
        self::ALIAS_FROM => FromFormatter::class,
        self::ALIAS_INLINE => InlineFormatter::class,

        self::ALIAS_IMAGE_INLINE => Image\InlineFormatter::class,
        self::ALIAS_IMAGE_FIT => Image\FitFormatter::class,
        self::ALIAS_IMAGE_RESIZE => Image\ResizeFormatter::class,
        self::ALIAS_IMAGE_THUMB => Image\ThumbnailFormatter::class,
        self::ALIAS_IMAGE_WATERMARK => Image\WatermarkFormatter::class,
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
     * @param callable $callback
     * @return InlineFormatter
     */
    public function inline(ContextInterface $context, $name, callable $callback)
    {
        $alias = $this->isImageInlineCallback($callback)
            ? self::ALIAS_IMAGE_INLINE
            : self::ALIAS_INLINE;
        return $this->make($context, $name, $alias, [
            'callback' => $callback,
        ]);
    }

    /**
     * @param callable $callback
     * @return boolean
     */
    protected function isImageInlineCallback(callable $callback)
    {
        foreach ($this->getCallbackReflector($callback)->getParameters() as $parameter) {
            /* @var $parameter \ReflectionParameter */
            $parameterClass = $parameter->getClass();
            if (!$parameterClass && $parameter->name === 'image') {
                return true;
            } elseif ($parameterClass && $parameterClass->name === InterventionImage::class) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the proper reflection instance for the given callback.
     *
     * @param  callable  $callback
     * @return \ReflectionFunctionAbstract
     */
    protected function getCallbackReflector(callable $callback)
    {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        }
        if (is_array($callback)) {
            return new \ReflectionMethod($callback[0], $callback[1]);
        }
        return new \ReflectionFunction($callback);
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

<?php

namespace Solbeg\FilesManager\Formatters;

use Solbeg\FilesManager\Contracts;
use Solbeg\FilesManager\Exceptions;

use Symfony\Component\HttpFoundation\File\File;

/**
 * ChainFormatter applies chain of other formatters to a file source.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class ChainFormatter extends AbstractFormatter
{
    /**
     * The chain of formatters that will be applied one by one to a file source.
     * 
     * @var array|Contracts\Formatter[] Format of values is identical to format in
     * `\Solbeg\FilesManager\Context\Context::$formats` property.
     */
    protected $formatters = [];

    /**
     * If this property is true and a formatter config is string,
     * then the formatter tries to parse this string before.
     * 
     * @var boolean
     */
    protected $parse = true;

    /**
     * @var Contracts\FormatterFactory
     */
    private $factory;

    /**
     * @var boolean
     */
    private $initialized = false;

    /**
     * @param Contracts\FormatterFactory $factory
     * @inheritdoc
     */
    public function __construct(Contracts\FormatterFactory $factory, $name, Contracts\Context $context, array $config = [])
    {
        $this->factory = $factory;
        parent::__construct($name, $context, $config);
    }

    /**
     * @inheritdoc
     * @throws Exceptions\InvalidConfigException
     */
    protected function init()
    {
        if (!$this->formatters || !is_array($this->formatters)) {
            throw new Exceptions\InvalidConfigException("Property 'formatters' required for the {$this->getName()} formatter.");
        }
        return parent::init();
    }

    /**
     * Creates $formatters if them has not been initialized yet.
     */
    protected function initFormatters()
    {
        if ($this->initialized) {
            return;
        }

        $factory = $this->getFactory();
        $context = $this->getContext();

        foreach ($this->formatters as $key => $formatter) {
            if ($this->parse && is_string($formatter)) {
                $parsed = $factory->parse($context, $formatter);
                if ($parsed !== null) {
                    $this->formatters[$key] = $parsed;
                    continue;
                }
            }

            $name = $this->getName() . "@$key";
            $this->formatters[$key] = $factory->build($context, $name, $formatter);
        }

        $this->initialized = true;
    }

    /**
     * @inheritdoc
     */
    public function format(Contracts\FileSource $source, Contracts\Storage $storage)
    {
        $this->initFormatters();

        $sourceFactory = $this->getContext()->getSourceFactory();
        $oldTmpPath = null;

        foreach ($this->formatters as $formatter) {
            if ($oldTmpPath !== null) {
                $source = $sourceFactory->simpleFile(new File($oldTmpPath));
            }

            try {
                $tmpPath = $formatter->format($source, $storage);
            } finally {
                if ($oldTmpPath !== null) {
                    @unlink($oldTmpPath);
                }
            }

            if ($tmpPath === null) {
                return null;
            }
            $oldTmpPath = $tmpPath;
        }

        return $tmpPath;
    }

    /**
     * @return Contracts\FormatterFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }
}

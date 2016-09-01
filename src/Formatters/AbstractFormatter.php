<?php

namespace Bicycle\FilesManager\Formatters;

use Bicycle\FilesManager\Contracts\Context as ContextInterface;
use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Contracts\Formatter as FormatterInterface;
use Bicycle\FilesManager\Exceptions\InvalidConfigException;
use Bicycle\FilesManager\Helpers\ConfigurableTrait;

/**
 * AbstractFormatter is the base class for all formatters.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
abstract class AbstractFormatter implements FormatterInterface
{
    use ConfigurableTrait;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var string
     */
    private $name;

    /**
     * @inheritdoc
     */
    abstract public function format(FileSourceInterface $source);

    /**
     * @param string $name
     * @param ContextInterface $context
     * @param array $config
     */
    public function __construct($name, ContextInterface $context, array $config = [])
    {
        $this->name = $name;
        $this->context = $context;
        $this->configure($config);
    }

    /**
     * @return ContextInterface
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

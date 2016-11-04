<?php

namespace Solbeg\FilesManager\Formatters;

use Solbeg\FilesManager\Contracts\Context as ContextInterface;
use Solbeg\FilesManager\Contracts\FileSource as FileSourceInterface;
use Solbeg\FilesManager\Contracts\Formatter as FormatterInterface;
use Solbeg\FilesManager\Contracts\Storage as StorageInterface;
use Solbeg\FilesManager\Helpers\ConfigurableTrait;

/**
 * AbstractFormatter is the base class for all formatters.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
abstract class AbstractFormatter implements FormatterInterface
{
    use ConfigurableTrait;

    /**
     * @var string|null
     */
    protected $defaultExtension = null;

    /**
     * @var string|null|boolean
     */
    protected $forceExtension = false;

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
    abstract public function format(FileSourceInterface $source, StorageInterface $storage);

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
        $this->init();
    }

    /**
     * Initializes config.
     * 
     * @throws \Solbeg\FilesManager\Exceptions\InvalidConfigException
     */
    protected function init()
    {
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

    /**
     * Generates and returns filename for new temporary file.
     * 
     * @param string|null $extension
     * @return string
     */
    public function generateNewTempFilename($extension = null)
    {
        return $this->getContext()->getManager()->generateNewTempFilename($extension);
    }

    /**
     * @param FileSourceInterface $source
     */
    public function generateExtension(FileSourceInterface $source)
    {
        if ($this->forceExtension !== false) {
            return $this->forceExtension;
        }

        $extension = $source->extension();
        if ($extension === null) {
            $extension = $this->defaultExtension;
        }
        return $extension;
    }
}

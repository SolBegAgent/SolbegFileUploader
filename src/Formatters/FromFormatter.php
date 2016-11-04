<?php

namespace Solbeg\FilesManager\Formatters;

use Solbeg\FilesManager\Contracts\Context as ContextInterface;
use Solbeg\FilesManager\Contracts\FileSource as FileSourceInterface;
use Solbeg\FilesManager\Contracts\Storage as StorageInterface;
use Solbeg\FilesManager\Exceptions\FileSystemException;
use Solbeg\FilesManager\Exceptions\InvalidConfigException;

/**
 * FromFormatter formats file from another formatted version.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class FromFormatter extends AbstractFormatter
{
    /**
     * @var string the name of another formatter from which file should be converted
     */
    protected $source;

    /**
     * @var string the name of formatter class
     */
    protected $formatter;

    /**
     * @var array params for the formatter.
     */
    protected $params = [];

    /**
     * @var boolean if it is false and source version does not exist then no file will be converted.
     */
    protected $required = true;

    /**
     * @var FormatterFactory
     */
    private $factory;

    /**
     * @var boolean
     */
    private $isProcess = false;

    /**
     * @param \Solbeg\FilesManager\Formatters\FormatterFactory $factory
     * @inheritdoc
     */
    public function __construct(FormatterFactory $factory, $name, ContextInterface $context, array $config = [])
    {
        $this->factory = $factory;

        $selfProperties = array_fill_keys($this->selfProperties(), true);
        $selfConfig = array_intersect_key($config, $selfProperties);
        $formatterConfig = array_diff_key($config, $selfProperties);

        $this->params = $formatterConfig;
        parent::__construct($name, $context, $selfConfig);
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        if (in_array($this->source, [null, ''], true)) {
            throw new InvalidConfigException('Property "source" is required for `' . $this->getName() . '` formatter.');
        } elseif (in_array($this->formatter, [null, ''], true)) {
            throw new InvalidConfigException('Property "formatter" is required for `' . $this->getName() . '` formatter.');
        }

        return parent::init();
    }

    /**
     * @return string[]
     */
    protected function selfProperties()
    {
        return [
            'source',
            'formatter',
            'required',
        ];
    }

    /**
     * @return FormatterFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @inheritdoc
     */
    public function format(FileSourceInterface $source, StorageInterface $storage)
    {
        if ($this->isProcess) {
            throw new InvalidConfigException("The '{$this->getName()}' formatter of '{$this->getContext()->getName()}' context has an infinite cycle in own formatters hierarchy.");
        }
        $this->isProcess = true;

        try {
            $result = $this->process($source, $storage);
        } catch (\Exception $ex) {
            $this->isProcess = false;
            throw $ex;
        } catch (\Throwable $ex) {
            $this->isProcess = false;
            throw $ex;
        }

        $this->isProcess = false;
        return $result;
    }

    /**
     * The main logic of this formatter.
     * 
     * @param FileSourceInterface $source
     * @param StorageInterface $storage
     * @return string|null
     */
    protected function process(FileSourceInterface $source, StorageInterface $storage)
    {
        $format = $this->source;
        if (!$this->ensureFormattedFileExists($source, $storage, $format)) {
            return null;
        }

        $fileSource = $this->getContext()->getSourceFactory()->formattedFile($source, $format);
        $formatter = $this->getFactory()->make($this->getContext(), $this->getName(), $this->formatter, $this->params);

        return $formatter->format($fileSource, $storage);
    }

    /**
     * @param FileSourceInterface $source
     * @param StorageInterface $storage
     * @param string $format
     * @return boolean
     * @throws FileSystemException
     */
    protected function ensureFormattedFileExists(FileSourceInterface $source, StorageInterface $storage, $format)
    {
        if ($source->exists($format)) {
            return true;
        } elseif ($storage->generateFormattedFile($source, $format)) {
            return true;
        } elseif ($this->required) {
            throw new FileSystemException("Cannot generate formatted as '$format' version of '{$source->relativePath()}' file.");
        }
        return false;
    }
}

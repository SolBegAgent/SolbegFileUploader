<?php

namespace Bicycle\FilesManager\Formatters;

use Bicycle\FilesManager\Contracts\Context as ContextInterface;
use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Exceptions\FileSystemException;
use Bicycle\FilesManager\Exceptions\InvalidConfigException;

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
     * @param \Bicycle\FilesManager\Formatters\FormatterFactory $factory
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
     * @param FileSourceInterface $source
     * @inheritdoc
     */
    public function format(FileSourceInterface $source)
    {
        if ($this->isProcess) {
            throw new InvalidConfigException("The '{$this->getName()}' formatter of '{$this->getContext()->getName()}' context has an infinite cycle in own formatters hierarchy.");
        }
        $this->isProcess = true;

        try {
            $result = $this->process($source);
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
     * @return string|null
     */
    protected function process(FileSourceInterface $source)
    {
        $format = $this->source;
        if ($format === null || $format === '') {
            throw new InvalidConfigException('Property $source is required for `from` formatter.');
        } elseif (!$this->ensureFormattedFileExists($source, $format)) {
            return null;
        }

        $fileSource = $this->getContext()->getSourceFactory()->formattedFile($source, $format);
        $formatter = $this->getFactory()->make($this->getContext(), $this->getName(), $this->formatter, $this->params);

        return $formatter->format($fileSource);
    }

    /**
     * @param FileSourceInterface $source
     * @param string $format
     * @return boolean
     * @throws FileSystemException
     */
    protected function ensureFormattedFileExists(FileSourceInterface $source, $format)
    {
        if ($source->exists($format)) {
            return true;
        } elseif ($this->getContext()->generateFormattedFile($source, $format)) {
            return true;
        } elseif ($this->required) {
            throw new FileSystemException("Cannot generate formatted as '$format' version of '{$source->relativePath()}' file.");
        }
        return false;
    }
}

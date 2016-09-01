<?php

namespace Bicycle\FilesManager\Formatters;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

use Bicycle\FilesManager\Contracts\Context as ContextInterface;
use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
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
        $this->params = $config;
        parent::__construct($name, $context, []);
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
        if (!$this->required && !$source->exists($format)) {
            return null;
        }

        $file = new SymfonyFile($source->readPath($format), false);
        $formatter = $this->getFactory()->make($this->getName(), $this->formatter, $this->params);

        $fileSource = $this->getContext()->getSourceFactory()->make($file);
        return $formatter->format($fileSource);
    }
}

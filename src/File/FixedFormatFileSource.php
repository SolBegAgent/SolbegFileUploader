<?php

namespace Bicycle\FilesManager\File;

use Bicycle\FilesManager\Contracts\FileSource as SourceInterface;
use Bicycle\FilesManager\Exceptions\NotSupportedException;

/**
 * FixedFormatFileSource always returns file of the passed in constructor format.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class FixedFormatFileSource extends AbstractFileSource
{
    use Traits\NotSupported;
    use Traits\WithoutFormatting;
    use Traits\WithoutRelativePath;

    /**
     * @var boolean
     */
    protected $always;

    /**
     * @var SourceInterface
     */
    private $source;

    /**
     * @var string|null
     */
    private $fixedFormat = null;

    /**
     * @param SourceInterface $source
     * @param string|null $fixedFormat
     * @param boolean $always
     */
    public function __construct(SourceInterface $source, $fixedFormat = null, $always = false)
    {
        $this->source = $source;
        $this->fixedFormat = $fixedFormat;
        $this->always = $always;
    }

    /**
     * @inheritdoc
     */
    protected function originContents()
    {
        return $this->getSource()->contents($this->getFixedFormat());
    }

    /**
     * @inheritdoc
     */
    protected function originExists()
    {
        return $this->getSource()->exists($this->getFixedFormat());
    }

    /**
     * @inheritdoc
     */
    protected function originUrl()
    {
        return $this->getSource()->url($this->getFixedFormat());
    }

    /**
     * @inheritdoc
     */
    protected function originName()
    {
        return $this->getSource()->name($this->getFixedFormat());
    }

    /**
     * @inheritdoc
     */
    protected function originBasename()
    {
        return $this->getSource()->basename($this->getFixedFormat());
    }

    /**
     * @inheritdoc
     */
    protected function originExtension()
    {
        return $this->getSource()->extension($this->getFixedFormat());
    }

    /**
     * @inheritdoc
     */
    protected function originMimeType()
    {
        return $this->getSource()->mimeType($this->getFixedFormat());
    }

    /**
     * @inheritdoc
     */
    protected function originSize()
    {
        return $this->getSource()->size($this->getFixedFormat());
    }

    /**
     * @inheridoc
     */
    protected function deleteOrigin()
    {
        $this->getSource()->delete($this->getFixedFormat());
    }

    /**
     * @return SourceInterface
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string|null
     */
    public function getFixedFormat()
    {
        return $this->fixedFormat;
    }

    /**
     * @param string|null $format
     * @throws NotSupportedException
     */
    protected function assertFormatIsNull($format)
    {
        if ($format !== null && !$this->always) {
            throw $this->createNotSupportedException('{class} does not support file formatting.');
        }
    }
}

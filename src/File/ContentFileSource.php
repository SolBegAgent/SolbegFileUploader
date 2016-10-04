<?php

namespace Bicycle\FilesManager\File;

/**
 * ContentsFileSource keeps file content.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class ContentFileSource extends AbstractFileSource
{
    use Traits\NotSupported;
    use Traits\WithoutFormatting;
    use Traits\WithoutRelativePath;

    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $mimeType;

    /**
     *
     * @var string|null
     */
    private $url;

    /**
     * @var integer
     */
    private $lastModified;

    /**
     * @param string $content
     * @param string $name
     * @param string|null $mimeType
     * @param string|null $url
     */
    public function __construct($content, $name, $mimeType = null, $url = null)
    {
        $this->lastModified = time();
        $this->content = (string) $content;
        $this->name = (string) $name;
        $this->mimeType = $mimeType;
        $this->url = $url;
    }

    /**
     * @inheritdoc
     */
    protected function originContents()
    {
        return new ContentStreams\Content($this->content);
    }

    /**
     * @inheritdoc
     */
    protected function originExists()
    {
        return $this->content !== null;
    }

    /**
     * @inheritdoc
     * @throws \Bicycle\FilesManager\Exceptions\NotSupportedException
     */
    protected function originUrl()
    {
        if ($this->url !== null) {
            return $this->url;
        }
        throw $this->createNotSupportedException('{class} does not support access by HTTP.');
    }

    /**
     * @inheritdoc
     */
    protected function originBasename()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    protected function originSize()
    {
        return mb_strlen($this->content, '8bit');
    }

   /**
    * @return integer
    */
    protected function originLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @inheritdoc
     */
    protected function originMimeType()
    {
        return $this->mimeType ?: null;
    }

    /**
     * @inheritdoc
     */
    protected function deleteOrigin()
    {
        $this->content = null;
    }
}

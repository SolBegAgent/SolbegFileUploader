<?php

namespace Bicycle\FilesManager\File;

use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

use Bicycle\FilesManager\Contracts\FileSource;
use Bicycle\FilesManager\Exceptions\NotSupportedException;
use Bicycle\FilesManager\Helpers\File as FileHelper;

/**
 * UploadedFileSource
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 * 
 * @method SymfonyUploadedFile getFile()
 */
class UploadedFileSource extends SplFileSource implements FileSource
{
    /**
     * @param SymfonyUploadedFile $uploadedFile
     */
    public function __construct(SymfonyUploadedFile $uploadedFile)
    {
        parent::__construct($uploadedFile);
    }

    /**
     * @inheritdoc
     */
    public function exists($format = null)
    {
        return $format === null ? $this->getFile()->getError() == UPLOAD_ERR_OK : false;
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function name($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->getFile()->getClientOriginalName();
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function extension($format = null)
    {
        $this->assertFormatIsNull($format);
        return $this->getFile()->guessExtension();
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException if `$format` is not null.
     */
    public function basename($format = null)
    {
        $this->assertFormatIsNull($format);
        return FileHelper::basename($this->name());
    }
}

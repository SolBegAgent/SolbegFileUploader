<?php

namespace Bicycle\FilesManager\File;

use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

/**
 * UploadedFileSource
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 * 
 * @method SymfonyUploadedFile getFile()
 */
class UploadedFileSource extends SplFileSource
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
    protected function originExists()
    {
        return $this->getFile()->isValid();
    }

    /**
     * @inheritdoc
     */
    protected function originName()
    {
        return $this->getFile()->getClientOriginalName();
    }

    /**
     * @inheritdoc
     */
    protected function originExtension()
    {
        return $this->getFile()->guessExtension();
    }
}

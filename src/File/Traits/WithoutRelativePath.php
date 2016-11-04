<?php

namespace Solbeg\FilesManager\File\Traits;

/**
 * WithoutRelativePath Trait
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
trait WithoutRelativePath
{
    /**
     * @param string $message
     * @return \Solbeg\FilesManager\Exceptions\NotSupportedException
     */
    abstract protected function createNotSupportedException($message);

    /**
     * @inheritdoc
     * @throws \Solbeg\FilesManager\Exceptions\NotSupportedException always
     */
    public function relativePath()
    {
        throw $this->createNotSupportedException('{class} does not support direct storing in database. You need save it before that.');
    }
}

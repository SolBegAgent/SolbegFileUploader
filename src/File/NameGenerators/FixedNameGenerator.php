<?php

namespace Solbeg\FilesManager\File\NameGenerators;

use Solbeg\FilesManager\Contracts\FileSource as FileSourceInterface;

/**
 * FixedNameGenerator saves files with a fixed name.
 * By default is `file.{extension}`.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class FixedNameGenerator extends AbstractNameGenerator
{
    /**
     * @var string
     */
    protected $filename = 'file';

    /**
     * @inheritdoc
     */
    protected function generateNewFilename(FileSourceInterface $source)
    {
        $extension = $source->extension();
        if (!$this->isValidExtension($extension)) {
            $extension = null;
        }
        return $extension === null ? $this->filename : "$this->filename.$extension";
    }
}

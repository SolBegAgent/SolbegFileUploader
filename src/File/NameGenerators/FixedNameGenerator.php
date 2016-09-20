<?php

namespace Bicycle\FilesManager\File\NameGenerators;

use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;

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
    protected $basename = 'file';

    /**
     * @inheritdoc
     */
    protected function generateNewFilename(FileSourceInterface $source)
    {
        $extension = $source->extension();
        if (!$this->isValidExtension($extension)) {
            $extension = null;
        }
        return $extension === null ? $this->basename : "$this->basename.$extension";
    }
}

<?php

namespace Bicycle\FilesManager\File\NameGenerators;

use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Helpers\File as FileHelper;

/**
 * RandomNameGenerator generates random names for each of files.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class RandomNameGenerator extends AbstractNameGenerator
{
    /**
     * @var integer
     */
    protected $length = 8;

    /**
     * @inheritdoc
     */
    protected function generateNewFilename(FileSourceInterface $source)
    {
        $extension = $source->extension();
        if (!$this->isValidExtension($extension)) {
            $extension = null;
        }
        return $this->generateRandomFilename($extension);
    }

    /**
     * Generates random name of file.
     * @param string|null $extension
     * @return string
     */
    protected function generateRandomFilename($extension = null)
    {
        $basename = FileHelper::generateRandomBasename($this->length);
        return $extension === null ? $basename : "$basename.$extension";
    }
}

<?php

namespace Solbeg\FilesManager\File\NameGenerators;

use Solbeg\FilesManager\Contracts\FileSource as FileSourceInterface;
use Solbeg\FilesManager\Helpers\File as FileHelper;

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
        $name = FileHelper::generateRandomName($this->length);
        return $extension === null ? $name : "$name.$extension";
    }
}

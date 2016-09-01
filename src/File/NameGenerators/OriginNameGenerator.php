<?php

namespace Bicycle\FilesManager\File\NameGenerators;

/**
 * OriginNameGenerator uses origin file names for generating new paths.
 * But if origin file name is not valid then random name will be generated (for security reasons).
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class OriginNameGenerator extends RandomNameGenerator
{
    /**
     * If length of origin filename is bigger than this property then random filename will be generated.
     * @var integer max length for filenames.
     * Default value is (255 - `$this->subdirsLength` - 1).
     * It means that you can keep file paths in databases in columns
     * typed as VARCHAR(255).
     */
    protected $maxLength = null;

    /**
     * @inheritdoc
     */
    public function generateNewFilename(\Bicycle\FilesManager\Contracts\FileSource $source)
    {
        $basename = $source->basename();
        $extension = $source->extension();

        if (!$this->isValidExtension($extension)) {
            $extension = null;
        }
        $filename = $extension === null ? $basename : "$basename.$extension";

        if (!$this->isValidFileName($filename) || (mb_strlen($filename, 'UTF-8') > $this->getMaxLength())) {
            if ($extension !== null && ($this->length + 1/*.*/ + mb_strlen($extension, 'UTF-8')) > $this->getMaxLength()) {
                $extension = null;
            }
            return $this->generateRandomFilename($extension);
        }
        return $filename;
    }

    /**
     * @return integer
     */
    protected function getMaxLength()
    {
        return $this->maxLength === null
            ? 255 - $this->subdirsLength - 1 /* slash */
            : $this->maxLength;
    }
}

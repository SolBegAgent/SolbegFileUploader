<?php

namespace Solbeg\FilesManager\File\NameGenerators;

use Illuminate\Support\Str;
use Solbeg\FilesManager\Contracts\FileSource as FileSourceInterface;

/**
 * SlugNameGenerator uses slugified origin file names for generating new paths.
 * But if origin file name is not valid, then random name will be generated (for security reasons).
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class SlugNameGenerator extends OriginNameGenerator
{
    /**
     * @inheritdoc
     */
    protected function fetchSourceName(FileSourceInterface $source)
    {
        $result = parent::fetchSourceName($source);
        return $this->slugify($result);
    }

    /**
     * @param string $str
     * @return string
     */
    protected function slugify($str)
    {
        return Str::slug($str, $this->separator);
    }
}

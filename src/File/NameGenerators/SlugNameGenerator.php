<?php

namespace Bicycle\FilesManager\File\NameGenerators;

use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Illuminate\Support\Str;

/**
 * SlugNameGenerator uses slugified origin file names for generating new paths.
 * But if origin file name is not valid, then random name will be generated (for security reasons).
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class SlugNameGenerator extends OriginNameGenerator
{
    /**
     * @var string
     */
    protected $separator = '-';

    /**
     * @inheritdoc
     */
    protected function fetchSourceBaseName(FileSourceInterface $source)
    {
        $result = parent::fetchSourceBaseName($source);
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
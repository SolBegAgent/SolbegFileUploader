<?php

namespace Bicycle\FilesManager\Validation;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Helpers;

/**
 * SizeValidator validates min/max file's size.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class SizeValidator extends AbstractValidator implements
    Contracts\Validators\MinSizeValidator,
    Contracts\Validators\MaxSizeValidator
{
    /**
     * @var integer|null in bytes
     */
    protected $min;

    /**
     * @var integer|null in bytes
     */
    protected $max;

    /**
     * @var string|null
     */
    protected $minMessage;

    /**
     * @var string|null
     */
    protected $maxMessage;

    /**
     * @var integer
     */
    protected $formatPrecision = 2;

    /**
     * @inheritdoc
     */
    protected function defaultConfigProperty()
    {
        return 'max';
    }

    /**
     * @param string $attr 'min'|'max'
     * @param Contracts\FileSource $source
     * @return string
     */
    protected function message($attr, Contracts\FileSource $source)
    {
        $msgProperty = "{$attr}Message";
        if ($this->{$msgProperty}) {
            return $this->{$msgProperty};
        }

        $method = 'get' . ucfirst($attr) . 'Size';
        $fileBytes = $source->size();

        return $this->trans()->trans("filesmanager::validation.$attr-size", [
            'file' => $source->name(),
            'fileBytes' => $fileBytes,
            'fileSize' => Helpers\File::formatBytes($fileBytes, $this->formatPrecision),
            'size' => $this->{$attr},
            'bytes' => $this->{$method}(false),
            'limit' => $this->{$method}(true),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function validate(Contracts\FileSource $source)
    {
        $fileSize = $source->size();
        $messages = [];

        $min = $this->getMinSize(false);
        if ($min !== null && $fileSize < $min) {
            $messages[] = $this->message('min', $source);
        }

        $max = $this->getMaxSize(false);
        if ($max !== null && $fileSize > $max) {
            $messages[] = $this->message('max', $source);
        }

        return $messages ? implode(' ', $messages) : null;
    }

    /**
     * @inheritdoc
     */
    public function getMinSize($formatted = false)
    {
        $size = Helpers\File::parseSize($this->min);
        return (!$formatted || $size === null) ? $size : Helpers\File::formatBytes($size, $this->formatPrecision);
    }

    /**
     * @inheritdoc
     */
    public function getMaxSize($formatted = false)
    {
        $size = Helpers\File::parseSize($this->max);
        return (!$formatted || $size === null) ? $size : Helpers\File::formatBytes($size, $this->formatPrecision);
    }
}

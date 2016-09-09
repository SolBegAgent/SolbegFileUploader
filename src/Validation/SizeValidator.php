<?php

namespace Bicycle\FilesManager\Validation;

use Bicycle\FilesManager\Contracts;

/**
 * SizeValidator validates min/max file's size.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class SizeValidator extends AbstractValidator
{
    /**
     * @var integer|null in bytes
     */
    public $min;

    /**
     * @var integer|null in bytes
     */
    public $max;

    /**
     * @var string|null
     */
    protected $minMessage;

    /**
     * @var string|null
     */
    protected $maxMessage;

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

        $size = $this->{$attr};
        $bytes = $this->parseSizeValue($size);
        $fileBytes = $source->size();

        return $this->trans()->trans("filesmanager::validation.$attr-size", [
            'file' => $source->name(),
            'fileBytes' => $fileBytes,
            'fileSize' => $this->formatBytes($fileBytes),
            'size' => $size,
            'bytes' => $bytes,
            'limit' => $this->formatBytes($bytes),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function validate(Contracts\FileSource $source)
    {
        $fileSize = $source->size();
        $messages = [];

        if ($this->min !== null && $fileSize < $this->min) {
            $messages[] = $this->message('min', $source);
        }
        if ($this->max !== null && $fileSize > $this->max) {
            $messages[] = $this->message('max', $source);
        }

        return $messages ? implode(' ', $messages) : null;
    }

    /**
     * @param string $sizeStr
     * @return integer in bytes
     */
    public function parseSizeValue($sizeStr)
    {
        if ($sizeStr === null || !is_scalar($sizeStr)) {
            return $sizeStr;
        } elseif (is_string($sizeStr) && preg_match('/^(\d+)([KMG])/i', $sizeStr, $matches)) {
            $bytes = (int) $matches[1];
            switch (strtoupper($matches[2])) {
                case 'G':
                    $bytes *= 1024; // no break
                case 'M':
                    $bytes *= 1024; // no break
                case 'K':
                    $bytes *= 1024; // no break
            }
            return $bytes;
        }
        return (int) $sizeStr;
    }

    /**
     * @param integer $bytes
     * @param integer $precision
     * @return string
     */
    public function formatBytes($bytes, $precision = 2)
    {
        if ($bytes > 0) {
            $bytes = (int) $bytes;
            $base = log($bytes) / log(1024);
            $suffixes = [' B', ' KB', ' MB', ' GB', ' TB'];
            $suffix = isset($suffixes[floor($base)]) ? $suffixes[floor($base)] : ' TB';
            return round(pow(1024, $base - floor($base)), $precision) . $suffix;
        } else {
            return $bytes;
        }
    }
}

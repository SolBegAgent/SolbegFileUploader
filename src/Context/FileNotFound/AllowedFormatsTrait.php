<?php

namespace Bicycle\FilesManager\Context\FileNotFound;

/**
 * AllowedFormatsTrait
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
trait AllowedFormatsTrait
{
    /**
     * @var array|string|null
     */
    protected $onlyFormats = null;

    /**
     * @var array|string|null
     */
    protected $exceptFormats = null;

    /**
     * @param \Bicycle\FilesManager\Contracts\FileNotFoundException $exception
     * @param boolean $allowOrigin
     * @return boolean
     */
    protected function isAllowedFormat($exception, $allowOrigin = true)
    {
        $format = $exception->getFormat();
        if (!$allowOrigin && $format === null) {
            return false;
        } elseif ($this->exceptFormats !== null && in_array($format, (array) $this->exceptFormats, !$allowOrigin)) {
            return false;
        } elseif ($this->onlyFormats === null) {
            return true;
        } else {
            return in_array($format, (array) $this->onlyFormats, !$allowOrigin);
        }
    }
}

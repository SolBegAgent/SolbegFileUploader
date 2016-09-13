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
     * @return boolean
     */
    protected function isAllowedFormat($exception)
    {
        $format = $exception->getFormat();
        if ($format === null) {
            return false;
        } elseif ($this->exceptFormats !== null && in_array($format, (array) $this->exceptFormats, true)) {
            return false;
        } elseif ($this->onlyFormats === null) {
            return true;
        } else {
            return in_array($format, (array) $this->onlyFormats, true);
        }
    }
}

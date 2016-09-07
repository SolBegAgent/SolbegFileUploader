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
     * @var array|null
     */
    protected $onlyFormats = null;

    /**
     * @var array|null
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
        } elseif (is_array($this->exceptFormats) && in_array($format, $this->exceptFormats, true)) {
            return false;
        } elseif (!is_array($this->onlyFormats)) {
            return true;
        } else {
            return in_array($format, $this->onlyFormats, true);
        }
    }
}

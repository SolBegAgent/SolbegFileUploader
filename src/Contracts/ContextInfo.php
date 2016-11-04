<?php

namespace Solbeg\FilesManager\Contracts;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
interface ContextInfo
{
    /**
     * @return array|null
     */
    public function allowedMimeTypes();

    /**
     * @return array|null
     */
    public function allowedExtensions();

    /**
     * @param boolean $formatted
     * @return integer|string
     */
    public function allowedMinSize($formatted = false);

    /**
     * @param boolean $formatted
     * @return integer|string
     */
    public function allowedMaxSize($formatted = false);
}

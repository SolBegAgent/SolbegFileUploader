<?php

namespace Bicycle\FilesManager\Contracts;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
interface Context
{
    /**
     * @return string the name of this context.
     */
    public function getName();

    /**
     * @return Manager the manager that is owner of this context
     */
    public function getManager();

    /**
     * @return FileSourceFactory
     */
    public function getSourceFactory();

    /**
     * @param boolean $temp whether it is meaning working with temporary files or not
     * @return Storage
     */
    public function storage($temp = false);

    /**
     * @param string $format
     */
    public function hasPredefinedFormat($format);

    /**
     * @return string[]
     */
    public function getPredefinedFormatNames();

    /**
     * @param string $format
     * @return Formatter
     */
    public function getFormatter($format);

    /**
     * @param FileNotFoundException $exception
     * @return FileSource|null source that may be used to return data or null if this case cannot be handled.
     */
    public function handleFileNotFound(FileNotFoundException $exception);
}

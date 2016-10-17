<?php

namespace Bicycle\FilesManager\Contracts;

/**
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
interface FileToArrayConverter
{
    /**
     * @param ExportableFileSource $file
     * @return array
     */
    public function convertToArray(ExportableFileSource $file);

    /**
     * @param ExportableFileSource $file
     * @return mixed
     */
    public function jsonSerialize(ExportableFileSource $file);
}

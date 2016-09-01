<?php

namespace Bicycle\FilesManager\Contracts;

interface FileNameGenerator
{
    /**
     * Generates path to root directory where all files of context will be stored.
     * 
     * @return string path to root directory.
     */
    public function generateRootDirectory();

    /**
     * Generates relative path that may be used for saving new file.
     * 
     * @param \Bicycle\FilesManager\Contracts\FileSource $source
     * @return string generated path
     */
    public function generatePathForNewFile(FileSource $source);

    /**
     * Generates relative path that may be used for saving formatted version of file.
     * 
     * @param string $format the name of format
     * @param string|null $extension the extension for formatted file version
     * @param \Bicycle\FilesManager\Contracts\FileSource $source
     * @return string generated path
     */
    public function generatePathForNewFormattedFile($format, $extension, FileSource $source);

    /**
     * Gets full path of formatted version of file.
     * 
     * @param string $format the name of format
     * @param string $relativePathToOrigin
     * @return string|null path to formatted version of file or null if file was not found.
     */
    public function getPathOfFormattedFile($format, $relativePathToOrigin);

    /**
     * Validates whether the `$path` has correct format.
     * It is meaning whether it can be potentially generated
     * by the `generatePathForNewFile()` method of this generator.
     * 
     * @param string $path the path that should be validated.
     * @return boolean whether `$path` is valid or not
     */
    public function validatePathOfOriginFile($path);
}

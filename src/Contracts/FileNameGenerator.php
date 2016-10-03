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
     * @param FileSource $source
     * @return string generated path
     */
    public function generatePathForNewFile(FileSource $source);

    /**
     * Generates relative path that may be used for saving formatted version of file.
     * 
     * @param string $relativePathToOrigin
     * @param string $format the name of format
     * @param FileSource $source source of formatted file.
     * @return string generated relative path
     */
    public function generatePathForNewFormattedFile($relativePathToOrigin, $format, FileSource $source);

    /**
     * Gets full path of original or formatted version of file.
     * 
     * @param string $relativePathToOrigin
     * @param string|null $format the name of format
     * @return string|null path to original or formatted version of file or null if file was not found.
     */
    public function getFileFullPath($relativePathToOrigin, $format = null);

    /**
     * Gets list of all saved origin files.
     * 
     * @return array Values of this array are the full paths to origin files, keys are relative paths.
     */
    public function getListOfOriginFiles();

    /**
     * Gets list of all generated formatted versions of file.
     * 
     * @param string $relativePathToOrigin
     * @return array Values of this array are the names of formats, keys are full path to formatted files.
     */
    public function getListOfFormattedFiles($relativePathToOrigin);

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

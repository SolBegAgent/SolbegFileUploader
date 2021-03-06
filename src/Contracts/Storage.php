<?php

namespace Solbeg\FilesManager\Contracts;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
interface Storage
{
    /**
     * @return string
     */
    public function name();

    /**
     * @return Context
     */
    public function context();

    /**
     * Saves new file to the the storage.
     * 
     * @param FileSource $source
     * @return StoredFileSource
     */
    public function saveNewFile(FileSource $source);

    /**
     * Converts and saves formatted version of file.
     * If formatted file has been already generated the method will regenerate it.
     * 
     * @param FileSource $source
     * @param string $format the name of formatter
     * @return boolean whether the file has been successfully generated.
     * False may be returned if current formatter may not convert this file.
     */
    public function generateFormattedFile(FileSource $source, $format);

    /**
     * Deletes file from the storage.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning deleting of origin file with all formatted versions.
     */
    public function deleteFile($relativePath, $format = null);

    /**
     * Deletes files from the storage.
     * 
     * @param string[] $relativePaths relative paths to origin files
     */
    public function deleteFiles(array $relativePaths);

    /**
     * Returns contents of the file.
     * This path may be used for reading file.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @return ContentStream file contents
     */
    public function fileContents($relativePath, $format = null);

    /**
     * Checks whether original or formatted version of file exists in this storage.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @return boolean whether file exists or not.
     */
    public function fileExists($relativePath, $format = null);

    /**
     * Returns url to file.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @return string
     */
    public function fileUrl($relativePath, $format = null);

    /**
     * Returns name of file with extension.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @return string
     */
    public function fileBasename($relativePath, $format = null);

    /**
     * Returns MIME type of file.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @return string|null
     */
    public function fileMimeType($relativePath, $format = null);

    /**
     * Returns size of file in bytes.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @return integer
     */
    public function fileSize($relativePath, $format = null);

    /**
     * Returns timestamp of the last modified time of a file.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @return integer
     */
    public function fileLastModified($relativePath, $format = null);

    /**
     * Returns list of existing formatted versions of the file.
     * 
     * @param string $relativePath relative path to origin file
     * @return array list of format names.
     */
    public function fileFormats($relativePath);

    /**
     * Returns list of existing origin files.
     * 
     * @return array list of relative paths.
     */
    public function files();
}

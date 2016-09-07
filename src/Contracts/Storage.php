<?php

namespace Bicycle\FilesManager\Contracts;

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
     * Saves new file to the context's storage.
     * 
     * @param FileSource $source
     * @return FileSource
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
     * Deletes file from context's storage.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning deleting of origin file with all formatted versions.
     */
    public function deleteFile($relativePath, $format = null);

    /**
     * Returns contents of the file.
     * This path may be used for reading file.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @return string file contents
     */
    public function fileContents($relativePath, $format = null);

    /**
     * Checks whether original or formatted version of file exists in this context.
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
     * Returns name of file.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @return string
     */
    public function fileName($relativePath, $format = null);

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
     * Returns list of existing formatted versions of the file.
     * 
     * @param string $relativePath relative path to origin file
     * @return array list of format names.
     */
    public function fileFormats($relativePath);
}

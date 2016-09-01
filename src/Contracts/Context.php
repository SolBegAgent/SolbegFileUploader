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
     * Saves new file to the context's storage.
     * 
     * @param FileSource $source
     * @param boolean $temp whether it is meaning working with temporary files or not
     * @return FileSource new source of saved file
     */
    public function saveNewFile(FileSource $source, $temp = false);

    /**
     * Deletes file from context's storage.
     * 
     * @param FileSource $source
     * @param boolean $temp whether it is meaning working with temporary files or not
     */
    public function deleteFile(FileSource $source, $temp = false);

    /**
     * Converts and saves formatted version of file.
     * If formatted file has been already generated the method will regenerate it.
     * 
     * @param FileSource $source
     * @param string $format the name of formatter
     * @param boolean $temp whether it is meaning working with temporary files or not
     * @return boolean whether the file has been successfully generated.
     * False may be returned if current formatter may not convert this file.
     */
    public function generateFormattedFile(FileSource $source, $format, $temp = false);

    /**
     * Returns full path to original file.
     * This path may be used for reading file.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @param boolean $temp whether it is meaning working with temporary files or not.
     * @return string full path to file
     */
    public function fileReadPath($relativePath, $format = null, $temp = false);

    /**
     * Checks whether original or formatted version of file exists in this context.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @param boolean $temp whether it is meaning working with temporary files or not.
     * @return boolean whether file exists or not.
     */
    public function fileExists($relativePath, $format = null, $temp = false);

    /**
     * Returns url to file.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @param boolean $temp whether it is meaning working with temporary files or not.
     * @return string
     */
    public function fileUrl($relativePath, $format = null, $temp = false);

    /**
     * Returns name of file.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @param boolean $temp whether it is meaning working with temporary files or not.
     * @return string
     */
    public function fileName($relativePath, $format = null, $temp = false);

    /**
     * Returns MIME type of file.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @param boolean $temp whether it is meaning working with temporary files or not.
     * @return string|null
     */
    public function fileMimeType($relativePath, $format = null, $temp = false);

    /**
     * Returns size of file in bytes.
     * 
     * @param string $relativePath relative path to origin file
     * @param string|null $format the name of format. Null is meaning origin file.
     * @param boolean $temp whether it is meaning working with temporary files or not.
     * @return integer
     */
    public function fileSize($relativePath, $format = null, $temp = false);
}

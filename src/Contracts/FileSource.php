<?php

namespace Bicycle\FilesManager\Contracts;

interface FileSource
{
    /**
     * Checks whether the original or formatted version of file exists in file system.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return boolean whether file exists or not.
     */
    public function exists($format = null);

    /**
     * Returns file contents.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return ContentStream
     */
    public function contents($format = null);

    /**
     * Returns relative path to the original file. This path will be stored in database.
     * 
     * @return string|null relative path or null if file does not exist.
     */
    public function relativePath();

    /**
     * Returns url to the file.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return string
     */
    public function url($format = null);

    /**
     * Returns name of the file (file name without extension).
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return string
     */
    public function filename($format = null);

    /**
     * Returns basename of the file (file name with extension).
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return string
     */
    public function basename($format = null);

    /**
     * Returns MIME type of the file.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return string|null
     */
    public function mimeType($format = null);

    /**
     * Returns size of the file in bytes.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return integer
     */
    public function size($format = null);

    /**
     * Returns timestamp of the last modified time of the file.
     * 
     * @param string|null $format the name of formatted version of the file.
     * @return integer
     */
    public function lastModified($format = null);

    /**
     * Returns extension of the file.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     * @return string|null file extension or null if file has not it.
     */
    public function extension($format = null);

    /**
     * Deletes this file.
     * 
     * @param string|null $format the name of formatted version of the file.
     * Null means original version.
     */
    public function delete($format = null);

    /**
     * Returns format names of all existing formated versions of files.
     * 
     * @return string[] format names.
     */
    public function formats();
}

<?php

namespace Bicycle\FilesManager\Helpers;

/**
 * File helper provides some helpful methods to manipulate with files.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class File
{
    /**
     * @param string $path
     * @return string
     */
    public static function basename($path)
    {
        $filename = static::filename($path);
        $pos = mb_strrpos($filename, '.', 0, 'UTF-8');
        return $pos === false ? $filename : mb_substr($filename, 0, $pos, 'UTF-8');
    }

    /**
     * @param string $path
     * @return string
     */
    public static function filename($path)
    {
        $normalized = str_replace('\\', '/', $path);
        $pos = mb_strrpos($normalized, '/', 0, 'UTF-8');
        return $pos === false ? $path : mb_substr($normalized, $pos + 1, null, 'UTF-8');
    }

    /**
     * @param string $path
     * @return string|null
     */
    public static function extension($path)
    {
        $filename = static::filename($path);
        $pos = mb_strrpos($filename, '.', 0, 'UTF-8');
        return $pos === false ? null : mb_substr($filename, $pos + 1, null, 'UTF-8');
    }

    /**
     * @param string $path
     * @return string
     */
    public static function dirname($path)
    {
        $normalized = str_replace('\\', '/', $path);
        if ($normalized === '/') {
            return DIRECTORY_SEPARATOR;
        }
        $pos = mb_strrpos($normalized, '/', 0, 'UTF-8');
        return $pos === false ? '.' : mb_substr($path, 0, $pos, 'UTF-8');
    }

    /**
     * Generates random alpha-numeric base name for a file.
     * 
     * @param integer $length the length of file name.
     * @return string generated name
     */
    public static function generateRandomBasename($length = 16)
    {
        $string = '';
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        return $string;
    }

    /**
     * Checks whether the `$filename` ends with `$extension` or contains it inside.
     * 
     * @param string $filename
     * @param string $extension
     * @param boolean $caseSensitive
     * @return boolean whether the `$filename` contains `$extension` or not.
     */
    public static function filenameContainsExtension($filename, $extension, $caseSensitive = false)
    {
        if (!$caseSensitive) {
            $extension = mb_strtolower($extension, 'UTF-8');
            $filename = mb_strtolower($filename, 'UTF-8');
        }

        $extWithDot = ".$extension";
        $extWithDotLength = mb_strlen($extWithDot, '8bit');
        return substr_compare($filename, $extWithDot, -$extWithDotLength, $extWithDotLength) === 0 ||
            mb_strrpos($filename, ".$extension.", 0, 'UTF-8') !== false;
    }
}
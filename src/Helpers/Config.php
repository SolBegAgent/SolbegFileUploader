<?php

namespace Bicycle\FilesManager\Helpers;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Array helper provides some helpful methods for internal usage in this package.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class Config
{
    /**
     * Recursively merges two or more configuration arrays.
     * 
     * @param array $config
     * @return array
     */
    public static function merge(array $config)
    {
        $args = array_slice(func_get_args(), 1);
        foreach ($args as $array) {
            foreach ($array as $key => $value) {
                if (is_int($key)) {
                    if (isset($config[$key])) {
                        $config[] = $value;
                    } else {
                        $config[$key] = $value;
                    }
                } elseif (is_array($value) && isset($config[$key]) && is_array($config[$key])) {
                    $config[$key] = static::merge($config[$key], $value);
                } elseif ($value instanceof Arrayable) {
                    $config[$key] = $value->toArray();
                } else {
                    $config[$key] = $value;
                }
            }
        }
        return $config;
    }

    /**
     * @param array $array
     * @return boolean whether the passed $array is indexed or not.
     */
    public static function isIndexed($array, $strictOrder = false)
    {
        if ($array instanceof Arrayable) {
            $array = $array->toArray();
        }
        if (!is_array($array)) {
            return false;
        }

        $index = -1;
        foreach ($array as $key => $value) {
            if (!is_int($key)) {
                return false;
            } elseif ($strictOrder && $key !== ++$index) {
                return false;
            }
        }
        return true;
    }

    /**
     * Parses params from string.
     * For example the code `Config::parse(' a = 15, b = true ')` returns array:
     * ```php ['a' => 15, 'b' => true] ```
     * 
     * 
     * @param string $string
     * @return boolean
     */
    public static function parse($string)
    {
        $parts = preg_split('/(\s*\,\s*)/u', $string, null, PREG_SPLIT_NO_EMPTY);
        $result = [];
        foreach ($parts as $part) {
            $keyValuePair = preg_split('/(\s*\=\s*)/u', $part);
            if (isset($keyValuePair[0], $keyValuePair[1])) {
                $result[$keyValuePair[0]] = static::typecastParsedValue($keyValuePair[1]);
            } else {
                $result[$part] = true;
            }
        }
        return $result;
    }

    /**
     * @param string $value
     * @return mixed
     */
    protected static function typecastParsedValue($value)
    {
        if (!is_string($value)) {
            return $value;
        } elseif ($value === '' || strcasecmp($value, 'null') === 0) {
            return null;
        } elseif (preg_match('/^\-?(?:0|[1-9]\d*)$/', $value)) {
            return (int) $value;
        } elseif (is_numeric($value)) {
            return (float) $value;
        } elseif (strcasecmp($value, 'true') === 0) {
            return true;
        } elseif (strcasecmp($value, 'false') === 0) {
            return false;
        } else {
            return $value;
        }
    }

    /**
     * @param string $value
     * @param string $pattern
     * @param boolean $caseSensitive
     * @return boolean
     */
    public static function matchWildcards($value, $pattern, $caseSensitive = true)
    {
        if ($value == $pattern) {
            return true;
        } elseif (mb_strpos($pattern, '*', 0, 'UTF-8') === false) {
            return false;
        }

        $parts = explode('*', $pattern);
        foreach ($parts as $key => $part) {
            $parts[$key] = preg_quote($part, '#');
        }

        $modificators = 'u';
        if (!$caseSensitive) {
            $modificators .= 'i';
        }

        return preg_match('#^' . implode('.*', $parts) . '\z#' . $modificators, $value);
    }
}

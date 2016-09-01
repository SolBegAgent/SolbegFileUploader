<?php

namespace Bicycle\FilesManager\Helpers;

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
                } else {
                    $config[$key] = $value;
                }
            }
        }
        return $config;
    }
}

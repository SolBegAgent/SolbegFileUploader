<?php

namespace Bicycle\FilesManager\Helpers;

/**
 * Html provides some helpful methods to generate HTML.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class Html
{
    /**
     * @param array $attributes
     */
    public static function attributes(array $attributes)
    {
        $html = '';
        foreach ($attributes as $name => $value) {
            if ($value === true) {
                $html .= ' ' . $name;
            } elseif ($value !== null && $value !== false) {
                $html .= " $name=\"" . static::encode($value) . '"';
            }
        }
        return $html;
    }

    /**
     * @param string $tag
     * @param string|null $content
     * @param array $attributes
     * @return string
     */
    public static function tag($tag, $content = '', array $attributes = [])
    {
        $htmlAttrs = static::attributes($attributes);
        return $content === null
            ? "<$tag$htmlAttrs />"
            : "<$tag$htmlAttrs>$content</$tag>";
    }

    /**
     * @param string $string
     * @return string
     */
    public static function encode($string)
    {
        return e($string);
    }
}

<?php

namespace Bicycle\FilesManager\File\Traits;

use Bicycle\FilesManager\Exceptions\ReadOnlyPropertyException;
use Bicycle\FilesManager\Exceptions\UnknownPropertyException;

/**
 * FormatsAsProperties adds "magic" properties.
 * So you may directly get url for any format using `$file->as{FormatName}`
 * Examples:
 * 
 * ```blade
 *  <?= $file->asThumb ?>
 *  <a href="{{ $file->as200x300 }}"></a>
 *  <img alt="" src="{{ $file->asSmall }}" />
 * ```
 * 
 * If your format is named as 'hyphen-words',
 * then you may use camelCase style: `$file->asHyphenWords`.
 * If your format is named as `underscored_words`,
 * then you may use the same style: `$file->as_underscored_words`.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 * 
 * Added "magic" properties:
 * @property-read string $url url to the original file
 * @property-read string $href url to the origin file
 * @property-read string $src url to the origin file
 */
trait FormatsAsProperties
{
    /**
     * @param string|null $format
     * @return string
     */
    abstract protected function url($format = null);

    /**
     * @param string $name
     * @return string
     */
    protected function normalizeFormatName($name)
    {
        return strtolower(ltrim(preg_replace('/(.)(?=[A-Z])/u', '$1-', $name), '_'));
    }

    /**
     * @param string $property
     * @return string|null|false
     */
    protected function parseFormatFromProperty($property)
    {
        if (strlen($property) > 2 && strncasecmp($property, 'as', 2) === 0) {
            $format = substr($property, 2);
            return $this->normalizeFormatName($format);
        } elseif (in_array(strtolower($property), ['url', 'href', 'src'], true)) {
            return null;
        }
        return false;
    }

    /**
     * @return string
     * @throws UnknownPropertyException
     */
    public function __get($name)
    {
        $format = $this->parseFormatFromProperty($name);
        if ($format !== false) {
            return $this->url($format);
        }
        throw new UnknownPropertyException($name, $this);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws ReadOnlyPropertyException
     * @throws UnknownPropertyException
     */
    public function __set($name, $value)
    {
        $format = $this->parseFormatFromProperty($name);
        if ($format === false) {
            throw new UnknownPropertyException($name, $this);
        }
        throw new ReadOnlyPropertyException($name, $this);
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        $format = $this->parseFormatFromProperty($name);
        if ($format === false) {
            return false;
        }
        try {
            return $this->url($format) !== null;
        } catch (\Exception $ex) {
            return false;
        } catch (\Throwable $ex) {
            return false;
        }
    }

    /**
     * @param string $name
     * @throws ReadOnlyPropertyException
     * @throws UnknownPropertyException
     */
    public function __unset($name)
    {
        $this->__set($name, null);
    }
}

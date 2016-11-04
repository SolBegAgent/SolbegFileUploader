<?php

namespace Solbeg\FilesManager\File\Traits;

use Illuminate\Support\HtmlString;

use Intervention\Image\ImageManager;

use Solbeg\FilesManager\Exceptions;
use Solbeg\FilesManager\Helpers;

/**
 * HelperMethods adds some helpful methods to file source.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
trait HelperMethods
{
    /**
     * @var \Intervention\Image\Image[]
     */
    private $imageObjects = [];

    /**
     * @param string|null
     * @return string
     */
    abstract protected function url($format = null);

    /**
     * @param string|null $format
     * @return \Solbeg\FilesManager\Contracts\ContentStream
     */
    abstract protected function contents($format = null);

    /**
     * Returns basename of the file (file name with extension).
     * 
     * @param string|null $format
     * @return string
     */
    abstract protected function basename($format = null);

    /**
     * Returns string HTML code for link to the file.
     * 
     * @param string|null $format
     * @param string|null $content
     * @param array $attributes
     * @param boolean $encodeContent
     * @return HtmlString
     */
    public function link($format = null, $content = null, array $attributes = [], $encodeContent = true)
    {
        $name = $this->basename($format);

        if ($content === null) {
            $content = Helpers\Html::encode($name);
        } elseif ($encodeContent) {
            $content = Helpers\Html::encode($content);
        }

        $attributes['href'] = $this->url($format);
        $html = Helpers\Html::tag('a', $content, $attributes);
        return new HtmlString($html);
    }

    /**
     * Returns string HTML code for img tag.
     * 
     * @param string|null $format
     * @param array $attributes
     * @return HtmlString
     */
    public function img($format = null, array $attributes = [])
    {
        $attributes['src'] = $this->url($format);
        if (!isset($attributes['alt'])) {
            $attributes['alt'] = $this->basename($format);
        }

        $html = Helpers\Html::tag('img', null, $attributes);
        return new HtmlString($html);
    }

    /**
     * @param string|null $format
     * @return \Intervention\Image\Image
     * @throws Exceptions\NotSupportedException
     */
    public function image($format = null)
    {
        if (!isset($this->imageObjects[$format])) {
            $manager = app('image');
            if (!$manager instanceof ImageManager) {
                throw new Exceptions\NotSupportedException(implode(' ', [
                    'The "intervention/image" plugin is not installed.',
                    'So the "' . __FUNCTION__ . '" method is not supported.',
                ]));
            }

            $contents = $this->contents($format);
            try {
                $img = $manager->make($contents->stream());
                $img->backup();
                $this->imageObjects[$format] = $img;
            } finally {
                $contents->close();
            }
        }

        // There is need to backup image before clone and reset after,
        // because simple cloning is working incorrectly
        // in intervention/image with transparent images
        $result = clone $this->imageObjects[$format];
        return $result->reset();
    }
}

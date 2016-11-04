<?php

namespace Solbeg\FilesManager\Formatters\Image;

use Intervention\Image\Image as InterventionImage;

use Solbeg\FilesManager\Contracts;
use Solbeg\FilesManager\Exceptions;

/**
 * WatermarkFormatter inserts watermark image to a source image.
 * 
 * It uses `insert` command of intervention/image plugin.
 * @see http://image.intervention.io/api/insert
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class WatermarkFormatter extends BaseImageFormatter
{
    /**
     * Root directory where `$img` path will be searched.
     * Default value is `resource_path()`.
     * 
     * @var string
     */
    protected $root /* = resource_path() */;

    /**
     * Relative path to file in `$root` directory.
     * 
     * @var string|InterventionImage
     */
    protected $img;

    /**
     * Possible values are:
     * 
     * - 'top-left'
     * - 'top'
     * - 'top-right'
     * - 'left'
     * - 'center'
     * - 'right'
     * - 'bottom-left'
     * - 'bottom'
     * - 'bottom-right' (default)
     * 
     * @var string
     */
    protected $position = 'bottom-right';

    /**
     * Optional relative offset of the new image on x-axis of the current image.
     * Offset will be calculated relative to the position parameter.
     * Default: 0.
     * 
     * @var integer
     */
    protected $x = 0;

    /**
     * Optional relative offset of the new image on y-axis of the current image.
     * Offset will be calculated relative to the position parameter.
     * Default: 0.
     * 
     * @var integer
     */
    protected $y = 0;

    /**
     * If assume your image for concrete dimensions, you may set this property to fixed value.
     * Then if source image differs from this value, $img watermark (and $x & $y too) will be accordingly resized.
     * 
     * @var integer|null
     */
    protected $forWidth = null;

    /**
     * If assume your image for concrete dimensions, you may set this property to fixed value.
     * Then if source image differs from this value, $img watermark (and $x & $y too) will be accordingly resized.
     * 
     * @var integer|null
     */
    protected $forHeight = null;

    /**
     * Constraint the current aspect-ratio of the watermark.
     * 
     * @var boolean
     */
    protected $aspectRatio = true;

    /**
     * Whether it need to keep the watermark from being upsized.
     * 
     * @var boolean
     */
    protected $upsize = false;

    /**
     * @inheritdoc
     */
    protected function init()
    {
        if ($this->root === null) {
            $this->root = resource_path();
        }

        if (!$this->img) {
            throw new Exceptions\InvalidConfigException("Property 'img' is required for the '{$this->getName()}' formatter.");
        } elseif (is_string($this->img)) {
            $path = rtrim($this->root, '\/') . '/' . ltrim($this->img, '\/');
            if (!is_file($path) || !is_readable($path)) {
                throw new Exceptions\InvalidConfigException("Invalid watermark '$this->img' in the '{$this->getName()}' formatter, file was not found in '$this->root' directory.");
            }
            $this->img = $path;
        }

        $this->x = (int) $this->x ?: 0;
        $this->y = (int) $this->y ?: 0;
        $this->forWidth = $this->forWidth ? (int) $this->forWidth : null;
        $this->forHeight = $this->forHeight ? (int) $this->forHeight : null;

        return parent::init();
    }

    /**
     * @param boolean $cloned
     * @return InterventionImage
     */
    protected function getWatermarkImage($cloned = true)
    {
        if (!$this->img instanceof InterventionImage) {
            $this->img = $this->getImageManager()->make($this->img);
            $this->img->backup();
        }

        if (!$cloned) {
            return $this->img;
        }

        // There is need to backup image before clone and reset after,
        // because simple cloning is working incorrectly
        // in intervention/image with transparent images
        $result = clone $this->img;
        return $result->reset();
    }

    /**
     * @param InterventionImage $image
     * @param Contracts\FileSource $source
     * @param Contracts\Storage $storage
     */
    protected function processImage(InterventionImage $image, Contracts\FileSource $source, Contracts\Storage $storage)
    {
        $watermark = $this->getNormalizedWatermark($source);
        list($x, $y) = $this->calculateCoords($watermark);
        return $image->insert($watermark, $this->position, $x, $y);
    }

    /**
     * @param Contracts\FileSource $source
     * @return InterventionImage
     */
    protected function getNormalizedWatermark($source)
    {
        if (!$this->forWidth && !$this->forHeight) {
            return $this->getWatermarkImage(false);
        }

        $newWidth = null;
        $newHeight = null;
        $watermark = $this->getWatermarkImage(true);
        $sourceImage = $this->makeImageFromSource($source);

        if ($this->forWidth) {
            $newWidth = (int) floor($watermark->width() * $sourceImage->width() / $this->forWidth);
        }
        if ($this->forHeight) {
            $newHeight = (int) floor($watermark->height() * $sourceImage->height() / $this->forHeight);
        }

        return $watermark->resize($newWidth, $newHeight, function ($constraint) {
            /* @var $constraint \Intervention\Image\Constraint */
            if ($this->aspectRatio) {
                $constraint->aspectRatio();
            }
            if ($this->upsize) {
                $constraint->upsize();
            }
        });
    }

    /**
     * @param InterventionImage $normalizedWatermark
     * @return integer[] [x, y]
     */
    protected function calculateCoords($normalizedWatermark)
    {
        $sourceWatermark = $this->getWatermarkImage(false);

        list($x, $y) = [$this->x, $this->y];
        $sourceWidth = $sourceWatermark->width();
        $sourceHeight = $sourceWatermark->height();

        if ($sourceWidth) {
            $x = (int) floor($x * $normalizedWatermark->width() / $sourceWidth);
        }
        if ($sourceHeight) {
            $y = (int) floor($y * $normalizedWatermark->height() / $sourceHeight);
        }

        return [$x, $y];
    }
}

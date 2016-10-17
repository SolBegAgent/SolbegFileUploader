<?php

namespace Bicycle\FilesManager\Formatters\Image;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Exceptions;

use Intervention\Image\Image;

/**
 * Image\ThumbnailFormatter scales down image so it is fully
 * contained within the passed dimensions.
 * The rest is filled with background that also could be configured.
 * 
 * This formatter combines 2 methods from intervention/image plugin: 'resize' & 'resizeCanvas'.
 * @see http://image.intervention.io/api/resize
 * @see http://image.intervention.io/api/resizeCanvas
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class ThumbnailFormatter extends BaseImageFormatter
{
    /**
     * The new width of the image.
     * If no width is given, method will use same value as height.
     * 
     * @var integer
     */
    protected $width;

    /**
     * The new height of the image.
     * If no height is given, method will use same value as width.
     * 
     * @var integer
     */
    protected $height;

    /**
     * Whether it need to keep image from being upsized.
     * 
     * @var boolean
     */
    protected $upsize = false;

    /**
     * @var mixed See link below to know wich color formats allowed by intervention/image plugin:
     * @see http://image.intervention.io/getting_started/formats
     * Default is white fully transparent color.
     */
    protected $background = [0xff, 0xff, 0xff, 0];

    /**
     * Set a point from where the image resizing is going to happen.
     * For example if you are setting the anchor to bottom-left this side
     * is pinned and the values of width/height will be added or
     * subtracted to the top-right corner of the image.
     *
     * @var string The possible values are:
     *  - 'top-left'
     *  - 'top'
     *  - 'top-right'
     *  - 'left'
     *  - 'center' (default)
     *  - 'right'
     *  - 'bottom-left'
     *  - 'bottom'
     *  - 'bottom-right'
     */
    protected $anchor = 'center';

    /**
     * Forced 'png' extension added here for guaranteed supporting of alpha channel.
     * 
     * @inheritdoc
     */
    protected $forceExtension = 'png';

    /**
     * @inheritdoc
     */
    protected function init()
    {
        if (!$this->width && !$this->height) {
            throw new Exceptions\InvalidConfigException('At least one of the properties "width" or "height" must be set in "' . $this->getName() . '" formatter.');
        } elseif (!$this->width) {
            $this->width = $this->height;
        } elseif (!$this->height) {
            $this->height = $this->width;
        }
        return parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function processImage(Image $image, Contracts\FileSource $source, Contracts\Storage $storage)
    {
        return $image
            ->resize($this->width, $this->height, function ($constraint) {
                /* @var $constraint \Intervention\Image\Constraint */
                $constraint->aspectRatio();
                if ($this->upsize) {
                    $constraint->upsize();
                }
            })
            ->resizeCanvas(
                $this->width,
                $this->height,
                $this->anchor,
                false,
                $this->background
            );
    }
}

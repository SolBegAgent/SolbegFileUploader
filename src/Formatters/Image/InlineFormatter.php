<?php

namespace Bicycle\FilesManager\Formatters\Image;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Exceptions\InvalidConfigException;

use Illuminate\Contracts\Container\Container;

use Intervention\Image\Image;
use Intervention\Image\ImageManager;

/**
 * InlineFormatter uses a callable to format file.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class InlineFormatter extends BaseImageFormatter
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     * @inheritdoc
     */
    public function __construct(Container $container, ImageManager $image, $name, Contracts\Context $context, array $config = [])
    {
        $this->container = $container;
        parent::__construct($image, $name, $context, $config);
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        if (!is_callable($this->callback)) {
            throw new InvalidConfigException('Invalid callable was passed in "' . $this->getName() . '" formatter.');
        }
        return parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function processImage(Image $image, Contracts\FileSource $source, Contracts\Storage $storage)
    {
        return $this->getContainer()->call($this->callback, [
            'image' => $image,
            'source' => $source,
            'storage' => $storage,
            'formatter' => $this,
        ]);
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}

<?php

namespace Bicycle\FilesManager\Formatters;

use Bicycle\FilesManager\Contracts\Context as ContextInterface;
use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Contracts\Storage as StorageInterface;
use Bicycle\FilesManager\Exceptions\InvalidConfigException;

use Illuminate\Contracts\Container\Container;

/**
 * InlineFormatter uses Closure to format file.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class InlineFormatter extends AbstractFormatter
{
    /**
     * @var \Closure
     */
    protected $closure;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     * @inheritdoc
     */
    public function __construct(Container $container, $name, ContextInterface $context, array $config = [])
    {
        $this->container = $container;
        parent::__construct($name, $context, $config);
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        if (!is_callable($this->closure)) {
            throw new InvalidConfigException('Invalid closure was passed in "' . $this->getName() . '" formatter.');
        }
        return parent::init();
    }

    /**
     * @inheritdoc
     */
    public function format(FileSourceInterface $source, StorageInterface $storage)
    {
        return $this->getContainer()->call($this->closure, [
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

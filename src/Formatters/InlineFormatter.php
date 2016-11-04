<?php

namespace Solbeg\FilesManager\Formatters;

use Illuminate\Contracts\Container\Container;

use Solbeg\FilesManager\Contracts\Context as ContextInterface;
use Solbeg\FilesManager\Contracts\FileSource as FileSourceInterface;
use Solbeg\FilesManager\Contracts\Storage as StorageInterface;
use Solbeg\FilesManager\Exceptions\InvalidConfigException;

/**
 * InlineFormatter uses a callable to format file.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class InlineFormatter extends AbstractFormatter
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
        if (!is_callable($this->callback)) {
            throw new InvalidConfigException('Invalid callable was passed in "' . $this->getName() . '" formatter.');
        }
        return parent::init();
    }

    /**
     * @inheritdoc
     */
    public function format(FileSourceInterface $source, StorageInterface $storage)
    {
        return $this->getContainer()->call($this->callback, [
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

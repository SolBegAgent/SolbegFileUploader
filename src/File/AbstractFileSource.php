<?php

namespace Bicycle\FilesManager\File;

use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;

/**
 * AbstractFileSource is the base class for all file sources.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
abstract class AbstractFileSource implements FileSourceInterface
{
    use Traits\FormatsAsProperties;
    use Traits\HelperMethods;

    /**
     * @inheritdoc
     */
    abstract public function url($format = null);

    /**
     * @inheritdoc
     */
    abstract public function contents($format = null);

    /**
     * @inheritdoc
     */
    abstract public function name($format = null);
}

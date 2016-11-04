<?php

namespace Solbeg\FilesManager\File;

use Solbeg\FilesManager\Contracts\FileSource as FileSourceInterface;

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
    abstract public function basename($format = null);
}

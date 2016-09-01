<?php

namespace Bicycle\FilesManager\Formatters;

use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Exceptions\InvalidConfigException;

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
     * @inheritdoc
     */
    public function format(FileSourceInterface $source)
    {
        if (!is_callable($this->closure)) {
            throw new InvalidConfigException('Invalid closure was passed in "' . static::class . '" formatter.');
        }
        return call_user_func($this->closure, $source, $this);
    }
}

<?php

namespace Bicycle\FilesManager\Formatters\Parsers;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Formatters\FormatterFactory;

/**
 * NumXNumParser parses strings like as '200x300'.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class NumXNumParser extends AbstractParser
{
    /**
     * @var string
     */
    protected $separator = 'x';

    /**
     * @var string
     */
    protected $formatter = FormatterFactory::ALIAS_IMAGE_THUMB;

    /**
     * @var string
     */
    protected $first = 'width';

    /**
     * @var string
     */
    protected $second = 'height';

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @inheritdoc
     */
    protected function parseName($name, Contracts\Context $context)
    {
        $separator = preg_quote($this->separator, '/');
        if (preg_match('/^([1-9]\d*)' . $separator . '([1-9]\d*)$/', $name, $matches)) {
            return array_merge([$this->formatter], [
                $this->first => (int) $matches[1],
                $this->second => (int) $matches[2],
            ], $this->params);
        }
    }
}

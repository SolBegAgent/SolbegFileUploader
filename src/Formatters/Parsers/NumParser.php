<?php

namespace Bicycle\FilesManager\Formatters\Parsers;

use Bicycle\FilesManager\Contracts;

use Bicycle\FilesManager\Formatters\FormatterFactory;

/**
 * NumParser parses string that contains only one number.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class NumParser extends AbstractParser
{
    /**
     * @var string
     */
    protected $formatter = FormatterFactory::ALIAS_IMAGE_RESIZE;

    /**
     * @var string
     */
    protected $property = 'width';

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @inheritdoc
     */
    protected function parseName($name, Contracts\Context $context)
    {
        if (preg_match('/^[1-9]\d*$/', $name)) {
            return array_merge([$this->formatter], [
                $this->property => (int) $name,
            ], $this->params);
        }
    }
}

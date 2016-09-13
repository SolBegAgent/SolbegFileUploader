<?php

namespace Bicycle\FilesManager\Contracts;

interface FormatterParser
{
    /**
     * @param string $name
     * @param Context $context
     * @return \Closure|array|string|null
     */
    public function parse($name, Context $context);
}

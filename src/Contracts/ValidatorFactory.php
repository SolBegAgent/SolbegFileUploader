<?php

namespace Solbeg\FilesManager\Contracts;

interface ValidatorFactory
{
    /**
     * Creates validator.
     * 
     * @param Context $context
     * @param string $name
     * @param mixed $config
     * @return Formatter
     */
    public function build(Context $context, $name, $config);

    /**
     * Adds new alias for any validator class.
     * 
     * @param string $alias
     * @param string $class
     */
    public function alias($alias, $class);
}

<?php

namespace Bicycle\FilesManager\Contracts;

interface FormatterFactory
{
    /**
     * Creates formatter.
     * 
     * @param ContextInterface $context
     * @param string $name
     * @param mixed $config
     * @return Formatter
     */
    public function build(ContextInterface $context, $name, $config);

    /**
     * Parses the name of format and creates formatter.
     * 
     * @param string $name the name of format
     * @return Formatter|null created formatter or null if name cannot be parsed.
     */
    public function parseFromName($name);

    /**
     * Adds new alias for any formatter class.
     * 
     * @param string $alias
     * @param string $class
     */
    public function alias($alias, $class);
}

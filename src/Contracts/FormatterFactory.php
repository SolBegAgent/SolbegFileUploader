<?php

namespace Solbeg\FilesManager\Contracts;

interface FormatterFactory
{
    /**
     * Creates formatter.
     * 
     * @param Context $context
     * @param string $name
     * @param mixed $config
     * @return Formatter
     */
    public function build(Context $context, $name, $config);

    /**
     * Adds new alias for any formatter class.
     * 
     * @param string $alias
     * @param string $class
     */
    public function alias($alias, $class);

    /**
     * @param Context $context
     * @param string $name
     * @return Formatter|null
     */
    public function parse(Context $context, $name);

    /**
     * Changes parses so you may configure your own logic of parsing format names.
     * 
     * @param array[]|string[]|\Closure[]|FormatterParser[] $parsers
     * @param boolean $replaceAll whether should be replaced all parsers or only passed in $parsers.
     */
    public function parsers(array $parsers, $replaceAll = true);
}

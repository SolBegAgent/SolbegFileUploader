<?php

namespace Solbeg\FilesManager\Contracts;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
interface ContextFactory
{
    /**
     * Creates context instance with provided config.
     * 
     * @param string $name
     * @param array $config
     * @return Context
     */
    public function make($name, array $config = []);

    /**
     * @param string $name
     * @return Context
     */
    public function resolve($name);

    /**
     * @param string $name
     * @return boolean
     */
    public function has($name);

    /**
     * Extends current manager with new config for a context.
     * 
     * @param string $name
     * @param array $config
     */
    public function extend($name, array $config);

    /**
     * Excends current manager with new config for a type.
     * 
     * @param string $type
     * @param array $config
     */
    public function configureType($type, array $config);

    /**
     * Returns the list of all defined context names.
     * This method will not return contexts defined in models,
     * because the factory cannot know about them.
     * 
     * @return string[]
     */
    public function names();
}

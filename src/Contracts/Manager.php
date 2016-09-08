<?php

namespace Bicycle\FilesManager\Contracts;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
interface Manager
{
    /**
     * Returns contexts factory or context instance by its name.
     * 
     * @param string|null $name
     * @return Context|ContextFactory
     */
    public function context($name = null);

    /**
     * Creates context instance by its configuration array.
     * 
     * @param string $name
     * @param array $config
     * @return Context
     */
    public function createContext($name, array $config = []);

    /**
     * Checks whether the manager has context with the $name or not.
     * 
     * @param string $name
     * @return boolean
     */
    public function hasContext($name);

    /**
     * Returns formatters factory.
     * 
     * @return FormatterFactory
     */
    public function formats();

    /**
     * Generates and returns filename for new temporary file.
     * 
     * @param string|null $extension
     * @return string
     */
    public function generateNewTempFilename($extension = null);
}

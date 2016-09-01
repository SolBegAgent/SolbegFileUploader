<?php

namespace Bicycle\FilesManager\Helpers;

use Bicycle\FilesManager\Exceptions\InvalidConfigException;

/**
 * ConfigurableTrait adds `configure()` method to a class.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
trait ConfigurableTrait
{
    /**
     * Configures this object according to `$config` array.
     * 
     * @param array $config
     * @throws Exceptions\InvalidConfigException
     */
    protected function configure(array $config)
    {
        $reflection = new \ReflectionClass($this);
        foreach ($config as $key => $value) {
            $normalized = str_replace(['_', '-'], ['', ''], $key);
            $setter = "set$normalized";

            if ($reflection->hasMethod($setter) && !$reflection->getMethod($setter)->isPrivate()) {
                $this->{$setter}($value);
            } elseif ($reflection->hasProperty($key) && !$reflection->getProperty($key)->isPrivate()) {
                $this->{$key} = $value;
            } else {
                $class = get_class($this);
                throw new InvalidConfigException("Unknown config key '$key' for '$class' class.");
            }
        }
    }
}

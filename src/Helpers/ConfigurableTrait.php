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

        // It need for guaranteed merging config arrays.
        // Because otherwise you may have one array with camelCase and another with snake_style,
        // so this array will be merged incorrectly.
        $checkSnakeStyle = function ($key) use ($reflection) {
            if (preg_match('/[A-Z]/', $key)) {
                throw new InvalidConfigException("Unknown config key '$key' for '$reflection->name' class. You should use `snake_style` for this property.");
            }
        };

        foreach ($config as $key => $value) {
            $normalized = lcfirst(str_replace(' ' , '', ucwords(str_replace('_', ' ', $key))));
            $setter = 'set' . ucfirst($normalized);

            if ($reflection->hasMethod($setter) && !$reflection->getMethod($setter)->isPrivate()) {
                $checkSnakeStyle($key);
                $this->{$setter}($value);
            } elseif ($reflection->hasProperty($normalized) && !$reflection->getProperty($normalized)->isPrivate()) {
                $checkSnakeStyle($key);
                $this->{$normalized} = $value;
            } else {
                throw new InvalidConfigException("Unknown config key '$key' for '$reflection->name' class.");
            }
        }
    }
}

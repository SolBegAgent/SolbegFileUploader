<?php

namespace Bicycle\FilesManager;

use Illuminate\Contracts\Foundation\Application;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class Manager implements Contracts\Manager
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Contracts\Context[]
     */
    private $contexts = [];

    /**
     * @var Contracts\ContextFactory|null
     */
    private $contextFactory;

    /**
     * @var Contracts\FormatterFactory|null
     */
    private $formattersFactory;

    /**
     * @var Contracts\ValidatorFactory|null
     */
    private $validatorsFactory;

    /**
     * @var string path to temporary directory.
     * System temp dir will be used by default.
     */
    private $tempDir;

    /**
     * Constructor for new files manager instance.
     * 
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @inheritdoc
     * @throws Exceptions\ContextNotFoundException
     */
    public function context($name = null)
    {
        if ($name === null) {
            return $this->getContextFactory();
        }

        if (!isset($this->contexts[$name])) {
            $this->contexts[$name] = $this->getContextFactory()->resolve($name);
        }
        return $this->contexts[$name];
    }

    /**
     * @inheritdoc
     * @param string $name
     * @param array $config
     * @return Contracts\Context
     * @throws Exceptions\UnknownContextTypeException
     * @throws Exceptions\InvalidConfigException
     */
    public function createContext($name, array $config = [])
    {
        if (isset($this->contexts[$name])) {
            return $this->contexts[$name];
        }
        return $this->contexts[$name] = $this->getContextFactory()->make($name, $config);
    }

    /**
     * @inheritdoc
     */
    public function hasContext($name)
    {
        return isset($this->contexts[$name]) || $this->getContextFactory()->has($name);
    }

    /**
     * @return Contracts\ContextFactory
     */
    protected function getContextFactory()
    {
        if ($this->contextFactory === null) {
            $this->contextFactory = $this->app->make(Context\ContextFactory::class, [
                'manager' => $this,
            ]);
        }
        return $this->contextFactory;
    }

    /**
     * @inheritdoc
     */
    public function formats()
    {
        if ($this->formattersFactory === null) {
            $this->formattersFactory = $this->app->make(Formatters\FormatterFactory::class, [
                'manager' => $this,
            ]);
        }
        return $this->formattersFactory;
    }

    /**
     * @inheritdoc
     */
    public function validators()
    {
        if ($this->validatorsFactory === null) {
            $this->validatorsFactory = $this->app->make(Validation\ValidatorFactory::class, [
                'manager' => $this,
            ]);
        }
        return $this->validatorsFactory;
    }

    /**
     * @return string
     */
    public function getTempDirectory()
    {
        if ($this->tempDir !== null) {
            return $this-> tempDir;
        }

        $configDir = $this->app['config']['filemanager.temp_directory'];
        if ($configDir) {
            if (!is_dir($configDir) || !is_writable($configDir)) {
                throw new Exceptions\InvalidConfigException("Invalid temporary directory: '$configDir'.");
            }
            $this->tempDir = rtrim($configDir, '\/');
        } else {
            $this->tempDir = rtrim(sys_get_temp_dir(), '\/');
        }

        return $this->tempDir;
    }

    /**
     * @return string
     */
    protected function getTempFilePrefix()
    {
        return '__php_' . strtolower(str_replace('\\', '_', static::class)) . '_';
    }

    /**
     * @inheritdoc
     */
    public function generateNewTempFilename($extension = null)
    {
        $prefix = $this->getTempFilePrefix();
        $dir = $this->getTempDirectory();
        do {
            $basename = $prefix . Helpers\File::generateRandomBasename();
            $filename = $extension === null ? $basename : "$basename.$extension";
            $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;
        } while (file_exists($fullPath));
        return $fullPath;
    }
}

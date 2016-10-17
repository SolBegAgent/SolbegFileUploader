<?php

namespace Bicycle\FilesManager\Context;

use Illuminate\Contracts\Foundation\Application;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Exceptions;
use Bicycle\FilesManager\File;
use Bicycle\FilesManager\Helpers;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class Context implements Contracts\Context, Contracts\ContextInfo
{
    use Helpers\ConfigurableTrait;

    /**
     * @var Application
     */
    protected $app;

    /**
     * The owner manager of this context.
     * 
     * @var Contracts\Manager
     */
    protected $manager;

    /**
     * The name of this context.
     * 
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $fileNotFoundHandlers = [
        FileNotFound\GenerateOnFlyHandler::class,
    ];

    /**
     * @var array config for the main storage.
     */
    protected $mainStorage = [
        'disk' => 'public',
        'name' => 'main',
        'generate_formats_on_save' => true,
        'name_generator' => [
            'class' => File\NameGenerators\RandomNameGenerator::class,
            'global_prefix' => 'uploads',
        ],
    ];

    /**
     * @var array config for temporary storage.
     */
    protected $tempStorage = [
        'disk' => 'public',
        'name' => 'temp',
        'generate_formats_on_save' => false,
        'name_generator' => [
            'class' => File\NameGenerators\SlugNameGenerator::class,
            'global_prefix' => 'temp',
        ],
    ];

    /**
     * Config for formats available to this context.
     * 
     * @var array Keys are formats names.
     * Values are formats config. Each of value may have one of the followings:
     *  - string in format 'Formatter\Class\Name:param1=value1,param2=value2'
     *  - array in format ['Formatter\Class\Name', 'param1' => 'value1', 'param2' => 'value2]
     *  - Closure in format `function (Contracts\FileSource $source, Contracts\Storage $storage, Formatters\InlineFormatter $formatter).
     * This Closure must return string path to temporary formatted file or null if file cannot be converted.
     * 
     * Without writing full class names you may use abbreviations like 'image/thumb'. See FormatterFactory for more info.
     * 
     */
    protected $formats = [];

    /**
     * @var boolean
     */
    protected $parseFormatNames = true;

    /**
     * Validators for this context.
     * 
     * @var array
     * 
     * Examples:
     * ```php
     *  'validate' => [
     *      'extensions' => 'jpg, png', // or ['jpg', 'png']
     *      'types' => 'image/*', // or ['image/jpeg', 'image/png'] or 'image/jpeg, image/png'
     *      'size' => 5 * 1024 * 1024, // or 'max = 5M, min = 1k' or ['max' => 5 * 1024 * 1024, 'min' => 1024]
     *  ],
     * ```
     */
    protected $validate = [];

    /**
     * @var string|array
     */
    protected $toArrayConverter = [
        'class' => FileToArrayConverter::class,
    ];

    /**
     * @var string|array
     */
    protected $garbageCollector = [
        'class' => GarbageCollector::class,
    ];


    /**
     * The `$gcProbability` in conjunction with `$gcDivisor`` is used to manage probability
     * that the garbage collection routine is started.
     * See `$gcDivisor` property for details.
     * 
     * 
     * @var integer
     */
    protected $gcProbability = 1;

    /**
     * The `$gcDivisor` coupled with `$gcProbability` defines the probability
     * that the garbage collection process is started on every storage initialization.
     * 
     * The probability is calculated by using `$gcProbability/$gcDivisor`,
     * e.g. 1/100 (default) means there is a 1% chance that the GC process starts on each request.
     * 
     * @var integer
     */
    protected $gcDivisor = 100;

    /**
     * @var File\FileSourceFactory|null
     */
    private $sourceFactory;

    /**
     * @var array
     */
    private $parsedFormatters = [];

    /**
     * @var boolean
     */
    private $gcProcessed = false;

    /**
     * Constructor for a new context instance.
     * 
     * @param Application $app
     * @param Contracts\Manager $manager
     * @param string $name
     * @param array $config
     * @throws Exceptions\InvalidConfigException
     */
    public function __construct(Application $app, Contracts\Manager $manager, $name, array $config = [])
    {
        $this->app = $app;
        $this->manager = $manager;
        $this->name = $name;

        $defaultConfig = [
            'main_storage' => $this->mainStorage,
            'temp_storage' => $this->tempStorage,
        ];

        $this->configure(Helpers\Config::merge($defaultConfig, $config));
    }

    /**
     * @inheritdoc
     * @return string the name of this context.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @inheritdoc
     * @return File\FileSourceFactory
     */
    public function getSourceFactory()
    {
        if ($this->sourceFactory === null) {
            $this->sourceFactory = $this->app->make(File\FileSourceFactory::class, [
                'context' => $this,
            ]);
        }
        return $this->sourceFactory;
    }

    /**
     * @inheritdoc
     */
    public function storage($temp = false)
    {
        $result = $temp ? $this->tempStorage : $this->mainStorage;
        if ($result instanceof Contracts\Storage) {
            return $result;
        }

        $property = $temp ? 'tempStorage' : 'mainStorage';
        $class = isset($result['class']) ? $result['class'] : Storage::class;
        unset($result['class']);
        $result = $this->app->make($class, [
            'context' => $this,
            'config' => $result,
        ]);

        if (!$result instanceof Contracts\Storage) {
            throw new Exceptions\InvalidConfigException("Invalid config of '$property' in the '{$this->getName()}' file context.");
        }

        $this->{$property} = $result;
        if ($temp) {
            $this->autoCollectGarbage();
        }
        return $result;
    }

    /**
     * @return Contracts\Storage
     */
    public function tempStorage()
    {
        return $this->storage(true);
    }

    /**
     * Automatically collects and cleans garbage.
     */
    protected function autoCollectGarbage()
    {
        if ($this->gcProcessed) {
            return;
        }
        $this->gcProcessed = true;

        switch (true) { // OR
            case method_exists($this->app, 'runningInConsole') && $this->app->runningInConsole(); // no break
            case $this->gcProbability < 1; // no break
            case $this->gcDivisor < 1; // no break
                return;
        }

        $rand = mt_rand(1, (int) $this->gcDivisor);
        if ($rand && $rand <= (int) $this->gcProbability) {
            $this->getGarbageCollector()->clean();
        }
    }

    /**
     * @inheritdoc
     */
    public function handleFileNotFound(Contracts\FileNotFoundException $exception)
    {
        foreach ($this->fileNotFoundHandlers as $key => $value) {
            if (!$value instanceof Contracts\FileNotFoundHandler) {
                list($class, $config) = $this->resolveFileNotFoundConfig($key, $value);
                $this->fileNotFoundHandlers[$key] = $value = $this->createFileNotFoundHandler($class, $config);
            }

            $result = $value->handle($exception);
            if ($result === null) {
                continue;
            } elseif ($result === false) {
                return null;
            } elseif ($result === true) {
                $sourceFactory = $this->getSourceFactory();
                $storedFile = $sourceFactory->storedFile($exception->getRelativePath(), $exception->getStorage());
                return $sourceFactory->formattedFile($storedFile, $exception->getFormat());
            } elseif ($result instanceof Contracts\FileSource) {
                return $result;
            } else {
                throw new \RuntimeException('Invalid result returned by "' . get_class($value) . '" handler: ' . gettype($result) . '.');
            }
        }
        return null;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return array with 2 elements:
     *  - string, class name
     *  - array, handler config
     * @throws Exceptions\InvalidConfigException
     */
    protected function resolveFileNotFoundConfig($key, $value)
    {
        if (!is_int($key) && is_array($value)) {
            return [$key, $value];
        } elseif (!is_array($value)) {
            return [$value, []];
        } elseif (!isset($value['class'])) {
            throw new Exceptions\InvalidConfigException("Invalid handler #$key config, 'class' property is required.");
        }

        $class = $value['class'];
        unset($value['class']);
        return [$class, $value];
    }

    /**
     * @param string $class
     * @param array $config
     * @return Contracts\FileNotFoundException
     * @throws Exceptions\InvalidConfigException
     */
    protected function createFileNotFoundHandler($class, array $config = [])
    {
        $result = $this->app->make($class, [
            'context' => $this,
            'config' => $config,
        ]);
        if (!$result instanceof Contracts\FileNotFoundHandler) {
            throw new Exceptions\InvalidConfigException("Invalid config of file not found handler '$class', it must implement '" . Contracts\FileNotFoundHandler::class . '\' interface.');
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hasPredefinedFormat($format)
    {
        return isset($this->formats[$format]);
    }

    /**
     * @inheritdoc
     */
    public function getPredefinedFormatNames()
    {
        return array_keys($this->formats);
    }

    /**
     * @inheritdoc
     * @throws Exceptions\FormatterNotFoundException
     */
    public function getFormatter($format)
    {
        if (isset($this->formats[$format])) {
            if (!$this->formats[$format] instanceof Contracts\Formatter) {
                $this->formats[$format] = $this->getManager()->formats()->build($this, $format, $this->formats[$format]);
            }
            return $this->formats[$format];
        }

        if ($this->parseFormatNames) {
            if (!isset($this->parsedFormatters[$format])) {
                $this->parsedFormatters[$format] = $this->getManager()->formats()->parse($this, $format) ?: false;
            }
            if ($this->parsedFormatters[$format]) {
                return $this->parsedFormatters[$format];
            }
        }

        throw new Exceptions\FormatterNotFoundException($this, $format);
    }

    /**
     * @return Contracts\Validator[]
     */
    public function getValidators()
    {
        foreach ($this->validate as $rule => $validator) {
            if (!$validator instanceof Contracts\Validator) {
                $this->validate[$rule] = $this->getManager()->validators()->build($this, $rule, $validator);
            }
        }
        return $this->validate;
    }

    /**
     * @param Contracts\FileSource $source
     * @throws Exceptions\ValidationException
     */
    public function validate(Contracts\FileSource $source)
    {
        $failed = $messages = [];
        foreach ($this->getValidators() as $rule => $validator) {
            if ($validator->skipOnError() && ($failed || $messages)) {
                continue;
            }

            $error = $validator->validate($source);
            if ($error !== null) {
                $messages[$rule] = $error;
                $failed[$rule] = $validator;
            }
        }
        if ($failed && $messages) {
            throw new Exceptions\ValidationException($this, $source, $messages, $failed);
        }
    }

    /**
     * @inheritdoc
     */
    public function getToArrayConverter()
    {
        if (!$this->toArrayConverter instanceof Contracts\FileToArrayConverter) {
            $this->toArrayConverter = $this->createToArrayConverter($this->toArrayConverter);
        }
        return $this->toArrayConverter;
    }

    /**
     * @param mixed $config
     * @return Contracts\FileToArrayConverter
     * @throws Exceptions\InvalidConfigException
     */
    protected function createToArrayConverter($config)
    {
        if (is_array($config)) {
            $class = isset($config['class']) ? $config['class'] : FileToArrayConverter::class;
            unset($config['class']);
        } else {
            list($class, $config) = [$config, []];
        }

        $result = $this->app->make($class, [
            'context' => $this,
            'config' => $config,
        ]);
        if (!$result instanceof Contracts\FileToArrayConverter) {
            throw new Exceptions\InvalidConfigException("Invalid config of `to_array_converter` in '{$this->getName()}' context.");
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getGarbageCollector()
    {
        if (!$this->garbageCollector instanceof Contracts\GarbageCollector) {
            $this->garbageCollector = $this->createGarbageCollector($this->garbageCollector);
        }
        return $this->garbageCollector;
    }

    /**
     * @param mixed $config
     * @return Contracts\GarbageCollector
     * @throws Exceptions\InvalidConfigException
     */
    protected function createGarbageCollector($config)
    {
        if (is_array($config)) {
            $class = isset($config['class']) ? $config['class'] : GarbageCollector::class;
            unset($config['class']);
        } else {
            list($class, $config) = [$config, []];
        }

        $result = $this->app->make($class, [
            'context' => $this,
            'storage' => $this->tempStorage(),
            'config' => $config,
        ]);
        if (!$result instanceof Contracts\GarbageCollector) {
            throw new Exceptions\InvalidConfigException("Invalid config of `garbage_collector` in '{$this->getName()}' context.");
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function allowedMimeTypes()
    {
        foreach ($this->getValidators() as $validator) {
            if ($validator instanceof Contracts\Validators\MimeTypeValidator) {
                return $validator->getTypes();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function allowedExtensions()
    {
        foreach ($this->getValidators() as $validator) {
            if ($validator instanceof Contracts\Validators\ExtensionValidator) {
                return $validator->getExtensions();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function allowedMinSize($formatted = false)
    {
        foreach ($this->getValidators() as $validator) {
            if ($validator instanceof Contracts\Validators\MinSizeValidator) {
                return $validator->getMinSize($formatted);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function allowedMaxSize($formatted = false)
    {
        foreach ($this->getValidators() as $validator) {
            if ($validator instanceof Contracts\Validators\MaxSizeValidator) {
                return $validator->getMaxSize($formatted);
            }
        }
    }
}

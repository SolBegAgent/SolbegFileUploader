<?php

namespace Bicycle\FilesManager\Context;

use Illuminate\Contracts\Container\Container;

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
     * @var Container
     */
    protected $container;

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
     * @var File\FileSourceFactory|null
     */
    private $sourceFactory;

    /**
     * @var array
     */
    private $parsedFormatters = [];

    /**
     * Constructor for a new context instance.
     * 
     * @param Container $container
     * @param Contracts\Manager $manager
     * @param string $name
     * @param array $config
     * @throws Exceptions\InvalidConfigException
     */
    public function __construct(Container $container, Contracts\Manager $manager, $name, array $config = [])
    {
        $this->container = $container;
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
            $this->sourceFactory = $this->container->make(File\FileSourceFactory::class, [
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
        $result = $this->container->make($class, [
            'context' => $this,
            'config' => $result,
        ]);

        if (!$result instanceof Contracts\Storage) {
            throw new Exceptions\InvalidConfigException("Invalid config of '$property' in the '{$this->getName()}' file context.");
        }
        return $this->{$property} = $result;
    }

    /**
     * @return Contracts\Storage
     */
    public function tempStorage()
    {
        return $this->storage(true);
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
        $result = $this->container->make($class, [
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

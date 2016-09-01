<?php

namespace Bicycle\FilesManager\Context;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Storage;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Exceptions;
use Bicycle\FilesManager\File;
use Bicycle\FilesManager\Helpers;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class Context implements Contracts\Context
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
     * The name of disk of laravel's filesystems.
     * 
     * @var string
     */
    protected $disk = 'public';

    /**
     * The name of disk of laravel's filesystems for storing temporary files.
     * 
     * @var string
     */
    protected $tempDisk = 'temporary';

    /**
     * @var array|string|null array (or comma separated string) of allowed mime types.
     * 
     */
    protected $mimeTypes;

    /**
     * @var array|string|null array (or comma separated string) of allowed extensions.
     */
    protected $extensions;

    /**
     * @var integer|array|null exact size in bytes or assoc array with params for size validator.
     */
    protected $size;

    /**
     * @var Contracts\FileNameGenerator|array config for name generator.
     */
    protected $nameGenerator = [
        'global_prefix' => 'uploads',
    ];

    /**
     * @var Contracts\FileNameGenerator|array config for name generator for temporary files.
     */
    protected $tempNameGenerator;

    /**
     * Config for formats available to this context.
     * 
     * @var array Keys are formats names.
     * Values are formats config. Each of value may have one of the followings:
     *  - string in format 'Formatter\Class\Name:param1=value1,param2=value2'
     *  - array in format ['Formatter\Class\Name', 'param1' => 'value1', 'param2' => 'value2]
     *  - Closure in format `function (Contracts\FileSource $source, Formatters\InlineFormatter $formatter).
     * This Closure must return string path to temporary formatted file or null if file cannot be converted.
     * 
     * Without writing full class names you may use abbreviations like 'image/thumb'. See FormatterFactory for more info.
     * 
     */
    protected $formats;

    /**
     * If this param is true the context will auto generate all formatted versions of file on fly.
     * 
     * @var mixed may be one of the followings:
     * - true (default), means all formatted versions of file will be generated,
     * - false, means formatted versions of file will not be generated,
     * - array of formats names, only these formats will be generated.
     */
    protected $generateFormatsOnSave = true;

    /**
     * Whether the context should try to parse format name when formatter was not found.
     *
     * @var boolean
     */
    protected $parseFormatName = true;

    /**
     * @var File\FileSourceFactory|null
     */
    private $sourceFactory;

    /**
     * @var string|null
     */
    private $rootDir;

    /**
     * @var string|null
     */
    private $tempRootDir;

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
        $this->configure($config);
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
     * @param boolean $temp
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    public function storage($temp = false)
    {
        return $temp ? $this->tempStorage() : Storage::disk($this->disk);
    }

    /**
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    public function tempStorage()
    {
        return Storage::disk($this->tempDisk);
    }

    /**
     * @param boolean $temp whether it is meaning working with temporary files or not.
     * @return string path to root directory where all files of the context will be stored.
     */
    public function getRootDir($temp = false)
    {
        if ($temp) {
            return $this->getTempRootDir();
        } elseif ($this->rootDir === null) {
            $this->rootDir = rtrim($this->getNamesGenerator()->generateRootDirectory(), '\/');
        }
        return $this->rootDir;
    }

    /**
     * @return string path to root directory where temporary files of the context will be stored.
     */
    public function getTempRootDir()
    {
        if ($this->tempRootDir === null) {
            $this->tempRootDir = rtrim($this->getTempNamesGenerator()->generateRootDirectory(), '\/');
        }
        return $this->tempRootDir;
    }

    /**
     * @param mixed $config
     * @param \Illuminate\Contracts\Filesystem\Filesystem $storage
     * @param string $defaultClass
     * @return Contracts\FileNameGenerator
     */
    protected function createNamesGenerator($config, $storage, $defaultClass)
    {
        if (is_array($config)) {
            $class = isset($config['class']) ? $config['class'] : $defaultClass;
            unset($config['class']);
        } elseif ($config && is_string($config)) {
            list($class, $config) = [$config, []];
        } else {
            list($class, $config) = [$defaultClass, []];
        }

        $result = $this->container->make($class, [
            'context' => $this,
            'storage' => $storage,
            'config' => $config,
        ]);
        if (!$result instanceof Contracts\FileNameGenerator) {
            throw new Exceptions\InvalidConfigException("Invalid configuration for names generator. It must be an instance of '" . Contracts\FileNameGenerator::class . '\'.');
        }
        return $result;
    }

    /**
     * @param boolean $temp whether it is meaning working with temporary files or not.
     * @return Contracts\FileNameGenerator
     */
    public function getNamesGenerator($temp = false)
    {
        if ($temp) {
            return $this->getTempNamesGenerator();
        } elseif (!$this->nameGenerator instanceof Contracts\FileNameGenerator) {
            $this->nameGenerator = $this->createNamesGenerator(
                $this->nameGenerator,
                $this->storage(false),
                File\NameGenerators\RandomNameGenerator::class
            );
        }
        return $this->nameGenerator;
    }

    /**
     * @return Contracts\FileNameGenerator
     */
    public function getTempNamesGenerator()
    {
        if (!$this->tempNameGenerator instanceof Contracts\FileNameGenerator) {
            $this->tempNameGenerator = $this->createNamesGenerator(
                $this->tempNameGenerator,
                $this->storage(true),
                File\NameGenerators\OriginNameGenerator::class
            );
        }
        return $this->tempNameGenerator;
    }

    /**
     * @inheritdoc
     */
    public function saveNewFile(Contracts\FileSource $source, $temp = false)
    {
        $readPath = $source->readPath();
        if (!is_string($content = @file_get_contents($readPath))) {
            throw new Exceptions\FileSystemException("Cannot read file: '$readPath'.");
        }

        $relativePath = $this->getNamesGenerator($temp)->generatePathForNewFile($source);
        $fullpath = $this->generateFullPathToFile($relativePath, null, $temp);

        if (!$this->storage($temp)->put($fullpath, $content)) {
            throw new Exceptions\FileSystemException("Cannot write file to path: '$fullpath'.");
        }

        $resultSource = $this->getSourceFactory()->storedFile($relativePath, $temp);
        if (!$temp && $this->generateFormatsOnSave) {
            $formats = is_array($this->generateFormatsOnSave) ? $this->generateFormatsOnSave : array_keys($this->formats);
            foreach ($formats as $format) {
                $this->generateFormattedFile($resultSource, $format, $temp);
            }
        }
        return $resultSource;
    }

    /**
     * @param \Bicycle\FilesManager\Contracts\FileSource $source
     * @return Contracts\FileSource
     */
    public function saveNewTempFile(Contracts\FileSource $source)
    {
        return $this->saveNewFile($source, true);
    }

    /**
     * @inheritdoc
     */
//    public function generateFormattedFile(Contracts\FileSource $source, $format)
//    {
//        $formatter = $this->getManager()->formats()->build($this, $format, $config)
//    }

    /**
     * Generates full path to original or formatted version of file.
     * 
     * @param string $relativePath
     * @param string|null $format the name of format or null for original file.
     * @param boolean $temp whether it is meaning working with temporary files or not.
     * @return string|null full path to file
     * Null value may be returned if $format was passed and formatted version of file has not been generated yet.
     */
    protected function generateFullPathToFile($relativePath, $format = null, $temp = false)
    {
        if ($format === null) {
            return "{$this->getRootDir($temp)}/$relativePath";
        } else {
            return $this->getNamesGenerator($temp)->getPathOfFormattedFile($format, $relativePath);
        }
    }

    /**
     * @inheritdoc
     */
    public function fileExists($relativePath, $format = null, $temp = false)
    {
        if (!$this->getNamesGenerator($temp)->validatePathOfOriginFile($relativePath)) {
            return false;
        }

        $originPath = $this->generateFullPathToFile($relativePath, null, $temp);
        if (!$this->storage($temp)->exists($originPath)) {
            return false;
        } elseif ($format === null) {
            return true;
        }

        $formattedPath = $this->generateFullPathToFile($relativePath, $format, $temp);
        return $formattedPath !== null && $this->storage($temp)->exists($formattedPath);
    }

    /**
     * @param callable $callback
     * @param string $relativePath
     * @param string $format
     * @param boolean $temp
     * @return mixed
     * @throws Exceptions\FileNotFoundException
     */
    protected function operateWithFile(callable $callback, $relativePath, $format = null, $temp = false)
    {
        $path = $this->generateFullPathToFile($relativePath, $format, $temp);
        $process = function () use ($relativePath, $format, $temp) {
            if (!$this->fileExists($relativePath, null, $temp)) {
                throw $this->createNotFoundException($relativePath, null, $temp);
            }

// @todo autogenerate (may be fire event)
            return true;
        };

        if (!$path && $format !== null && $process()) {
            $path = $this->generateFullPathToFile($relativePath, $format, $temp);
        }
        if (!$path) {
            throw $this->createNotFoundException($relativePath, $format, $temp);
        }

        try {
            return call_user_func($callback, $path, false);
        } catch (\Exception $ex) {
            if ($format === null || $this->fileExists($relativePath, $format, $temp)) {
                throw $ex;
            } elseif (!$process()) {
                throw $this->createNotFoundException($relativePath, $format, $temp, $ex);
            }
            return call_user_func($callback, $path, true);
        }
    }

    /**
     * @param string $relativePath
     * @param string|null $format
     * @param boolean $temp
     * @param \Exception|null $previous
     * @return Exceptions\FileNotFoundException
     * @return Exceptions\FormattedFileNotFoundException
     */
    private function createNotFoundException($relativePath, $format, $temp, \Exception $previous = null)
    {
        $code = $previous === null ? 0 : $previous->getCode();
        return $format === null
            ? new Exceptions\FileNotFoundException($this, $relativePath, $temp, null, $code, $previous)
            : new Exceptions\FormattedFileNotFoundException($this, $format, $relativePath, $temp, null, $code, $previous);
    }

    /**
     * @param string $operationMsg
     * @param string $relativePath
     * @param string|null $format
     * @param boolean $temp
     */
    private function createOperationException($operationMsg, $relativePath, $format, $temp)
    {
        $fileLabel = $temp ? 'temporary file' : 'file';
        if ($format !== null) {
            $fileLabel = "formatted as '$format' version of $fileLabel";
        }
        $message = rtrim($operationMsg, '.') . " for $fileLabel '$relativePath' of {$this->getName()} context.";
        throw new Exceptions\FileSystemException($message);
    }

    /**
     * @inheritdoc
     */
    public function fileReadPath($relativePath, $format = null, $temp = false)
    {
        return $this->operateWithFile(function ($path) {
            return $path;
        }, $relativePath, $format, $temp);
    }

    /**
     * @inheritdoc
     */
    public function fileName($relativePath, $format = null, $temp = false)
    {
        return $this->operateWithFile(function ($path) {
            return Helpers\File::filename($path);
        }, $relativePath, $format, $temp);
    }

    /**
     * @inheritdoc
     */
    public function fileUrl($relativePath, $format = null, $temp = false)
    {
        try {
            return $this->operateWithFile(function ($path) use ($temp) {
                return $this->storage($temp)->url($path);
            }, $relativePath, $format, $temp);
        } catch (Exceptions\FileNotFoundException $ex) {
$defaultUrl = null;
// @todo get default url (may be fire event)
            if ($defaultUrl !== null) {
                return $defaultUrl;
            }
            throw $ex;
        }
    }

    /**
     * @inheritdoc
     */
    public function fileMimeType($relativePath, $format = null, $temp = false)
    {
        return $this->operateWithFile(function ($path) use ($relativePath, $format, $temp) {
            $result = $this->storage($temp)->mimeType($path);
            if (!$result) {
                throw $this->createOperationException('Cannot detect MIME type', $relativePath, $format, $temp);
            }
            return $result;
        }, $relativePath, $format, $temp);
    }

    /**
     * @inheritdoc
     */
    public function fileSize($relativePath, $format = null, $temp = false)
    {
        return $this->operateWithFile(function ($path) use ($temp) {
            return (int) $this->storage($temp)->size($path);
        }, $relativePath, $format, $temp);
    }
}

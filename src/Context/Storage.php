<?php

namespace Solbeg\FilesManager\Context;

use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemAdapter;

use Solbeg\FilesManager\Contracts;
use Solbeg\FilesManager\Exceptions;
use Solbeg\FilesManager\File\NameGenerators\RandomNameGenerator;
use Solbeg\FilesManager\File\ContentStreams;
use Solbeg\FilesManager\Helpers;

use Symfony\Component\HttpFoundation\File\File;

/**
 * Storage class is used for pworking with files.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class Storage implements Contracts\Storage
{
    use Helpers\ConfigurableTrait;

    /**
     * The name of disk of laravel's filesystems for storing temporary files.
     * 
     * @var FilesystemAdapter|string
     */
    protected $disk = 'public';

    /**
     * @var Contracts\FileNameGenerator|array config for name generator.
     */
    protected $nameGenerator;

    /**
     * @var string 'main'|'temp'
     */
    protected $name;

    /**
     * If this param is true the storage will auto generate all formatted versions of file on fly.
     * 
     * @var mixed may be one of the followings:
     * - true (default), means all formatted versions of file will be generated,
     * - false, means formatted versions of file will not be generated,
     * - array of formats names, only these formats will be generated.
     */
    protected $generateFormatsOnSave = true;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     * @param \Solbeg\FilesManager\Context\Context $context
     * @param array $config
     */
    public function __construct(Container $container, Context $context, array $config = [])
    {
        $this->container = $container;
        $this->context = $context;
        $this->configure($config);

        if ($this->name === null && is_string($this->disk)) {
            $this->name = $this->disk;
        }
    }

    /**
     * @return string 'main'|'temp'
     */
    public function name()
    {
        if ($this->name === null) {
            $this->name = Helpers\File::basename(get_class($this->disk()));
        }
        return $this->name;
    }

    /**
     * @return Context
     */
    public function context()
    {
        return $this->context;
    }

    /**
     * @return FilesystemAdapter
     */
    public function disk()
    {
        if (!$this->disk instanceof FilesystemAdapter) {
            $this->disk = $this->container['filesystem']->disk($this->disk);
            if (!$this->disk instanceof FilesystemAdapter) {
                throw new Exceptions\InvalidConfigException("Invalid filesystem disk: '$this->disk'. \"" . static::class . '" allows only "' . FilesystemAdapter::class . '" disks.');
            }
        }
        return $this->disk;
    }

    /**
     * @return Container
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @return Contracts\FileNameGenerator
     * @throws Exceptions\InvalidConfigException
     */
    public function getNameGenerator()
    {
        if ($this->nameGenerator instanceof Contracts\FileNameGenerator) {
            return $this->nameGenerator;
        }

        $result = $this->createNameGenerator($this->nameGenerator);
        if (!$result instanceof Contracts\FileNameGenerator) {
            throw new Exceptions\InvalidConfigException("Invalid configuration for names generator. It must be an instance of '" . Contracts\FileNameGenerator::class . '\'.');
        }
        return $this->nameGenerator = $result;
    }

    /**
     * @param mixed $config
     * @return \Solbeg\FilesManager\Contracts\FileNameGenerator
     */
    protected function createNameGenerator($config)
    {
        if (is_array($config)) {
            $class = isset($config['class']) ? $config['class'] : RandomNameGenerator::class;
            unset($config['class']);
        } elseif ($config && is_string($config)) {
            list($class, $config) = [$config, []];
        } else {
            list($class, $config) = [RandomNameGenerator::class, []];
        }

        return $this->getContainer()->make($class, [
            'context' => $this->context(),
            'disk' => $this->disk(),
            'storage' => $this,
            'config' => $config,
        ]);
    }

    /**
     * @return string path to root directory where all files of the context will be stored.
     */
    public function getRootDir()
    {
        if ($this->rootDir === null) {
            $this->rootDir = rtrim($this->getNameGenerator()->generateRootDirectory(), '\/');
        }
        return $this->rootDir;
    }

    /**
     * @inheritdoc
     * @param array $options
     * @throws Exceptions\ValidationException
     * @throws Exceptions\FileSystemException
     */
    public function saveNewFile(Contracts\FileSource $source, array $options = [])
    {
        if (!isset($options['validate']) || $options['validate']) {
            $this->context()->validate($source);
        }

        $relativePath = $this->getNameGenerator()->generatePathForNewFile($source);
        $fullpath = $this->getNameGenerator()->getFileFullPath($relativePath, null);

        $contents = $source->contents();
        try {
            if (!$this->disk()->put($fullpath, $contents->stream())) {
                throw new Exceptions\FileSystemException("Cannot write file by path: '$fullpath'.");
            }
        } finally {
            $contents->close();
        }
        $resultSource = $this->context()->getSourceFactory()->storedFile($relativePath, $this);

        if ($this->generateFormatsOnSave) {
            $formats = is_array($this->generateFormatsOnSave) ? $this->generateFormatsOnSave : $this->context->getPredefinedFormatNames();
            foreach ($formats as $format) {
                if (!$this->fileExists($relativePath, $format)) {
                    $this->generateFormattedFile($resultSource, $format);
                }
            }
        }

        return $resultSource;
    }

    /**
     * @inheritdoc
     */
    public function generateFormattedFile(Contracts\FileSource $source, $format)
    {
        $formatter = $this->context()->getFormatter($format);
        $tmpPath = $formatter->format($source, $this);
        if (!$tmpPath) {
            return false;
        }

        $tmpSource = $this->context()->getSourceFactory()->simpleFile(new File($tmpPath));
        $tmpContents = $tmpSource->contents();
        try {
            $fullPath = implode('/', [
                $this->getRootDir(),
                $this->getNameGenerator()->generatePathForNewFormattedFile($source->relativePath(), $format, $tmpSource),
            ]);
            if (!$this->disk()->put($fullPath, $tmpContents->stream())) {
                throw new Exceptions\FileSystemException("Cannot write file by path: '$fullPath'.");
            }
            return true;
        } finally {
            $tmpContents->close();
            $tmpSource->delete();
        }
    }

    /**
     * @param boolean $throwExceptions
     * @return FilesCleaner
     */
    protected function createFilesCleaner($throwExceptions = true)
    {
        $result = new FilesCleaner($this->disk(), $this->getNameGenerator());
        $result->throwExceptions = $throwExceptions;
        return $result;
    }

    /**
     * @inheritdoc
     * @param array $options
     */
    public function deleteFile($relativePath, $format = null, array $options = [])
    {
        $throwExceptions = isset($options['throwExceptions']) ? $options['throwExceptions'] : true;
        $cleaner = $this->createFilesCleaner($throwExceptions);

        if ($format === null) {
            $clearFormattedFiles = isset($options['clearFormattedFiles']) ? $options['clearFormattedFiles'] : true;
            $cleaner->deleteFile($relativePath, $clearFormattedFiles);
        } else {
            $cleaner->deleteFormattedFile($relativePath, $format);
        }

        if (!isset($options['clearEmptyDirs']) || $options['clearEmptyDirs']) {
            $cleaner->clearEmptyDirs();
        }
    }

    /**
     * Deletes files from context's storage.
     * 
     * @param string[] $relativePaths relative paths to origin files
     * @param array $options
     */
    public function deleteFiles(array $relativePaths, array $options = [])
    {
        $throwExceptions = isset($options['throwExceptions']) ? $options['throwExceptions'] : true;
        $clearFormattedFiles = isset($options['clearFormattedFiles']) ? $options['clearFormattedFiles'] : true;

        $cleaner = $this->createFilesCleaner($throwExceptions);
        $cleaner->deleteFile($relativePaths, $clearFormattedFiles);

        if (!isset($options['clearEmptyDirs']) || $options['clearEmptyDirs']) {
            $cleaner->clearEmptyDirs();
        }
    }

    /**
     * @inheritdoc
     */
    public function fileExists($relativePath, $format = null)
    {
        if (!$this->getNameGenerator()->validatePathOfOriginFile($relativePath)) {
            return false;
        }

        $originPath = $this->getNameGenerator()->getFileFullPath($relativePath, null);
        if (!$this->disk()->exists($originPath)) {
            return false;
        } elseif ($format === null) {
            return true;
        }

        $formattedPath = $this->getNameGenerator()->getFileFullPath($relativePath, $format);
        return $formattedPath !== null && $this->disk()->exists($formattedPath);
    }

    /**
     * @param callable $callback
     * @param string $sourceMethod
     * @param string $relativePath
     * @param string|null $format
     * @return mixed
     * @throws Exceptions\FileNotFoundException
     */
    protected function operateWithFile(callable $callback, $sourceMethod, $relativePath, $format = null) {
        try {
            $path = $this->getNameGenerator()->getFileFullPath($relativePath, $format);
            if (!$path) {
                throw $this->createNotFoundException($relativePath, $format);
            }
            try {
                $result = call_user_func($callback, $path);
                if ($result === false) {
                    throw new Exceptions\FileSystemException("Cannot calculate '$sourceMethod' of the '$relativePath' file.");
                }
                return $result;
            } catch (\Exception $ex) {
                throw $this->createNotFoundException($relativePath, $format, $ex);
            }
        } catch (Exceptions\FileNotFoundException $notFound) {
            $handled = $this->context()->handleFileNotFound($notFound);
            if ($handled) {
                return $handled->{$sourceMethod}();
            }
            throw $notFound->getPrevious() ? $notFound->getPrevious() : $notFound;
        }
    }

    /**
     * @param string $relativePath
     * @param string $format
     * @param \Exception $previous
     * @return Exceptions\FileNotFoundException|Exceptions\FormattedFileNotFoundException
     */
    protected function createNotFoundException($relativePath, $format = null, \Exception $previous = null)
    {
        $code = $previous ? $previous->getCode() : 0;
        if ($format === null) {
            return new Exceptions\FileNotFoundException($this, $relativePath, null, $code, $previous);
        } else {
            return new Exceptions\FormattedFileNotFoundException($this, $format, $relativePath, null, $code, $previous);
        }
    }

    /**
     * @inheritdoc
     */
    public function fileUrl($relativePath, $format = null)
    {
        return $this->operateWithFile(function ($path) {
            return $this->disk()->url($path);
        }, 'url', $relativePath, $format);
    }

    /**
     * @inheritdoc
     */
    public function fileContents($relativePath, $format = null)
    {
        return $this->operateWithFile(function ($path) {
            $disk = $this->disk();
            $driver = $disk->getDriver();
            if (method_exists($driver, 'readStream')) {
                return new ContentStreams\Stream($driver->readStream($path));
            }
            return new ContentStreams\Content($disk->get($path));
        }, 'contents', $relativePath, $format);
    }

    /**
     * @inheritdoc
     */
    public function fileBasename($relativePath, $format = null)
    {
        return $this->operateWithFile(function ($path) {
            return Helpers\File::basename($path);
        }, 'basename', $relativePath, $format);
    }

    /**
     * @inheritdoc
     */
    public function fileSize($relativePath, $format = null)
    {
        return $this->operateWithFile(function ($path) {
            return $this->disk()->size($path);
        }, 'size', $relativePath, $format);
    }

    /**
     * @inheritdoc
     */
    public function fileLastModified($relativePath, $format = null)
    {
        return $this->operateWithFile(function ($path) {
            return $this->disk()->lastModified($path);
        }, 'lastModified', $relativePath, $format);
    }

    /**
     * @inheritdoc
     */
    public function fileMimeType($relativePath, $format = null)
    {
        return $this->operateWithFile(function ($path) {
            return $this->disk()->mimeType($path) ?: null;
        }, 'mimeType', $relativePath, $format);
    }

    /**
     * @inheritdoc
     */
    public function fileFormats($relativePath)
    {
        $list = $this->getNameGenerator()->getListOfFormattedFiles($relativePath);
        return array_values(array_unique(array_filter($list)));
    }

    /**
     * @inheritdoc
     */
    public function files()
    {
        $list = $this->getNameGenerator()->getListOfOriginFiles();
        return array_keys($list);
    }
}

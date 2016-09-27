<?php

namespace Bicycle\FilesManager\Context;

use Bicycle\FilesManager\Contracts\FileNameGenerator as GeneratorInterface;
use Bicycle\FilesManager\Exceptions\FileSystemException;
use Bicycle\FilesManager\Helpers\File as FileHelper;

use Illuminate\Filesystem\FilesystemAdapter;

/**
 * FilesCleaner is used to clear all or any specific files in directory.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class FilesCleaner
{
    /**
     * @var FilesystemAdapter
     */
    protected $disk;

    /**
     * @var GeneratorInterface
     */
    protected $generator;

    /**
     * @var array
     */
    protected $processedDirs = [];

    /**
     * @var boolean
     */
    public $throwExceptions = true;

    /**
     * @param FilesystemAdapter $disk
     * @param GeneratorInterface $generator
     */
    public function __construct(FilesystemAdapter $disk, GeneratorInterface $generator)
    {
        $this->disk = $disk;
        $this->generator = $generator;
    }

    /**
     * @param string $relativePath
     * @param string $format
     * @return static $this
     */
    public function deleteFormattedFile($relativePath, $format)
    {
        $oldPath = null;
        do {
            $path = $this->generator->getFileFullPath($format, $relativePath);
            if (!$path || $oldPath === $path) {
                break;
            }

            if (!$this->remove($path)) {
                break;
            }
            $oldPath = $path;
        } while (true);
        return $this;
    }

    /**
     * @param string $relativePath
     * @return static $this
     */
    public function deleteAllFormattedFiles($relativePath)
    {
        $files = $this->generator->getListOfFormattedFiles($relativePath);
        $this->remove(array_keys($files));
        return $this;
    }

    /**
     * @param string $relativePath
     * @param boolean $clearFormattedFiles
     * @return static $this
     */
    public function deleteFile($relativePath, $clearFormattedFiles = true)
    {
        if ($clearFormattedFiles) {
            $this->deleteAllFormattedFiles($relativePath);
        }

        $path = $this->generator->getFileFullPath($relativePath, null);
        $this->remove($path);

        return $this;
    }

    /**
     * Clears processed empty directories.
     * @return static $this
     */
    public function clearEmptyDirs()
    {
        $rootDir = str_replace('\\', '/', rtrim($this->generator->generateRootDirectory(), '\/')) ?: false;
        foreach (array_keys($this->processedDirs) as $dir) {
            $dir = str_replace('\\', '/', rtrim($dir, '/'));
            while (trim($dir, '.') && $dir !== $rootDir && $this->dirIsEmpty($dir)) {
                if (!$this->removeDirectory($dir)) {
                    break;
                }
                $dir = FileHelper::dirname($dir);
            }
        }

        $this->processedDirs = [];
        return $this;
    }

    /**
     * @param string $dir
     * @return boolean
     */
    protected function dirIsEmpty($dir)
    {
        return !$this->disk->files($dir, false) && !$this->disk->directories($dir, false);
    }

    /**
     * @param string|string[] $paths
     * @return boolean
     * @throws FileSystemException
     */
    protected function remove($paths)
    {
        $onException = function ($exception) {
            if ($this->throwExceptions) {
                throw $exception;
            }
            return false;
        };

        try {
            if (!$this->disk->delete($paths)) {
                throw new FileSystemException('Cannot delete file by path(s): ' . implode(', ', array_map(function ($path) {
                    return "'$path'";
                }, (array) $paths)) . '.');
            }
        } catch (\Exception $ex) {
            return $onException($ex);
        } catch (\Throwable $ex) {
            return $onException($ex);
        }

        foreach ((array) $paths as $path) {
            $this->processedDirs[FileHelper::dirname($path)] = true;
        }
        return true;
    }

    /**
     * @param string $dirPath
     * @return boolean
     * @throws FileSystemException
     */
    protected function removeDirectory($dirPath)
    {
        $onException = function ($exception) {
            if ($this->throwExceptions) {
                throw $exception;
            }
            return false;
        };

        try {
            if (!$this->disk->deleteDirectory($dirPath)) {
                throw new FileSystemException("Cannot delete directory by path: '$dirPath'.");
            }
        } catch (\Exception $ex) {
            return $onException($ex);
        } catch (\Throwable $ex) {
            return $onException($ex);
        }
        return true;
    }
}

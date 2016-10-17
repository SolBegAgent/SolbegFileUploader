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
     * @return string[]
     */
    protected function findAllFormattedFiles($relativePath)
    {
        $files = $this->generator->getListOfFormattedFiles($relativePath);
        return array_keys($files);
    }

    /**
     * @param string $relativePath
     * @return static $this
     */
    public function deleteAllFormattedFiles($relativePath)
    {
        $this->remove($this->findAllFormattedFiles($relativePath));
        return $this;
    }

    /**
     * @param string|string[] $relativePaths
     * @param boolean $clearFormattedFiles
     * @return static $this
     */
    public function deleteFile($relativePaths, $clearFormattedFiles = true)
    {
        $paths = [];

        foreach ((array) $relativePaths as $relativePath) {
            if ($clearFormattedFiles) {
                foreach ($this->findAllFormattedFiles($relativePath) as $formattedPath) {
                    $paths[$formattedPath] = true;
                }
            }

            $originPath = $this->generator->getFileFullPath($relativePath, null);
            $paths[$originPath] = true;
        }

        $this->remove(array_keys($paths));
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
        try {
            return (bool) $this->disk->deleteDirectory($dirPath);
        } catch (\Exception $ex) {
            return false;
        } catch (\Throwable $ex) {
            return false;
        }
        return true;
    }
}

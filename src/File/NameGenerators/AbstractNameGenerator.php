<?php

namespace Bicycle\FilesManager\File\NameGenerators;

use Illuminate\Contracts\Filesystem\Filesystem;

use Bicycle\FilesManager\Contracts\Context as ContextInterface;
use Bicycle\FilesManager\Contracts\FileNameGenerator as GeneratorInterface;
use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Helpers\ConfigurableTrait;
use Bicycle\FilesManager\Helpers\File as FileHelper;

/**
 * AbstractNameGenerator is the base class for name generators.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
abstract class AbstractNameGenerator implements GeneratorInterface
{
    use ConfigurableTrait;

    /**
     * @var string|null the name of global subdirectory in storage for all contexts.
     */
    protected $globalPrefix = null;

    /**
     * @var string the subdirectory for saving formatted versions of files.
     */
    protected $formatsSubdir = 'formats';

    /**
     * @var integer|null max count of files in one subderictory of `$dir` directory.
     */
    protected $maxSubdirFilesCount = 1000;

    /**
     * @var integer length for new generated subdirectories.
     */
    protected $subdirsLength = 4;

    /**
     * @var string regular expression. Used in `isValidExtension()` method.
     * Note, this expression limits extension length too.
     */
    protected $extensionRegular = '/^[a-z0-9_\-]{1,16}$/i';

    /**
     * @var array extensions that will be disallowed in principle.
     * If file has one of these extensions than file without extension will be saved.
     * Comparing with these extensions will be case insensitive.
     */
    protected $disallowedExtensions = [
        'htaccess',
        'php',
        'php3',
        'php4',
        'php5',
        'pl',
        'py',
        'jsp',
        'asp',
        'shtml',
        'sh',
        'cgi',
        'inc',
        'phtml',
    ];

    /**
     * Used for validating strings in `containsSpecialChar()` method.
     * @var string local file system special chars.
     */
    protected $specialChars = '\'\\/?<>:*|"';

    /**
     * @var boolean whether new generated names should be lowerize or not.
     */
    protected $lowerize = true;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * Generates filename for new file.
     * 
     * @param FileSourceInterface $source
     * @return string generated name for new file
     */
    abstract protected function generateNewFilename(FileSourceInterface $source);

    /**
     * @param ContextInterface $context
     * @param array $config
     */
    public function __construct(ContextInterface $context, array $config = [])
    {
        $this->context = $context;
        $this->configure($config);
    }

    /**
     * @return ContextInterface
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @inheritdoc
     */
    public function generateRootDirectory()
    {
        $dir = str_replace(['@', '\\'], '/', $this->context->getName());
        $result = preg_replace('/(?<=\\w)([A-Z])/', '/\\1', $dir);
        if ($this->globalPrefix) {
            $result = rtrim($this->globalPrefix, '\/') . "/$result";
        }
        return $this->lowerize ? strtolower($result) : $result;
    }

    /**
     * @inheritdoc
     */
    public function generatePathForNewFile(Filesystem $storage, FileSourceInterface $source)
    {
        $filename = $this->generateNewFilename($source);
        if ($this->lowerize) {
            $filename = mb_strtolower($filename, 'UTF-8');
        }

        $subdir = $this->getSubdirForNewFile($storage, $filename, $source);
        return "$subdir/$filename";
    }

    /**
     * @inheritdoc
     */
    public function generatePathForNewFormattedFile(Filesystem $storage, $format, $extension, FileSourceInterface $source)
    {
        $filename = $extension === null ? $format : "$format.$extension";
        $originPath = $source->relativePath();
        return "{$this->generateFormatsRelativeDir($originPath)}/$filename";
    }

    /**
     * @inheritdoc
     */
    public function getPathOfFormattedFile(Filesystem $storage, $format, $relativePathToOrigin)
    {
        $rootDir = rtrim($this->generateRootDirectory(), '\/');
        $formatsSubdir = $this->generateFormatsRelativeDir($relativePathToOrigin);
        foreach ($storage->files("$rootDir/$formatsSubdir") as $file) {
            if ($format === FileHelper::basename($file)) {
                return $file;
            }
        }
        return null;
    }

    /**
     * Generates relative path to subdirectory with formatted version of origin file.
     * 
     * @param string $relativePathToOrigin
     * @return string
     */
    protected function generateFormatsRelativeDir($relativePathToOrigin)
    {
        $originSubdir = FileHelper::dirname($relativePathToOrigin);
        $originFilename = FileHelper::filename($relativePathToOrigin);
        return "$originSubdir/$this->formatsSubdir/$originFilename";
    }

    /**
     * @inheritdoc
     */
    public function validatePathOfOriginFile($path)
    {
        switch (true) { // OR
            case !is_string($path); // no break
            case $path === ''; // no break
            case count($parts = explode('/', $path)) !== 2; // no break
            case !preg_match('/^[a-z0-9]+$/i', $parts[0]); // no break
            case in_array($parts[1], ['', '.', '..'], true); // no break
            case $this->containsSpecialChar($parts[1]); // no break
                return false;
        }
        return true;
    }

    /**
     * Generates subdirectory for file with passed `$filename`.
     * 
     * @param Filesystem $storage
     * @param string $filename
     * @param FileSourceInterface $source
     * @return string subdirectory name
     */
    protected function getSubdirForNewFile(Filesystem $storage, $filename, FileSourceInterface $source)
    {
        $rootDir = $this->generateRootDirectory();
        $resultDir = null;

        $subdirs = $storage->directories($rootDir);
        if (count($subdirs) > 1) {
            shuffle($subdirs);
        }

        foreach ($subdirs as $subdir) {
            if ($storage->exists("$subdir/$filename")) {
                continue;
            } elseif (count($storage->files($subdir)) >= $this->maxSubdirFilesCount) {
                continue;
            }
            $resultDir = FileHelper::filename($subdir);
            break;
        }

        while ($resultDir === null) {
            $resultDir = FileHelper::generateRandomBasename($this->subdirsLength);
            if ($this->lowerize) {
                $resultDir = strtolower($resultDir);
            }
            if ($storage->exists("$rootDir/$resultDir")) {
                $resultDir = null;
            }
        }
        return $resultDir;
    }

    /**
     * Checks input string for special chars.
     * @param string $str
     * @return boolean whether input string contains special chars or not.
     * @see $specialChars
     */
    protected function containsSpecialChar($str)
    {
        $chars = $this->specialChars;
        for ($i = 0, $length = strlen($chars); $i < $length; ++$i) {
            if (strpos($str, $chars[$i]) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validates file extension.
     * 
     * @param string $extension the extension that must be validated.
     * @return boolean whether this extension can be used for saving new file.
     */
    protected function isValidExtension($extension)
    {
        if (!is_string($extension) || $extension === '' || mb_strrpos($extension, '.', 0, 'UTF-8') !== false) {
            return false;
        } elseif (!preg_match($this->extensionRegular, $extension)) {
            return false;
        } elseif ($this->containsSpecialChar($extension)) {
            return false;
        }

        $extensionLoweredCase = mb_strtolower($extension, 'UTF-8');
        foreach ($this->disallowedExtensions as $disallowedExt) {
            if (mb_strtolower($disallowedExt, 'UTF-8') === $extensionLoweredCase) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validates file name.
     * @param string $filename the filename that must be validated.
     * @return boolean whether this filename can be used for saving new file.
     */
    protected function isValidFileName($filename)
    {
        if (!is_string($filename) || in_array($filename, ['', '.', '..'], true)) {
            return false;
        } elseif ($this->containsSpecialChar($filename)) {
            return false;
        }

        foreach ($this->disallowedExtensions as $disallowedExt) {
            if (FileHelper::filenameContainsExtension($filename, $disallowedExt)) {
                return false;
            }
        }
        return true;
    }
}

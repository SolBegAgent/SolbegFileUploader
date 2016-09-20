<?php

namespace Bicycle\FilesManager\File\NameGenerators;

use Illuminate\Filesystem\FilesystemAdapter;

use Bicycle\FilesManager\Contracts\Context as ContextInterface;
use Bicycle\FilesManager\Contracts\FileNameGenerator as GeneratorInterface;
use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Exceptions\InvalidConfigException;
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
     * @var integer length for new generated subdirectories, that are storing files subdirectories.
     * The subdirs will have format \d{length}, e.g. `0035`.
     */
    protected $commonSubdirLength = 4;

    /**
     * @var integer|null max count of files in one subderictory of `$dir` directory.
     */
    protected $maxCommonSubdirFilesCount = 1000;

    /**
     * @var integer length for new generated subdirectories, that are storing origin file.
     */
    protected $fileSubdirLength = 16;

    /**
     * Suffix that will be added to subdirectory that stores formatted versions of file.
     * 
     * @var string
     */
    protected $formatSubdirSuffix = '-format';

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
     * Used in `normalizeCase()` method.
     */
    protected $lowerize = true;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var FilesystemAdapter
     */
    private $disk;

    /**
     * Generates filename for new file.
     * 
     * @param FileSourceInterface $source
     * @return string generated name for new file
     */
    abstract protected function generateNewFilename(FileSourceInterface $source);

    /**
     * @param ContextInterface $context
     * @param FilesystemAdapter $disk
     * @param array $config
     */
    public function __construct(ContextInterface $context, FilesystemAdapter $disk, array $config = [])
    {
        $this->context = $context;
        $this->disk = $disk;
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
     * @return FilesystemAdapter
     */
    public function getDisk()
    {
        return $this->disk;
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
        return $this->normalizeCase($result);
    }

    /**
     * Generates common subdirectory for new file.
     * 
     * @return string subdirectory name
     */
    protected function getCommonSubdirForNewFile()
    {
        $rootDir = $this->generateRootDirectory();

        $maxSubdirNum = 0;
        foreach ($this->getDisk()->directories($rootDir) as $subdirPath) {
            $subdir = FileHelper::filename($subdirPath);
            if ($this->isValidCommonSubdir($subdir) && intval($subdir) > $maxSubdirNum) {
                $maxSubdirNum = (int) $subdir;
            }
        }
        $maxSubdir = $this->normalizeCommonSubdir($maxSubdirNum);

        if ($this->calcSubdirsCount("$rootDir/$maxSubdir") < $this->maxCommonSubdirFilesCount) {
            return $maxSubdir;
        }
        return $this->normalizeCommonSubdir($maxSubdirNum + 1);
    }

    /**
     * Generates subdirectory where origin file will be stored.
     * 
     * @param string $commonSubdir
     * @return string generated subdir name
     */
    protected function generateNewFileSubdir($commonSubdir)
    {
        $rootDir = $this->generateRootDirectory();
        $disk = $this->getDisk();
        do {
            $random = FileHelper::generateRandomBasename($this->fileSubdirLength);
            $result = $this->normalizeCase($random);
        } while($disk->exists("$rootDir/$commonSubdir/$result"));
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function generatePathForNewFile(FileSourceInterface $source)
    {
        $filename = $this->normalizeCase($this->generateNewFilename($source));
        do {
            $filename = $this->cutFormatSubdirSuffix($oldFilename = $filename, false);
        } while ($filename !== $oldFilename);

        $commonSubdir = $this->getCommonSubdirForNewFile();
        $fileSubdir = $this->generateNewFileSubdir($commonSubdir);
        return "$commonSubdir/$fileSubdir/$filename";
    }

    /**
     * @inheritdoc
     */
    public function generatePathForNewFormattedFile($relativePathToOrigin, $format, FileSourceInterface $source)
    {
        if (!$this->isValidFormatName($format)) {
            throw new InvalidConfigException(implode(' ', [
                "Invalid name of format: '$format'.",
                FileHelper::basename(static::class) . ' allows only names that are valid directory names.',
            ]));
        }

        $originSubdir = FileHelper::dirname($relativePathToOrigin);
        $originBasename = FileHelper::basename($relativePathToOrigin);
        $extension = $this->normalizeCase($source->extension());
        return implode('/', [
            $originSubdir,
            $format . $this->formatSubdirSuffix,
            $extension === null ? $originBasename : "$originBasename.$extension",
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getFileFullPath($relativePathToOrigin, $format = null)
    {
        $rootDir = rtrim($this->generateRootDirectory(), '\/');
        if ($format === null) {
            return "$rootDir/$relativePathToOrigin";
        }

        $originSubdir = FileHelper::dirname($relativePathToOrigin);
        $originBasename = FileHelper::basename($relativePathToOrigin);
        foreach ($this->getDisk()->files("$rootDir/$originSubdir/$format$this->formatSubdirSuffix") as $file) {
            if ($originBasename === FileHelper::basename($file)) {
                return $file;
            }
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getListOfFormattedFiles($relativePathToOrigin)
    {
        $rootDir = rtrim($this->generateRootDirectory(), '\/');
        $originSubdir = FileHelper::dirname($relativePathToOrigin);
        $originBasename = FileHelper::basename($relativePathToOrigin);

        $disk = $this->getDisk();
        $result = [];
        foreach ($disk->directories("$rootDir/$originSubdir") as $formatPath) {
            $format = $this->cutFormatSubdirSuffix(FileHelper::filename($formatPath));
            if ($format === null) {
                continue;
            }
            foreach ($disk->files($formatPath) as $file) {
                if ($originBasename === FileHelper::basename($file)) {
                    $result[$file] = $format;
                }
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function validatePathOfOriginFile($path)
    {
        switch (true) { // OR
            case !is_string($path); // no break
            case $path === ''; // no break
            case count($parts = explode('/', $path)) !== 3; // no break
            case !$this->isValidCommonSubdir($parts[0], false); // no break
            case !$this->isValidFileSubdir($parts[1], false); // no break
            case in_array($parts[2], ['', '.', '..'], true); // no break
            case $this->containsSpecialChar($parts[2]); // no break
                return false;
        }
        return true;
    }

    /**
     * @param string $directory
     * @return integer
     */
    protected function calcSubdirsCount($directory)
    {
        $disk = $this->getDisk();
        return (int) array_sum([
            count($disk->files($directory)),
            count($disk->directories($directory)),
        ]);
    }

    /**
     * @param string|integer $subdir
     * @return string
     */
    protected function normalizeCommonSubdir($subdir)
    {
        return str_pad($subdir, $this->commonSubdirLength, '0', STR_PAD_LEFT);
    }

    /**
     * @param string $name
     * @return string
     */
    protected function normalizeCase($name)
    {
        return $this->lowerize ? mb_strtolower($name, 'UTF-8') : $name;
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
     * @param string $formatSubdir
     * @param boolean $required
     * @return string|null
     */
    protected function cutFormatSubdirSuffix($formatSubdir, $required = true)
    {
        $suffix = $this->formatSubdirSuffix;
        $suffixLength = strlen($suffix);
        if (!$suffixLength) {
            return $formatSubdir;
        } elseif (substr_compare($formatSubdir, $suffix, -$suffixLength) !== 0) {
            return $required ? null : $formatSubdir;
        }

        $result = substr($formatSubdir, 0, -$suffixLength);
        return $result === '' ? ($required ? null : $formatSubdir) : $result;
    }

    /**
     * @param string $subdir
     * @param boolean $checkLength
     * @return boolean
     */
    protected function isValidCommonSubdir($subdir, $checkLength = true)
    {
        $pattern = '/^\d' . ($checkLength ? "{{$this->commonSubdirLength}}" : '+') . '$/';
        return (bool) preg_match($pattern, $subdir);
    }

    /**
     * @param string $subdir
     * @param boolean $checkLength
     * @return boolean
     */
    protected function isValidFileSubdir($subdir, $checkLength = true)
    {
        $pattern = '/^[a-z0-9]' . ($checkLength ? "{{$this->fileSubdirLength}}" : '+') . '$/i';
        return (bool) preg_match($pattern, $subdir);
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

    /**
     * Validates formatter name.
     * @param string $formatName
     * @return boolean whether the name is valid or not.
     */
    protected function isValidFormatName($formatName)
    {
        if (!is_string($formatName) || in_array($formatName, ['', '.', '..'], true)) {
            return false;
        }
        return !$this->containsSpecialChar($formatName);
    }
}

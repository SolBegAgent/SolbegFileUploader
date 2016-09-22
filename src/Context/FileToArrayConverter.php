<?php

namespace Bicycle\FilesManager\Context;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Helpers;

/**
 * FileToArrayConverter
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class FileToArrayConverter implements Contracts\FileToArrayConverter
{
    use Helpers\ConfigurableTrait;

    /**
     * Possible values:
     *  - 'url'|'href'|'src', urls will be absolute by default
     *  - 'absoluteUrl'
     *  - 'size'
     *  - 'mimeType'
     *  - 'name'
     *  - 'basename'
     *  - 'extension'
     *  - 'width'
     *  - 'height'
     *  - 'relativePath'|'path'
     *  - 'formats'
     * 
     * @var string[]|string
     */
    protected $originExport = ['url', 'path', 'formats'];

    /**
     * Possible values:
     *  - 'url'|'href'|'src', urls will be absolute by default
     *  - 'absoluteUrl'
     *  - 'size'
     *  - 'mimeType'
     *  - 'name'
     *  - 'basename'
     *  - 'extension'
     *  - 'width'
     *  - 'height'
     * 
     * @var string[]|string
     */
    protected $formatExport = 'url';

    /**
     * @var boolean whether urls must be an absolute or not.
     * If false then only `absoluteUrl` export key will return absolute url.
     */
    protected $absoluteUrls = true;

    /**
     * @var boolean|null
     */
    protected $secureUrls = null;

    /**
     * @var string[]|null
     */
    protected $formatNames = null;

    /**
     * @var boolean
     */
    protected $appendExistingFormats = true;

    /**
     * @var Contracts\Context
     */
    private $context;

    /**
     * @param Contracts\Context $context
     * @param array $config
     */
    public function __construct(Contracts\Context $context, array $config = [])
    {
        $this->context = $context;
        $this->configure($config);
    }

    /**
     * @inheritdoc
     */
    public function convertToArray(Contracts\ExportableFileSource $file)
    {
        if (!$file->isStored()) {
            return [];
        }
        return $this->generateFileData($file);
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(Contracts\ExportableFileSource $file)
    {
        if (!$file->isStored()) {
            return null;
        }

        $result = $this->generateFileData($file);
        if (isset($result['formats']) && $result['formats'] === []) {
            $result['formats'] = new \stdClass;
        }
        return $result;
    }

    /**
     * @param Contracts\ExportableFileSource $file
     * @param string|null $format
     * @return array
     */
    protected function generateFileData(Contracts\ExportableFileSource $file, $format = null)
    {
        $exportKeys = $format === null ? $this->originExport : $this->formatExport;

        if (!is_array($exportKeys)) {
            return $this->fetchFileDataByKey($file, $exportKeys, $format);
        }

        $result = [];
        foreach ($exportKeys as $key) {
            $result[$key] = $this->fetchFileDataByKey($file, $key, $format);
        }
        return $result;
    }

    /**
     * @param Contracts\ExportableFileSource $file
     * @param string $key
     * @param string|null $format
     * @return mixed
     */
    protected function fetchFileDataByKey(Contracts\ExportableFileSource $file, $key, $format = null)
    {
        $normalized = $this->normalizeKey($key);

        if ($this->absoluteUrls && (in_array($normalized, ['url', 'href', 'src'], true))) {
            $normalized = 'absoluteUrl';
        }

        if ($format === null) {
            if ($normalized === 'formats') {
                return $this->generateFormatsData($file);
            } elseif ($normalized === 'relativePath' || $normalized === 'path') {
                return $file->relativePath();
            }
        }

        if ($normalized === 'absoluteUrl') {
            return $file->absoluteUrl($format, $this->secureUrls);
        } elseif ($normalized === 'width' || $normalized === 'height') {
            return $file->image($format)->{$normalized}();
        } else {
            return $file->{$normalized}($format);
        }
    }

    /**
     * @param string $key
     * @return string
     */
    protected function normalizeKey($key)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $key))));
    }

    /**
     * @param Contracts\ExportableFileSource $file
     * @return array
     */
    protected function generateFormatsData(Contracts\ExportableFileSource $file)
    {
        $result = [];
        $formatNames = $this->getFormatNames($file);
        foreach ($formatNames as $format) {
            $result[$format] = $this->generateFileData($file, $format);
        }
        return $result;
    }

    /**
     * @param Contracts\ExportableFileSource $file
     * @return string[]
     */
    public function getFormatNames(Contracts\ExportableFileSource $file)
    {
        $formats = $this->formatNames;
        if ($formats === null) {
            $formats = $this->context()->getPredefinedFormatNames();
        }
        if ($this->appendExistingFormats) {
            $formats = array_unique(array_merge($formats, $file->formats()));
        }
        return $formats;
    }

    /**
     * @return Contracts\Context
     */
    public function context()
    {
        return $this->context;
    }
}

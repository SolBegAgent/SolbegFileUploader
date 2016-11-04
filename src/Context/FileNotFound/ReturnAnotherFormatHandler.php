<?php

namespace Solbeg\FilesManager\Context\FileNotFound;

use Solbeg\FilesManager\Contracts;
use Solbeg\FilesManager\Helpers;

/**
 * ReturnAnotherFormatHandler return another formatted version of source file.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class ReturnAnotherFormatHandler implements Contracts\FileNotFoundHandler
{
    use Helpers\ConfigurableTrait, AllowedFormatsTrait, AllowedStoragesTrait;

    /**
     * @var string|null
     */
    protected $returnFormat = null;

    /**
     * @var boolean
     */
    protected $onlyIfExists = false;

    /**
     * @var boolean
     */
    protected $skipWhenRequestedExists = true;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->configure($config);
    }

    /**
     * @inheritdoc
     */
    public function handle(Contracts\FileNotFoundException $exception)
    {
        if (!$this->isAllowedFormat($exception, false) || !$this->isAllowedStorage($exception)) {
            return null;
        } elseif ($exception->getFormat() === $this->returnFormat) {
            return null;
        } elseif (!$exception->isOriginFileExists()) {
            return null;
        } elseif ($this->skipWhenRequestedExists && $exception->isRequestedFileExists()) {
            return null;
        }

        $storage = $exception->getStorage();
        $relativePath = $exception->getRelativePath();

        if ($this->onlyIfExists && $this->returnFormat !== null) {
            if (!$storage->fileExists($relativePath, $this->returnFormat)) {
                return null;
            }
        }

        $sourceFactory = $storage->context()->getSourceFactory();
        $storedFile = $sourceFactory->storedFile($relativePath, $storage);
        return $sourceFactory->formattedFile($storedFile, $this->returnFormat);
    }
}

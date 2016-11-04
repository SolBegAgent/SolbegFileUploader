<?php

namespace Solbeg\FilesManager\Context\FileNotFound;

use Solbeg\FilesManager\Contracts;
use Solbeg\FilesManager\Helpers;

/**
 * GenerateOnFlyHandler tries to generate missed formatted file on fly.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class GenerateOnFlyHandler implements Contracts\FileNotFoundHandler
{
    use Helpers\ConfigurableTrait, AllowedFormatsTrait, AllowedStoragesTrait;

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
        } elseif (!$exception->isOriginFileExists() || $exception->isRequestedFileExists()) {
            return null;
        }

        $storage = $exception->getStorage();
        $source = $storage->context()->getSourceFactory()->storedFile($exception->getRelativePath(), $storage);

        $generated = $storage->generateFormattedFile($source, $exception->getFormat()) && $exception->isRequestedFileExists(true);
        return $generated ? true : null;
    }
}

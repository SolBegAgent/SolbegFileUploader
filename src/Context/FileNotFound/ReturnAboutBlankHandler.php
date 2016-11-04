<?php

namespace Solbeg\FilesManager\Context\FileNotFound;

use Solbeg\FilesManager\Contracts;
use Solbeg\FilesManager\Helpers;

/**
 * ReturnAboutBlankHandler returns `about:blank`.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class ReturnAboutBlankHandler implements Contracts\FileNotFoundHandler
{
    use Helpers\ConfigurableTrait, AllowedFormatsTrait, AllowedStoragesTrait;

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
        if (!$this->isAllowedFormat($exception) || !$this->isAllowedStorage($exception)) {
            return null;
        } elseif ($this->skipWhenRequestedExists && $exception->isRequestedFileExists()) {
            return null;
        }

        $sourceFactory = $exception->getStorage()->context()->getSourceFactory();
        return $sourceFactory->contentFile('', '', 'application/x-empty', 'about:blank');
    }
}

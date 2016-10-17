<?php

namespace Bicycle\FilesManager\Context\FileNotFound;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Helpers;

/**
 * ReturnUrlHandler returns predefined url.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class ReturnUrlHandler implements Contracts\FileNotFoundHandler
{
    use Helpers\ConfigurableTrait, AllowedStoragesTrait;

    /**
     * @var string|null
     */
    protected $origin;

    /**
     * @var array
     */
    protected $formats = [];

    /**
     * @var string
     */
    protected $default;

    /**
     * @var Contracts\FileSource[]
     */
    private $sources = [];

    /**
     * @var boolean
     */
    protected $skipWhenRequestedExists = true;

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
    public function handle(Contracts\FileNotFoundException $exception)
    {
        if (!$this->isAllowedStorage($exception)) {
            return null;
        } elseif ($this->skipWhenRequestedExists && $exception->isRequestedFileExists()) {
            return null;
        }
        return $this->getSource($exception->getFormat());
    }

    /**
     * @param string|null $format
     * @return Contracts\FileSource|null
     */
    protected function getSource($format = null)
    {
        if (!isset($this->sources[$format])) {
            $url = $this->getUrl($format);
            $this->sources[$format] = $url
                ? $this->context()->getSourceFactory()->urlFile($url)
                : false;
        }
        return $this->sources[$format] ?: null;
    }

    /**
     * @param string|null $format
     * @return string|null
     */
    protected function getUrl($format = null)
    {
        if ($format === null) {
            if ($this->origin !== null) {
                return $this->origin;
            }
        } elseif (isset($this->formats[$format])) {
            return $this->formats[$format];
        }
        return $this->default === null ? null : $this->default;
    }

    /**
     * @return Contracts\Context
     */
    protected function context()
    {
        return $this->context;
    }
}

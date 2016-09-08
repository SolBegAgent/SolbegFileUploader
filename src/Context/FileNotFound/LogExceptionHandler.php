<?php

namespace Bicycle\FilesManager\Context\FileNotFound;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Exceptions;
use Bicycle\FilesManager\Helpers;

use Psr\Log\LoggerInterface;

/**
 * LogExceptionHandler
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class LogExceptionHandler implements Contracts\FileNotFoundHandler
{
    use Helpers\ConfigurableTrait, AllowedFormatsTrait, AllowedStoragesTrait;

    /**
     * @var string
     */
    protected $level = 'error';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param array $config
     * @throws Exceptions\InvalidConfigException
     */
    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->configure($config);

        if ($this->level !== 'error' && !method_exists($logger, $this->level)) {
            throw new Exceptions\InvalidConfigException("Invalid logger level: '$this->level'.");
        }
    }

    /**
     * @inheritdoc
     */
    public function handle(Contracts\FileNotFoundException $exception)
    {
        if (!$this->isAllowedFormat($exception) || !$this->isAllowedStorage($exception)) {
            return null;
        }

        $this->logger()->{$this->level}($exception instanceof \Exception ? $exception : $exception->getMessage(), [
            'relativePath' => $exception->getRelativePath(),
            'format' => $exception->getFormat(),
            'storage' => $exception->getStorage()->name(),
            'context' => $exception->getStorage()->context()->getName(),
        ]);
    }

    /**
     * @return LoggerInterface
     */
    protected function logger()
    {
        return $this->logger;
    }
}
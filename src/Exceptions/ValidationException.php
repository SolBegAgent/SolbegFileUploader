<?php

namespace Bicycle\FilesManager\Exceptions;

use Bicycle\FilesManager\Contracts;

class ValidationException extends \Exception implements Contracts\ValidationException
{
    /**
     * @var Contracts\Context
     */
    private $context;

    /**
     * @var Contracts\FileSource
     */
    private $source;

    /**
     * @var Contracts\Validator[]
     */
    private $failed;

    /**
     * @var string[]
     */
    private $messages;

    /**
     * @param \Bicycle\FilesManager\Contracts\Context $context
     * @param \Bicycle\FilesManager\Contracts\FileSource $source
     * @param Contracts\Validator[] $failedValidators in rule => Validator format
     */
    public function __construct(
        Contracts\Context $context,
        Contracts\FileSource $source,
        array $messages,
        array $failedValidators
    ) {
        $this->context = $context;
        $this->source = $source;
        $this->messages = $messages;
        $this->failed = $failedValidators;

        parent::__construct($this->generateMessage());
    }

    /**
     * @return string
     */
    protected function generateMessage()
    {
        $messages = [];
        foreach ($this->getMessages() as $message) {
            $messages[] = rtrim(trim($message), '.') . '.';
        }
        return implode(' ', $messages);
    }

    /**
     * @inheritdoc
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @inheritdoc
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @inheritdoc
     */
    public function getFailedValidators()
    {
        return $this->failed;
    }

    /**
     * @inheritdoc
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @inheritdoc
     */
    public function getFirstMessage()
    {
        $messages = $this->getMessages();
        return reset($messages) ?: null;
    }
}

<?php

namespace Bicycle\FilesManager\Exceptions;

use Bicycle\FilesManager\Contracts\Context;

class FormatterNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $context;

    /**
     * @var string
     */
    private $formatterName;

    /**
     * @param Context $context
     * @param string $formatterName
     * @param string|null $message
     * @param integer $code
     * @param \Exception $previous
     */
    public function __construct(Context $context, $formatterName, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->context = $context;
        $this->formatterName = $formatterName;
        if ($message === null || $message === '') {
            $message = $this->generateMessage();
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getFormatterName()
    {
        return $this->formatterName;
    }

    /**
     * @return string
     */
    protected function generateMessage()
    {
        $contextName = $this->getContext()->getName();
        $formatterName = $this->getFormatterName();
        return implode(' ', [
            "Formatter '$formatterName' was not found",
            "and cannot be parsed in '$contextName' file context.",
            "May be you forgot to add it in your `app/config/filecontexts/$contextName.php` config.",
        ]);
    }
}

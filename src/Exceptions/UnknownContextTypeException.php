<?php

namespace Solbeg\FilesManager\Exceptions;

class UnknownContextTypeException extends \Exception
{
    /**
     * @var string
     */
    private $contextType;

    /**
     * @param string $contextType
     * @param string|null $message
     * @param integer $code
     * @param \Exception $previous
     */
    public function __construct($contextType, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->contextType = $contextType;
        if ($message === null || $message === '') {
            $message = $this->generateMessage();
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getContextType()
    {
        return $this->contextType;
    }

    /**
     * @return string
     */
    protected function generateMessage()
    {
        return "Unknown files context type: '{$this->getContextType()}'.";
    }
}

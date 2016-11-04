<?php

namespace Solbeg\FilesManager\Exceptions;

class FormatterParserNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $parserName;

    /**
     * @param string $parserName
     * @param string|null $message
     * @param integer $code
     * @param \Exception $previous
     */
    public function __construct($parserName, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->parserName = $parserName;
        if ($message === null || $message === '') {
            $message = $this->generateMessage();
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    protected function generateMessage()
    {
        return "Parser '{$this->getParserName()}' was not found.";
    }

    /**
     * @return string
     */
    public function getParserName()
    {
        return $this->parserName;
    }
}

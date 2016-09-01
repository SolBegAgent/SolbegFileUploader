<?php

namespace Bicycle\FilesManager\Exceptions;

class FileAttributeNotDefinedException extends \Exception
{
    /**
     * @var string
     */
    protected $modelClass;

    /**
     * @var string
     */
    protected $attributeName;

    /**
     * @param string $modelClass
     * @param string $attributeName
     * @param string|null $message
     * @param integer $code
     * @param \Exception $previous
     */
    public function __construct($modelClass, $attributeName, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->modelClass = $modelClass;
        $this->attributeName = $attributeName;

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
        return "File attribute '$this->attributeName' is not defined in `{$this->modelClass}::filesAttributes()`.";
    }
}

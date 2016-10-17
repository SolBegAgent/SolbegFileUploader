<?php

namespace Bicycle\FilesManager\Exceptions;

/**
 * UnknownPropertyException
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class UnknownPropertyException extends \LogicException
{
    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var mixed
     */
    private $objectClass;

    /**
     * @param string $propertyName
     * @param mixed $objectClass
     * @param string|null $message
     * @param integer $code
     * @param \Exception $previous
     */
    public function __construct($propertyName, $objectClass, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->propertyName = $propertyName;
        $this->objectClass = $objectClass;
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
        return "The property \"{$this->getPropertyName()}\" does not exist in \"{$this->getObjectClassName()}\" class.";
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * @return mixed
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * @return string
     */
    public function getObjectClassName()
    {
        $objectClass = $this->getObjectClass();
        return is_object($objectClass) ? get_class($objectClass) : $objectClass;
    }
}

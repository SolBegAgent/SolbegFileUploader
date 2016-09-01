<?php

namespace Bicycle\FilesManager\Exceptions;

use Bicycle\FilesManager\Manager;

class ContextNotFoundException extends \Exception
{
    /**
     * @var string
     */
    protected $contextName;

    /**
     * @param string $contextName
     * @param string|null $message
     * @param integer $code
     * @param \Exception $previous
     */
    public function __construct($contextName, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->contextName = $contextName;
        if ($message === null || $message === '') {
            $message = $this->generateMessage();
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getContextName()
    {
        return $this->contextName;
    }

    /**
     * @return string
     */
    protected function generateMessage()
    {
        $contextName = $this->getContextName();
        return implode(' ', [
            "File context '$contextName' was not found.",
            "You should add `$contextName.php` file in your `app/config/filecontexts/` directory."
        ]);
    }
}

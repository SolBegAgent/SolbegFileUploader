<?php

namespace Bicycle\FilesManager;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Validation\Validator;

/**
 * RequestValidator
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class RequestValidator
{
    /**
     * @var Contracts\Manager
     */
    private $manager;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var boolean
     */
    private $autoAssoc = true;

    /**
     * @param Application $app
     * @param Contracts\Manager $manager
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @param Validator $validator
     */
    public function __construct(Application $app, Contracts\Manager $manager)
    {
        $this->app = $app;
        $this->manager = $manager;
    }

    /**
     * Extracts context name from parameters.
     * 
     * @param mixed $parameters
     * @throws Exceptions\InvalidConfigException
     */
    protected function extractContextName($parameters)
    {
        if (!is_array($parameters) || !isset($parameters[0])) {
            throw new Exceptions\InvalidConfigException('File context validator must have context name as the first parameter.');
        }
        return $parameters[0];
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return boolean
     */
    public function validate($attribute, $value, $parameters)
    {
        $contextName = $this->extractContextName($parameters);
        if ($this->getAutoAssoc()) {
            $this->assocAttributeContext($attribute, $contextName);
        }

        if ($value === null || $value === '') {
            return true;
        }

        $context = $this->getContext($contextName);
        $source = $context->getSourceFactory()->make($value);
        return $source->exists();
    }

    /**
     * 
     * @param string $attribute
     * @param string $contextName
     * @return static $this
     */
    public function assocAttributeContext($attribute, $contextName)
    {
        if (!$this->app->bound('filesmanager.middleware')) {
            return $this;
        }

        $middleware = $this->app['filesmanager.middleware'];
        if ($middleware instanceof StoreUploadedFilesMiddleware) {
            $middleware->assocInputWithContext($attribute, $contextName);
        }

        return $this;
    }

    /**
     * @param string $contextName
     * @return Contracts\Context
     */
    protected function getContext($contextName)
    {
        return $this->manager->context($contextName);
    }

    /**
     * @return boolean
     */
    public function getAutoAssoc()
    {
        return $this->autoAssoc;
    }

    /**
     * @param boolean $value
     * @return static $this
     */
    public function setAutoAssoc($value)
    {
        $this->autoAssoc = (bool) $value;
        return $this;
    }
}

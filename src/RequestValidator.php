<?php

namespace Bicycle\FilesManager;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Validation\Validator;

use Symfony\Component\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    private $trans;

    /**
     * @var boolean
     */
    private $autoAssoc = true;

    /**
     * @param Application $app
     * @param Contracts\Manager $manager
     */
    public function __construct(Application $app, Contracts\Manager $manager, TranslatorInterface $trans)
    {
        $this->app = $app;
        $this->manager = $manager;
        $this->trans = $trans;
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
        return trim($parameters[0]);
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @param Validator $validator
     * @return boolean
     */
    public function validate($attribute, $value, $parameters, Validator $validator)
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
        if (!$source->exists()) {
            $validator->getMessageBag()->add($attribute, $this->trans->trans('filesmanager::validation.not-found', [
                'attribute' => $attribute,
                'value' => (string) $value,
                'context' => $contextName,
            ]));
            return false;
        }

        try {
            $context->validate($source);
        } catch (Contracts\ValidationException $ex) {
            foreach ($ex->getMessages() as $message) {
                $validator->getMessageBag()->add($attribute, $message);
            }
            return false;
        }

        return true;
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

<?php

namespace Bicycle\FilesManager\Validation;

use Bicycle\FilesManager\Contracts;
use Bicycle\FilesManager\Helpers;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * AbstractValidator is the base class for all validators.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
abstract class AbstractValidator implements Contracts\Validator
{
    use Helpers\ConfigurableTrait;

    /**
     * @var boolean
     */
    protected $skipOnError = false;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Contracts\Context
     */
    private $context;

    /**
     * @param Contracts\Context $context
     * @param TranslatorInterface $translator
     * @param mixed $config
     */
    public function __construct(Contracts\Context $context, TranslatorInterface $translator, $config = null)
    {
        $this->context = $context;
        $this->translator = $translator;

        $defaultProperty = $this->defaultConfigProperty();
        if ($defaultProperty !== null && ($config === null || is_scalar($config) || Helpers\Config::isIndexed($config))) {
            $config = [$defaultProperty => $config];
        }
        if ($config) {
            $this->configure($config);
        }
    }

    /**
     * @return string|null
     */
    abstract protected function defaultConfigProperty();

    /**
     * @inheritdoc
     */
    abstract public function validate(Contracts\FileSource $source);

    /**
     * @inheritdoc
     */
    public function skipOnError()
    {
        return $this->skipOnError;
    }

    /**
     * @return Contracts\Context
     */
    public function context()
    {
        return $this->context;
    }

    /**
     * @return TranslatorInterface
     */
    public function trans()
    {
        return $this->translator;
    }
}

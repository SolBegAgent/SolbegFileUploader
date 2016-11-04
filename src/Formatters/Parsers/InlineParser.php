<?php

namespace Solbeg\FilesManager\Formatters\Parsers;

use Solbeg\FilesManager\Contracts;
use Solbeg\FilesManager\Exceptions;

/**
 * InlineParser uses Closure to parse formatter name.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class InlineParser extends AbstractParser
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var Contracts\FormatterFactory
     */
    private $factory;

    /**
     * @param Contracts\FormatterFactory $factory
     * @inheritdoc
     */
    public function __construct(Contracts\FormatterFactory $factory, array $config = [])
    {
        $this->factory = $factory;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     * @throws Exceptions\InvalidConfigException
     */
    protected function init()
    {
        if (!is_callable($this->callback)) {
            throw new Exceptions\InvalidConfigException('Property "callback" is required and must have callable type in "' . static::class . '".');
        }
    }

    /**
     * @param string $name
     * @param Contracts\Context $context
     */
    protected function parseName($name, Contracts\Context $context)
    {
        return call_user_func($this->callback, $name, $context, $this);
    }

    /**
     * @return Contracts\FormatterFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }
}

<?php

namespace Solbeg\FilesManager\Formatters\Parsers;

use Solbeg\FilesManager\Contracts;
use Solbeg\FilesManager\Helpers;

/**
 * NumberParser
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
abstract class AbstractParser implements Contracts\FormatterParser
{
    use Helpers\ConfigurableTrait;

    /**
     * @var boolean
     */
    protected $enabled = true;

    /**
     * @var array|string|null
     */
    protected $only;

    /**
     * @var array|string|null
     */
    protected $except;

    /**
     * @param string $name
     * @param Context $context
     * @return \Closure|array|string|null
     */
    abstract protected function parseName($name, Contracts\Context $context);

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->configure($config);
        $this->init();
    }

    /**
     * Initializes this parser.
     */
    protected function init()
    {
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function parse($name, Contracts\Context $context)
    {
        if (!is_scalar($name)) {
            throw new \InvalidArgumentException("Invalid type of format's name: '" . gettype($name) . '\'');
        } elseif (!$this->enabled() || !$this->isAllowedContext($context)) {
            return null;
        }
        return $this->parseName($name, $context);
    }

    /**
     * Enables this parser.
     * @return static $this
     */
    public function enable()
    {
        $this->enabled = true;
        return $this;
    }

    /**
     * Disables this parser.
     * @return static $this
     */
    public function disable()
    {
        $this->enabled = false;
        return $this;
    }

    /**
     * @return boolean
     */
    public function enabled()
    {
        return $this->enabled;
    }

    /**
     * @param Contracts\Context $context
     * @return boolean
     */
    protected function isAllowedContext(Contracts\Context $context)
    {
        $name = $context->getName();
        if ($this->except !== null && in_array($name, (array) $this->except, true)) {
            return false;
        } elseif ($this->only === null) {
            return true;
        } else {
            return in_array($name, (array) $this->only, true);
        }
    }
}

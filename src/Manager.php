<?php

namespace Bicycle\FilesManager;

use Illuminate\Contracts\Foundation\Application;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class Manager implements Contracts\Manager
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Contracts\Context[]
     */
    private $contexts = [];

    /**
     * @var Contracts\ContextFactory|null
     */
    private $contextFactory;

    /**
     * @var Contracts\FormatterFactory
     */
    private $formattersFactory;

    /**
     * Constructor for new files manager instance.
     * 
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @inheritdoc
     * @throws Exceptions\ContextNotFoundException
     */
    public function context($name = null)
    {
        if ($name === null) {
            return $this->getContextFactory();
        }

        if (!isset($this->contexts[$name])) {
            $this->contexts[$name] = $this->getContextFactory()->resolve($name);
        }
        return $this->contexts[$name];
    }

    /**
     * @inheritdoc
     * @param string $name
     * @param array $config
     * @return Contracts\Context
     * @throws Exceptions\UnknownContextTypeException
     * @throws Exceptions\InvalidConfigException
     */
    public function createContext($name, array $config = [])
    {
        if (isset($this->contexts[$name])) {
            return $this->contexts[$name];
        }
        return $this->contexts[$name] = $this->getContextFactory()->make($name, $config);
    }

    /**
     * @inheritdoc
     */
    public function hasContext($name)
    {
        return isset($this->contexts[$name]) || $this->getContextFactory()->has($name);
    }

    /**
     * @return Contracts\ContextFactory
     */
    protected function getContextFactory()
    {
        if ($this->contextFactory === null) {
            $this->contextFactory = $this->app->make(Context\ContextFactory::class, [
                'manager' => $this,
            ]);
        }
        return $this->contextFactory;
    }

    /**
     * @inheritdoc
     */
    public function formats()
    {
        if ($this->formattersFactory === null) {
            $this->formattersFactory = $this->app->make(Formatters\FormatterFactory::class, [
                'manager' => $this,
            ]);
        }
        return $this->formattersFactory;
    }
}

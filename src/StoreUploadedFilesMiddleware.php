<?php

namespace Bicycle\FilesManager;

use Illuminate\Http\RedirectResponse;
use Illuminate\Session\Store as SessionStore;

/**
 * StoreUploadedFilesMiddleware saves uploaded file to context temp storage.
 * And then stores relative path to saved file in session.
 * So if validation failed user does not have to upload this file again.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class StoreUploadedFilesMiddleware
{
    /**
     * @var string
     */
    public static $oldInputKey = '_old_input';

    /**
     * @var string[]
     */
    protected $fileContexts = [];

    /**
     * @var SessionStore
     */
    private $session;

    /**
     * @var Contracts\Manager
     */
    private $manager;

    /**
     * @param Contracts\Manager $manager
     * @param SessionStore $session
     */
    public function __construct(Contracts\Manager $manager, SessionStore $session)
    {
        $this->manager = $manager;
        $this->session = $session;
    }

    /**
     * Handles an incoming request.
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param array|string|null $names
     * @return mixed
     */
    public function handle($request, \Closure $next, $names = null)
    {
        $response = $next($request);

        if ($response instanceof RedirectResponse && $this->isSessionHasOldInput()) {
            $this->saveFiles($request, $names ? $this->parseNames($names) : $this->fileContexts);
        }

        return $response;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param array $fileContextsNames
     */
    public function saveFiles($request, array $fileContextsNames)
    {
        $sources = $this->saveFileInputs($request, $fileContextsNames);

        foreach ($sources as $name => $source) {
            $this->getSession()->flashInput([$name => $source]);
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param array $fileContextsNames
     * @return Contracts\StoredFileSource[]
     */
    protected function saveFileInputs($request, array $fileContextsNames)
    {
        $result = [];
        foreach ($fileContextsNames as $name => $contextName) {
            $datas = array_filter([
                $request->hasFile($name) ? $request->file($name) : null,
                $request->get($name, null),
            ]);
            $result[$name] = null;

            foreach ($datas as $data) {
                $source = $this->saveFileInput($contextName, $data);
                if ($source) {
                    $result[$name] = $source;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * @param string $contextName
     * @param string|\Illuminate\Http\UploadedFile $data
     * @return Contracts\StoredFileSource|null
     */
    protected function saveFileInput($contextName, $data)
    {
        $context = $this->getManager()->context($contextName);
        $storage = $context->storage(true);
        $source = $context->getSourceFactory()->make($data);
        if ($source instanceof Contracts\StoredFileSource && $storage === $source->getStorage()) {
            return $source;
        } elseif (!$source->exists()) {
            return null;
        }

        try {
            return $storage->saveNewFile($source);
        } catch (Contracts\ValidationException $ex) {
            return null;
        }
    }

    /**
     * Remembers association between input name and file context.
     * So this middleware may save and store input file in session.
     * 
     * @param string $inputName
     * @param string $contextName
     */
    public function assocInputWithContext($inputName, $contextName)
    {
        $this->fileContexts[$inputName] = $contextName;
    }

    /**
     * @param mixed $names
     * @return array
     */
    protected function parseNames($names)
    {
        if (!$names) {
            return [];
        } elseif (is_array($names)) {
            return $names;
        }

        $attrs = Helpers\Config::explode(';', $names, true);
        $result = [];
        foreach ($attrs as $attr) {
            $parts = Helpers\Config::explode('=>', $attr, true);
            if (isset($parts[0], $parts[1])) {
                $result[$parts[0]] = $parts[1];
            } elseif (isset($parts[0])) {
                $result[$parts[0]] = $parts[0];
            }
        }

        return $result;
    }

    /**
     * @return Contracts\Manager
     */
    protected function getManager()
    {
        return $this->manager;
    }

    /**
     * @return SessionStore
     */
    protected function getSession()
    {
        return $this->session;
    }

    /**
     * @return boolean
     */
    protected function isSessionHasOldInput()
    {
        $session = $this->getSession();
        return $session->hasOldInput() || $session->get(static::$oldInputKey, null) !== null;
    }
}

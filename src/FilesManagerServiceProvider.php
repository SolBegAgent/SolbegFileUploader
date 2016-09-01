<?php

namespace Bicycle\FilesManager;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

/**
 * FilesManagerServiceProvider shoulb connected in your application as service provider in kernel.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class FilesManagerServiceProvider extends ServiceProvider
{
    /**
     * @inheritdoc
     */
    protected $defer = false;

    /**
     * @var string
     */
    protected $requestValidatorClass = RequestValidator::class;

    /**
     * @var boolean whether uploaded files would be saved in session on validation fails.
     */
    protected $autoSaveUploadsToSession = true;

    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->registerManager();
        $this->registerMiddleware();
    }

    /**
     * @inheritdoc
     */
    public function boot()
    {
        $this->registerFileNotFoundHandlers();
        $this->registerValidators();
    }

    /**
     * Registers files manager
     */
    protected function registerManager()
    {
        $this->app->singleton('filesmanager', function () {
            return new Manager($this->app);
        });
        $this->app->alias('filesmanager', Contracts\Manager::class);
        $this->app->alias('filesmanager', Manager::class);
    }

    

    /**
     * Registers handlers for file not found events.
     */
    protected function registerFileNotFoundHandlers()
    {
// @todo add default event handlers
    }

    /**
     * Registers file validators.
     */
    protected function registerValidators()
    {
        if ($this->requestValidatorClass) {
            Validator::extend('filecontext', "$this->requestValidatorClass@validate");
            $this->app->resolving(function (RequestValidator $validator) {
                $validator->setAutoAssoc($this->autoSaveUploadsToSession);
            });
        }
    }

    /**
     * @return string
     */
    protected function validatorClassName()
    {
        return ;
    }

    /**
     * Registers middleware that stores uploaded files in session.
     */
    protected function registerMiddleware()
    {
        $this->app->singleton(StoreUploadedFilesMiddleware::class);
        $this->app->alias('filesmanager.middleware', StoreUploadedFilesMiddleware::class);
    }
}

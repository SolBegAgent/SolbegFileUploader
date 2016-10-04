<?php

namespace Bicycle\FilesManager;

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
        $this->registerConfigMakeCommand();
        $this->registerCleanGarbageCommand();
        $this->registerCreateSymlinkCommand();
    }

    /**
     * @inheritdoc
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerValidators();
        $this->publishConfig();
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
     * Registers middleware that stores uploaded files in session.
     */
    protected function registerMiddleware()
    {
        $this->app->singleton(StoreUploadedFilesMiddleware::class);
        $this->app->alias(StoreUploadedFilesMiddleware::class, 'filesmanager.middleware');
    }

    /**
     * Registers file validators.
     */
    protected function registerValidators()
    {
        if ($this->requestValidatorClass) {
            $message = $this->app['translator']->trans('filesmanager::validation.failed');
            $this->app['validator']->extend('filecontext', "$this->requestValidatorClass@validate", $message);
            $this->app->resolving(function (RequestValidator $validator) {
                $validator->setAutoAssoc($this->autoSaveUploadsToSession);
            });
        }
    }

    /**
     * Registers translations.
     */
    protected function registerTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'filesmanager');
    }

    /**
     * Publishes the main config of filesmanager.
     */
    protected function publishConfig()
    {
        $path = __DIR__ . '/resources/config/filemanager.php';
        $configKey = 'filemanager';

        $this->mergeConfigFrom($path, $configKey);
        $this->publishes([
            $path => config_path("$configKey.php"),
        ], 'config');
    }

    /**
     * Register the `make:filecontext` command.
     *
     * @return void
     */
    protected function registerConfigMakeCommand()
    {
        $this->app->singleton('command.filecontext-config.make', function ($app) {
            return new Console\ContextConfigMakeCommand($app['files']);
        });
        $this->commands(['command.filecontext-config.make']);
    }

    /**
     * Registers the `filecontext:clear-garbage` command.
     * 
     * @return void
     */
    protected function registerCleanGarbageCommand()
    {
        $this->app->singleton('command.filecontext-garbage.clear', function ($app) {
            return new Console\CleanGarbageCommand($app['filesmanager']);
        });
        $this->commands(['command.filecontext-garbage.clear']);
    }

    /**
     * Registers the `filecontext:create-symlink` command.
     * 
     * @return void
     */
    protected function registerCreateSymlinkCommand()
    {
        $this->app->singleton('command.filecontext-symlink.create', function ($app) {
            return new Console\CreatePublicStorageSymlinkCommand($app['files']);
        });
        $this->commands(['command.filecontext-symlink.create']);
    }
}

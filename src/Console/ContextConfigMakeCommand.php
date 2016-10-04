<?php

namespace Bicycle\FilesManager\Console;

use Bicycle\FilesManager\Helpers;

use Illuminate\Console\GeneratorCommand;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * ContextConfigMakeCommand generates configuration file for files context.
 */
class ContextConfigMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:filecontext';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create config file for a new files context';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Files context';

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $filename = Helpers\File::filename($name);
        return implode(DIRECTORY_SEPARATOR, [
            $this->laravel['path.config'],
            'filecontexts',
            strtolower($filename) . '.php',
        ]);
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        return strtr(parent::replaceClass($stub, $name), [
            'dummy-type' => $this->option('type') ?: $this->getDefaultContextType(),
        ]);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../resources/config/filecontext-config.stub';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the context.'],
        ];
    }

    /**
     * @return string
     */
    protected function getDefaultContextType()
    {
        return $this->laravel['config']['filemanager.default_type'] ?: 'default';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['type', 't', InputOption::VALUE_OPTIONAL, implode(' ', [
                'The type of a new context.',
                'The value of `default_type` in your `filemanager.php` config will be used by default.',
            ]), null],
        ];
    }
}

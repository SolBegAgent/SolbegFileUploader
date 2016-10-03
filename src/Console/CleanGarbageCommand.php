<?php

namespace Bicycle\FilesManager\Console;

use Bicycle\FilesManager\Manager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * CleanGarbageCommand collects and cleans garbage in temporary storages.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class CleanGarbageCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'filecontext:clear-garbage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears old files in temporary storages.';

    /**
     * The files manager instance.
     *
     * @var Manager
     */
    protected $filesmanager;

    /**
     * Create a new cache clear command instance.
     *
     * @param Manager $filesmanager
     * @return void
     */
    public function __construct(Manager $filesmanager)
    {
        parent::__construct();

        $this->filesmanager = $filesmanager;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $contexts = $this->getContextNames();
        $lifetime = $this->parseLifetimeOption();
        $this->warn('The following contexts will be searched:');
        foreach ($contexts as $name) {
            $this->info($name);
        }

        $collectors = [];
        $files = [];
        foreach ($contexts as $name) {
            $collector = $this->filesmanager->context($name)->getGarbageCollector();

            $collectors[$name] = $collector;
            $files[$name] = $collector->collect($lifetime);
        }

        if (!array_filter($files, 'count')) {
            $this->line('Nothing to clear.');
            return;
        }

        $this->warn(PHP_EOL . 'The following files will be cleaned:');
        foreach ($files as $name => $paths) {
            foreach ($paths as $path) {
                $this->info("$name: $path");
            }
        }
        $this->line('');

        if ($this->option('pretend')) {
            $this->line('Cleaning imitation complete.');
            return;
        } elseif (!$this->confirm('Are you sure?', true)) {
            $this->line('Canceled!');
            return;
        }

        foreach ($collectors as $name => $collector) {
            $this->info("Clearing '$name' context...");
            $collector->clean($files[$name]);
        }

        $this->line('Done!');
    }


    /**
     * Gets context names that should be cleaned.
     * @return string[]
     */
    protected function getContextNames()
    {
        $contexts = $this->parseContextOption();
        if (!$contexts) {
            $contexts = $this->laravel['config']['filemanager.console_clean_contexts'];
        }
        if (!$contexts) {
            $contexts = $this->filesmanager->context()->names();
        }
        return $contexts;
    }

    /**
     * @return string[]
     */
    protected function parseContextOption()
    {
        $result = (array) $this->option('context');
        foreach ($result as $key => $value) {
            $result[$key] = explode(',', $value);
        }

        if ($result) {
            $result = call_user_func_array('array_merge', $result);
            $result = array_unique(array_filter(array_map('trim', $result)));
        }
        return $result;
    }

    /**
     * @return integer|null
     */
    public function parseLifetimeOption()
    {
        $result = trim($this->option('lifetime'));
        return (strlen($result) && ctype_digit($result)) ? (int) $result : null;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['context', 'c', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The names of contexts you would like to clear.', null],
            ['lifetime', null, InputOption::VALUE_OPTIONAL, 'Life time of files in seconds.', null],
            ['pretend', null,  InputOption::VALUE_NONE, 'Dump file paths that would be deleted.', null],
        ];
    }
}

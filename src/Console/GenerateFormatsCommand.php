<?php

namespace Bicycle\FilesManager\Console;

use Bicycle\FilesManager\Manager;

use Illuminate\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * GenerateFormatsCommand generates/regenerates formatted versions of file.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class GenerateFormatsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'filecontext:generate-formats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates/regenerates formatted versions of files.';

    /**
     * The files manager instance.
     *
     * @var Manager
     */
    protected $filesmanager;

    /**
     * Create a new command instance.
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
        $context = $this->filesmanager->context($this->argument('name'));
        $storage = $context->storage((bool) $this->option('temp'));
        $this->line("Process the '{$storage->name()}' storage of the '{$context->getName()}' context...");

        $formats = (array) ($this->option('format') ?: $context->getPredefinedFormatNames());
        $regenerate = (bool) $this->option('regenerate');

        $this->line('The following formats will be ' . ($regenerate ? 'regenerated' : 'generated') . ':');
        foreach ($formats as $format) {
            $this->info($format);
        }
        $this->line('');

        if (!$this->confirm('Are you sure?', true)) {
            $this->warn('Canceled!');
            return;
        }

        $this->processGenerating($storage, $formats, $regenerate);
        $this->line('Done!');
    }

    /**
     * @param \Bicycle\FilesManager\Contracts\Storage $storage
     * @param array $formats
     * @param boolean $regenerate
     */
    protected function processGenerating($storage, array $formats = [], $regenerate = false)
    {
        foreach ($storage->files() as $relativePath) {
            $this->line("Process file '$relativePath'...");
            foreach ($formats as $format) {
                $this->processFormatting($storage, $relativePath, $format, $regenerate);
            }
        }
    }

    /**
     * @param \Bicycle\FilesManager\Contracts\Storage $storage
     * @param string $relativePath
     * @param string $format
     * @param boolean $regenerate
     */
    protected function processFormatting($storage, $relativePath, $format, $regenerate = false)
    {
        $source = $storage->context()->getSourceFactory()->storedFile($relativePath, $storage);
        $exists = $source->exists($format);
        if ($exists && !$regenerate) {
            $this->info("Exists: $format");
            return;
        }

        $label = $exists ? 'Regenerating' : 'Generating';
        $this->info("$label: $format");

        if (!$storage->generateFormattedFile($source, $format)) {
            $this->warn("Error: cannot generate '$format' format for the '$relativePath' file.");
        }
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
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['format', 'f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The names of formats you would like to generate.', null],
            ['regenerate', 'r', InputOption::VALUE_NONE, 'Whether all files must be regenerated even if exist.', null],
            ['temp', 't', InputOption::VALUE_NONE, 'Whether the temporary storage must be processed.', null],
        ];
    }
}

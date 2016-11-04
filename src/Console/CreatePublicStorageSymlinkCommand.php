<?php

namespace Solbeg\FilesManager\Console;

use Illuminate\Console\Command;

use Symfony\Component\Console\Input\InputOption;

/**
 * CreatePublicStorageSymlinkCommand creates symbolic link from `public/storage` to `storage/app/public` directory.
 * So all files in `storage/app/public` will be accessable through HTTP requests.
 * 
 * It is required by Laravel's file system:
 * @see https://laravel.com/docs/5.3/filesystem
 * 
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class CreatePublicStorageSymlinkCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'filecontext:create-symlink';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = <<<'DESCRIPTION'
Creates symbolic link from `public/storage` to `storage/app/public` directory.
So all files in `storage/app/public` will be accessable through HTTP requests.
DESCRIPTION;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $from = $this->option('from');
        $to = $this->option('to');

        $this->line('Creating symbolic link...');
        $this->info("From: $from");
        $this->info("To: $to");

        if ($this->createSymlink($from, $to)) {
            $this->line('Done!');
        } else {
            $this->error('Error!');
        }
    }

    /**
     * Creates symlink.
     * 
     * @param string $from
     * @param string $to
     * @return boolean
     */
    protected function createSymlink($from, $to)
    {
        if (false === $currentDir = getcwd()) {
            throw new \Exception('Cannot retreive current working directory.');
        }

        $fromDir = realpath(dirname($from));
        $fromName = basename($from);
        if (!$fromDir) {
            throw new \Exception("Invalid value of the 'from' option: '$from'.");
        }

        $realTo = realpath($to);
        if (!$realTo) {
            throw new \Exception("Invalid value of the 'to' option: '$to'.");
        }

        $this->info("cd $fromDir");
        if (!chdir($fromDir)) {
            throw new \Exception("Cannot change current working directory to: '$fromDir'.");
        }

        try {
            $command = strtr($this->option('command'), [
                '{from}' => escapeshellarg('.' . DIRECTORY_SEPARATOR . $fromName),
                '{to}' => escapeshellarg(rtrim($this->generateRelativePath($fromDir, $realTo), '\/') . DIRECTORY_SEPARATOR),
            ]);
            $this->info($command);
            passthru($command, $code);
            return !$code;
        } finally {
            @chdir($currentDir);
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['from', null, InputOption::VALUE_OPTIONAL, 'Path to link that would be created.', public_path('storage')],
            ['to', null,  InputOption::VALUE_OPTIONAL, 'Path to storage public path.', storage_path('app/public')],
            ['command', null, InputOption::VALUE_REQUIRED, 'Command to create symlink.', $this->defaultSymlinkCommand()],
        ];
    }

    /**
     * @return string
     */
    protected function defaultSymlinkCommand()
    {
        $isWin = $this->isWindows();
        return implode(' ', [
            $isWin ? 'mklink' : 'ln',
            $isWin ? '/D' : '-s',
            $isWin ? '{from}' : '{to}',
            $isWin ? '{to}' : '{from}',
        ]);
    }

    /**
     * @param string $from
     * @param string $to
     * @return string
     */
    protected function generateRelativePath($from, $to)
    {
        $fromParts = explode('/', rtrim(str_replace('\\', '/', $from), '/'));
        $toParts = explode('/', rtrim(str_replace('\\', '/', $to), '/'));

        while ($fromParts && $toParts && reset($fromParts) === reset($toParts)) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        return str_pad(implode(DIRECTORY_SEPARATOR, array_merge(
            array_fill(0, count($fromParts), '..'),
            $toParts
        )), 1, '.');
    }

    /**
     * @return boolean
     */
    protected function isWindows()
    {
        return (bool) windows_os();
    }
}

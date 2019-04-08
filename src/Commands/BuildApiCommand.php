<?php

namespace JoseLoarca\LaravelApiBuilder\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

class BuildApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:build {--T|translations=true : Whether ES translations should be installed, defaults to TRUE.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build a new API with ApiHandler trait, translations (optional), etc.';

    /**
     * The Filesystem instance.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The progress bar instance.
     *
     * @var
     */
    private $progressBar;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $filesystem
     *
     * @return void
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->progressBar = $this->output->createProgressBar(4);
        $this->progressBar->setFormat('[%bar%] %percent:3s%% -- %message%');
        $this->info('Building API...');

        //Retrieve --translations option
        $installTranslations = filter_var($this->option('translations'), FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE);

        if ($installTranslations) {
            $this->publishTranslations();
        }

        //Requests logger
        $this->publishLoggerConfig();

        //ApiHandler trait
        $this->publishApiHandler();

        $this->progressBar->setMessage('Done');
        $this->progressBar->advance();
    }

    /**
     * Publish translations files.
     *
     * @return void
     */
    protected function publishTranslations()
    {
        Artisan::call('vendor:publish', [
            '--provider' => 'JoseLoarca\LaravelApiBuilder\LaravelApiBuilderServiceProvider', '--tag' => 'lang',
        ]);

        $this->progressBar->setMessage('Publishing translations...');
        $this->progressBar->advance();
    }

    /**
     * Publish Requests Logger configuration file.
     *
     * @return void
     */
    protected function publishLoggerConfig()
    {
        Artisan::call('vendor:publish', [
            '--provider' => 'JoseLoarca\LaravelApiBuilder\LaravelApiBuilderServiceProvider', '--tag' => 'config',
        ]);

        $this->progressBar->setMessage('Publishing configuration files...');
        $this->progressBar->advance();
    }

    /**
     * Publish the API handler trait.
     *
     * @return void
     */
    protected function publishApiHandler()
    {
        $this->progressBar->setMessage('Publishing API handler...');
        $this->progressBar->advance();
    }

}

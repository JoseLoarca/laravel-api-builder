<?php

namespace JoseLoarca\LaravelApiBuilder\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class BuildApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:build 
                                {--m|models=* : All the models that should be generated.} {attributes?* : Model attributes.}
                                {--t|translations=true : Whether ES translations should be installed, defaults to TRUE.}';

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
     * @throws FileNotFoundException
     *
     * @return mixed
     */
    public function handle()
    {
        $this->progressBar = $this->output->createProgressBar(4);
        $this->progressBar->setFormat('[%bar%] %percent:3s%% -- %message%');
        $this->info('Building API...');

        if ($this->option('models')) {
            if ($modelsData = $this->checkModelInput($this->option('models'), $this->argument('attributes'))) {
                $this->handleModelGeneration($modelsData);
            }
        }

        //Retrieve --translations option
        if (filter_var($this->option('translations'), FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE)) {
            $this->publishTranslations();
        } else {
            $this->progressBar->setMessage('Translations skipped...');
            $this->progressBar->advance();
        }

        //Requests logger
        $this->publishLoggerConfig();

        //Move ApiController.php to app/Http/Controllers directory
        $this->publishApiController();

        //Publish exception handler file
        $this->publishExceptionHandler();

        $this->progressBar->setMessage('Done.');
        $this->progressBar->finish();
    }

    /**
     * Check the model input and build an array associating model with attributes.
     *
     * @param array $models
     * @param array $attributes
     *
     * @return array|false
     */
    public function checkModelInput(array $models, array $attributes)
    {
        if (count($models) && is_array($models)) {
            $modelArray = [];

            for ($i = 0; $i < count($models); $i++) {
                $modelArray[] = ['model' => $models[$i], 'attributes' => $attributes[$i]];
            }

            return $modelArray;
        } else {
            $this->progressBar->setMessage('Model generation skipped...');
            $this->progressBar->advance();

            return false;
        }
    }

    /**
     * Generate models.
     *
     * @param array $modelsData
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    public function handleModelGeneration(array $modelsData)
    {
        foreach ($modelsData as $model) {

            //Model and filename name
            $modelName = Str::studly($model['model']);
            $fileName = "{$modelName}.php";

            //Table info
            $tableName = Str::snake($modelName);

            //Model stub actions
            $modelStub = $this->getStub('model');
            $modelStub = str_replace('MODEL_NAME', $modelName, $modelStub);
            $modelStub = str_replace('TABLE_NAME', $tableName, $modelStub);

            //Save Model file
            $this->filesystem->put(app_path("/{$fileName}"), $modelStub);

            //Generate transformer
            $this->handleTransformerGeneration($modelName);

            //Generate controller and routes
            $this->handleControllerGeneration($modelName);

            $this->info($modelName);
        }
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
     * Publish the API Controller.
     *
     * @return void
     */
    protected function publishApiController()
    {
        $this->filesystem->copy(__DIR__.'./../Http/Controllers/ApiController.php', app_path('Http/Controllers/ApiController.php'));

        $this->progressBar->setMessage('Publishing API controller...');
        $this->progressBar->advance();
    }

    /**
     * Generate controllers and routes.
     *
     * @param string $modelName
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    public function handleControllerGeneration(string $modelName)
    {
        //Get the controller stub
        $controllerStub = $this->getStub('controller');

        //Controller name
        $controllerName = "{$modelName}Controller";

        //Replace data
        $controllerStub = str_replace('MODEL_CLASS', $modelName, $controllerStub);
        $controllerStub = str_replace('MODEL_VAR', Str::camel($modelName), $controllerStub);

        //Create directory if its missing
        if (!$this->filesystem->exists(app_path("Http/Controllers/{$modelName}"))) {
            $this->filesystem->makeDirectory(app_path("Http/Controllers/{$modelName}"));
        }

        //Write controller file
        $this->filesystem->put(app_path("Http/Controllers/{$modelName}/{$controllerName}.php"), $controllerStub);

        //Get the routes stub
        $routesStub = $this->getStub('routes');

        //Resource actions
        $resourceActions = "['except' => ['create', 'edit']]";

        //Replace data
        $routesStub = str_replace('MODEL_CLASS', $modelName, $routesStub);
        $routesStub = str_replace('RESOURCE_NAME', Str::snake(Str::plural($modelName)), $routesStub);
        $routesStub = str_replace('RESOURCE_ACTIONS', $resourceActions, $routesStub);

        //Append routes to routes/api.php
        $this->filesystem->append(base_path('routes/api.php'), $routesStub);
    }

    /**
     * Generate transformers.
     *
     * @param string $modelName
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    protected function handleTransformerGeneration(string $modelName)
    {
        //Get transformer stub
        $transformerStub = $this->getStub('transformer');

        $transformableAttributes = '[]';

        //Replace data
        $transformerStub = str_replace('MODEL_CLASS', $modelName, $transformerStub);
        $transformerStub = str_replace('MODEL_VAR', Str::camel($modelName), $transformerStub);
        $transformerStub = str_replace('TRANSFORMABLE_ATTRIBUTES', $transformableAttributes, $transformerStub);

        //Create Transformers directory if its missing
        if (!$this->filesystem->exists(app_path('Transformers'))) {
            $this->filesystem->makeDirectory(app_path('Transformers'));
        }

        //Write file
        $this->filesystem->put(app_path("Transformers/{$modelName}Transformer.php"), $transformerStub);
    }

    /**
     * Publish exception handler file.
     *
     * @void
     *
     * @throws FileNotFoundException
     */
    protected function publishExceptionHandler()
    {
        $this->filesystem->put(app_path('Exceptions/Handler.php'), $this->filesystem->get(__DIR__.'./../Exceptions/Handler.php'));
    }

    /**
     * Get the stub file.
     *
     * @param string $stubType
     *
     * @throws FileNotFoundException
     *
     * @return string
     */
    protected function getStub(string $stubType)
    {
        return $this->filesystem->get(__DIR__."/../stubs/{$stubType}.stub");
    }
}

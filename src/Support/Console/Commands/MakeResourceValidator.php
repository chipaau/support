<?php

namespace Support\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\AppNamespaceDetectorTrait;

class MakeResourceValidator extends Command
{
    use AppNamespaceDetectorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:resource:validator {resources*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a validator that extends the Support abstract validator';

    protected $namespace;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->namespace = $this->getAppNamespace();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ds = DIRECTORY_SEPARATOR;
        $resources = $this->argument('resources');
        $resources = !is_array($resources) ? [$resources] : $resources;
        
        foreach ($resources as $resource) {
            $stub = file_get_contents(__DIR__ . $ds . '..' . $ds . '..' . $ds . '..' . $ds . '..' . $ds . 'resources' . $ds . 'Stubs' . $ds . 'Validator.stub');
            $stub = str_replace([
                        '{{namespace}}',   '{{resource}}'], 
                        [$this->namespace,  str_singular(ucfirst($resource))],
                        $stub);

            $directory = app_path('Validators');

            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            $filename = $directory . $ds . str_singular(ucfirst($resource)) . 'Validator.php';
            file_put_contents($filename, $stub);
            $this->info($filename . ' created!');
        }
    }
}

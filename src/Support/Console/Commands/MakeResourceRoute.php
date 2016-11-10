<?php

namespace Support\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\AppNamespaceDetectorTrait;

class MakeResourceRoute extends Command
{
    use AppNamespaceDetectorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:resource:route {resources*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a resource route for a resource';

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
            $stub = file_get_contents(__DIR__ . $ds . '..' . $ds . '..' . $ds . '..' . $ds . '..' . $ds . 'resources' . $ds . 'Stubs' . $ds . 'Route.stub');
            $stub = str_replace([
                        '{{resource}}',                    '{{class}}'],
                        [str_singular(ucfirst($resource)),  str_plural(ucfirst($resource))],
                        $stub);

            $directory = base_path('routes');

            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            $routes = file_get_contents($directory . $ds . 'web.php');
            $stub = $routes ? $routes . "\n" . $stub : $stub;

            $filename = $directory . $ds . 'web.php';
            file_put_contents($filename, $stub);
            $this->info($filename . ' routes updated!');
        }
    }
}

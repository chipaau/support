<?php

namespace Support\Console\Commands;

class MakeResourceRoute extends AbstractCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:resource:route {resources*} {--module=*}';

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
        $module = $this->option('module');
        $routesFile = base_path('routes') . $ds . 'web.php';

        if ($module) {
            $module = is_array($module) ? $module[0] : $module;
            $moduleDirectory = config('support.module.directory') . $ds . ucfirst($module);
            
            $this->namespace = $this->getAppNamespace() . ucfirst($module);
            $routesFile = config('support.module.directory') . $ds . ucfirst($module) . $ds . config('support.module.routes');
            
            if (!file_exists($moduleDirectory)) {
                $this->error("The requested module '$moduleDirectory' does not exists!");
                return;
            }

            if(config('support.namespace')) {
                $this->namespace = config('support.namespace');
            }
        }
        
        foreach ($resources as $resource) {
            $stub = file_get_contents(__DIR__ . $ds . '..' . $ds . '..' . $ds . '..' . $ds . '..' . $ds . 'resources' . $ds . 'Stubs' . $ds . (empty($module) ? 'Standard' : 'Modular') . $ds . 'Route.stub');
            $stub = str_replace([
                        '{{resource}}',                    '{{class}}'],
                        [str_plural(strtolower($resource)),  str_plural(ucfirst($resource))],
                        $stub);

            if (!file_exists($routesFile)) {
                $this->error('Could not update routes file \'' . $routesFile . '\' as it does not exist!');
                return;
            }

            $routes = file_get_contents($routesFile);
            $stub = $routes ? $routes . "\n" . $stub : $stub;

            file_put_contents($routesFile, $stub);
            $this->info($routesFile . ' routes updated!');
        }
    }
}

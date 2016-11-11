<?php

namespace Support\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\AppNamespaceDetectorTrait;

class MakeResourceController extends Command
{
    use AppNamespaceDetectorTrait;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:resource:controller {resources*} {--module=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create controller(s) that extends the Support controller';

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
        $directory = app_path('Http' . $ds . 'Controllers');

        if ($module) {
            $module = is_array($module) ? $module[0] : $module;
            $moduleDirectory = config('support.module.directory') . $ds . ucfirst($module);
            
            $this->namespace = $this->getAppNamespace() . ucfirst($module);
            $directory = config('support.module.directory') . $ds . ucfirst($module) . $ds . config('support.module.controllers');
            
            if (!file_exists($moduleDirectory)) {
                $this->error("The requested module '$moduleDirectory' does not exists!");
                return;
            }

            if(config('support.namespace')) {
                $this->namespace = config('support.namespace');
            }
        }
        
        foreach ($resources as $resource) {
            $stub = file_get_contents(__DIR__ . $ds . '..' . $ds . '..' . $ds . '..' . $ds . '..' . $ds . 'resources' . $ds . 'Stubs' . $ds . (empty($module) ? 'Standard' : 'Modular') . $ds . 'Controller.stub');
            $stub = str_replace([
                            '{{namespace}}',
                            '{{controllers}}',
                            '{{requests}}',
                            '{{repositories}}',
                            '{{model}}',
                            '{{resource}}',
                            '{{class}}'
                        ], 
                        [
                            $this->namespace,
                            str_replace($ds, '\\', config('support.module.controllers')),
                            str_replace($ds, '\\', config('support.module.requests')),
                            str_replace($ds, '\\', config('support.module.repositories')),
                            str_replace($ds, '\\', config('support.module.models')),
                            str_singular(ucfirst($resource)),
                            str_plural(ucfirst($resource))
                        ], $stub);

            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            $filename = $directory . $ds . str_plural(ucfirst($resource)) . 'Controller.php';
            file_put_contents($filename, $stub);
            $this->info($filename . ' created!');
        }
    }
}

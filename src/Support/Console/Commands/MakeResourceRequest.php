<?php

namespace Support\Console\Commands;

class MakeResourceRequest extends AbstractCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:resource:request {resources*} {--module=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a request that extends the Support request';

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
        $directory = app_path('Http' . $ds . 'Requests');

        if ($module) {
            $module = is_array($module) ? $module[0] : $module;
            $moduleDirectory = config('support.module.directory') . $ds . ucfirst($module);
            
            $this->namespace = $this->getAppNamespace() . ucfirst($module);
            $directory = config('support.module.directory') . $ds . ucfirst($module) . $ds . config('support.module.requests');
            
            if (!file_exists($moduleDirectory)) {
                $this->error("The requested module '$moduleDirectory' does not exists!");
                return;
            }

            if(config('support.namespace')) {
                $this->namespace = config('support.namespace');
            }
        }
        
        foreach ($resources as $resource) {
            $stub = file_get_contents(__DIR__ . $ds . '..' . $ds . '..' . $ds . '..' . $ds . '..' . $ds . 'resources' . $ds . 'Stubs' . $ds . (empty($module) ? 'Standard' : 'Modular') . $ds . 'Request.stub');
            $stub = str_replace([
                            '{{namespace}}',
                            '{{validators}}',
                            '{{requests}}',
                            '{{resource}}'
                        ], 
                        [
                            $this->namespace,
                            str_replace($ds, '\\', config('support.module.validators')),
                            str_replace($ds, '\\', config('support.module.requests')),
                            str_singular(ucfirst($resource))
                        ], $stub);

            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            $filename = $directory . $ds . str_singular(ucfirst($resource)) . 'Request.php';
            file_put_contents($filename, $stub);
            $this->info($filename . ' created!');
        }
    }
}

<?php

namespace Support\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\AppNamespaceDetectorTrait;

class MakeResourceModel extends Command
{
    use AppNamespaceDetectorTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:resource:model {resources*} {--module=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create model(s) that extends the Support model';

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
        $directory = app_path();

        if ($module) {
            $module = is_array($module) ? $module[0] : $module;
            $moduleDirectory = config('support.module.directory') . DIRECTORY_SEPARATOR . ucfirst($module);

            $this->namespace = $this->getAppNamespace() . ucfirst($module) . '\\' . str_replace($ds, '\\', config('support.module.models'));
            $directory = config('support.module.directory') . DIRECTORY_SEPARATOR . ucfirst($module) . $ds . config('support.module.models');
            
            if (!file_exists($moduleDirectory)) {
                $this->error("The requested module '$moduleDirectory' does not exists!");
                return;
            }

            if(config('support.namespace')) {
                $this->namespace = config('support.namespace');
            }
        } else {
            $this->namespace = rtrim($this->namespace, "\\");
        }
        
        foreach ($resources as $resource) {

            $stub = file_get_contents(__DIR__ . $ds . '..' . $ds . '..' . $ds . '..' . $ds . '..' . $ds . 'resources' . $ds . 'Stubs' . $ds . (empty($module) ? 'Standard' : 'Modular') . $ds . 'Model.stub');
            $stub = str_replace(
                        ['{{namespace}}',  '{{resource}}'], 
                        [$this->namespace,  str_singular(ucfirst($resource))],
                        $stub);

            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            $filename = $directory . $ds . str_singular(ucfirst($resource)) . '.php';
            
            if (file_exists($filename)) {
                $this->info($filename . ' already exists!');
                return;
            }

            file_put_contents($filename, $stub);
            $this->info($filename . ' created!');
        }
    }
}

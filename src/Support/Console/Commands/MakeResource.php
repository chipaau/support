<?php

namespace Support\Console\Commands;

class MakeResource extends AbstractCommand
{    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:resource {resources*} {--module=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default scaffolding for resources in compliance with the Support module';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $resources = $this->argument('resources');
        $module = $this->option('module');

        if ($module) {
            $module = is_array($module) ? $module[0] : $module;
            $moduleDirectory = config('support.module.directory') . DIRECTORY_SEPARATOR . ucfirst($module);
            if (!file_exists($moduleDirectory)) {
                $this->error("The requested module '$moduleDirectory' does not exists!");
                return;
            }
        }
        

        $resources = !is_array($resources) ? [$resources] : $resources;

        foreach ($resources as $resource) {

            $this->info('Creating resource: ' . $resource);
            $this->info('=================');

            $this->call('support:resource:model', ['resources' => $resource, '--module' => $module]);
            $this->call('support:resource:repository', ['resources' => $resource, '--module' => $module]);
            $this->call('support:resource:schema', ['resources' => $resource, '--module' => $module]);
            $this->call('support:resource:validator', ['resources' => $resource, '--module' => $module]);
            $this->call('support:resource:request', ['resources' => $resource, '--module' => $module]);
            $this->call('support:resource:controller', ['resources' => $resource, '--module' => $module]);
            $this->call('support:resource:route', ['resources' => $resource, '--module' => $module]);
            $this->info('');
        }

    }
}

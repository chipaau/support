<?php

namespace Support\Console\Commands;

use Illuminate\Console\Command;

class MakeResource extends Command
{    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:resource {resources*}';

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
        $resources = !is_array($resources) ? [$resources] : $resources;

        foreach ($resources as $resource) {
            $this->info('Creating resource: ' . $resource);
            $this->info('=================');

            $this->call('support:resource:model', ['resources' => $resource]);
            $this->call('support:resource:repository', ['resources' => $resource]);
            $this->call('support:resource:schema', ['resources' => $resource]);
            $this->call('support:resource:validator', ['resources' => $resource]);
            $this->call('support:resource:request', ['resources' => $resource]);
            $this->call('support:resource:controller', ['resources' => $resource]);
            $this->call('support:resource:route', ['resources' => $resource]);

            $this->info('Resource \'' . $resource . '\' created!');
            $this->info('');
        }

    }
}

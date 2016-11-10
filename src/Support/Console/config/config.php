<?php

return [
    'module' => [
        /*
         * Directory name of the modules
         */
        'directory' => 'module',

        /*
         * If left blank, the namespace of the application would be used
         */
        'namespace' => null,

        /*
         * Routes file has to be relative to the module directory
         * Example: If the module directory is "Module", the routes file could be "Http/routes.php"
         */
        'routes' => 'Http' . DIRECTORY_SEPARATOR . 'routes.php',

        /*
         * Models directory has to be relative to the module directory
         * Example: If the module directory is "Module", the models directory could be "Entities"
         */
        'models' => 'Entities',

        /*
         * Controllers directory has to be relative to the module directory
         * Example: If the module directory is "Module", the controllers directory could be "Http/Controllers"
         */
        'controllers' => 'Http' . DIRECTORY_SEPARATOR . 'Controllers',

        /*
         * Requests directory has to be relative to the module directory
         * Example: If the module directory is "Module", the requests directory could be "Http/Requests"
         */
        'requests' => 'Http' . DIRECTORY_SEPARATOR . 'Requests',

        /*
         * Repositories directory has to be relative to the module directory
         * Example: If the module directory is "Module", the repositories directory could be "Http/Controllers"
         */
        'repositories' => 'Repositories',

        /*
         * Schemas directory has to be relative to the module directory
         * Example: If the module directory is "Module", the schemas directory could be "Schemas"
         */
        'schemas' => 'Schemas',

        /*
         * Validators directory has to be relative to the module directory
         * Example: If the module directory is "Module", the validators directory could be "Validators"
         */
        'validators' => 'Validators'
    ],
];

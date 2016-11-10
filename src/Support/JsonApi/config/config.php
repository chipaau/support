<?php

use Support\JsonApi\Settings\Settings;

return [


    Settings::SCHEMAS_PATH => [
        'path' => app_path('Schemas'),
        'namespace' => "App\\Schemas\\",
        'extra' => [
            'path' => base_path('modules'),
            'schemas' => 'Schemas',
            'namespace' => 'App\\'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON encoding options
    |--------------------------------------------------------------------------
    |
    | Here you can specify options to be used while converting data to actual
    | JSON representation with json_encode function.
    |
    | For example if options are set to JSON_PRETTY_PRINT then returned data
    | will be nicely formatted with spaces.
    |
    | see http://php.net/manual/en/function.json-encode.php
    |
    | If this section is omitted default values will be used.
    |
    */
    Settings::JSON => [
        Settings::JSON_OPTIONS         => JSON_PRETTY_PRINT,
        Settings::JSON_DEPTH           => Settings::JSON_DEPTH_DEFAULT,
        Settings::JSON_IS_SHOW_VERSION => Settings::JSON_IS_SHOW_VERSION_DEFAULT,
        Settings::JSON_URL_PREFIX      => '',
        Settings::JSON_VERSION_META    => [
            'name'  =>  'Laravel Application',
            'copyright'  => 'Copyright Information'
        ],
    ]
];

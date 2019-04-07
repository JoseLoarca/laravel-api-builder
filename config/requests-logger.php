<?php

return [

    /*
     * Enable Requests logging by default
     */
    'enabled' => env('REQUESTS_LOGGER_ENABLED', true),

    /*
     * Should files be logged?
     */
    'log_files' => false,

    /*
     * Methods that should be logged
     */
    'should_log' => [
        'get', 'post', 'put', 'patch', 'delete',
    ],

    /*
     * Body fields that should never be logged
     */
    'except' => [
        'password',
        'password_confirmation',
    ],
];

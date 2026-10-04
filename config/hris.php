<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default administrator
    |--------------------------------------------------------------------------
    |
    | Credentials of the administrator created by the database seeder.
    | Change the password right after the first login.
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

];

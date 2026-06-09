<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default User Password
    |--------------------------------------------------------------------------
    |
    | This option defines the password assigned to newly created users when
    | no password is provided, such as during database seeding or when a
    | system administrator creates an account. Set SEEDING_PASSWORD in your
    | environment file before running db:seed or provisioning users.
    |
    */

    'default_password' => env('SEEDING_PASSWORD'),

];

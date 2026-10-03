<?php

/*
|--------------------------------------------------------------------------
| Every Nation Bekasi — installation settings
|--------------------------------------------------------------------------
|
| Read by the seeders and the `church:admin` command. Kept in config (not env())
| so they keep working after `php artisan optimize` caches the configuration.
|
*/

return [

    'admin_email' => env('ADMIN_EMAIL', 'admin@everynationbekasi.test'),

    'admin_password' => env('ADMIN_PASSWORD'),

    'seed_demo' => (bool) env('SEED_DEMO', false),

    'demo_password' => env('DEMO_PASSWORD'),

];

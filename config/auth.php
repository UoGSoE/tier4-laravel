<?php

return [

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'ldapusers',
        ],
    ],

    'providers' => [
        'ldapusers' => [
            'driver' => 'ldapeloquent',
            'model' => App\Models\User::class,
        ],
    ],

];

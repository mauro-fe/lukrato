<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Limites de uso por plano
    |--------------------------------------------------------------------------
    */

    'limits' => [

        'free' => [
            'lancamentos_per_month' => 50,
            'warning_at'            => 40,
        ],

        'pro' => [
            'lancamentos_per_month' => null, // ilimitado
        ],

    ],

];

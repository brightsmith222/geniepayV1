<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notify Theme
    |--------------------------------------------------------------------------
    |
    | You can change the theme of notifications by specifying the desired theme.
    | By default the theme light is activated, but you can change it by
    | specifying the dark mode. To change theme, update the global variable to `dark`
    |
    */

    'theme' => env('NOTIFY_THEME', 'light'),

    /*
    |--------------------------------------------------------------------------
    | Notification timeout
    |--------------------------------------------------------------------------
    |
    | Defines the number of seconds during which the notification will be visible.
    |
    */

    'timeout' => 5000,

    /*
    |--------------------------------------------------------------------------
    | Preset Messages
    |--------------------------------------------------------------------------
    |
    | Define any preset messages here that can be reused.
    | Available model: connect, drake, emotify, smiley, toast
    |
    */

    'preset-messages' => [
        // An example preset 'user updated' Connectify notification.
        'user-refunded' => [
            'message' => 'The user has been refunded successfully.',
            'type' => 'success',
            'model' => 'connect',
            'title' => 'Refund Successful',
        ],

        'already-refunded' => [
            'message' => 'Transaction already refunded',
            'type' => 'warning',
            'model' => 'connect',
            'title' => 'Already Refunded',
        ],
        'no-user' => [
            'message' => 'User not found for transaction',
            'type' => 'error',
            'model' => 'connect',
            'title' => 'User not found',
        ],
    ],

];

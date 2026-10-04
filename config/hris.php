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

    /*
    |--------------------------------------------------------------------------
    | Attendance rules
    |--------------------------------------------------------------------------
    */

    'attendance' => [
        // Overtime shorter than this is ignored.
        'overtime_threshold_minutes' => (int) env('ATTENDANCE_OT_THRESHOLD', 30),

        // Night differential window (Labor Code Art. 86: 10 PM to 6 AM).
        'night_diff_start_hour' => 22,
        'night_diff_end_hour' => 6,

        // Punches up to this many minutes before shift start / after shift end
        // belong to that shift's day.
        'log_window_before_minutes' => 240,
        'log_window_after_minutes' => 480,
    ],

];

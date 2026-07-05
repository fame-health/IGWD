<?php

return [
    'timezone' => env('HD_SCHEDULE_TIMEZONE', env('APP_TIMEZONE', 'Asia/Jakarta')),

    'shift_times' => [
        'Pagi' => env('HD_SHIFT_PAGI_TIME', '08:00'),
        'Siang' => env('HD_SHIFT_SIANG_TIME', '12:00'),
        'Sore' => env('HD_SHIFT_SORE_TIME', '16:00'),
        'Malam' => env('HD_SHIFT_MALAM_TIME', '20:00'),
    ],

    'reminders' => [
        'h_minus_1_day' => 24,
        'h_minus_2_hours' => 2,
    ],

    'reminder_window_minutes' => (int) env('HD_REMINDER_WINDOW_MINUTES', 10),
];

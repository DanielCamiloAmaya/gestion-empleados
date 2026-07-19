<?php

return [
    'version' => 2,
    'admin_idle_minutes' => (int) env('ADMIN_SESSION_IDLE_MINUTES', 15),
    'admin_absolute_minutes' => (int) env('ADMIN_SESSION_ABSOLUTE_MINUTES', 480),
    'employee_idle_minutes' => (int) env('EMPLOYEE_SESSION_IDLE_MINUTES', 30),
    'employee_absolute_minutes' => (int) env('EMPLOYEE_SESSION_ABSOLUTE_MINUTES', 720),
    'platform_idle_minutes' => (int) env('PLATFORM_SESSION_IDLE_MINUTES', 10),
    'platform_absolute_minutes' => (int) env('PLATFORM_SESSION_ABSOLUTE_MINUTES', 240),
];

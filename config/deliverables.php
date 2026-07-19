<?php

return [
    'antivirus_enabled' => (bool) env('DELIVERABLES_ANTIVIRUS_ENABLED', false),
    'antivirus_binary' => env('DELIVERABLES_ANTIVIRUS_BINARY', 'clamscan'),
    'antivirus_timeout_seconds' => (int) env('DELIVERABLES_ANTIVIRUS_TIMEOUT', 60),
];

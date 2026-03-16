<?php

return [
    'sms' => [
        'url' => env('SMS_GATEWAY_URL', 'http://10.0.13.10:8081/sms/v1/send'),
        'from' => env('SMS_FROM', 'NIOT'),
    ],
];

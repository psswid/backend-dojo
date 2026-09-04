<?php

return [
    // Isolated PHP code runner (snippet/debug tasks).
    'url' => env('RUNNER_URL', 'http://runner:8080'),
    'timeout' => env('RUNNER_TIMEOUT', 15),
];

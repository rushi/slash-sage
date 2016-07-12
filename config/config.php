<?php
$_sageConfig = [
    'settings' => [
        'displayErrorDetails' => false, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Monolog settings
        'logger' => [
            'name' => 'ci',
            'path' => __DIR__ . '/../logs/app.log',
        ],
        'slack' => [
            'allowed_users' => [], // If this is empty, then all slack users are allowed. Otherwise only specific users allowed
            'token' => '' // Slack token to verify that request came from slack. Leave it empty to ignore the check
        ],
        'guzzle' => [
            'base_uri' => '', // Your Go CD base URL it should end in /go/api
            'auth' => ['username', 'password']
        ]
    ]
];

// Some quick boiler plate code to have local configs that can override defaults
// local configs are not to be checked in
$localConfigPath = __DIR__ . '/config.local.php';
if (file_exists($localConfigPath)) {
    $_localConfig = include_once $localConfigPath;
    if (is_array($_localConfig) && isset($_localConfig['settings'])) {
        $_sageConfig['settings'] = array_merge($_sageConfig['settings'], $_localConfig['settings']);
    }
}

return $_sageConfig;

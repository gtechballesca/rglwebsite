<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$configFile = __DIR__ . '/mail-config.php';
$config = is_file($configFile) ? require $configFile : [];
if (!is_array($config)) {
    $config = [];
}

$key = (string) ($config['web3forms_access_key'] ?? '');
if ($key === '' || $key === 'YOUR_WEB3FORMS_ACCESS_KEY') {
    $key = '';
}

echo json_encode(['web3formsKey' => $key], JSON_UNESCAPED_SLASHES);

<?php
declare(strict_types=1);

function formulaire_config(): array
{
    $defaults = [
        'to_email' => 'emersonfrancois77@gmail.com',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_user' => 'emersonfrancois77@gmail.com',
        'smtp_pass' => '',
        'from_name' => 'Geek Elite Trading',
    ];

    $local = __DIR__ . DIRECTORY_SEPARATOR . 'config.local.php';
    if (is_file($local)) {
        $loaded = include $local;
        if (is_array($loaded)) {
            return array_merge($defaults, $loaded);
        }
    }

    return $defaults;
}

function formulaire_smtp_ready(): bool
{
    $config = formulaire_config();
    return trim((string) $config['smtp_pass']) !== '';
}

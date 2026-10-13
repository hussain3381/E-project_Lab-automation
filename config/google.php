<?php
// Private Google OAuth settings are read from the developer's .env or host environment.
declare(strict_types=1);

require_once __DIR__ . '/app.php';

function google_oauth_config(): array
{
    $clientId = trim((string) (env_value('GOOGLE_CLIENT_ID', '') ?? ''));
    $clientSecret = trim((string) (env_value('GOOGLE_CLIENT_SECRET', '') ?? ''));
    $redirectUri = trim((string) (env_value('GOOGLE_REDIRECT_URI', '') ?? ''));
    $parts = $redirectUri !== '' ? parse_url($redirectUri) : false;
    $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';
    $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';
    $localHttpAllowed = $scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true);
    $validRedirect = is_array($parts)
        && isset($parts['host'], $parts['path'])
        && ($scheme === 'https' || $localHttpAllowed)
        && !isset($parts['user'])
        && !isset($parts['pass'])
        && !isset($parts['fragment']);

    return [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'configured' => $clientId !== '' && $clientSecret !== '' && $validRedirect,
    ];
}

function google_oauth_is_configured(): bool
{
    return (bool) google_oauth_config()['configured'];
}

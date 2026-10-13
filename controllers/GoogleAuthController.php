<?php
// Small, framework-free Google OAuth flow with state/PKCE, verified email, and safe account linking.
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/security.php';
require_once dirname(__DIR__) . '/config/google.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once __DIR__ . '/AuthController.php';

function google_base64url(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

/** Start OAuth with a one-time state value and PKCE verifier kept in the session. */
function google_oauth_begin(): void
{
    $config = google_oauth_config();
    if (!$config['configured']) {
        throw new DomainException('Google sign-in has not been configured for this installation.');
    }

    app_start_session();
    session_regenerate_id(true);
    $state = bin2hex(random_bytes(32));
    $verifier = google_base64url(random_bytes(48));
    $_SESSION['google_oauth_pending'] = [
        'state' => $state,
        'verifier' => $verifier,
        'started_at' => time(),
    ];

    $challenge = google_base64url(hash('sha256', $verifier, true));
    $parameters = [
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
        'prompt' => 'select_account',
    ];
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986), true, 302);
    exit;
}

/** Make a bounded HTTPS request to fixed Google OAuth endpoints; never follow redirects. */
function google_oauth_http(string $url, string $method, array $headers = [], ?string $body = null): array
{
    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Google sign-in request could not be initialized.');
        }
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($handle, $options);
        $responseBody = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $requestError = curl_errno($handle) !== 0;
        curl_close($handle);
        if (!is_string($responseBody) || $requestError || $status < 200 || $status >= 300) {
            throw new RuntimeException('Google sign-in could not verify the authorization response.');
        }
        return ['status' => $status, 'body' => $responseBody];
    }

    $headerText = implode("\r\n", $headers);
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => $headerText,
            'content' => $body ?? '',
            'timeout' => 20,
            'ignore_errors' => true,
            'follow_location' => 0,
            'max_redirects' => 0,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $responseBody = @file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? '';
    preg_match('/\s(\d{3})\s/', $statusLine, $statusMatch);
    $status = isset($statusMatch[1]) ? (int) $statusMatch[1] : 0;
    if (!is_string($responseBody) || $status < 200 || $status >= 300) {
        throw new RuntimeException('Google sign-in could not verify the authorization response.');
    }
    return ['status' => $status, 'body' => $responseBody];
}

/** Decode a successful JSON response, failing closed on malformed provider data. */
function google_oauth_json(string $responseBody): array
{
    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Google sign-in returned an invalid authorization response.');
    }
    return $decoded;
}

/** Pick a unique, human-readable local username for a newly provisioned Google Tester. */
function google_oauth_new_username(mysqli $connection, string $email): string
{
    $localPart = strtolower((string) strstr($email, '@', true));
    $base = preg_replace('/[^a-z0-9._-]+/', '', $localPart) ?? '';
    $base = trim($base, '._-');
    if (strlen($base) < 3) {
        $base = 'tester';
    }
    $base = substr($base, 0, 30);

    for ($attempt = 0; $attempt < 12; $attempt++) {
        $suffix = (string) random_int(1000, 999999);
        $candidate = substr($base, 0, 39 - strlen($suffix)) . '.' . $suffix;
        $lookup = $connection->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $lookup->bind_param('s', $candidate);
        $lookup->execute();
        $exists = $lookup->get_result()->num_rows > 0;
        $lookup->close();
        if (!$exists) {
            return $candidate;
        }
    }
    throw new RuntimeException('A unique username could not be allocated. Ask an Administrator to create the account.');
}

/** Keep one Tester identity per account, reusing an unlinked exact-email profile when there is one unambiguous match. */
function google_oauth_ensure_tester_profile(mysqli $connection, array $user): void
{
    if ((string) ($user['role'] ?? '') !== 'Tester' || (int) ($user['is_active'] ?? 0) !== 1) {
        return;
    }
    $userId = (int) $user['id'];
    $name = substr((string) $user['name'], 0, 120);
    $email = trim((string) ($user['email'] ?? ''));
    $emailValue = $email !== '' ? $email : null;

    $byUser = $connection->prepare('SELECT id, is_active FROM testers WHERE user_id = ? LIMIT 1 FOR UPDATE');
    $byUser->bind_param('i', $userId);
    $byUser->execute();
    $profile = $byUser->get_result()->fetch_assoc() ?: null;
    $byUser->close();
    if ($profile !== null) {
        if ((int) $profile['is_active'] !== 1) {
            throw new DomainException('The linked Tester profile is inactive. Ask an Administrator to restore access before signing in.');
        }
        $update = $connection->prepare('UPDATE testers SET name = ?, email = ?, is_active = 1 WHERE user_id = ?');
        $update->bind_param('ssi', $name, $emailValue, $userId);
        $update->execute();
        $update->close();
        return;
    }

    if ($emailValue !== null) {
        $byEmail = $connection->prepare('SELECT id, user_id, is_active FROM testers WHERE LOWER(TRIM(email)) = LOWER(?) LIMIT 3 FOR UPDATE');
        $byEmail->bind_param('s', $emailValue);
        $byEmail->execute();
        $allMatches = $byEmail->get_result()->fetch_all(MYSQLI_ASSOC);
        $byEmail->close();
        $matches = array_values(array_filter($allMatches, static fn (array $candidate): bool => $candidate['user_id'] === null));
        if (count($allMatches) !== count($matches)) {
            throw new DomainException('This verified email is already attached to another Tester account. Ask an Administrator to reconcile the profile before Google sign-in.');
        }
        if (count($matches) > 1) {
            throw new DomainException('More than one unlinked Tester profile uses this email. Ask an Administrator to resolve it.');
        }
        if (count($matches) === 1 && (int) $matches[0]['is_active'] !== 1) {
            throw new DomainException('An inactive Tester profile uses this email. Ask an Administrator to reactivate it before Google sign-in.');
        }
        if (count($matches) === 1) {
            $profileId = (int) $matches[0]['id'];
            $link = $connection->prepare('UPDATE testers SET user_id = ?, name = ?, email = ?, is_active = 1 WHERE id = ? AND user_id IS NULL');
            $link->bind_param('issi', $userId, $name, $emailValue, $profileId);
            $link->execute();
            $linked = $link->affected_rows === 1;
            $link->close();
            if (!$linked) {
                throw new DomainException('The existing Tester profile could not be linked. Ask an Administrator to review it.');
            }
            return;
        }
    }

    $insert = $connection->prepare("INSERT INTO testers (user_id, name, department, designation, email, is_active) VALUES (?, ?, NULL, 'Lab Tester', ?, 1)");
    $insert->bind_param('iss', $userId, $name, $emailValue);
    $insert->execute();
    $insert->close();
}

/** Complete the OAuth callback and return a local account; caller establishes the session. */
function google_oauth_complete(mysqli $connection): array
{
    app_start_session();
    $pending = $_SESSION['google_oauth_pending'] ?? null;
    unset($_SESSION['google_oauth_pending']);
    $returnedState = (string) ($_GET['state'] ?? '');
    $startedAt = is_array($pending) ? (int) ($pending['started_at'] ?? 0) : 0;
    if (!is_array($pending)
        || $returnedState === ''
        || !isset($pending['state'], $pending['verifier'])
        || !hash_equals((string) $pending['state'], $returnedState)
        || $startedAt < time() - 600
        || $startedAt > time() + 60) {
        throw new DomainException('Google sign-in expired or could not be verified. Please try again.');
    }
    if (isset($_GET['error'])) {
        throw new DomainException('Google sign-in was cancelled or denied. Please try again.');
    }
    $code = trim((string) ($_GET['code'] ?? ''));
    if ($code === '' || strlen($code) > 4096) {
        throw new DomainException('Google did not return a usable authorization code.');
    }

    $config = google_oauth_config();
    if (!$config['configured']) {
        throw new DomainException('Google sign-in is not configured for this installation.');
    }
    $tokenRequest = http_build_query([
        'code' => $code,
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'grant_type' => 'authorization_code',
        'code_verifier' => (string) $pending['verifier'],
    ], '', '&', PHP_QUERY_RFC3986);
    $tokenResponse = google_oauth_http(
        'https://oauth2.googleapis.com/token',
        'POST',
        ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        $tokenRequest
    );
    $tokenData = google_oauth_json($tokenResponse['body']);
    $accessToken = (string) ($tokenData['access_token'] ?? '');
    if ($accessToken === '' || strlen($accessToken) > 8192) {
        throw new DomainException('Google did not issue an access token. Please try again.');
    }

    $profileResponse = google_oauth_http(
        'https://openidconnect.googleapis.com/v1/userinfo',
        'GET',
        ['Authorization: Bearer ' . $accessToken, 'Accept: application/json']
    );
    $profile = google_oauth_json($profileResponse['body']);
    $subject = trim((string) ($profile['sub'] ?? ''));
    $email = strtolower(trim((string) ($profile['email'] ?? '')));
    $verified = ($profile['email_verified'] ?? false) === true || (string) ($profile['email_verified'] ?? '') === 'true';
    if ($subject === '' || strlen($subject) > 191 || $email === '' || strlen($email) > 190
        || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$verified) {
        throw new DomainException('A verified Google email address is required for sign-in.');
    }
    $googleName = trim((string) ($profile['name'] ?? ''));
    if ($googleName === '') {
        $googleName = (string) strstr($email, '@', true);
    }
    $googleName = substr($googleName, 0, 120);

    $connection->begin_transaction();
    $createdUser = false;
    try {
        $byGoogle = $connection->prepare('SELECT u.id, u.name, u.username, u.email, COALESCE(r.role_name, u.role) AS role, u.is_active FROM users AS u LEFT JOIN roles AS r ON r.id = u.role_id WHERE u.google_sub = ? LIMIT 1 FOR UPDATE');
        $byGoogle->bind_param('s', $subject);
        $byGoogle->execute();
        $user = $byGoogle->get_result()->fetch_assoc() ?: null;
        $byGoogle->close();

        if ($user !== null) {
            if ((int) $user['is_active'] !== 1) {
                throw new DomainException('This linked Lab Automation account is inactive. Contact an Administrator.');
            }
        } else {
            $byEmail = $connection->prepare('SELECT u.id, u.name, u.username, u.email, COALESCE(r.role_name, u.role) AS role, u.is_active, u.google_sub FROM users AS u LEFT JOIN roles AS r ON r.id = u.role_id WHERE u.email = ? LIMIT 2 FOR UPDATE');
            $byEmail->bind_param('s', $email);
            $byEmail->execute();
            $emailRows = $byEmail->get_result()->fetch_all(MYSQLI_ASSOC);
            $byEmail->close();
            if (count($emailRows) > 1) {
                throw new DomainException('More than one Lab account uses this email. Ask an Administrator to resolve it.');
            }
            if (count($emailRows) === 1) {
                $user = $emailRows[0];
                if ((int) $user['is_active'] !== 1) {
                    throw new DomainException('This Lab Automation account is inactive. Contact an Administrator.');
                }
                $existingSubject = (string) ($user['google_sub'] ?? '');
                if ($existingSubject !== '' && !hash_equals($existingSubject, $subject)) {
                    throw new DomainException('This account is linked to a different Google identity. Contact an Administrator.');
                }
                $link = $connection->prepare('UPDATE users SET google_sub = ? WHERE id = ? AND (google_sub IS NULL OR google_sub = ?)');
                $userId = (int) $user['id'];
                $link->bind_param('sis', $subject, $userId, $subject);
                $link->execute();
                if ($link->affected_rows !== 1 && (string) ($user['google_sub'] ?? '') !== $subject) {
                    $link->close();
                    throw new DomainException('The Google identity could not be linked to this account. Contact an Administrator.');
                }
                $link->close();
                if ($existingSubject === '') {
                    audit_write($connection, 'google_account_linked', 'User', (string) $userId, 'A verified Google identity was linked to an existing account.', [
                        'email' => $email,
                    ]);
                }
            } else {
                $username = google_oauth_new_username($connection, $email);
                $randomPassword = bin2hex(random_bytes(32));
                $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);
                if (!is_string($passwordHash)) {
                    throw new RuntimeException('The new Google account password could not be secured.');
                }
                $roleLookup = $connection->prepare("SELECT id FROM roles WHERE role_name = 'Tester' LIMIT 1 FOR UPDATE");
                $roleLookup->execute();
                $testerRoleId = (int) ($roleLookup->get_result()->fetch_assoc()['id'] ?? 0);
                $roleLookup->close();
                if ($testerRoleId < 1) {
                    throw new RuntimeException('The built-in Tester role is missing. Apply the latest database migration.');
                }
                $insert = $connection->prepare("INSERT INTO users (name, username, email, password, role, role_id, is_active, google_sub) VALUES (?, ?, ?, ?, 'Tester', ?, 1, ?)");
                $insert->bind_param('ssssis', $googleName, $username, $email, $passwordHash, $testerRoleId, $subject);
                $insert->execute();
                $userId = (int) $connection->insert_id;
                $insert->close();

                // The shared profile helper reuses one exact-email unlinked Tester profile when available.
                $createdUser = true;
                $user = [
                    'id' => $userId,
                    'name' => $googleName,
                    'username' => $username,
                    'email' => $email,
                    'role' => 'Tester',
                    'is_active' => 1,
                ];
            }
        }

        if (!is_array($user)) {
            throw new RuntimeException('Google identity was not mapped to a Lab Automation account.');
        }
        google_oauth_ensure_tester_profile($connection, $user);
        if ($createdUser) {
            $profileLookup = $connection->prepare('SELECT id FROM testers WHERE user_id = ? LIMIT 1');
            $createdUserId = (int) $user['id'];
            $profileLookup->bind_param('i', $createdUserId);
            $profileLookup->execute();
            $createdProfileId = (int) ($profileLookup->get_result()->fetch_assoc()['id'] ?? 0);
            $profileLookup->close();
            audit_write($connection, 'google_user_registered', 'User', (string) $createdUserId, 'A new Tester account was provisioned using verified Google sign-in.', [
                'role' => 'Tester',
                'email' => $email,
                'tester_profile_id' => $createdProfileId > 0 ? $createdProfileId : null,
            ]);
        }
        $connection->commit();
        return $user;
    } catch (Throwable $exception) {
        $connection->rollback();
        throw $exception;
    }
}

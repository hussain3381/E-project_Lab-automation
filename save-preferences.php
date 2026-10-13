<?php
// Save the signed-in user's editable profile and dashboard preferences (never lab-wide settings).
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_page_access(__FILE__);
require_once __DIR__ . '/models/UserPreferences.php';

$isAjaxPreference = (string) ($_POST['preference_ajax'] ?? '') === '1';
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: profile.php', true, 303);
    exit;
}

if ($isAjaxPreference) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $theme = (string) ($_POST['theme'] ?? '');
    if (!in_array($theme, ['dark', 'light'], true)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Choose a valid dashboard theme.']);
        exit;
    }

    try {
        $current = UserPreferences::find($conn, $userId);
        if (!$current['schema_available']) {
            throw new RuntimeException('Apply database migration 010 before saving dashboard preferences.');
        }
        $saved = UserPreferences::save($conn, $userId, [
            'theme' => $theme,
            'compact_dashboard' => $current['compact_dashboard'],
            'reduced_motion' => $current['reduced_motion'],
        ]);
        audit_write($conn, 'dashboard_theme_updated', 'User', (string) $userId, 'A user changed their personal dashboard theme.', [
            'theme' => $saved['theme'],
        ]);
        echo json_encode(['ok' => true, 'theme' => $saved['theme']]);
    } catch (Throwable $exception) {
        error_log('Lab Automation preference save failed: ' . $exception->getMessage());
        http_response_code(503);
        echo json_encode(['ok' => false, 'message' => 'Could not save this preference. Apply the latest database migration and try again.']);
    }
    exit;
}

$redirectWithError = static function (string $message): void {
    $_SESSION['profile_error'] = $message;
    header('Location: profile.php', true, 303);
    exit;
};

$name = trim((string) ($_POST['name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$phone = trim((string) ($_POST['phone'] ?? ''));
$theme = (string) ($_POST['theme'] ?? 'dark');
$compactDashboard = isset($_POST['compact_dashboard']) && (string) $_POST['compact_dashboard'] === '1';
$reducedMotion = isset($_POST['reduced_motion']) && (string) $_POST['reduced_motion'] === '1';

if ($name === '' || strlen($name) > 120) {
    $redirectWithError('Enter your name using 1 to 120 characters.');
}
if ($email !== '' && (strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
    $redirectWithError('Enter a valid email address, or leave the field blank.');
}
if (strlen($phone) > 40) {
    $redirectWithError('Phone number must be 40 characters or fewer.');
}
if (!in_array($theme, ['dark', 'light'], true)) {
    $redirectWithError('Choose either the dark or light dashboard theme.');
}

$emailValue = $email === '' ? null : $email;
$phoneValue = $phone === '' ? null : $phone;
$transactionStarted = false;
try {
    $preferences = UserPreferences::find($conn, $userId);
    if (!$preferences['schema_available']) {
        throw new RuntimeException('Apply database migration 010 before editing profile preferences.');
    }

    $duplicate = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $duplicate->bind_param('si', $emailValue, $userId);
    $duplicate->execute();
    $emailInUse = $duplicate->get_result()->num_rows > 0;
    $duplicate->close();
    if ($emailInUse) {
        $redirectWithError('That email address is already linked to another account.');
    }

    $conn->begin_transaction();
    $transactionStarted = true;
    $update = $conn->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
    $update->bind_param('sssi', $name, $emailValue, $phoneValue, $userId);
    $update->execute();
    $update->close();

    $savedPreferences = UserPreferences::save($conn, $userId, [
        'theme' => $theme,
        'compact_dashboard' => $compactDashboard,
        'reduced_motion' => $reducedMotion,
    ]);
    audit_write($conn, 'profile_updated', 'User', (string) $userId, 'A user updated their personal profile and dashboard preferences.', [
        'email_changed' => $emailValue !== null,
        'phone_provided' => $phoneValue !== null,
        'theme' => $savedPreferences['theme'],
        'compact_dashboard' => $savedPreferences['compact_dashboard'],
        'reduced_motion' => $savedPreferences['reduced_motion'],
    ]);

    $conn->commit();
    $transactionStarted = false;
    $_SESSION['name'] = $name;
    $_SESSION['email'] = (string) ($emailValue ?? '');
    $_SESSION['profile_success'] = 'Profile and dashboard preferences saved.';
    header('Location: profile.php', true, 303);
    exit;
} catch (Throwable $exception) {
    if ($transactionStarted) {
        try {
            $conn->rollback();
        } catch (Throwable $rollbackException) {
            // Preserve the original profile-save error.
        }
    }
    error_log('Lab Automation profile update failed: ' . $exception->getMessage());
    $redirectWithError($exception instanceof DomainException || $exception instanceof InvalidArgumentException
        ? $exception->getMessage()
        : 'Could not save the profile. Check the database migration and try again.');
}

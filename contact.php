<?php
// Store public questions in the database; sending email is intentionally not implied.
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/controllers/ContactController.php';
app_start_session();

$contactSuccess = (string) ($_SESSION['contact_success'] ?? '');
unset($_SESSION['contact_success']);
$contactError = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $state = contact_process_submission(Database::connection());
        $contactError = $state['error'];
        if ($state['success'] !== '') {
            $_SESSION['contact_success'] = $state['success'];
            header('Location: contact.php?sent=1', true, 303);
            exit;
        }
    } catch (Throwable $exception) {
        error_log('Lab Automation contact form failed: ' . $exception->getMessage());
        $contactError = APP_DEBUG ? 'The message could not be saved. Run the latest database migration.' : 'The message could not be saved right now.';
    }
}

$csrfToken = csrf_token();
require __DIR__ . '/views/public/contact.php';

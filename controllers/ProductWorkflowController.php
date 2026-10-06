<?php
// Coordinate manual re-manufacture release and CPRI handoff actions.

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/ProductWorkflow.php';

function product_workflow_handle_request(mysqli $connection): array
{
    $state = ['message' => '', 'error' => ''];
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return $state;
    }

    $action = (string) ($_POST['action'] ?? '');
    $productId = trim((string) ($_POST['product_id'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $reference = trim((string) ($_POST['reference'] ?? ''));
    $changedBy = (string) ($_SESSION['name'] ?? $_SESSION['username'] ?? 'Lab User');

    if (!preg_match('/^\d{10}$/', $productId)) {
        $state['error'] = 'Choose a valid ten-digit Product ID.';
        return $state;
    }

    try {
        $connection->begin_transaction();
        if ($action === 'mark_remanufactured') {
            ProductWorkflow::markRemanufactured($connection, $productId, $changedBy, $notes);
            $state['message'] = 'Re-manufacture recorded. The product is released for its next test cycle.';
        } elseif ($action === 'cpri_handoff') {
            ProductWorkflow::markCpriHandoff($connection, $productId, $reference, $notes, $changedBy);
            $state['message'] = 'Manual CPRI handoff recorded. No external API was called.';
        } else {
            throw new InvalidArgumentException('Choose a valid workflow action.');
        }
        $connection->commit();
    } catch (Throwable $exception) {
        $connection->rollback();
        error_log('Lab Automation product workflow action failed: ' . $exception->getMessage());
        $state['error'] = $exception instanceof DomainException || $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'The workflow action could not be saved. Reload and try again.';
    }

    return $state;
}

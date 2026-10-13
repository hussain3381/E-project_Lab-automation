<?php
// Coordinate QC-owned re-manufacture, manual CPRI outcome, and market-release actions.

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
    $cpriResult = strtoupper(trim((string) ($_POST['cpri_result'] ?? '')));
    $changedBy = (string) ($_SESSION['name'] ?? $_SESSION['username'] ?? 'Lab User');

    if (!preg_match('/^\d{10}$/', $productId)) {
        $state['error'] = 'Choose a valid ten-digit Product ID.';
        return $state;
    }

    try {
        $connection->begin_transaction();
        if ($action === 'mark_remanufactured') {
            ProductWorkflow::markRemanufactured($connection, $productId, $changedBy, $notes);
            $state['message'] = 'Re-manufacture recorded. The product is released for a full new test cycle.';
        } elseif ($action === 'cpri_handoff') {
            ProductWorkflow::markCpriHandoff($connection, $productId, $reference, $notes, $changedBy);
            $state['message'] = 'Manual CPRI handoff recorded. No external API was called.';
        } elseif ($action === 'cpri_result') {
            ProductWorkflow::recordCpriResult($connection, $productId, $cpriResult, $notes, $changedBy);
            $state['message'] = $cpriResult === 'PASS'
                ? 'CPRI PASS recorded. Quality Control can now record the market release.'
                : 'CPRI rejection recorded. The product is routed to re-manufacture; a full new test cycle will follow.';
        } elseif ($action === 'market_release') {
            ProductWorkflow::recordMarketRelease($connection, $productId, $notes, $changedBy);
            $state['message'] = 'Market release recorded after CPRI approval.';
        } else {
            throw new InvalidArgumentException('Choose a valid workflow action.');
        }

        $auditActions = [
            'mark_remanufactured' => 'product_remanufactured',
            'cpri_handoff' => 'product_cpri_handoff',
            'cpri_result' => 'product_cpri_result_recorded',
            'market_release' => 'product_market_released',
        ];
        audit_write($connection, $auditActions[$action], 'Product', $productId, $state['message'], [
            'notes_provided' => $notes !== '',
            'reference_provided' => $action === 'cpri_handoff' && $reference !== '',
            'cpri_result' => $action === 'cpri_result' ? $cpriResult : null,
        ]);
        $connection->commit();
    } catch (Throwable $exception) {
        try { $connection->rollback(); } catch (Throwable $rollbackException) { /* Preserve the original failure. */ }
        error_log('Lab Automation product workflow action failed: ' . $exception->getMessage());
        $state['error'] = $exception instanceof DomainException || $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'The workflow action could not be saved. Reload and try again.';
    }

    return $state;
}

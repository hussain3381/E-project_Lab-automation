<?php
// Record accountable events without ever storing passwords, OAuth tokens, or CSRF values.
declare(strict_types=1);

function audit_write(
    mysqli $connection,
    string $action,
    string $entity,
    ?string $entityId,
    string $summary,
    array $metadata = []
): void {
    $actorId = isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0
        ? (int) $_SESSION['user_id']
        : null;
    $actorName = (string) ($_SESSION['name'] ?? 'Public visitor');
    $actorRole = (string) ($_SESSION['role'] ?? 'Public');
    $action = substr(trim($action), 0, 80);
    $entity = substr(trim($entity), 0, 80);
    $entityId = $entityId !== null ? substr($entityId, 0, 100) : null;
    $summary = substr(trim($summary), 0, 500);
    $metadataJson = $metadata !== []
        ? json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)
        : null;
    $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? substr((string) $_SERVER['REMOTE_ADDR'], 0, 45) : null;
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

    $statement = $connection->prepare(
        'INSERT INTO audit_logs (actor_user_id, actor_name, actor_role, action, entity, entity_id, summary, metadata, ip_address, user_agent) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->bind_param(
        'isssssssss',
        $actorId,
        $actorName,
        $actorRole,
        $action,
        $entity,
        $entityId,
        $summary,
        $metadataJson,
        $ipAddress,
        $userAgent
    );
    $statement->execute();
    $statement->close();
}

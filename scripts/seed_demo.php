<?php
// Load clearly marked, non-production sample records from the command line only.

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This demo seeder must be run from the command line.\n");
}

require_once dirname(__DIR__) . '/models/Database.php';

$seedFile = dirname(__DIR__) . '/database/seeds/001_demo.sql';
if (!is_file($seedFile)) {
    fwrite(STDERR, "Demo seed file not found: {$seedFile}\n");
    exit(1);
}

try {
    $connection = Database::connection();
    $sql = file_get_contents($seedFile);
    if ($sql === false) {
        throw new RuntimeException('Could not read the demo seed file.');
    }

    if (!$connection->multi_query($sql)) {
        throw new RuntimeException($connection->error);
    }

    while (true) {
        if ($result = $connection->store_result()) {
            $result->free();
        }
        if (!$connection->more_results()) {
            break;
        }
        if (!$connection->next_result()) {
            throw new RuntimeException($connection->error);
        }
    }

    echo "Demo data loaded. Logins: admin, manager, tester, quality; shared demo password: LabDemo@123 (never use in production).\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Demo seed failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

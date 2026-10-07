<?php
// Map authenticated lab staff accounts to the tester records used by test assignments.
declare(strict_types=1);

final class Tester
{
    /** Return the active tester profile linked to an account, if it exists. */
    public static function findByUserId(mysqli $connection, int $userId): ?array
    {
        $statement = $connection->prepare(
            'SELECT id, name, department, designation, email FROM testers WHERE user_id = ? AND is_active = 1 LIMIT 1'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $profile = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $profile;
    }
}

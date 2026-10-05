<?php
// Keep user lookup queries out of the login screen and controller.

declare(strict_types=1);

final class User
{
    /**
     * Find a user by their unique login name using a prepared statement.
     */
    public static function findByUsername(mysqli $connection, string $username): ?array
    {
        $statement = $connection->prepare(
            'SELECT id, name, username, password, role, is_active FROM users WHERE username = ? LIMIT 1'
        );
        $statement->bind_param('s', $username);
        $statement->execute();
        $result = $statement->get_result();
        $user = $result->fetch_assoc() ?: null;
        $statement->close();

        return $user;
    }
}

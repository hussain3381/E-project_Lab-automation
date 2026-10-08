<?php
// Keep account lookup and the constrained public registration transaction in one model.
declare(strict_types=1);

final class User
{
    /** Find an active/disabled account by its unique login name. */
    public static function findByUsername(mysqli $connection, string $username): ?array
    {
        $statement = $connection->prepare(
            'SELECT id, name, username, email, password, role, is_active FROM users WHERE username = ? LIMIT 1'
        );
        $statement->bind_param('s', $username);
        $statement->execute();
        $result = $statement->get_result();
        $user = $result->fetch_assoc() ?: null;
        $statement->close();

        return $user;
    }

    /**
     * Register only a basic Tester account and its linked staff profile.
     * Public input can never select an elevated role.
     */
    public static function createTesterAccount(
        mysqli $connection,
        string $name,
        string $username,
        string $email,
        string $password
    ): array {
        $email = strtolower(trim($email));
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if (!is_string($passwordHash)) {
            throw new RuntimeException('Could not secure the password.');
        }

        $connection->begin_transaction();
        try {
            $duplicate = $connection->prepare(
                'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1'
            );
            $duplicate->bind_param('ss', $username, $email);
            $duplicate->execute();
            $alreadyExists = $duplicate->get_result()->num_rows > 0;
            $duplicate->close();
            if ($alreadyExists) {
                throw new DomainException('That username or email is already registered.');
            }

            $insertUser = $connection->prepare(
                "INSERT INTO users (name, username, email, password, role, is_active) VALUES (?, ?, ?, ?, 'Tester', 1)"
            );
            $insertUser->bind_param('ssss', $name, $username, $email, $passwordHash);
            $insertUser->execute();
            $userId = (int) $connection->insert_id;
            $insertUser->close();

            $insertProfile = $connection->prepare(
                "INSERT INTO testers (user_id, name, department, designation, email, is_active) VALUES (?, ?, NULL, 'Lab Tester', ?, 1)"
            );
            $insertProfile->bind_param('iss', $userId, $name, $email);
            $insertProfile->execute();
            $insertProfile->close();

            $connection->commit();
            return [
                'id' => $userId,
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'role' => 'Tester',
            ];
        } catch (Throwable $exception) {
            $connection->rollback();
            throw $exception;
        }
    }
}

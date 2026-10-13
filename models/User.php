<?php
// Keep account lookup and the constrained public registration transaction in one model.
declare(strict_types=1);

final class User
{
    /** Find one account by its login name or the unique email stored on that account. */
    public static function findByLoginIdentity(mysqli $connection, string $identity): ?array
    {
        $identity = trim($identity);
        $normalizedEmail = strtolower($identity);
        $statement = $connection->prepare(
            'SELECT u.id, u.name, u.username, u.email, u.password, COALESCE(r.role_name, u.role) AS role, u.is_active '
            . 'FROM users AS u LEFT JOIN roles AS r ON r.id = u.role_id '
            . 'WHERE u.username = ? OR (u.email IS NOT NULL AND LOWER(TRIM(u.email)) = ?) LIMIT 2'
        );
        $statement->bind_param('ss', $identity, $normalizedEmail);
        $statement->execute();
        $matches = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        // Ambiguous legacy identity values must not select an arbitrary account.
        return count($matches) === 1 ? $matches[0] : null;
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
                'SELECT id FROM users WHERE username = ? OR (email IS NOT NULL AND LOWER(TRIM(email)) = ?) LIMIT 1'
            );
            $duplicate->bind_param('ss', $username, $email);
            $duplicate->execute();
            $alreadyExists = $duplicate->get_result()->num_rows > 0;
            $duplicate->close();
            if ($alreadyExists) {
                throw new DomainException('That username or email is already registered.');
            }

            $roleLookup = $connection->prepare("SELECT id FROM roles WHERE role_name = 'Tester' LIMIT 1 FOR UPDATE");
            $roleLookup->execute();
            $testerRoleId = (int) ($roleLookup->get_result()->fetch_assoc()['id'] ?? 0);
            $roleLookup->close();
            if ($testerRoleId < 1) {
                throw new RuntimeException('The built-in Tester role is missing. Apply the latest database migration.');
            }

            $insertUser = $connection->prepare(
                "INSERT INTO users (name, username, email, password, role, role_id, is_active) VALUES (?, ?, ?, ?, 'Tester', ?, 1)"
            );
            $insertUser->bind_param('ssssi', $name, $username, $email, $passwordHash, $testerRoleId);
            $insertUser->execute();
            $userId = (int) $connection->insert_id;
            $insertUser->close();

            // Public email is not verified by this app, so it must never claim an existing staff profile.
            $existingProfiles = $connection->prepare('SELECT user_id FROM testers WHERE LOWER(TRIM(email)) = ? LIMIT 2 FOR UPDATE');
            $existingProfiles->bind_param('s', $email);
            $existingProfiles->execute();
            $profileMatches = $existingProfiles->get_result()->fetch_all(MYSQLI_ASSOC);
            $existingProfiles->close();
            if ($profileMatches !== []) {
                $hasLinkedProfile = count(array_filter($profileMatches, static fn (array $profile): bool => $profile['user_id'] !== null)) > 0;
                throw new DomainException($hasLinkedProfile
                    ? 'This email already belongs to a linked Tester profile. Sign in or ask an Administrator to reconcile the account.'
                    : 'A Tester profile already uses this email. Ask an Administrator to create and link the account.');
            }

            // Only create a fresh profile from public registration; administrators handle identity linking.
            $insertProfile = $connection->prepare(
                "INSERT INTO testers (user_id, name, department, designation, email, is_active) VALUES (?, ?, NULL, 'Lab Tester', ?, 1)"
            );
            $insertProfile->bind_param('iss', $userId, $name, $email);
            $insertProfile->execute();
            $profileId = (int) $connection->insert_id;
            $insertProfile->close();

            // Audit the new public registration inside the same account/profile transaction.
            audit_write($connection, 'user_registered', 'User', (string) $userId, 'A new Tester account was registered.', [
                'username' => $username,
                'role' => 'Tester',
                'tester_profile_id' => $profileId,
            ]);

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

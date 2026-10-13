<?php
// Store workspace choices against the authenticated account so preferences follow each staff role.
declare(strict_types=1);

final class UserPreferences
{
    /** Default values are returned safely while an older database awaits the additive migration. */
    public static function defaults(): array
    {
        return [
            'theme' => 'dark',
            'compact_dashboard' => false,
            'reduced_motion' => false,
            'schema_available' => true,
        ];
    }

    /** Read preferences from the database; do not make the rest of the workspace depend on migration timing. */
    public static function find(mysqli $connection, int $userId): array
    {
        $defaults = self::defaults();
        if ($userId < 1) {
            return $defaults;
        }

        try {
            $statement = $connection->prepare(
                'SELECT theme, compact_dashboard, reduced_motion FROM user_preferences WHERE user_id = ? LIMIT 1'
            );
            $statement->bind_param('i', $userId);
            $statement->execute();
            $row = $statement->get_result()->fetch_assoc() ?: null;
            $statement->close();
            if ($row === null) {
                return $defaults;
            }

            return [
                'theme' => in_array((string) $row['theme'], ['dark', 'light'], true) ? (string) $row['theme'] : 'dark',
                'compact_dashboard' => (int) $row['compact_dashboard'] === 1,
                'reduced_motion' => (int) $row['reduced_motion'] === 1,
                'schema_available' => true,
            ];
        } catch (Throwable $exception) {
            $defaults['schema_available'] = false;
            return $defaults;
        }
    }

    /** Upsert one full, validated preference set without exposing any other account's settings. */
    public static function save(mysqli $connection, int $userId, array $preferences): array
    {
        if ($userId < 1) {
            throw new InvalidArgumentException('A signed-in account is required to save preferences.');
        }

        $theme = (string) ($preferences['theme'] ?? 'dark');
        if (!in_array($theme, ['dark', 'light'], true)) {
            throw new InvalidArgumentException('Choose either the dark or light dashboard theme.');
        }
        $compactDashboard = !empty($preferences['compact_dashboard']) ? 1 : 0;
        $reducedMotion = !empty($preferences['reduced_motion']) ? 1 : 0;

        $statement = $connection->prepare(
            'INSERT INTO user_preferences (user_id, theme, compact_dashboard, reduced_motion) VALUES (?, ?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE theme = VALUES(theme), compact_dashboard = VALUES(compact_dashboard), reduced_motion = VALUES(reduced_motion)'
        );
        $statement->bind_param('isii', $userId, $theme, $compactDashboard, $reducedMotion);
        $statement->execute();
        $statement->close();

        return [
            'theme' => $theme,
            'compact_dashboard' => $compactDashboard === 1,
            'reduced_motion' => $reducedMotion === 1,
            'schema_available' => true,
        ];
    }
}

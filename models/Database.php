<?php
// Provide one configured MySQL connection for the legacy pages and new MVC code.

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

final class Database
{
    private static ?mysqli $connection = null;

    /**
     * Return a UTF-8 MySQL connection, optionally using a provider CA certificate.
     */
    public static function connection(): mysqli
    {
        if (self::$connection instanceof mysqli) {
            return self::$connection;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $host = env_value('DB_HOST', '127.0.0.1') ?? '127.0.0.1';
        $port = (int) (env_value('DB_PORT', '3306') ?? '3306');
        $database = env_value('DB_NAME', 'lab_automation') ?? 'lab_automation';
        $username = env_value('DB_USER', 'root') ?? 'root';
        $password = env_value('DB_PASSWORD', '') ?? '';
        $caFile = env_value('DB_SSL_CA', '') ?? '';

        try {
            $connection = mysqli_init();
            if (!$connection instanceof mysqli) {
                throw new RuntimeException('Could not initialize the MySQL client.');
            }

            $connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 8);
            $flags = 0;

            if ($caFile !== '') {
                if (!is_readable($caFile)) {
                    throw new RuntimeException('The configured DB_SSL_CA file is not readable.');
                }

                $connection->ssl_set(null, null, $caFile, null, null);
                if (defined('MYSQLI_OPT_SSL_VERIFY_SERVER_CERT')) {
                    $connection->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
                }
                $flags = MYSQLI_CLIENT_SSL;
            }

            $connection->real_connect($host, $username, $password, $database, $port, null, $flags);
            $connection->set_charset('utf8mb4');
            self::$connection = $connection;

            return self::$connection;
        } catch (Throwable $exception) {
            error_log('Lab Automation database connection failed: ' . $exception->getMessage());
            throw new RuntimeException('Database connection failed. Check the private .env settings.', 0, $exception);
        }
    }
}

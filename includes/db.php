<?php
/**
 * Database connection (mysqli). Every query in the app uses prepared
 * statements; mysqli throws exceptions on error so failures are never silent.
 */

declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $conn->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        error_log('[Aatman] DB connection failed: ' . $e->getMessage());
        http_response_code(503);
        exit(APP_DEBUG
            ? 'Database connection failed: ' . htmlspecialchars($e->getMessage())
            : 'The service is temporarily unavailable. Please try again later.');
    }
    return $conn;
}

/**
 * Run a prepared statement and return the mysqli_stmt.
 * Types are inferred: int => i, float => d, everything else => s.
 */
function db_exec(string $sql, array $params = []): mysqli_stmt
{
    $stmt = db()->prepare($sql);
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        }
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

/** Fetch all rows as associative arrays. */
function db_all(string $sql, array $params = []): array
{
    return db_exec($sql, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** Fetch a single row, or null. */
function db_one(string $sql, array $params = []): ?array
{
    $row = db_exec($sql, $params)->get_result()->fetch_assoc();
    return $row ?: null;
}

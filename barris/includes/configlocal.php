<?php
/**
 * Alacant Barris · Configuració de Base de Dades
 * Modifica aquestes constants amb les teves credencials
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'alacant_barris');
define('DB_USER', 'root');       // ← canvia
define('DB_PASS', '');           // ← canvia
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// Entorn
define('APP_DEBUG', true);
define('APP_VERSION', '1.0.0');

/**
 * Retorna connexió PDO singleton
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(503);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'    => false,
                'error' => APP_DEBUG ? $e->getMessage() : 'Error de connexió a la base de dades'
            ]);
            exit;
        }
    }
    return $pdo;
}

/**
 * Respon JSON i acaba l'execució
 */
function json_response(mixed $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Neteja i valida entrada
 */
function sanitize(string $val, int $maxlen = 500): string {
    return mb_substr(trim(strip_tags($val)), 0, $maxlen);
}

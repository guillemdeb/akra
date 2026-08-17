<?php
/**
 * API · /api/peticions.php
 * GET  ?action=list&status=X&barri_id=X&cat_id=X&page=X
 * POST ?action=create   → nova petició
 * POST ?action=update   → canviar estat (admin)
 * POST ?action=vot      → votar (+1)
 */

require_once __DIR__ . '/../includes/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = sanitize($_GET['action'] ?? 'list', 50);
$method = $_SERVER['REQUEST_METHOD'];

match(true) {

    // ── Llista de peticions ─────────────────────────
    $action === 'list' && $method === 'GET' => (function() {
        $pdo    = db();
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $where = ['1=1'];
        $params = [];

        if (!empty($_GET['status'])) {
            $s = sanitize($_GET['status'], 20);
            if (in_array($s, ['pendent','process','resolt','rebutjat'])) {
                $where[] = 'p.estat = ?';
                $params[] = $s;
            }
        }
        if (!empty($_GET['barri_id'])) {
            $where[] = 'p.barri_id = ?';
            $params[] = (int)$_GET['barri_id'];
        }
        if (!empty($_GET['cat_id'])) {
            $where[] = 'p.categoria_id = ?';
            $params[] = (int)$_GET['cat_id'];
        }

        $whereStr = implode(' AND ', $where);

        $total = $pdo->prepare("SELECT COUNT(*) FROM peticions p WHERE $whereStr");
        $total->execute($params);
        $totalCount = (int)$total->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT p.id, p.titol, p.descripcio, p.prioritat, p.estat,
                   p.votos, p.creat_en, p.actualitzat,
                   b.nom AS barri_nom, b.color AS barri_color,
                   c.nom AS cat_nom, c.icona AS cat_icona, c.color AS cat_color
            FROM peticions p
            LEFT JOIN barris     b ON b.id = p.barri_id
            LEFT JOIN categories c ON c.id = p.categoria_id
            WHERE $whereStr
            ORDER BY
                FIELD(p.estat,'pendent','process','resolt','rebutjat'),
                FIELD(p.prioritat,'alta','mitja','baixa'),
                p.creat_en DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(array_merge($params, [$limit, $offset]));

        json_response([
            'ok'    => true,
            'data'  => $stmt->fetchAll(),
            'meta'  => [
                'total'      => $totalCount,
                'page'       => $page,
                'per_page'   => $limit,
                'last_page'  => (int)ceil($totalCount / $limit),
            ]
        ]);
    })(),

    // ── Crear nova petició ──────────────────────────
    $action === 'create' && $method === 'POST' => (function() {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $barri_id    = (int)($body['barri_id']    ?? 0);
        $categoria_id= (int)($body['categoria_id'] ?? 0);
        $titol       = sanitize($body['titol']      ?? '', 200);
        $descripcio  = sanitize($body['descripcio'] ?? '', 2000);
        $prioritat   = sanitize($body['prioritat']  ?? 'mitja', 10);
        $email       = filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';

        if (!$barri_id || !$categoria_id || strlen($titol) < 5 || strlen($descripcio) < 10) {
            json_response(['ok' => false, 'error' => 'Dades incompletes o invàlides'], 422);
        }
        if (!in_array($prioritat, ['alta','mitja','baixa'])) {
            $prioritat = 'mitja';
        }

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $ip = trim(explode(',', $ip)[0]); // Primer IP en cas de proxy

        $pdo = db();

        // Límit anti-spam: màx 5 peticions per IP en l'última hora
        $spam = $pdo->prepare("SELECT COUNT(*) FROM peticions WHERE ip=? AND creat_en > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $spam->execute([$ip]);
        if ((int)$spam->fetchColumn() >= 5) {
            json_response(['ok'=>false,'error'=>'Has superat el límit de sol·licituds. Torna-ho a provar en una hora.'], 429);
        }

        $stmt = $pdo->prepare("
            INSERT INTO peticions (barri_id, categoria_id, titol, descripcio, prioritat, email, ip)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$barri_id, $categoria_id, $titol, $descripcio, $prioritat, $email, $ip]);
        $newId = $pdo->lastInsertId();

        json_response(['ok' => true, 'id' => (int)$newId, 'message' => 'Sol·licitud enviada correctament'], 201);
    })(),

    // ── Actualitzar estat (requereix sessió admin) ──────────
    $action === 'update' && $method === 'POST' => (function() {
        // Protegit: només des del panell admin (sessió activa)
        session_start();
        if (empty($_SESSION['admin'])) {
            json_response(['ok' => false, 'error' => 'No autoritzat'], 403);
        }

        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = (int)($body['id']    ?? 0);
        $estat = sanitize($body['estat'] ?? '', 20);

        if (!$id || !in_array($estat, ['pendent','process','resolt','rebutjat'])) {
            json_response(['ok' => false, 'error' => 'Paràmetres invàlids'], 400);
        }

        $pdo  = db();
        // Guarda auditoria
        $old = $pdo->prepare("SELECT estat FROM peticions WHERE id = ?");
        $old->execute([$id]);
        $prev = $old->fetchColumn();

        $pdo->prepare("UPDATE peticions SET estat = ? WHERE id = ?")->execute([$estat, $id]);

        if ($prev !== false && $prev !== $estat) {
            $pdo->prepare("
                INSERT INTO auditoria (taula, registre_id, camp, valor_old, valor_new)
                VALUES ('peticions', ?, 'estat', ?, ?)
            ")->execute([$id, $prev, $estat]);
        }

        json_response(['ok' => true, 'message' => 'Estat actualitzat']);
    })(),

    // ── Votar petició ───────────────────────────────
    $action === 'vot' && $method === 'POST' => (function() {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        if (!$id) json_response(['ok' => false, 'error' => 'ID invàlid'], 400);

        $ip  = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')[0]);
        $pdo = db();

        // Comprova si aquest IP ja ha votat aquesta petició
        $chk = $pdo->prepare("SELECT id FROM auditoria WHERE taula='vots' AND registre_id=? AND camp=?");
        $chk->execute([$id, $ip]);
        if ($chk->fetch()) {
            // Retorna el recompte actual sense error visible
            $votos = $pdo->prepare("SELECT votos FROM peticions WHERE id=?");
            $votos->execute([$id]);
            json_response(['ok' => true, 'votos' => (int)$votos->fetchColumn(), 'ja_votat' => true]);
        }

        $pdo->prepare("UPDATE peticions SET votos = votos + 1 WHERE id = ? AND estat != 'resolt'")->execute([$id]);

        // Registra el vot a auditoria per evitar duplicats
        $pdo->prepare("INSERT INTO auditoria (taula, registre_id, camp, valor_new) VALUES ('vots', ?, ?, '1')")
            ->execute([$id, $ip]);

        $votos = $pdo->prepare("SELECT votos FROM peticions WHERE id = ?");
        $votos->execute([$id]);

        json_response(['ok' => true, 'votos' => (int)$votos->fetchColumn()]);
    })(),

    default => json_response(['ok' => false, 'error' => 'Acció no trobada'], 404),
};

<?php
/**
 * API · /api/barris.php
 * GET  ?action=list            → tots els barris amb puntuació
 * GET  ?action=get&id=X        → un barri amb tots els recursos
 * GET  ?action=stats           → estadístiques globals
 * GET  ?action=categories      → llista de categories
 * POST ?action=update_estat    → actualitza estat d'un recurs
 */

require_once __DIR__ . '/../includes/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = sanitize($_GET['action'] ?? 'list', 50);
$method = $_SERVER['REQUEST_METHOD'];

// ── Helpers ─────────────────────────────────────────
function calcScore(array $recursos): int {
    if (!$recursos) return 0;
    $sum = 0;
    foreach ($recursos as $r) {
        $sum += match($r['estat']) { 'ok' => 1, 'partial' => 0.5, default => 0 };
    }
    return (int) round($sum / count($recursos) * 100);
}

// ── Router ──────────────────────────────────────────
match(true) {

    // ── Llista de barris ──────────────────────────────
    $action === 'list' && $method === 'GET' => (function() {
        $pdo = db();

        $barris = $pdo->query("
            SELECT b.id, b.nom, b.slug, b.color, b.lat, b.lng, b.poblacio,
                   d.nom AS districte, d.numero AS districte_num
            FROM barris b
            JOIN districtes d ON d.id = b.districte_id
            WHERE b.actiu = 1
            ORDER BY d.numero, b.nom
        ")->fetchAll();

        $recursos_raw = $pdo->query("
            SELECT rb.barri_id, rb.estat, c.slug AS cat_slug, c.nom AS cat_nom,
                   c.icona, c.color AS cat_color
            FROM recursos_barri rb
            JOIN categories c ON c.id = rb.categoria_id
            WHERE c.activa = 1
            ORDER BY c.ordre
        ")->fetchAll();

        // Agrupar recursos per barri
        $rm = [];
        foreach ($recursos_raw as $r) {
            $rm[$r['barri_id']][] = $r;
        }

        foreach ($barris as &$b) {
            $res = $rm[$b['id']] ?? [];
            $b['recursos'] = $res;
            $b['score']    = calcScore($res);
            $b['missing']  = count(array_filter($res, fn($r) => $r['estat'] === 'missing'));
            $b['partial']  = count(array_filter($res, fn($r) => $r['estat'] === 'partial'));
        }

        json_response(['ok' => true, 'data' => $barris]);
    })(),

    // ── Un barri ──────────────────────────────────────
    $action === 'get' && $method === 'GET' => (function() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) json_response(['ok' => false, 'error' => 'ID invàlid'], 400);

        $pdo  = db();
        $barri = $pdo->prepare("
            SELECT b.*, d.nom AS districte, d.numero AS districte_num, d.color AS districte_color
            FROM barris b JOIN districtes d ON d.id = b.districte_id
            WHERE b.id = ? AND b.actiu = 1
        ");
        $barri->execute([$id]);
        $b = $barri->fetch();
        if (!$b) json_response(['ok' => false, 'error' => 'Barri no trobat'], 404);

        $recursos = $pdo->prepare("
            SELECT rb.id, rb.estat, rb.notes, rb.actualitzat,
                   c.id AS cat_id, c.slug AS cat_slug, c.nom AS cat_nom,
                   c.icona, c.color AS cat_color
            FROM recursos_barri rb
            JOIN categories c ON c.id = rb.categoria_id
            WHERE rb.barri_id = ? AND c.activa = 1
            ORDER BY c.ordre
        ");
        $recursos->execute([$id]);
        $res = $recursos->fetchAll();

        $peticions = $pdo->prepare("
            SELECT p.id, p.titol, p.prioritat, p.estat, p.votos, p.creat_en,
                   c.nom AS cat_nom, c.icona
            FROM peticions p
            LEFT JOIN categories c ON c.id = p.categoria_id
            WHERE p.barri_id = ?
            ORDER BY p.creat_en DESC
            LIMIT 5
        ");
        $peticions->execute([$id]);

        $b['recursos']  = $res;
        $b['score']     = calcScore($res);
        $b['peticions'] = $peticions->fetchAll();

        json_response(['ok' => true, 'data' => $b]);
    })(),

    // ── Estadístiques globals ──────────────────────────
    $action === 'stats' && $method === 'GET' => (function() {
        $pdo = db();

        $totals = $pdo->query("
            SELECT
                COUNT(DISTINCT b.id)                               AS total_barris,
                SUM(rb.estat = 'missing')                          AS total_missing,
                SUM(rb.estat = 'partial')                          AS total_partial,
                SUM(rb.estat = 'ok')                               AS total_ok
            FROM barris b
            JOIN recursos_barri rb ON rb.barri_id = b.id
            WHERE b.actiu = 1
        ")->fetch();

        $total_recursos = $totals['total_missing'] + $totals['total_partial'] + $totals['total_ok'];
        $cobertura = $total_recursos > 0
            ? round(($totals['total_ok'] + $totals['total_partial'] * 0.5) / $total_recursos * 100)
            : 0;

        $peticions = $pdo->query("
            SELECT
                COUNT(*)                         AS total,
                SUM(estat = 'pendent')           AS pendents,
                SUM(estat = 'process')           AS en_proces,
                SUM(estat = 'resolt')            AS resoltes
            FROM peticions
        ")->fetch();

        // Per categoria
        $per_cat = $pdo->query("
            SELECT c.id, c.slug, c.nom, c.icona, c.color,
                   SUM(rb.estat = 'ok')      AS total_ok,
                   SUM(rb.estat = 'partial') AS total_partial,
                   SUM(rb.estat = 'missing') AS total_missing,
                   COUNT(*)                  AS total
            FROM categories c
            JOIN recursos_barri rb ON rb.categoria_id = c.id
            JOIN barris b ON b.id = rb.barri_id AND b.actiu = 1
            WHERE c.activa = 1
            GROUP BY c.id
            ORDER BY c.ordre
        ")->fetchAll();

        foreach ($per_cat as &$c) {
            $c['cobertura'] = (int) round(($c['total_ok'] + $c['total_partial'] * 0.5) / $c['total'] * 100);
        }

        // Pitjors barris
        $pitjors = $pdo->query("
            SELECT b.id, b.nom, b.color,
                   d.nom AS districte,
                   ROUND(
                     (SUM(rb.estat='ok') + SUM(rb.estat='partial')*0.5)
                     / COUNT(rb.id) * 100
                   ) AS score,
                   SUM(rb.estat='missing') AS missing_count
            FROM barris b
            JOIN districtes d ON d.id = b.districte_id
            JOIN recursos_barri rb ON rb.barri_id = b.id
            WHERE b.actiu = 1
            GROUP BY b.id
            ORDER BY score ASC
            LIMIT 5
        ")->fetchAll();

        json_response([
            'ok'   => true,
            'data' => [
                'totals'    => array_merge($totals, ['cobertura' => $cobertura]),
                'peticions' => $peticions,
                'per_cat'   => $per_cat,
                'pitjors'   => $pitjors,
            ]
        ]);
    })(),

    // ── Categories ──────────────────────────────────
    $action === 'categories' && $method === 'GET' => (function() {
        $cats = db()->query("
            SELECT id, slug, nom, icona, color, ordre
            FROM categories WHERE activa = 1 ORDER BY ordre
        ")->fetchAll();
        json_response(['ok' => true, 'data' => $cats]);
    })(),

    // ── Actualitzar estat recurs (admin) ────────────
    $action === 'update_estat' && $method === 'POST' => (function() {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $barri_id    = (int)($body['barri_id']    ?? 0);
        $categoria_id= (int)($body['categoria_id'] ?? 0);
        $estat       = sanitize($body['estat'] ?? '', 10);
        $notes       = sanitize($body['notes'] ?? '', 500);

        if (!$barri_id || !$categoria_id || !in_array($estat, ['ok','partial','missing'])) {
            json_response(['ok' => false, 'error' => 'Paràmetres invàlids'], 400);
        }

        $pdo = db();

        // Verifica que barri i categoria existisquen
        $chk = $pdo->prepare("SELECT b.id FROM barris b WHERE b.id=? AND b.actiu=1");
        $chk->execute([$barri_id]);
        if (!$chk->fetch()) json_response(['ok'=>false,'error'=>'Barri no trobat o inactiu'], 404);

        $chk2 = $pdo->prepare("SELECT id FROM categories WHERE id=? AND activa=1");
        $chk2->execute([$categoria_id]);
        if (!$chk2->fetch()) json_response(['ok'=>false,'error'=>'Categoria no trobada'], 404);

        $stmt = $pdo->prepare("
            INSERT INTO recursos_barri (barri_id, categoria_id, estat, notes)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE estat = VALUES(estat), notes = VALUES(notes), actualitzat = NOW()
        ");
        $stmt->execute([$barri_id, $categoria_id, $estat, $notes]);

        json_response(['ok' => true, 'message' => 'Estat actualitzat correctament']);
    })(),

    default => json_response(['ok' => false, 'error' => 'Acció no trobada'], 404),
};

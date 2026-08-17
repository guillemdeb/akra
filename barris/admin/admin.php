<?php
/**
 * Alacant Barris · PANELL D'ADMINISTRACIÓ
 * admin/index.php
 */
require_once __DIR__ . '/../includes/config.php';

define('ADMIN_PASS', 'alacant2026'); // ← CANVIA AÇÒ

session_start();

// ── IDIOMA (ha d'estar ABANS del login check) ─────────────────────────────────
$lang = $_COOKIE['lang'] ?? 'ca';
if (!in_array($lang, ['ca','es'])) $lang = 'ca';
$at = $lang === 'ca' ? [
    'title'       => "Panell d'Administració",
    'dashboard'   => 'Dashboard',
    'mapa'        => 'Mapa interactiu',
    'barris'      => 'Barris',
    'districtes'  => 'Districtes',
    'categories'  => 'Categories',
    'peticions'   => 'Peticions',
    'geo'         => 'Gestió territorial',
    'sol'         => "Sol·licituds",
    'general'     => 'Visió general',
    'logout'      => 'Tancar sessió',
    'login_lbl'   => 'Contrasenya',
    'login_btn'   => 'Entrar →',
    'login_err'   => 'Contrasenya incorrecta',
    'new_barri'   => 'Nou barri',
    'new_dist'    => 'Nou districte',
    'new_cat'     => 'Nova categoria',
    'save'        => 'Guardar',
    'cancel'      => "Cancel·lar",
    'delete'      => 'Eliminar',
    'barris_act'  => 'Barris actius',
    'mancances'   => 'Mancances',
    'cobertura'   => 'Cobertura',
    'pend'        => 'Peticions pendents',
    'quick'       => 'Accions ràpides',
    'lang_btn'    => 'ES',
    'info_tab'    => 'Informació',
    'pos_tab'     => 'Posició al mapa',
    'nom_lbl'     => 'Nom *',
    'dist_lbl'    => 'Districte *',
    'color_lbl'   => 'Color',
    'pob_lbl'     => 'Població',
    'actiu_lbl'   => 'Actiu al sistema',
    'actiu_si'    => '✅ Sí, visible',
    'actiu_no'    => '❌ No, ocult',
    'num_lbl'     => 'Número',
    'icona_lbl'   => 'Icona (emoji) *',
    'ordre_lbl'   => 'Ordre',
    'slug_lbl'    => 'Slug (ID intern) *',
    'deactivate'  => 'Desactivar',
    'pos_tip'     => "Fes clic al mapa per col·locar el barri, o arrossega el marcador.",
    'save_barri'  => 'Guardar barri',
    'save_dist'   => 'Guardar districte',
    'save_cat'    => 'Guardar categoria',
    'col_id'      => 'ID',
    'col_color'   => 'Color',
    'col_nom'     => 'Nom',
    'col_dist'    => 'Districte',
    'col_coords'  => 'Lat / Lng',
    'col_pob'     => 'Població',
    'col_actiu'   => 'Actiu',
    'col_acc'     => 'Accions',
    'col_num'     => 'Nº',
    'col_barris'  => 'Barris',
    'col_icona'   => 'Icona',
    'col_slug'    => 'Slug',
    'col_ordre'   => 'Ordre',
    'col_cat'     => 'Cat.',
    'col_titol'   => 'Títol',
    'col_prio'    => 'Prio.',
    'col_estat'   => 'Estat',
    'col_vots'    => 'Vots',
    'col_data'    => 'Data',
    'drag_info'   => 'Arrossega per moure · Clic per inspeccionar',
    'light_mode'  => 'Mode clar',
    'dark_mode'   => 'Mode fosc',
    'move_on'     => 'Moure ON',
    'move_off'    => 'Moure OFF',
    'center'      => 'Centrar',
    'all'         => 'Totes',
    'pendent'     => '⏳ Pendents',
    'process'     => '🔄 En procés',
    'resolt'      => '✅ Resoltes',
    'rebutjat'    => '🚫 Rebutjades',
    'edit_map'    => '🗺️ Editar mapa',
    'search_ph'   => 'Cercar...',
    'res_section' => "Recursos · fes clic per canviar l'estat",
    'edit_barri_btn' => "Editar informació del barri",
] : [
    'title'       => 'Panel de Administración',
    'dashboard'   => 'Dashboard',
    'mapa'        => 'Mapa interactivo',
    'barris'      => 'Barrios',
    'districtes'  => 'Distritos',
    'categories'  => 'Categorías',
    'peticions'   => 'Peticiones',
    'geo'         => 'Gestión territorial',
    'sol'         => 'Solicitudes',
    'general'     => 'Visión general',
    'logout'      => 'Cerrar sesión',
    'login_lbl'   => 'Contraseña',
    'login_btn'   => 'Entrar →',
    'login_err'   => 'Contraseña incorrecta',
    'new_barri'   => 'Nuevo barrio',
    'new_dist'    => 'Nuevo distrito',
    'new_cat'     => 'Nueva categoría',
    'save'        => 'Guardar',
    'cancel'      => 'Cancelar',
    'delete'      => 'Eliminar',
    'barris_act'  => 'Barrios activos',
    'mancances'   => 'Carencias',
    'cobertura'   => 'Cobertura',
    'pend'        => 'Peticiones pendientes',
    'quick'       => 'Acciones rápidas',
    'lang_btn'    => 'CA',
    'info_tab'    => 'Información',
    'pos_tab'     => 'Posición en mapa',
    'nom_lbl'     => 'Nombre *',
    'dist_lbl'    => 'Distrito *',
    'color_lbl'   => 'Color',
    'pob_lbl'     => 'Población',
    'actiu_lbl'   => 'Activo en el sistema',
    'actiu_si'    => '✅ Sí, visible',
    'actiu_no'    => '❌ No, oculto',
    'num_lbl'     => 'Número',
    'icona_lbl'   => 'Icono (emoji) *',
    'ordre_lbl'   => 'Orden',
    'slug_lbl'    => 'Slug (ID interno) *',
    'deactivate'  => 'Desactivar',
    'pos_tip'     => 'Haz clic en el mapa para colocar el barrio, o arrastra el marcador.',
    'save_barri'  => 'Guardar barrio',
    'save_dist'   => 'Guardar distrito',
    'save_cat'    => 'Guardar categoría',
    'col_id'      => 'ID',
    'col_color'   => 'Color',
    'col_nom'     => 'Nombre',
    'col_dist'    => 'Distrito',
    'col_coords'  => 'Lat / Lng',
    'col_pob'     => 'Población',
    'col_actiu'   => 'Activo',
    'col_acc'     => 'Acciones',
    'col_num'     => 'Nº',
    'col_barris'  => 'Barrios',
    'col_icona'   => 'Icono',
    'col_slug'    => 'Slug',
    'col_ordre'   => 'Orden',
    'col_cat'     => 'Cat.',
    'col_titol'   => 'Título',
    'col_prio'    => 'Prio.',
    'col_estat'   => 'Estado',
    'col_vots'    => 'Votos',
    'col_data'    => 'Fecha',
    'drag_info'   => 'Arrastra para mover · Clic para inspeccionar',
    'light_mode'  => 'Modo claro',
    'dark_mode'   => 'Modo oscuro',
    'move_on'     => 'Mover ON',
    'move_off'    => 'Mover OFF',
    'center'      => 'Centrar',
    'all'         => 'Todas',
    'pendent'     => '⏳ Pendientes',
    'process'     => '🔄 En proceso',
    'resolt'      => '✅ Resueltas',
    'rebutjat'    => '🚫 Rechazadas',
    'edit_map'    => '🗺️ Editar mapa',
    'search_ph'   => 'Buscar...',
    'res_section' => 'Recursos · haz clic para cambiar el estado',
    'edit_barri_btn' => 'Editar información del barrio',
];

$loginError = '';

if (isset($_POST['logout'])) { session_destroy(); header('Location: index.php'); exit; }
if (isset($_POST['login_pass'])) {
    if ($_POST['login_pass'] === ADMIN_PASS) { $_SESSION['admin'] = true; header('Location: index.php'); exit; }
    $loginError = $at['login_err'];
}
if (!isset($_SESSION['admin'])) { showLogin($loginError, $lang, $at); exit; }

// ── API INTERNA ──────────────────────────────────────────────────────────────
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['ajax'] ?? '';
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    try {
        $pdo = db();
        switch ($action) {

            case 'barris_list':
                $rows = $pdo->query("
                    SELECT b.*, d.nom AS districte_nom, d.numero AS districte_num, d.color AS districte_color
                    FROM barris b JOIN districtes d ON d.id = b.districte_id
                    ORDER BY d.numero, b.nom
                ")->fetchAll();
                echo json_encode(['ok'=>true,'data'=>$rows]);
                break;

            case 'barri_recursos':
                $id = (int)($_GET['id'] ?? 0);
                $rows = $pdo->prepare("
                    SELECT rb.id, rb.estat, rb.notes,
                           c.id AS cat_id, c.slug, c.nom AS cat_nom, c.icona, c.color AS cat_color, c.ordre
                    FROM recursos_barri rb
                    JOIN categories c ON c.id = rb.categoria_id
                    WHERE rb.barri_id = ? AND c.activa = 1
                    ORDER BY c.ordre
                ");
                $rows->execute([$id]);
                echo json_encode(['ok'=>true,'data'=>$rows->fetchAll()]);
                break;

            case 'barri_coords':
                $id  = (int)($body['id'] ?? 0);
                $lat = (float)($body['lat'] ?? 0);
                $lng = (float)($body['lng'] ?? 0);
                if (!$id || !$lat || !$lng) { echo json_encode(['ok'=>false,'error'=>'Dades invàlides']); break; }
                $pdo->prepare("UPDATE barris SET lat=?, lng=? WHERE id=?")->execute([$lat,$lng,$id]);
                echo json_encode(['ok'=>true]);
                break;

            case 'barri_save':
                $id           = (int)($body['id'] ?? 0);
                $nom          = trim($body['nom'] ?? '');
                $districte_id = (int)($body['districte_id'] ?? 0);
                $color        = trim($body['color'] ?? '#3b82f6');
                $lat          = (float)($body['lat'] ?? 0);
                $lng          = (float)($body['lng'] ?? 0);
                $poblacio     = (int)($body['poblacio'] ?? 0);
                $actiu        = (int)($body['actiu'] ?? 1);
                if (!$nom || !$districte_id) { echo json_encode(['ok'=>false,'error'=>'Nom i districte obligatoris']); break; }
                $slug = preg_replace('/[^a-z0-9]+/','_', strtolower(strtr($nom, ['à'=>'a','á'=>'a','è'=>'e','é'=>'e','í'=>'i','ï'=>'i','ò'=>'o','ó'=>'o','ú'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n','·'=>''])));
                // Assegura slug únic afegint sufix numèric si cal
                $slug_base = $slug; $suf = 1;
                while (true) {
                    $chk = $pdo->prepare("SELECT id FROM barris WHERE slug=? AND id!=?");
                    $chk->execute([$slug, $id ?: 0]);
                    if (!$chk->fetch()) break;
                    $slug = $slug_base.'_'.$suf++;
                }
                if ($id) {
                    $pdo->prepare("UPDATE barris SET nom=?,slug=?,districte_id=?,color=?,lat=?,lng=?,poblacio=?,actiu=? WHERE id=?")
                        ->execute([$nom,$slug,$districte_id,$color,$lat,$lng,$poblacio,$actiu,$id]);
                } else {
                    $pdo->prepare("INSERT INTO barris (nom,slug,districte_id,color,lat,lng,poblacio,actiu) VALUES (?,?,?,?,?,?,?,?)")
                        ->execute([$nom,$slug,$districte_id,$color,$lat,$lng,$poblacio,$actiu]);
                    $id = (int)$pdo->lastInsertId();
                    // Crea registres de recursos per totes les categories actives
                    $cats = $pdo->query("SELECT id FROM categories WHERE activa=1")->fetchAll(PDO::FETCH_COLUMN);
                    $ins  = $pdo->prepare("INSERT IGNORE INTO recursos_barri (barri_id,categoria_id,estat) VALUES (?,?,'missing')");
                    foreach ($cats as $cid) $ins->execute([$id, $cid]);
                }
                echo json_encode(['ok'=>true,'id'=>(int)$id,'slug'=>$slug]);
                break;

            case 'barri_delete':
                $id = (int)($body['id'] ?? 0);
                if (!$id) { echo json_encode(['ok'=>false,'error'=>'ID invàlid']); break; }
                $pdo->prepare("UPDATE barris SET actiu=0 WHERE id=?")->execute([$id]);
                echo json_encode(['ok'=>true]);
                break;

            case 'recurs_update':
                $barri_id     = (int)($body['barri_id'] ?? 0);
                $categoria_id = (int)($body['categoria_id'] ?? 0);
                $estat         = trim($body['estat'] ?? 'missing');
                $notes         = trim($body['notes'] ?? '');
                if (!$barri_id || !$categoria_id) { echo json_encode(['ok'=>false,'error'=>'barri_id i categoria_id obligatoris']); break; }
                if (!in_array($estat, ['ok','partial','missing'])) { echo json_encode(['ok'=>false,'error'=>'Estat invàlid']); break; }
                $pdo->prepare("INSERT INTO recursos_barri (barri_id,categoria_id,estat,notes) VALUES (?,?,?,?)
                    ON DUPLICATE KEY UPDATE estat=VALUES(estat),notes=VALUES(notes)")
                    ->execute([$barri_id,$categoria_id,$estat,$notes]);
                echo json_encode(['ok'=>true]);
                break;

            case 'districtes_list':
                echo json_encode(['ok'=>true,'data'=>$pdo->query("SELECT * FROM districtes ORDER BY numero")->fetchAll()]);
                break;

            case 'districte_save':
                $id    = (int)($body['id'] ?? 0);
                $nom   = trim($body['nom'] ?? '');
                $num   = (int)($body['numero'] ?? 0);
                $color = trim($body['color'] ?? '#3b82f6');
                if (!$nom || !$num) { echo json_encode(['ok'=>false,'error'=>'Dades incompletes']); break; }
                if ($id) {
                    $pdo->prepare("UPDATE districtes SET nom=?,numero=?,color=? WHERE id=?")->execute([$nom,$num,$color,$id]);
                } else {
                    $pdo->prepare("INSERT INTO districtes (nom,numero,color) VALUES (?,?,?)")->execute([$nom,$num,$color]);
                    $id = $pdo->lastInsertId();
                }
                echo json_encode(['ok'=>true,'id'=>(int)$id]);
                break;

            case 'districte_delete':
                $id = (int)($body['id'] ?? 0);
                if (!$id) { echo json_encode(['ok'=>false,'error'=>'ID invàlid']); break; }
                // Comprova tots els barris (actius i inactius) per no trencar la FK
                $count = $pdo->prepare("SELECT COUNT(*) FROM barris WHERE districte_id=?");
                $count->execute([$id]);
                $n = (int)$count->fetchColumn();
                if ($n > 0) { echo json_encode(['ok'=>false,'error'=>"No es pot eliminar: té $n barri(s) assignat(s) (actius o inactius). Reassigna'ls primer."]); break; }
                $pdo->prepare("DELETE FROM districtes WHERE id=?")->execute([$id]);
                echo json_encode(['ok'=>true]);
                break;

            case 'categories_list':
                echo json_encode(['ok'=>true,'data'=>$pdo->query("SELECT * FROM categories ORDER BY ordre")->fetchAll()]);
                break;

            case 'categoria_save':
                $id    = (int)($body['id'] ?? 0);
                $slug  = preg_replace('/[^a-z0-9_]/','', strtolower(trim($body['slug'] ?? '')));
                $nom   = trim($body['nom'] ?? '');
                $icona = trim($body['icona'] ?? '📌');
                $color = trim($body['color'] ?? '#3b82f6');
                $ordre = (int)($body['ordre'] ?? 0);
                if (!$nom || !$slug) { echo json_encode(['ok'=>false,'error'=>'Nom i slug obligatoris']); break; }
                // Comprova slug duplicat
                $chk = $pdo->prepare("SELECT id FROM categories WHERE slug=? AND id!=?");
                $chk->execute([$slug, $id ?: 0]);
                if ($chk->fetch()) { echo json_encode(['ok'=>false,'error'=>'Aquest slug ja existeix']); break; }
                if ($id) {
                    $pdo->prepare("UPDATE categories SET slug=?,nom=?,icona=?,color=?,ordre=? WHERE id=?")->execute([$slug,$nom,$icona,$color,$ordre,$id]);
                } else {
                    $pdo->prepare("INSERT INTO categories (slug,nom,icona,color,ordre) VALUES (?,?,?,?,?)")->execute([$slug,$nom,$icona,$color,$ordre]);
                    $id = (int)$pdo->lastInsertId();
                    // Crea registres de recursos per tots els barris actius
                    $bids = $pdo->query("SELECT id FROM barris WHERE actiu=1")->fetchAll(PDO::FETCH_COLUMN);
                    $ins  = $pdo->prepare("INSERT IGNORE INTO recursos_barri (barri_id,categoria_id,estat) VALUES (?,'.$id.','missing')");
                    $ins2 = $pdo->prepare("INSERT IGNORE INTO recursos_barri (barri_id,categoria_id,estat) VALUES (?,?,'missing')");
                    foreach ($bids as $bid) $ins2->execute([$bid, $id]);
                }
                echo json_encode(['ok'=>true,'id'=>(int)$id]);
                break;

            case 'peticions_list':
                $page   = max(1, (int)($_GET['page'] ?? 1));
                $st_raw = $_GET['st'] ?? '';
                $st     = in_array($st_raw, ['pendent','process','resolt','rebutjat']) ? $st_raw : '';
                $params = [];
                $where  = '1=1';
                if ($st) { $where .= ' AND p.estat = ?'; $params[] = $st; }
                $limit  = 25;
                $offset = ($page - 1) * $limit;

                $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM peticions p WHERE $where");
                $total_stmt->execute($params);
                $total = (int)$total_stmt->fetchColumn();

                $rows_stmt = $pdo->prepare("
                    SELECT p.*, b.nom AS barri_nom, c.nom AS cat_nom, c.icona AS cat_icona
                    FROM peticions p
                    LEFT JOIN barris b ON b.id = p.barri_id
                    LEFT JOIN categories c ON c.id = p.categoria_id
                    WHERE $where
                    ORDER BY FIELD(p.estat,'pendent','process','resolt','rebutjat'),
                             FIELD(p.prioritat,'alta','mitja','baixa'),
                             p.creat_en DESC
                    LIMIT ? OFFSET ?
                ");
                $rows_stmt->execute(array_merge($params, [$limit, $offset]));
                echo json_encode(['ok'=>true,'data'=>$rows_stmt->fetchAll(),'total'=>$total,'page'=>$page,'last_page'=>(int)ceil($total/$limit)]);
                break;

            case 'peticio_estat':
                $id    = (int)($body['id'] ?? 0);
                $estat = $body['estat'] ?? '';
                if (!$id || !in_array($estat,['pendent','process','resolt','rebutjat'])) { echo json_encode(['ok'=>false,'error'=>'Invàlid']); break; }
                $pdo->prepare("UPDATE peticions SET estat=? WHERE id=?")->execute([$estat,$id]);
                echo json_encode(['ok'=>true]);
                break;

            case 'peticio_to_mancanca':
                $id = (int)($body['peticio_id'] ?? 0);
                if (!$id) { echo json_encode(['ok'=>false,'error'=>'ID invàlid']); break; }
                $pet = $pdo->prepare("SELECT barri_id, categoria_id FROM peticions WHERE id=?");
                $pet->execute([$id]);
                $row = $pet->fetch(PDO::FETCH_ASSOC);
                if (!$row || !$row['barri_id'] || !$row['categoria_id']) {
                    echo json_encode(['ok'=>false,'error'=>'Petició sense barri o categoria assignats']); break;
                }
                // Marca el recurs com a missing (mancança oficial)
                $pdo->prepare("INSERT INTO recursos_barri (barri_id,categoria_id,estat)
                    VALUES (?,?,'missing')
                    ON DUPLICATE KEY UPDATE estat='missing', notes=CONCAT(IFNULL(notes,''),' [Petició #".$id."]')")
                    ->execute([$row['barri_id'], $row['categoria_id']]);
                // Passa la petició a "en procés"
                $pdo->prepare("UPDATE peticions SET estat='process' WHERE id=?")->execute([$id]);
                echo json_encode(['ok'=>true]);
                break;

            case 'peticio_delete':
                $id = (int)($body['id'] ?? 0);
                if (!$id) { echo json_encode(['ok'=>false,'error'=>'ID invàlid']); break; }
                $check = $pdo->prepare("SELECT id FROM peticions WHERE id=?");
                $check->execute([$id]);
                if (!$check->fetch()) { echo json_encode(['ok'=>false,'error'=>'Petició no trobada']); break; }
                $pdo->prepare("DELETE FROM peticions WHERE id=?")->execute([$id]);
                echo json_encode(['ok'=>true]);
                break;

            case 'stats':
                $s = $pdo->query("
                    SELECT
                        (SELECT COUNT(*) FROM barris WHERE actiu=1) AS barris,
                        (SELECT COUNT(*) FROM peticions) AS peticions,
                        (SELECT COUNT(*) FROM peticions WHERE estat='pendent') AS pendents,
                        (SELECT SUM(estat='missing') FROM recursos_barri rb JOIN barris b ON b.id=rb.barri_id AND b.actiu=1) AS missing_total,
                        (SELECT SUM(estat='ok') FROM recursos_barri rb JOIN barris b ON b.id=rb.barri_id AND b.actiu=1) AS ok_total,
                        (SELECT COUNT(*) FROM recursos_barri rb JOIN barris b ON b.id=rb.barri_id AND b.actiu=1) AS total_r
                ")->fetch();
                // Cobertura: ok=1pt, partial=0.5pt, missing=0pt
                $partial_total = $s['total_r'] - $s['ok_total'] - $s['missing_total'];
                $s['cobertura'] = $s['total_r'] > 0
                    ? (int)round(($s['ok_total'] + $partial_total * 0.5) / $s['total_r'] * 100)
                    : 0;
                echo json_encode(['ok'=>true,'data'=>$s]);
                break;

            default: echo json_encode(['ok'=>false,'error'=>'Accio no trobada']);
        }
    } catch (Exception $e) { echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
    exit;
}

// Dades inicials pel HTML
$pdo_init = db();
$districtes_init = $pdo_init->query("SELECT * FROM districtes ORDER BY numero")->fetchAll();

function showLogin($err='', $lang='ca', $at=[]) { if(empty($at)){$at=['login_lbl'=>'Contrasenya','login_btn'=>'Entrar','title'=>"Panell d'Administració",'lang_btn'=>'ES'];} ?><!DOCTYPE html>
<html lang="ca"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin · <?= $lang=='ca'?'Alacant':'Alicante' ?> Barris</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0a0e1a;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:'DM Sans',sans-serif}
.card{background:#1e2d42;border:1px solid #2a3f5f;border-radius:20px;padding:40px;width:340px;box-shadow:0 8px 48px rgba(0,0,0,.6)}
.logo{width:56px;height:56px;background:linear-gradient(135deg,#3b82f6,#06b6d4);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 20px}
h1{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:#e8edf5;text-align:center;margin-bottom:6px}
p{font-size:13px;color:#8da0bb;text-align:center;margin-bottom:28px}
label{display:block;font-size:12px;color:#8da0bb;font-weight:500;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
input[type=password]{width:100%;background:#111827;border:1px solid #2a3f5f;border-radius:8px;color:#e8edf5;font-size:15px;padding:13px 16px;outline:none;transition:border .2s}
input:focus{border-color:#3b82f6}.err{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.4);color:#fca5a5;padding:10px 14px;border-radius:8px;font-size:13px;margin:12px 0}
button{width:100%;height:48px;background:linear-gradient(135deg,#3b82f6,#06b6d4);color:#fff;border:none;border-radius:8px;font-family:'Syne',sans-serif;font-size:15px;font-weight:700;cursor:pointer;margin-top:16px;transition:opacity .2s}
button:hover{opacity:.9}
</style></head><body>
<div class="card"><div class="logo">🏛️</div><h1><?= $lang=='ca'?'Alacant':'Alicante' ?> Barris</h1><p><?= $at['title'] ?></p>
<?php if($err): ?><div class="err">⚠️ <?=htmlspecialchars($err)?></div><?php endif ?>
<form method="POST"><label><?= $at['login_lbl'] ?></label><input type="password" name="login_pass" placeholder="••••••••" autofocus><button type="submit"><?= $at['login_btn'] ?></button></form>
</div><div style='text-align:right;margin-top:12px'><button onclick="var n=document.cookie.includes('lang=es')?'ca':'es';document.cookie='lang='+n+';path=/;max-age=31536000';location.reload()" style='background:none;border:1px solid rgba(255,255,255,.2);border-radius:6px;color:#8da0bb;padding:4px 10px;font-size:11px;font-weight:700;cursor:pointer'><?= $at['lang_btn'] ?></button></div></body></html><?php exit; }
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin · <?= $lang=='ca'?'Alacant':'Alicante' ?> Barris</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
:root{
  --bg:#0a0e1a;--bg2:#111827;--bg3:#1a2235;--surface:#1e2d42;
  --border:#2a3f5f;--text:#e8edf5;--text2:#8da0bb;--text3:#4a6080;
  --accent:#3b82f6;--accent2:#06b6d4;
  --success:#10b981;--warn:#f59e0b;--danger:#ef4444;
  --radius:12px;--radius-sm:8px;
  --shadow:0 4px 24px rgba(0,0,0,.4);
  --fd:'Syne',sans-serif;--fb:'DM Sans',sans-serif;
  --sidebar:260px;
}
/* MODE CLAR */
html.light{
  --bg:#f0f4f8;--bg2:#ffffff;--bg3:#e8edf5;--surface:#ffffff;
  --border:#d1dbe8;--text:#1a2235;--text2:#4a6080;--text3:#8da0bb;
  --shadow:0 4px 24px rgba(0,0,0,.1);
}
html.light .sb-item:hover{background:rgba(0,0,0,.04)}
html.light .sb-item.act{background:rgba(59,130,246,.12)}
html.light .btn-secondary{background:#f0f4f8;color:#4a6080}
html.light .btn-secondary:hover{color:var(--accent)}
html.light .fc{background:#f8fafc;color:#1a2235}
html.light .fc option{background:#fff}
html.light .map-tool-btn{background:#f0f4f8;color:#4a6080}
html.light .map-tool-btn:hover{color:var(--accent)}
html.light .map-tool-btn.active{background:rgba(59,130,246,.12)}
html.light .bsp-rec-sel select{background:#f8fafc;color:#1a2235}
html.light .bsp-rec-sel select option{background:#fff}
html.light thead th{background:#e8edf5}
html.light .sb-logo{border-color:var(--border)}
html.light .sb-badge{background:var(--danger)}
html.light .btn-logout{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.25);color:#dc2626}
html.light .scard::before{filter:none}
html.light .est.ok{background:rgba(16,185,129,.1);color:#059669}
html.light .est.partial{background:rgba(245,158,11,.1);color:#d97706}
html.light .est.missing{background:rgba(239,68,68,.1);color:#dc2626}
html.light .est.pendent{background:rgba(59,130,246,.1);color:#2563eb}
html.light .est.process{background:rgba(245,158,11,.1);color:#d97706}
html.light .est.resolt{background:rgba(16,185,129,.1);color:#059669}
html.light .est.rebutjat{background:rgba(100,116,139,.1);color:#64748b}
html.light .pill{background:#fff;color:#4a6080}
html.light .pill.act{background:var(--accent);color:#fff}
html.light .modal{background:#fff}
html.light .modal-head{background:#fff}
html.light .modal-foot{background:#fff}
html.light .barri-side-panel{background:#fff}
html.light .bsp-header{border-color:var(--border)}
html.light .bsp-score-row{border-color:var(--border)}
html.light .bsp-rec-item{background:#f8fafc;border-color:#d1dbe8}
html.light .bsp-rec-item[data-estat="ok"]{background:rgba(16,185,129,.06);border-color:rgba(16,185,129,.3)}
html.light .bsp-rec-item[data-estat="partial"]{background:rgba(245,158,11,.06);border-color:rgba(245,158,11,.3)}
html.light .bsp-rec-item[data-estat="missing"]{background:rgba(239,68,68,.06);border-color:rgba(239,68,68,.2)}
html.light .bsp-track{stroke:#d1dbe8}
html.light .leaflet-popup-content-wrapper{background:#fff;color:#1a2235;border-color:#d1dbe8}
html.light .leaflet-popup-tip{background:#fff}
html.light .map-toolbar{background:#fff;border-color:#d1dbe8}
*{box-sizing:border-box;margin:0;padding:0}
body,html{transition:background .25s,color .25s}
body{background:var(--bg);color:var(--text);font-family:var(--fb);min-height:100vh;display:flex;overflow:hidden}

/* ── SIDEBAR ── */
.sidebar{width:var(--sidebar);flex-shrink:0;background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;height:100vh;overflow-y:auto;transition:transform .3s;z-index:300}
.sb-logo{padding:20px;display:flex;align-items:center;gap:12px;border-bottom:1px solid var(--border)}
.sb-logo-icon{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.sb-logo-text .t1{font-family:var(--fd);font-weight:800;font-size:15px;line-height:1}
.sb-logo-text .t2{font-size:10px;color:var(--text2);text-transform:uppercase;letter-spacing:.8px;margin-top:2px}
.sb-nav{flex:1;padding:12px 8px}
.sb-section{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:16px 10px 6px}
.sb-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--radius-sm);cursor:pointer;transition:all .15s;font-size:13px;font-weight:500;color:var(--text2);border:none;background:none;width:100%;text-align:left}
.sb-item:hover{background:rgba(255,255,255,.04);color:var(--text)}
.sb-item.act{background:rgba(59,130,246,.15);color:var(--accent)}
.sb-item .ico{font-size:17px;flex-shrink:0}
.sb-badge{margin-left:auto;background:var(--danger);color:#fff;font-size:9px;font-weight:700;border-radius:20px;padding:1px 6px}
.sb-footer{padding:16px;border-top:1px solid var(--border)}
.btn-logout{width:100%;padding:9px;border-radius:var(--radius-sm);background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#fca5a5;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s}
.btn-logout:hover{background:rgba(239,68,68,.2)}

/* ── MAIN ── */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;height:100vh}
.topbar{height:60px;border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 24px;gap:16px;background:var(--bg2);flex-shrink:0;z-index:100}
.topbar-title{font-family:var(--fd);font-size:18px;font-weight:800;flex:1}
.content{flex:1;overflow-y:auto;padding:24px}
.panel{display:none}.panel.act{display:block}

/* ── MAP TOOLBAR ── */
.map-toolbar{height:48px;background:var(--bg2);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;gap:12px;flex-shrink:0;z-index:200}
.map-toolbar-left{display:flex;align-items:center;gap:10px;font-size:13px}
.map-toolbar-right{display:flex;gap:8px}
.map-tool-btn{display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--surface);color:var(--text2);font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;font-family:var(--fb);white-space:nowrap}
.map-tool-btn:hover{border-color:var(--accent);color:var(--accent)}
.map-tool-btn.active{background:rgba(59,130,246,.15);border-color:var(--accent);color:var(--accent)}

/* ── MAPA PANEL ── */
#p-mapa{padding:0;height:calc(100vh - 60px);display:none;flex-direction:column}
#p-mapa.act{display:flex}
#admin-map{flex:1;min-width:0;min-height:0}

/* ── BARRI SIDE PANEL ── */
.barri-side-panel{
  width:0;overflow:hidden;transition:width .3s ease;
  background:var(--bg2);border-left:0px solid var(--border);
  display:flex;flex-direction:column;height:100%;
}
.barri-side-panel.open{width:320px;border-left-width:1px}
.bsp-header{display:flex;align-items:center;gap:10px;padding:16px;border-bottom:1px solid var(--border);flex-shrink:0}
.bsp-color-bar{width:5px;height:44px;border-radius:3px;flex-shrink:0}
.bsp-nom{font-family:var(--fd);font-size:17px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bsp-dist{font-size:11px;color:var(--text2);margin-top:2px}
.bsp-score-row{display:flex;align-items:center;gap:16px;padding:16px;border-bottom:1px solid var(--border);flex-shrink:0}
.bsp-score-circle{width:70px;height:70px;position:relative;flex-shrink:0}
.bsp-score-circle svg{width:70px;height:70px;transform:rotate(-90deg)}
.bsp-track{fill:none;stroke:var(--border);stroke-width:4}
.bsp-fill{fill:none;stroke-width:4;stroke-linecap:round;transition:stroke-dashoffset .8s ease, stroke .3s}
.bsp-score-num{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-family:var(--fd);font-size:14px;font-weight:800}
.bsp-score-meta{flex:1}
.bsp-section-title{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:1px;padding:12px 16px 8px;flex-shrink:0}
.bsp-recursos{flex:1;overflow-y:auto;padding:0 12px 12px}
.bsp-rec-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--radius-sm);border:1px solid var(--border);margin-bottom:8px;background:var(--bg3);transition:border-color .2s}
.bsp-rec-item:hover{border-color:var(--border)}
.bsp-rec-ico{font-size:22px;flex-shrink:0;width:32px;text-align:center}
.bsp-rec-info{flex:1;min-width:0}
.bsp-rec-nom{font-size:12px;font-weight:600;margin-bottom:4px}
.bsp-rec-sel select{width:100%;background:var(--bg2);border:1px solid var(--border);border-radius:6px;color:var(--text);font-family:var(--fb);font-size:11px;padding:4px 8px;outline:none;cursor:pointer;appearance:none;-webkit-appearance:none}
.bsp-rec-sel select:focus{border-color:var(--accent)}
.bsp-rec-sel select option{background:var(--bg2)}
.bsp-actions{padding:12px 16px;border-top:1px solid var(--border);flex-shrink:0}
.bsp-rec-item[data-estat="ok"]{border-color:rgba(16,185,129,.3)}
.bsp-rec-item[data-estat="partial"]{border-color:rgba(245,158,11,.3)}
.bsp-rec-item[data-estat="missing"]{border-color:rgba(239,68,68,.2)}

/* ── STATS ── */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
.scard{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px;position:relative;overflow:hidden}
.scard::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--c,var(--accent))}
.scard .si{font-size:28px;margin-bottom:10px;display:block}
.scard .sn{font-family:var(--fd);font-size:32px;font-weight:800;line-height:1;margin-bottom:4px}
.scard .sl{font-size:11px;color:var(--text2);text-transform:uppercase;letter-spacing:.5px}

/* ── TABLE ── */
.table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.table-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);gap:12px}
.table-head h3{font-family:var(--fd);font-size:16px;font-weight:700}
table{width:100%;border-collapse:collapse}
thead th{background:var(--bg3);padding:11px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);white-space:nowrap}
tbody tr{border-bottom:1px solid var(--border);transition:background .15s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,.02)}
tbody td{padding:12px 16px;font-size:13px;vertical-align:middle}

/* ── FORM ── */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.fg{display:flex;flex-direction:column;gap:5px}.fg.full{grid-column:1/-1}
.fl{font-size:11px;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:.5px}
.fc{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-family:var(--fb);font-size:13px;padding:9px 12px;outline:none;transition:border .2s;appearance:none;-webkit-appearance:none;width:100%}
.fc:focus{border-color:var(--accent)}.fc option{background:var(--bg2)}
textarea.fc{resize:vertical;min-height:60px}
select.fc{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%238da0bb' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--radius-sm);font-family:var(--fb);font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .2s;white-space:nowrap}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff}
.btn-primary:hover{opacity:.9;box-shadow:0 4px 16px rgba(59,130,246,.4)}
.btn-secondary{background:var(--bg3);border:1px solid var(--border);color:var(--text2)}
.btn-secondary:hover{border-color:var(--accent);color:var(--accent)}
.btn-success{background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.3);color:#6ee7b7}
.btn-warn{background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.3);color:#fcd34d}
.btn-danger{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.btn-danger:hover{background:rgba(239,68,68,.25)}
.btn-sm{padding:5px 10px;font-size:11px}
.btn-icon{width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:6px}

/* ── MODAL ── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:2000;display:none;align-items:center;justify-content:center;padding:20px}
.modal-overlay.open{display:flex}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:20px;width:100%;max-width:620px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,.8)}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg2);z-index:1}
.modal-head h2{font-family:var(--fd);font-size:18px;font-weight:800}
.modal-body{padding:24px}
.modal-foot{padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px;position:sticky;bottom:0;background:var(--bg2)}

/* ── MINI MAP (dins modal) ── */
#barri-map-edit{height:300px;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border)}

/* ── MISC ── */
.cdot{width:14px;height:14px;border-radius:50%;display:inline-block;border:2px solid rgba(255,255,255,.2);flex-shrink:0}
.est{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.est.ok{background:rgba(16,185,129,.15);color:#6ee7b7;border:1px solid rgba(16,185,129,.3)}
.est.partial{background:rgba(245,158,11,.15);color:#fcd34d;border:1px solid rgba(245,158,11,.3)}
.est.missing{background:rgba(239,68,68,.15);color:#fca5a5;border:1px solid rgba(239,68,68,.3)}
.est.pendent{background:rgba(59,130,246,.15);color:#93c5fd;border:1px solid rgba(59,130,246,.3)}
.est.process{background:rgba(245,158,11,.15);color:#fcd34d;border:1px solid rgba(245,158,11,.3)}
.est.resolt{background:rgba(16,185,129,.15);color:#6ee7b7;border:1px solid rgba(16,185,129,.3)}
.est.rebutjat{background:rgba(100,116,139,.15);color:#94a3b8;border:1px solid rgba(100,116,139,.3)}
.pgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.popt{padding:10px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--bg3);cursor:pointer;text-align:center;font-size:11px;font-weight:600;transition:all .2s}
.popt.alta{color:var(--danger)}.popt.mitja{color:var(--warn)}.popt.baixa{color:var(--success)}
.popt.sel.alta{background:rgba(239,68,68,.1);border-color:var(--danger)}
.popt.sel.mitja{background:rgba(245,158,11,.1);border-color:var(--warn)}
.popt.sel.baixa{background:rgba(16,185,129,.1);border-color:var(--success)}
.pills{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.pill{padding:6px 14px;border-radius:20px;border:1px solid var(--border);background:var(--surface);color:var(--text2);font-size:12px;font-weight:500;cursor:pointer;transition:all .2s}
.pill.act{background:var(--accent);border-color:var(--accent);color:#fff}
.search-wrap{position:relative}
.search-ico{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text2);font-size:14px;pointer-events:none}
.search-wrap .fc{padding-left:33px}
.toast{position:fixed;bottom:24px;right:24px;z-index:9999;background:var(--success);color:#fff;padding:12px 20px;border-radius:50px;font-size:13px;font-weight:600;box-shadow:var(--shadow);opacity:0;transform:translateY(10px);transition:all .3s;pointer-events:none}
.toast.show{opacity:1;transform:translateY(0)}
.pagination{display:flex;align-items:center;gap:8px;justify-content:center;margin-top:16px}

/* ── LEAFLET ── */
.leaflet-container{background:#0d1b2e}
.leaflet-popup-content-wrapper{background:var(--bg2);border:1px solid var(--border);border-radius:10px;color:var(--text);font-family:var(--fb)}
.leaflet-popup-tip{background:var(--bg2)}
.leaflet-container.light-mode{background:#e8e0d8}

/* MODE CLAR mapa popup */
.leaflet-container.light-mode .leaflet-popup-content-wrapper{background:#fff;color:#1a1a2e;border-color:#ccc}
.leaflet-container.light-mode .leaflet-popup-tip{background:#fff}

/* ── BOTTOM NAV ADMIN (mòbil) ── */
.admin-bnav{
  display:none;position:fixed;bottom:0;left:0;right:0;z-index:500;
  background:var(--bg2);border-top:1px solid var(--border);
  padding:6px 0 calc(6px + env(safe-area-inset-bottom));
  grid-template-columns:repeat(5,1fr);
}
.admin-bnav .ani{
  display:flex;flex-direction:column;align-items:center;gap:2px;
  padding:4px 2px;cursor:pointer;border:none;background:none;
  color:var(--text2);font-size:9px;font-weight:500;font-family:var(--fb);
  transition:color .2s;
}
.admin-bnav .ani .anic{font-size:20px;line-height:1.2}
.admin-bnav .ani.act{color:var(--accent)}

html.light .admin-bnav{background:rgba(240,244,248,.97)}

@media(max-width:900px){
  .sidebar{position:fixed;left:0;top:0;bottom:0;z-index:400;transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main{width:100%;min-height:100vh}
  .stats-row{grid-template-columns:repeat(2,1fr)}
  .form-grid{grid-template-columns:1fr}
  .barri-side-panel.open{width:100%!important;position:absolute;z-index:900}
  .map-toolbar-left{display:none}
  .map-toolbar-right{gap:4px}
  .map-tool-btn{padding:5px 8px;font-size:11px}
  #mob-menu-btn{display:flex!important}
  .content{padding:12px;padding-bottom:80px}
  .table-head{flex-wrap:wrap;gap:8px}
  .table-head h3{font-size:14px}
  .admin-bnav{display:grid}
  .main .topbar{padding:0 12px}
  .topbar-title{font-size:15px}
  /* Taules: amagar columnes menys importants */
  table th:nth-child(5),table td:nth-child(5),
  table th:nth-child(6),table td:nth-child(6){display:none}
  /* Search input més estret */
  #barri-search{width:130px!important}
  /* Mapa panel */
  #p-mapa{height:calc(100vh - 60px - 56px)!important}
  /* Modal full screen en mòbil */
  .modal-overlay{padding:0;align-items:flex-end}
  .modal{border-radius:20px 20px 0 0;max-height:90vh;width:100%;max-width:100%}
  /* BSP full height en mòbil */
  .barri-side-panel{height:calc(100vh - 60px - 48px - 56px)}
  /* Stats row 2x2 */
  .scard .sn{font-size:24px}
  .scard .si{font-size:22px}
  /* Peticions: amagar columnes */
  #pet-tbody td:nth-child(1),#pet-tbody th:nth-child(1),
  #pet-tbody td:nth-child(7),#pet-tbody th:nth-child(7),
  #pet-tbody td:nth-child(8),#pet-tbody th:nth-child(8){display:none}
  #p-peticions table th:nth-child(1),
  #p-peticions table th:nth-child(7),
  #p-peticions table th:nth-child(8){display:none}
}
@media(max-width:480px){
  .stats-row{grid-template-columns:repeat(2,1fr);gap:8px}
  .form-grid{grid-template-columns:1fr!important}
  #barri-search{width:100px!important}
}
</style>
</head>
<body>

<aside class="sidebar" id="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-icon">🏛️</div>
    <div class="sb-logo-text"><div class="t1"><?= $lang=="ca"?"Alacant":"Alicante" ?> Barris</div><div class="t2"><?= $at["title"] ?></div></div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section"><?= $at["general"] ?></div>
    <button class="sb-item act" onclick="goPanel('dash',this)"><span class="ico">📊</span><?= $at["dashboard"] ?></button>
    <div class="sb-section"><?= $at["geo"] ?></div>
    <button class="sb-item" onclick="goPanel('mapa',this)"><span class="ico">🗺️</span><?= $at["mapa"] ?></button>
    <button class="sb-item" onclick="goPanel('barris',this)"><span class="ico">🏘️></span><?= $at["barris"] ?></button>
    <button class="sb-item" onclick="goPanel('districtes',this)"><span class="ico">🗂️></span><?= $at["districtes"] ?></button>
    <button class="sb-item" onclick="goPanel('categories',this)"><span class="ico">📋></span><?= $at["categories"] ?></button>
    <div class="sb-section"><?= $at["sol"] ?></div>
    <button class="sb-item" onclick="goPanel('peticions',this)">
      <span class="ico">📝</span><?= $at["peticions"] ?><span class="sb-badge" id="sb-badge">—</span>
    </button>
  </nav>
  <div class="sb-footer">
    <form method="POST"><button class="btn-logout" type="submit" name="logout" value="1"><?= $at["logout"] ?></button></form>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <button class="map-tool-btn" onclick="toggleSidebar()" id="mob-menu-btn" style="display:none">☰</button>
    <div class="topbar-title" id="topbar-title">Dashboard</div>
    <div id="topbar-actions"></div>
  </div>
  <div class="content" id="main-content">

    <!-- DASHBOARD -->
    <div id="p-dash" class="panel act">
      <div class="stats-row">
        <div class="scard" style="--c:var(--accent)"><span class="si">🏘️</span><div class="sn" id="ds-barris">—</div><div class="sl"><?= $at["barris_act"] ?></div></div>
        <div class="scard" style="--c:var(--danger)"><span class="si">⚠️</span><div class="sn" id="ds-miss">—</div><div class="sl"><?= $at["mancances"] ?></div></div>
        <div class="scard" style="--c:var(--success)"><span class="si">📊</span><div class="sn" id="ds-cov">—%</div><div class="sl"><?= $at["cobertura"] ?></div></div>
        <div class="scard" style="--c:var(--warn)"><span class="si">📝</span><div class="sn" id="ds-pet">—</div><div class="sl"><?= $at["pend"] ?></div></div>
      </div>
      <div class="table-wrap">
        <div class="table-head"><h3><?= $at["quick"] ?></h3></div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;padding:20px">
          <button class="btn btn-primary" onclick="goPanel('mapa',document.querySelectorAll('.sb-item')[1])"><?= $at["edit_map"] ?></button>
          <button class="btn btn-secondary" onclick="goPanel('barris',document.querySelectorAll('.sb-item')[2]);setTimeout(()=>openBarriModal(0),100)">➕ Nou barri</button>
          <button class="btn btn-secondary" onclick="goPanel('peticions',document.querySelectorAll('.sb-item')[5])">📝 Peticions</button>
          <button class="btn btn-secondary" onclick="goPanel('categories',document.querySelectorAll('.sb-item')[4])">📋 Categories</button>
        </div>
      </div>
    </div>

    <!-- MAPA (gestionat fora del content div) -->

    <!-- BARRIS -->
    <div id="p-barris" class="panel">
      <div class="table-wrap">
        <div class="table-head">
          <h3><?= $lang=="ca"?"Gestió de Barris":"Gestión de Barrios" ?></h3>
          <div style="display:flex;gap:8px;align-items:center">
            <div class="search-wrap"><span class="search-ico">🔍</span><input class="fc" type="search" placeholder="<?= $at["search_ph"] ?>" id="barri-search" oninput="filterBarriTable(this.value)" style="width:200px"></div>
            <button class="btn btn-primary" onclick="openBarriModal(0)">➕ Nou barri</button>
          </div>
        </div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr><th><?= $at["col_id"] ?></th><th><?= $at["col_color"] ?></th><th><?= $at["col_nom"] ?></th><th><?= $at["col_dist"] ?></th><th><?= $at["col_coords"] ?></th><th><?= $at["col_pob"] ?></th><th><?= $at["col_actiu"] ?></th><th><?= $at["col_acc"] ?></th></tr></thead>
            <tbody id="barri-tbody"><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text2)">Carregant...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- DISTRICTES -->
    <div id="p-districtes" class="panel">
      <div class="table-wrap">
        <div class="table-head"><h3><?= $lang=="ca"?"Gestió de Districtes":"Gestión de Distritos" ?></h3><button class="btn btn-primary" onclick="openDistricteModal(0)">➕ Nou districte</button></div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr><th><?= $at["col_num"] ?></th><th><?= $at["col_color"] ?></th><th><?= $at["col_nom"] ?></th><th><?= $at["col_acc"] ?></th></tr></thead>
            <tbody id="dist-tbody"><tr><td colspan="4" style="text-align:center;padding:40px;color:var(--text2)">Carregant...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- CATEGORIES -->
    <div id="p-categories" class="panel">
      <div class="table-wrap">
        <div class="table-head"><h3><?= $lang=="ca"?"Gestió de Categories":"Gestión de Categorías" ?></h3><button class="btn btn-primary" onclick="openCatModal(0)">➕ Nova categoria</button></div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr><th><?= $at["col_icona"] ?></th><th><?= $at["col_nom"] ?></th><th><?= $at["col_slug"] ?></th><th><?= $at["col_color"] ?></th><th><?= $at["col_ordre"] ?></th><th><?= $at["col_acc"] ?></th></tr></thead>
            <tbody id="cat-tbody"><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text2)">Carregant...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- PETICIONS -->
    <div id="p-peticions" class="panel">
      <div class="pills" id="pet-pills">
        <div class="pill act" onclick="filterPets('',this)"><?= $at["all"] ?></div>
        <div class="pill" onclick="filterPets('pendent',this)"><?= $at["pendent"] ?></div>
        <div class="pill" onclick="filterPets('process',this)"><?= $at["process"] ?></div>
        <div class="pill" onclick="filterPets('resolt',this)"><?= $at["resolt"] ?></div>
        <div class="pill" onclick="filterPets('rebutjat',this)"><?= $at["rebutjat"] ?></div>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th><?= $at["col_id"] ?></th><th><?= $at["col_nom"] ?></th><th><?= $at["col_cat"] ?></th><th><?= $at["col_titol"] ?></th><th><?= $at["col_prio"] ?></th><th><?= $at["col_estat"] ?></th><th><?= $at["col_vots"] ?></th><th><?= $at["col_data"] ?></th><th><?= $at["col_acc"] ?></th></tr></thead>
          <tbody id="pet-tbody"><tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text2)">Carregant...</td></tr></tbody>
        </table>
      </div>
      <div class="pagination" id="pet-pag"></div>
    </div>

  </div><!-- /content -->

  <!-- MAPA PANEL (fora del scroll) -->
  <div id="p-mapa" class="panel" style="flex:1;overflow:hidden">
    <div class="map-toolbar">
      <div class="map-toolbar-left">
        <span style="font-size:13px;color:var(--text2)">
          📌 <strong style="color:var(--text)">Arrossega</strong> per moure ·
          <strong style="color:var(--text)">Clic</strong> per veure mancances
        </span>
      </div>
      <div class="map-toolbar-right">
        <button class="map-tool-btn" id="btn-map-mode" onclick="toggleMapMode()"><?= $at["light_mode"] ?></button>
        <button class="map-tool-btn" onclick="if(adminMap)adminMap.setView([38.345,-0.490],13)"><?= $at["center"] ?></button>
        <button class="map-tool-btn active" id="btn-drag" onclick="toggleDragMode()"><?= $at["move_on"] ?></button>
      </div>
    </div>
    <div style="flex:1;display:flex;overflow:hidden;position:relative">
      <div id="admin-map" style="flex:1;min-width:0;min-height:300px"></div>
      <!-- PANEL LATERAL BARRI -->
      <div id="barri-side-panel" class="barri-side-panel">
        <div class="bsp-header">
          <div id="bsp-color-bar" class="bsp-color-bar"></div>
          <div style="flex:1;min-width:0">
            <div id="bsp-nom" class="bsp-nom">Selecciona un barri</div>
            <div id="bsp-dist" class="bsp-dist"></div>
          </div>
          <button onclick="closeBarriPanel()" class="map-tool-btn" style="padding:4px 8px;flex-shrink:0">✕</button>
        </div>
        <div class="bsp-score-row">
          <div class="bsp-score-circle">
            <svg viewBox="0 0 60 60">
              <circle class="bsp-track" cx="30" cy="30" r="26"/>
              <circle class="bsp-fill" id="bsp-arc" cx="30" cy="30" r="26" stroke-dasharray="163.4" stroke-dashoffset="163.4"/>
            </svg>
            <div class="bsp-score-num" id="bsp-score-num">—</div>
          </div>
          <div class="bsp-score-meta">
            <div id="bsp-pop" style="font-size:12px;color:var(--text2);margin-bottom:6px"></div>
            <div id="bsp-miss-count" style="font-size:12px;color:var(--danger);margin-bottom:3px"></div>
            <div id="bsp-partial-count" style="font-size:12px;color:var(--warn);margin-bottom:3px"></div>
            <div id="bsp-ok-count" style="font-size:12px;color:var(--success)"></div>
          </div>
        </div>
        <div class="bsp-section-title"><?= $at["res_section"] ?></div>
        <div id="bsp-recursos" class="bsp-recursos">
          <div style="padding:40px;text-align:center;color:var(--text2);font-size:13px">Clic en un barri del mapa per veure i editar els seus recursos</div>
        </div>
        <div class="bsp-actions">
          <button class="btn btn-primary" style="width:100%;font-size:12px;height:38px" id="bsp-edit-btn">✏️ <?= $at["edit_barri_btn"] ?></button>
        </div>
      </div>
    </div>
  </div>
</div><!-- /main -->

<!-- MODAL BARRI -->
<div class="modal-overlay" id="modal-barri">
<div class="modal">
  <div class="modal-head">
    <h2 id="modal-barri-title">Barri</h2>
    <button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modal-barri')">✕</button>
  </div>
  <div class="modal-body">
    <input type="hidden" id="b-id">
    <div class="pills" id="modal-tabs" style="margin-bottom:20px">
      <div class="pill act" onclick="modalTab('info',this)">ℹ️ <?= $at["info_tab"] ?></div>
      <div class="pill" onclick="modalTab('pos',this)">📍 <?= $at["pos_tab"] ?></div>
    </div>
    <div id="mt-info">
      <div class="form-grid">
        <div class="fg"><label class="fl"><?= $at["nom_lbl"] ?></label><input class="fc" id="b-nom" placeholder="Ex: El Pla"></div>
        <div class="fg"><label class="fl"><?= $at["dist_lbl"] ?></label>
          <select class="fc" id="b-districte">
            <?php foreach($districtes_init as $d): ?>
            <option value="<?=$d['id']?>"><?=htmlspecialchars($d['nom'])?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="fg"><label class="fl"><?= $at["color_lbl"] ?></label>
          <div style="display:flex;gap:8px;align-items:center">
            <input type="color" id="b-color" value="#3b82f6" style="width:44px;height:36px;border:none;background:none;cursor:pointer;padding:0;border-radius:6px">
            <input class="fc" id="b-color-hex" value="#3b82f6" placeholder="#3b82f6" style="flex:1;font-family:monospace">
          </div>
        </div>
        <div class="fg"><label class="fl"><?= $at["pob_lbl"] ?></label><input class="fc" type="number" id="b-pob" placeholder="0" min="0"></div>
        <div class="fg full"><label class="fl"><?= $at["actiu_lbl"] ?></label>
          <select class="fc" id="b-actiu"><option value="1"><?= $at["actiu_si"] ?></option><option value="0"><?= $at["actiu_no"] ?></option></select>
        </div>
      </div>
    </div>
    <div id="mt-pos" style="display:none">
      <div style="background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.3);border-radius:8px;padding:10px 14px;font-size:12px;color:#93c5fd;margin-bottom:12px">
        💡 Fes clic al mapa per col·locar el barri, o arrossega el marcador. Les coordenades s'actualitzen automàticament.
      </div>
      <div id="barri-map-edit"></div>
      <div class="form-grid" style="margin-top:14px">
        <div class="fg"><label class="fl">Latitud</label><input class="fc" id="b-lat" type="number" step="0.000001" placeholder="38.345000"></div>
        <div class="fg"><label class="fl">Longitud</label><input class="fc" id="b-lng" type="number" step="0.000001" placeholder="-0.490000"></div>
      </div>
    </div>
  </div>
  <div class="modal-foot">
    <button class="btn btn-danger btn-sm" id="btn-del-barri" onclick="deleteBarri()" style="margin-right:auto;display:none">🗑️ <?= $at["deactivate"] ?></button>
    <button class="btn btn-secondary" onclick="closeModal('modal-barri')"><?= $at["cancel"] ?></button>
    <button class="btn btn-primary" onclick="saveBarri()">💾 <?= $at["save_barri"] ?></button>
  </div>
</div>
</div>

<!-- MODAL DISTRICTE -->
<div class="modal-overlay" id="modal-dist">
<div class="modal" style="max-width:440px">
  <div class="modal-head"><h2 id="modal-dist-title">Districte</h2><button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modal-dist')">✕</button></div>
  <div class="modal-body">
    <input type="hidden" id="d-id">
    <div class="form-grid">
      <div class="fg"><label class="fl"><?= $at["num_lbl"] ?></label><input class="fc" type="number" id="d-num" min="1" max="20"></div>
      <div class="fg"><label class="fl"><?= $at["color_lbl"] ?></label>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="color" id="d-color" style="width:44px;height:36px;border:none;background:none;cursor:pointer;padding:0;border-radius:6px">
          <input class="fc" id="d-color-hex" style="flex:1;font-family:monospace">
        </div>
      </div>
      <div class="fg full"><label class="fl"><?= $at["nom_lbl"] ?></label><input class="fc" id="d-nom" placeholder="Ex: Districte 1 · Centre"></div>
    </div>
  </div>
  <div class="modal-foot">
    <button class="btn btn-danger btn-sm" id="btn-del-dist" onclick="deleteDistricte()" style="margin-right:auto;display:none">🗑️ <?= $at["delete"] ?></button>
    <button class="btn btn-secondary" onclick="closeModal('modal-dist')"><?= $at["cancel"] ?></button>
    <button class="btn btn-primary" onclick="saveDistricte()">💾 <?= $at["save_dist"] ?></button>
  </div>
</div>
</div>

<!-- MODAL CATEGORIA -->
<div class="modal-overlay" id="modal-cat">
<div class="modal" style="max-width:480px">
  <div class="modal-head"><h2 id="modal-cat-title">Categoria</h2><button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modal-cat')">✕</button></div>
  <div class="modal-body">
    <input type="hidden" id="c-id">
    <div class="form-grid">
      <div class="fg"><label class="fl"><?= $at["icona_lbl"] ?></label><input class="fc" id="c-icona" placeholder="🏥" style="font-size:20px;text-align:center"></div>
      <div class="fg"><label class="fl"><?= $at["ordre_lbl"] ?></label><input class="fc" type="number" id="c-ordre" min="0" max="99"></div>
      <div class="fg full"><label class="fl"><?= $at["nom_lbl"] ?></label><input class="fc" id="c-nom" placeholder="Ex: Centre de Salut"></div>
      <div class="fg"><label class="fl"><?= $at["slug_lbl"] ?></label><input class="fc" id="c-slug" placeholder="salut" style="font-family:monospace"></div>
      <div class="fg"><label class="fl"><?= $at["color_lbl"] ?></label>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="color" id="c-color" style="width:44px;height:36px;border:none;background:none;cursor:pointer;padding:0;border-radius:6px">
          <input class="fc" id="c-color-hex" style="flex:1;font-family:monospace">
        </div>
      </div>
    </div>
  </div>
  <div class="modal-foot">
    <button class="btn btn-secondary" onclick="closeModal('modal-cat')"><?= $at["cancel"] ?></button>
    <button class="btn btn-primary" onclick="saveCat()">💾 <?= $at["save_cat"] ?></button>
  </div>
</div>
</div>

<nav class="admin-bnav" id="admin-bnav">
  <button class="ani act" id="ani-dash" onclick="goPanel('dash',document.querySelector('.sb-item.act'));goBnav('dash')"><span class="anic">📊</span><?= $at['dashboard'] ?></button>
  <button class="ani" id="ani-mapa" onclick="goPanel('mapa',null);goBnav('mapa')"><span class="anic">🗺️</span><?= $at['mapa'] ?></button>
  <button class="ani" id="ani-barris" onclick="goPanel('barris',null);goBnav('barris')"><span class="anic">🏘️</span><?= $at['barris'] ?></button>
  <button class="ani" id="ani-dist" onclick="goPanel('districtes',null);goBnav('dist')"><span class="anic">🗂️</span><?= $at['districtes'] ?></button>
  <button class="ani" id="ani-pet" onclick="goPanel('peticions',null);goBnav('pet')"><span class="anic">📝</span><?= $at['peticions'] ?></button>
</nav>
<div class="toast" id="toast"></div>

<script>
// ── UTILS ──────────────────────────────────────────────────────
const $ = id => document.getElementById(id);
const api  = async (action, params='') => { const r=await fetch(`index.php?ajax=${action}${params}`); return r.json(); };
const post = async (action, data)      => { const r=await fetch(`index.php?ajax=${action}`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}); return r.json(); };

function toast(msg, color='var(--success)', dur=3000) {
  const t=$('toast'); t.textContent=msg; t.style.background=color;
  t.classList.add('show'); clearTimeout(t._t);
  t._t=setTimeout(()=>t.classList.remove('show'),dur);
}

// ── PANELS ────────────────────────────────────────────────────
const PANELS=['dash','mapa','barris','districtes','categories','peticions'];
const TITLES={dash:'<?= $at["dashboard"] ?>',mapa:'<?= $at["mapa"] ?>',barris:'<?= $at["barris"] ?>',districtes:'<?= $at["districtes"] ?>',categories:'<?= $at["categories"] ?>',peticions:'<?= $at["peticions"] ?>'};

function goPanel(name, btn) {
  // El panel mapa és especial: viu fora del content scroll
  const content = $('main-content');
  PANELS.filter(p=>p!=='mapa').forEach(p=>{ const el=$('p-'+p); if(el){el.classList.remove('act');el.style.display='';} });
  $('p-mapa').classList.remove('act');

  document.querySelectorAll('.sb-item').forEach(b=>b.classList.remove('act'));
  $('topbar-title').textContent=TITLES[name]||name;
  btn?.classList.add('act');
  closeSidebar();

  if(name==='mapa'){
    content.style.display='none';
    $('p-mapa').style.display='flex'; $('p-mapa').classList.add('act');
    initAdminMap();
  } else {
    content.style.display='';
    $('p-mapa').style.display='none';
    $('p-'+name)?.classList.add('act');
    if(name==='barris') loadBarris();
    if(name==='districtes') loadDistrictes();
    if(name==='categories') loadCategories();
    if(name==='peticions') loadPeticions();
  }
}

function toggleSidebar(){ $('sidebar').classList.toggle('open'); }
function closeSidebar(){ $('sidebar').classList.remove('open'); }
function goBnav(name){
  document.querySelectorAll('.admin-bnav .ani').forEach(b=>b.classList.remove('act'));
  const map={dash:'ani-dash',mapa:'ani-mapa',barris:'ani-barris',dist:'ani-dist',pet:'ani-pet'};
  const el=$(map[name]||map.dash); if(el) el.classList.add('act');
}

// ── MODAL ──────────────────────────────────────────────────────
function openModal(id){ $(id).classList.add('open'); }
function closeModal(id){ $(id).classList.remove('open'); if(id==='modal-barri') destroyBarriMap(); }
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open')}));

// ══════════════════════════════════════════════════════════════
//  DASHBOARD
// ══════════════════════════════════════════════════════════════
async function loadDash(){
  const res=await api('stats'); if(!res.ok) return;
  const d=res.data;
  $('ds-barris').textContent=d.barris; $('ds-miss').textContent=d.missing_total;
  $('ds-cov').textContent=d.cobertura+'%'; $('ds-pet').textContent=d.pendents;
  $('sb-badge').textContent=d.pendents||'0';
}

// ══════════════════════════════════════════════════════════════
//  MAPA ADMIN
// ══════════════════════════════════════════════════════════════
let adminMap=null, adminMapInit=false, mapMarkers={}, mapBarrisData=[];
let mapDark=true, dragEnabled=true;

const TILE_DARK  = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
const TILE_LIGHT = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
let tileLayer=null;

async function initAdminMap(){
  if(adminMapInit){ setTimeout(()=>adminMap?.invalidateSize(),80); return; }
  adminMapInit=true;

  adminMap = L.map('admin-map',{zoomControl:true}).setView([38.345,-0.490],13);
  tileLayer = L.tileLayer(TILE_DARK,{attribution:'&copy; OpenStreetMap &copy; Carto',subdomains:'abcd',maxZoom:19}).addTo(adminMap);

  await refreshMapBarris();
}

async function refreshMapBarris(){
  // Elimina marcadors antics
  Object.values(mapMarkers).forEach(mk=>mk.remove());
  mapMarkers={};

  const res=await api('barris_list'); if(!res.ok) return;
  mapBarrisData=res.data;

  mapBarrisData.forEach(b=>{
    const mk=L.marker([+b.lat,+b.lng],{
      draggable: dragEnabled,
      icon: buildMapIcon(b)
    }).addTo(adminMap);

    mk.bindTooltip(`<strong>${b.nom}</strong><small style="display:block;color:#8da0bb">${b.districte_nom}</small>`, {direction:'top',offset:[0,-20]});

    // Clic → obre panel lateral amb recursos
    mk.on('click', ()=> openBarriPanel(b));

    // Drag → guarda coords
    mk.on('dragend', async e=>{
      const ll=e.target.getLatLng();
      const r=await post('barri_coords',{id:b.id,lat:ll.lat.toFixed(6),lng:ll.lng.toFixed(6)});
      b.lat=ll.lat.toFixed(6); b.lng=ll.lng.toFixed(6);
      if(r.ok) toast('📍 '+b.nom+' - <?= $lang=="ca"?"posici\xf3 guardada":"posici\xf3n guardada" ?>!');
      else toast('❌ <?= $lang=="ca"?"Error guardant posici\xf3":"Error guardando posici\xf3n" ?>','var(--danger)');
    });

    mapMarkers[b.id]=mk;
  });
}

function buildMapIcon(b){
  return L.divIcon({
    html:`<div style="
      background:${b.color};
      border:3px solid rgba(255,255,255,${mapDark?'.4':'.8'});
      border-radius:50%;width:38px;height:38px;
      display:flex;align-items:center;justify-content:center;
      font-family:'Syne',sans-serif;font-size:9px;font-weight:800;color:#fff;
      box-shadow:0 2px 16px ${b.color}88;
      cursor:${dragEnabled?'grab':'pointer'};
      transition:transform .15s;
    " onmouseenter="this.style.transform='scale(1.15)'" onmouseleave="this.style.transform='scale(1)'">
      ${dragEnabled?'✎':'👁'}
    </div>`,
    iconSize:[38,38],iconAnchor:[19,19],className:''
  });
}

function toggleMapMode(){
  mapDark=!mapDark;
  if(tileLayer) tileLayer.remove();
  tileLayer=L.tileLayer(mapDark?TILE_DARK:TILE_LIGHT,{attribution:'&copy; OpenStreetMap &copy; Carto',subdomains:'abcd',maxZoom:19}).addTo(adminMap);
  adminMap.getContainer().classList.toggle('light-mode',!mapDark);
  const btn=$('btn-map-mode');
  btn.textContent=mapDark?'☀️ Mode clar':'🌙 Mode fosc';
  // Actualitza icones pels nous colors de border
  mapBarrisData.forEach(b=>{ if(mapMarkers[b.id]) mapMarkers[b.id].setIcon(buildMapIcon(b)); });
}

function toggleDragMode(){
  dragEnabled=!dragEnabled;
  const btn=$('btn-drag');
  btn.textContent=dragEnabled?'✋ <?= $at["move_on"] ?>':'👁 <?= $at["move_off"] ?>';
  btn.classList.toggle('active',dragEnabled);
  mapBarrisData.forEach(b=>{
    if(mapMarkers[b.id]){
      dragEnabled ? mapMarkers[b.id].dragging.enable() : mapMarkers[b.id].dragging.disable();
      mapMarkers[b.id].setIcon(buildMapIcon(b));
    }
  });
}

// ── Panel lateral barri ────────────────────────────────────────
let currentBarriId=null;

async function openBarriPanel(b){
  currentBarriId=b.id;
  const panel=$('barri-side-panel');
  panel.classList.add('open');

  // Fa zoom al barri
  adminMap.flyTo([+b.lat,+b.lng], Math.max(adminMap.getZoom(),15), {duration:0.8});

  // Info capçalera
  $('bsp-color-bar').style.background=b.color;
  $('bsp-nom').textContent=b.nom;
  $('bsp-dist').textContent=b.districte_nom;
  $('bsp-score-num').textContent='...';
  $('bsp-arc').style.stroke=b.color;
  $('bsp-pop').textContent=b.poblacio?'👥 '+Number(b.poblacio).toLocaleString()+' hab.':'';
  $('bsp-miss-count').textContent=''; $('bsp-partial-count').textContent=''; $('bsp-ok-count').textContent='';
  $('bsp-recursos').innerHTML='<div style="padding:30px;text-align:center;color:var(--text2)"><div style="font-size:24px;margin-bottom:8px">⏳</div>Carregant recursos...</div>';

  // Botó editar
  $('bsp-edit-btn').onclick=()=>{ openBarriModal(b.id); };

  // Carrega recursos
  const res=await api('barri_recursos','&id='+b.id);
  if(!res.ok){ $('bsp-recursos').innerHTML='<div style="color:var(--danger);padding:20px">Error carregant recursos</div>'; return; }

  const recursos=res.data;
  const okC=recursos.filter(r=>r.estat==='ok').length;
  const partC=recursos.filter(r=>r.estat==='partial').length;
  const missC=recursos.filter(r=>r.estat==='missing').length;
  const total=recursos.length||1;
  const score=Math.round((okC+partC*.5)/total*100);

  // Score ring
  const circ=2*Math.PI*26; // r=26
  const offset=circ-(score/100)*circ;
  $('bsp-arc').style.strokeDasharray=circ;
  $('bsp-arc').style.strokeDashoffset=offset;
  $('bsp-score-num').textContent=score+'%';

  // Comptadors
  $('bsp-miss-count').textContent=missC?'❌ '+missC+' manques':'';
  $('bsp-partial-count').textContent=partC?'🟡 '+partC+' parcials':'';
  $('bsp-ok-count').textContent=okC?'✅ '+okC+' coberts':'';

  // Recursos editable
  const estatOpts=(sel)=>`
    <option value="ok" ${sel==='ok'?'selected':''}>✅ Cobert</option>
    <option value="partial" ${sel==='partial'?'selected':''}>🟡 Parcial</option>
    <option value="missing" ${sel==='missing'?'selected':''}>❌ Mancat</option>`;

  const colorEstat={ok:'rgba(16,185,129,.2)',partial:'rgba(245,158,11,.15)',missing:'rgba(239,68,68,.1)'};
  const borderEstat={ok:'rgba(16,185,129,.4)',partial:'rgba(245,158,11,.35)',missing:'rgba(239,68,68,.25)'};

  $('bsp-recursos').innerHTML=recursos.map(r=>`
    <div class="bsp-rec-item" data-estat="${r.estat}" id="rec-item-${r.cat_id}"
         style="background:${colorEstat[r.estat]};border-color:${borderEstat[r.estat]}">
      <div class="bsp-rec-ico">${r.icona}</div>
      <div class="bsp-rec-info">
        <div class="bsp-rec-nom" style="color:${r.cat_color}">${r.cat_nom}</div>
        <div class="bsp-rec-sel">
          <select onchange="changeRecurs(${b.id},${r.cat_id},this.value,this)" style="color:${r.estat==='ok'?'#6ee7b7':r.estat==='partial'?'#fcd34d':'#fca5a5'}">
            ${estatOpts(r.estat)}
          </select>
        </div>
      </div>
    </div>`).join('');
}

async function changeRecurs(barriId, catId, estat, selectEl){
  const item=$('rec-item-'+catId);
  const colorEstat={ok:'rgba(16,185,129,.2)',partial:'rgba(245,158,11,.15)',missing:'rgba(239,68,68,.1)'};
  const borderEstat={ok:'rgba(16,185,129,.4)',partial:'rgba(245,158,11,.35)',missing:'rgba(239,68,68,.25)'};
  const textColor={ok:'#6ee7b7',partial:'#fcd34d',missing:'#fca5a5'};

  const res=await post('recurs_update',{barri_id:barriId,categoria_id:catId,estat});
  if(res.ok){
    item.dataset.estat=estat;
    item.style.background=colorEstat[estat];
    item.style.borderColor=borderEstat[estat];
    selectEl.style.color=textColor[estat];
    toast('✅ <?= $lang=="ca"?"Recurs actualitzat":"Recurso actualizado" ?>!','var(--success)',1500);
    // Recalcula score
    const tots=document.querySelectorAll('.bsp-rec-item');
    const okC=[...tots].filter(i=>i.dataset.estat==='ok').length;
    const partC=[...tots].filter(i=>i.dataset.estat==='partial').length;
    const total=tots.length||1;
    const score=Math.round((okC+partC*.5)/total*100);
    const circ=2*Math.PI*26;
    $('bsp-arc').style.strokeDashoffset=circ-(score/100)*circ;
    $('bsp-score-num').textContent=score+'%';
    $('bsp-miss-count').textContent=[...tots].filter(i=>i.dataset.estat==='missing').length?'❌ '+[...tots].filter(i=>i.dataset.estat==='missing').length+' manques':'';
    $('bsp-partial-count').textContent=partC?'🟡 '+partC+' parcials':'';
    $('bsp-ok-count').textContent=okC?'✅ '+okC+' coberts':'';
    // Actualitza marcador mapa
    const b=mapBarrisData.find(x=>x.id==barriId);
    if(b && mapMarkers[barriId]) mapMarkers[barriId].setIcon(buildMapIcon(b));
  } else {
    toast('❌ Error: '+res.error,'var(--danger)');
    // Reverteix
  }
}

function closeBarriPanel(){
  $('barri-side-panel').classList.remove('open');
  currentBarriId=null;
}

// ══════════════════════════════════════════════════════════════
//  BARRIS TABLE
// ══════════════════════════════════════════════════════════════
let barrisData=[];
async function loadBarris(){
  const res=await api('barris_list'); if(!res.ok) return;
  barrisData=res.data; renderBarrisTable(barrisData);
}
function renderBarrisTable(data){
  $('barri-tbody').innerHTML=data.length?data.map(b=>`
    <tr>
      <td style="color:var(--text2);font-family:monospace">${b.id}</td>
      <td><div style="display:flex;align-items:center;gap:6px"><div class="cdot" style="background:${b.color}"></div><code style="font-size:11px;color:var(--text2)">${b.color}</code></div></td>
      <td><strong>${b.nom}</strong></td>
      <td><span class="est pendent" style="background:${b.color}15;color:${b.color};border-color:${b.color}40">${b.districte_nom}</span></td>
      <td style="font-family:monospace;font-size:11px;color:var(--text2)">${(+b.lat).toFixed(5)}, ${(+b.lng).toFixed(5)}</td>
      <td>${b.poblacio?Number(b.poblacio).toLocaleString():'—'}</td>
      <td>${b.actiu=='1'?'<span class="est ok">✅</span>':'<span class="est rebutjat">❌</span>'}</td>
      <td>
        <div style="display:flex;gap:6px">
          <button class="btn btn-secondary btn-sm btn-icon" onclick="openBarriModal(${b.id})" title="Editar">✏️</button>
          <button class="btn btn-secondary btn-sm btn-icon" onclick="goToMapBarri(${b.id},${b.lat},${b.lng})" title="Veure al mapa">🗺️</button>
        </div>
      </td>
    </tr>`).join(''):'<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text2)">Cap barri</td></tr>';
}
function filterBarriTable(v){
  renderBarrisTable(v?barrisData.filter(b=>b.nom.toLowerCase().includes(v.toLowerCase())||b.districte_nom.toLowerCase().includes(v.toLowerCase())):barrisData);
}
function goToMapBarri(id,lat,lng){
  goPanel('mapa',document.querySelectorAll('.sb-item')[1]);
  setTimeout(async()=>{
    adminMap?.flyTo([+lat,+lng],16,{duration:1});
    const b=mapBarrisData.find(x=>x.id==id);
    if(b) setTimeout(()=>openBarriPanel(b),1000);
  },300);
}

// ── Modal barri ──────────────────────────────────────────────
let barriEditMap=null, barriEditMk=null;
async function openBarriModal(id){
  $('modal-barri-title').textContent=id?'Editar Barri':'Nou Barri';
  $('btn-del-barri').style.display=id?'':'none';
  $('b-id').value=''; $('b-nom').value=''; $('b-pob').value='';
  $('b-lat').value='38.345000'; $('b-lng').value='-0.490000'; $('b-actiu').value='1';
  setColor('b-color','b-color-hex','#3b82f6');
  modalTab('info',document.querySelector('#modal-tabs .pill'));
  if(id){
    $('b-id').value=id;
    // Intenta trobar de barrisData o mapBarrisData
    const src=[...barrisData,...mapBarrisData];
    const found=src.find(b=>b.id==id);
    if(found){
      $('b-nom').value=found.nom;
      $('b-districte').value=found.districte_id;
      $('b-pob').value=found.poblacio||'';
      $('b-lat').value=(+found.lat).toFixed(6);
      $('b-lng').value=(+found.lng).toFixed(6);
      $('b-actiu').value=found.actiu;
      setColor('b-color','b-color-hex',found.color);
    }
  }
  openModal('modal-barri');
}
function modalTab(tab,el){
  ['info','pos'].forEach(t=>$('mt-'+t).style.display='none');
  document.querySelectorAll('#modal-tabs .pill').forEach(p=>p.classList.remove('act'));
  $('mt-'+tab).style.display='';
  el?.classList.add('act');
  if(tab==='pos') initBarriEditMap();
}
function initBarriEditMap(){
  const lat=parseFloat($('b-lat').value)||38.345;
  const lng=parseFloat($('b-lng').value)||-0.490;
  if(barriEditMap){ barriEditMap.setView([lat,lng],15); barriEditMk?.setLatLng([lat,lng]); return; }
  setTimeout(()=>{
    barriEditMap=L.map('barri-map-edit').setView([lat,lng],15);
    L.tileLayer(mapDark?TILE_DARK:TILE_LIGHT,{subdomains:'abcd',maxZoom:19}).addTo(barriEditMap);
    barriEditMk=L.marker([lat,lng],{draggable:true}).addTo(barriEditMap);
    barriEditMk.on('drag',e=>{
      const l=e.target.getLatLng();
      $('b-lat').value=l.lat.toFixed(6); $('b-lng').value=l.lng.toFixed(6);
    });
    barriEditMap.on('click',e=>{
      $('b-lat').value=e.latlng.lat.toFixed(6); $('b-lng').value=e.latlng.lng.toFixed(6);
      barriEditMk.setLatLng(e.latlng);
    });
    ['b-lat','b-lng'].forEach(fid=>$(fid).addEventListener('input',()=>{
      const la=parseFloat($('b-lat').value), ln=parseFloat($('b-lng').value);
      if(la&&ln){ barriEditMk.setLatLng([la,ln]); barriEditMap.panTo([la,ln]); }
    }));
  },80);
}
function destroyBarriMap(){ if(barriEditMap){barriEditMap.remove();barriEditMap=null;barriEditMk=null;} }

async function saveBarri(){
  const id=parseInt($('b-id').value)||0;
  const data={id,nom:$('b-nom').value.trim(),districte_id:+$('b-districte').value,color:$('b-color-hex').value,lat:+$('b-lat').value,lng:+$('b-lng').value,poblacio:+$('b-pob').value,actiu:+$('b-actiu').value};
  if(!data.nom){toast('⚠️ <?= $lang=="ca"?"El nom \xe9s obligatori":"El nombre es obligatorio" ?>','var(--warn)');return;}
  const res=await post('barri_save',data);
  if(res.ok){
    toast('✅ <?= $lang=="ca"?"Barri guardat":"Barrio guardado" ?>!');
    $('b-id').value=res.id; $('btn-del-barri').style.display='';
    $('modal-barri-title').textContent='Editar Barri';
    loadBarris(); loadDash();
    // Refresca mapa si esta obert
    if(adminMapInit) await refreshMapBarris();
    closeModal('modal-barri');
  } else toast('❌ '+res.error,'var(--danger)');
}
async function deleteBarri(){
  const id=parseInt($('b-id').value);
  if(!id||!confirm('<?= $lang=="ca"?"Segur que vols desactivar aquest barri?":"\xbfSeguro que quieres desactivar este barrio?" ?>')) return;
  const res=await post('barri_delete',{id});
  if(res.ok){toast('<?= $lang=="ca"?"Barri desactivat":"Barrio desactivado" ?>');closeModal('modal-barri');loadBarris();loadDash();if(adminMapInit)refreshMapBarris();}
  else toast('❌ '+res.error,'var(--danger)');
}

// ══════════════════════════════════════════════════════════════
//  DISTRICTES
// ══════════════════════════════════════════════════════════════
let districteData=[];
async function loadDistrictes(){
  const res=await api('districtes_list'); if(!res.ok) return;
  districteData=res.data;
  $('dist-tbody').innerHTML=res.data.map(d=>`
    <tr>
      <td><strong>D${d.numero}</strong></td>
      <td><div style="display:flex;align-items:center;gap:6px"><div class="cdot" style="background:${d.color}"></div><code style="font-size:11px;color:var(--text2)">${d.color}</code></div></td>
      <td>${d.nom}</td>
      <td><button class="btn btn-secondary btn-sm btn-icon" onclick="openDistricteModal(${d.id})">✏️</button></td>
    </tr>`).join('');
}
function switchAdminLang(){
  const cur=document.cookie.match(/lang=([a-z]{2})/);
  const next=(cur&&cur[1]==='ca')?'es':'ca';
  document.cookie='lang='+next+';path=/;max-age=31536000';
  location.reload();
}
function toggleTheme(){
  const isLight=document.documentElement.classList.toggle('light');
  localStorage.setItem('admin-theme', isLight?'light':'dark');
  const btn=$('btn-theme');
  btn.textContent=isLight?'🌙 <?= $at["dark_mode"] ?>':'☀️ <?= $at["light_mode"] ?>';
  // Actualitza tile mapa si esta obert
  if(adminMap && tileLayer){
    tileLayer.remove();
    tileLayer=L.tileLayer(isLight?TILE_LIGHT:TILE_DARK,{attribution:'&copy; OpenStreetMap &copy; Carto',subdomains:'abcd',maxZoom:19}).addTo(adminMap);
    mapDark=!isLight;
    if(mapBarrisData.length) mapBarrisData.forEach(b=>{ if(mapMarkers[b.id]) mapMarkers[b.id].setIcon(buildMapIcon(b)); });
  }
}
function openDistricteModal(id){
  $('modal-dist-title').textContent=id?'Editar Districte':'Nou Districte';
  $('btn-del-dist').style.display=id?'':'none';
  $('d-id').value=''; $('d-nom').value=''; $('d-num').value='';
  setColor('d-color','d-color-hex','#3b82f6');
  if(id){ const d=districteData.find(x=>x.id==id); if(d){$('d-id').value=d.id;$('d-nom').value=d.nom;$('d-num').value=d.numero;setColor('d-color','d-color-hex',d.color);} }
  openModal('modal-dist');
}
async function deleteDistricte(){
  const id=parseInt($('d-id').value);
  if(!id) return;
  const res=await post('districte_delete',{id});
  if(res.ok){toast('🗑️ <?= $lang=="ca"?"Districte eliminat":"Distrito eliminado" ?>');closeModal('modal-dist');loadDistrictes();}
  else toast('❌ '+res.error,'var(--danger)',5000);
}
async function saveDistricte(){
  const data={id:+$('d-id').value||0,nom:$('d-nom').value.trim(),numero:+$('d-num').value,color:$('d-color-hex').value};
  if(!data.nom||!data.numero){toast('⚠️ Nom i numero obligatoris','var(--warn)');return;}
  const res=await post('districte_save',data);
  if(res.ok){toast('✅ <?= $lang=="ca"?"Districte guardat":"Distrito guardado" ?>!');closeModal('modal-dist');loadDistrictes();}
  else toast('❌ '+res.error,'var(--danger)');
}

// ══════════════════════════════════════════════════════════════
//  CATEGORIES
// ══════════════════════════════════════════════════════════════
let catData=[];
async function loadCategories(){
  const res=await api('categories_list'); if(!res.ok) return;
  catData=res.data;
  $('cat-tbody').innerHTML=res.data.map(c=>`
    <tr>
      <td style="font-size:22px">${c.icona}</td>
      <td><strong>${c.nom}</strong></td>
      <td><code style="font-size:11px;color:var(--text2);background:var(--bg3);padding:2px 6px;border-radius:4px">${c.slug}</code></td>
      <td><div style="display:flex;align-items:center;gap:6px"><div class="cdot" style="background:${c.color}"></div><code style="font-size:11px;color:var(--text2)">${c.color}</code></div></td>
      <td>${c.ordre}</td>
      <td><button class="btn btn-secondary btn-sm btn-icon" onclick="openCatModal(${c.id})">✏️</button></td>
    </tr>`).join('');
}
function openCatModal(id){
  $('modal-cat-title').textContent=id?'Editar Categoria':'Nova Categoria';
  $('c-id').value=''; $('c-nom').value=''; $('c-slug').value=''; $('c-icona').value='📌'; $('c-ordre').value='0';
  setColor('c-color','c-color-hex','#3b82f6');
  if(id){ const c=catData.find(x=>x.id==id); if(c){$('c-id').value=c.id;$('c-nom').value=c.nom;$('c-slug').value=c.slug;$('c-icona').value=c.icona;$('c-ordre').value=c.ordre;setColor('c-color','c-color-hex',c.color);} }
  openModal('modal-cat');
}
async function saveCat(){
  const data={id:+$('c-id').value||0,nom:$('c-nom').value.trim(),slug:$('c-slug').value.trim(),icona:$('c-icona').value,color:$('c-color-hex').value,ordre:+$('c-ordre').value};
  if(!data.nom||!data.slug){toast('⚠️ <?= $lang=="ca"?"Nom i slug obligatoris":"Nombre y slug obligatorios" ?>','var(--warn)');return;}
  const res=await post('categoria_save',data);
  if(res.ok){toast('✅ <?= $lang=="ca"?"Categoria guardada":"Categor\xeda guardada" ?>!');closeModal('modal-cat');loadCategories();}
  else toast('❌ '+res.error,'var(--danger)');
}
// Auto-slug
$('c-nom').addEventListener('input',e=>{
  if(!$('c-id').value){
    $('c-slug').value=e.target.value.toLowerCase()
      .replace(/[àáâ]/g,'a').replace(/[èéê]/g,'e').replace(/[ìíî]/g,'i')
      .replace(/[òóô]/g,'o').replace(/[ùúû]/g,'u').replace(/[ç]/g,'c')
      .replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'');
  }
});

// ══════════════════════════════════════════════════════════════
//  PETICIONS
// ══════════════════════════════════════════════════════════════
let petStatus='',petPage=1;
function filterPets(st,el){
  petStatus=st;petPage=1;
  document.querySelectorAll('#pet-pills .pill').forEach(p=>p.classList.remove('act'));
  el?.classList.add('act'); loadPeticions();
}
async function loadPeticions(){
  $('pet-tbody').innerHTML='<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text2)">Carregant...</td></tr>';
  const res=await api(`peticions_list&page=${petPage}&st=${petStatus}`); if(!res.ok) return;
  const pc=p=>p==='alta'?'var(--danger)':p==='mitja'?'var(--warn)':'var(--success)';
  const sl={pendent:'⏳ Pendent',process:'🔄 En proces',resolt:'✅ Resolt',rebutjat:'Rebutjat'};
  $('pet-tbody').innerHTML=res.data.length?res.data.map(p=>`
    <tr>
      <td style="color:var(--text2);font-family:monospace">${p.id}</td>
      <td>${p.barri_nom||'—'}</td>
      <td>${p.cat_icona||''} ${p.cat_nom||'—'}</td>
      <td style="max-width:200px"><div style="font-weight:500;margin-bottom:2px">${p.titol}</div><div style="font-size:11px;color:var(--text2)">${p.descripcio.substring(0,60)}...</div></td>
      <td><span style="color:${pc(p.prioritat)};font-weight:700;font-size:12px">${p.prioritat.toUpperCase()}</span></td>
      <td><span class="est ${p.estat}">${sl[p.estat]||p.estat}</span></td>
      <td>👍 ${p.votos}</td>
      <td style="font-size:11px;color:var(--text2)">${new Date(p.creat_en).toLocaleDateString('ca-ES')}</td>
      <td>
        <div style="display:flex;gap:4px">
          ${p.estat!=='resolt'?`<button class="btn btn-success btn-sm" onclick="updPet(${p.id},'resolt')">✓</button>`:''}
          ${p.estat==='pendent'?`<button class="btn btn-warn btn-sm" onclick="updPet(${p.id},'process')">🔄</button>`:''}
          ${p.estat!=='rebutjat'?`<button class="btn btn-secondary btn-sm" onclick="updPet(${p.id},'rebutjat')">🚫</button>`:''}
          ${(p.barri_nom&&p.cat_nom)?`<button class="btn btn-sm" style="background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.4);color:#fca5a5;font-size:10px;padding:2px 6px;" onclick="petToMancanca(${p.id})" title="Marcar com a mancança oficial al barri">⚠️ Mancança</button>`:''}
          <button class="btn btn-danger btn-sm btn-icon" onclick="delPet(${p.id})">🗑️</button>
        </div>
      </td>
    </tr>`).join(''):'<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text2)">Cap peticio</td></tr>';
  const totalPg=Math.ceil(res.total/25);
  $('pet-pag').innerHTML=totalPg>1?`
    ${petPage>1?`<button class="btn btn-secondary btn-sm" onclick="petPage--;loadPeticions()">Anterior</button>`:''}
    <span style="font-size:13px;color:var(--text2)">Pagina ${petPage} de ${totalPg}</span>
    ${petPage<totalPg?`<button class="btn btn-secondary btn-sm" onclick="petPage++;loadPeticions()">Seguent</button>`:''}
  `:'';
}
async function updPet(id,estat){
  const res=await post('peticio_estat',{id,estat});
  if(res.ok){toast('✅ <?= $lang=="ca"?"Estat actualitzat":"Estado actualizado" ?>!');loadPeticions();loadDash();}
  else toast('❌ '+res.error,'var(--danger)');
}
async function petToMancanca(id){
  if(!confirm('<?= $lang=="ca"?"Marcar com a mancança oficial al barri? Això actualitzarà l\'estat del recurs.":"¿Marcar como carencia oficial del barrio? Esto actualizará el estado del recurso." ?>')) return;
  const res=await post('peticio_to_mancanca',{peticio_id:id});
  if(res.ok){
    toast('⚠️ <?= $lang=="ca"?"Mancança registrada i petició marcada en procés":"Carencia registrada y petición marcada en proceso" ?>');
    loadPeticions(); loadDash();
  } else toast('❌ '+res.error,'var(--danger)');
}
async function delPet(id){
  if(!confirm('<?= $lang=="ca"?"Eliminar aquesta petici\xf3?":"\xbfEliminar esta petici\xf3n?" ?>')) return;
  const res=await post('peticio_delete',{id});
  if(res.ok){toast('<?= $lang=="ca"?"Petici\xf3 eliminada":"Petici\xf3n eliminada" ?>');loadPeticions();loadDash();}
  else toast('❌ '+res.error,'var(--danger)');
}

// ── COLOR HELPERS ─────────────────────────────────────────────
function setColor(pickerId,hexId,val){$(pickerId).value=val;$(hexId).value=val;}
$('b-color').addEventListener('input',e=>{$('b-color-hex').value=e.target.value;});
$('b-color-hex').addEventListener('input',e=>{if(/^#[0-9A-Fa-f]{6}$/.test(e.target.value))$('b-color').value=e.target.value;});
$('d-color').addEventListener('input',e=>{$('d-color-hex').value=e.target.value;});
$('d-color-hex').addEventListener('input',e=>{if(/^#[0-9A-Fa-f]{6}$/.test(e.target.value))$('d-color').value=e.target.value;});
$('c-color').addEventListener('input',e=>{$('c-color-hex').value=e.target.value;});
$('c-color-hex').addEventListener('input',e=>{if(/^#[0-9A-Fa-f]{6}$/.test(e.target.value))$('c-color').value=e.target.value;});

// ── INIT ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded',()=>{
  // Aplica tema guardat
  const savedTheme=localStorage.getItem('admin-theme');
  if(savedTheme==='light'){
    document.documentElement.classList.add('light');
    $('btn-theme').textContent='🌙 <?= $at["dark_mode"] ?>';
  }
  loadDash();
  // Mobile
  if(window.innerWidth<=900){ $('mob-menu-btn').style.display='flex'; $('mob-fab').style.display='flex'; }
  window.addEventListener('resize',()=>{
    const mob=window.innerWidth<=900;
    $('mob-menu-btn').style.display=mob?'flex':'none';
    $('mob-fab').style.display=mob?'flex':'none';
  });
});
</script>
</body>
</html>

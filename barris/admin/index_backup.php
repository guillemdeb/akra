<?php
/**
 * Alacant Barris · PANELL D'ADMINISTRACIÓ
 * admin/index.php
 *
 * Accés: /admin/  (protegit per contrasenya)
 * Canvia ADMIN_PASS per la teua contrasenya
 */
require_once __DIR__ . '/../includes/config.php';

// ── AUTENTICACIÓ ─────────────────────────────────
define('ADMIN_PASS', 'alacant2026'); // ← CANVIA AÇÒ

session_start();
$loginError = '';

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
if (isset($_POST['login_pass'])) {
    if ($_POST['login_pass'] === ADMIN_PASS) {
        $_SESSION['admin'] = true;
        header('Location: index.php');
        exit;
    }
    $loginError = 'Contrasenya incorrecta';
}
if (!isset($_SESSION['admin'])) {
    // Mostra login
    showLogin($loginError);
    exit;
}

// ── API INTERNA (peticions AJAX des del propi admin) ───
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['ajax'] ?? '';
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];

    try {
        $pdo = db();
        switch ($action) {

            // Llista barris completa
            case 'barris_list':
                $rows = $pdo->query("
                    SELECT b.*, d.nom AS districte_nom, d.numero AS districte_num
                    FROM barris b
                    JOIN districtes d ON d.id = b.districte_id
                    ORDER BY d.numero, b.nom
                ")->fetchAll();
                echo json_encode(['ok'=>true,'data'=>$rows]);
                break;

            // Actualitzar posició (lat/lng) d'un barri
            case 'barri_coords':
                $id  = (int)($body['id'] ?? 0);
                $lat = (float)($body['lat'] ?? 0);
                $lng = (float)($body['lng'] ?? 0);
                if (!$id || !$lat || !$lng) { echo json_encode(['ok'=>false,'error'=>'Dades invàlides']); break; }
                $pdo->prepare("UPDATE barris SET lat=?, lng=? WHERE id=?")->execute([$lat,$lng,$id]);
                echo json_encode(['ok'=>true]);
                break;

            // Guardar barri (create o update)
            case 'barri_save':
                $id          = (int)($body['id'] ?? 0);
                $nom         = trim($body['nom'] ?? '');
                $districte_id= (int)($body['districte_id'] ?? 0);
                $color       = trim($body['color'] ?? '#3b82f6');
                $lat         = (float)($body['lat'] ?? 0);
                $lng         = (float)($body['lng'] ?? 0);
                $poblacio    = (int)($body['poblacio'] ?? 0);
                $actiu       = (int)($body['actiu'] ?? 1);
                if (!$nom || !$districte_id) { echo json_encode(['ok'=>false,'error'=>'Nom i districte obligatoris']); break; }
                $slug = preg_replace('/[^a-z0-9]+/','_',strtolower(transliterate($nom)));
                if ($id) {
                    $pdo->prepare("UPDATE barris SET nom=?,slug=?,districte_id=?,color=?,lat=?,lng=?,poblacio=?,actiu=? WHERE id=?")
                        ->execute([$nom,$slug,$districte_id,$color,$lat,$lng,$poblacio,$actiu,$id]);
                } else {
                    $pdo->prepare("INSERT INTO barris (nom,slug,districte_id,color,lat,lng,poblacio,actiu) VALUES (?,?,?,?,?,?,?,?)")
                        ->execute([$nom,$slug,$districte_id,$color,$lat,$lng,$poblacio,$actiu]);
                    $id = $pdo->lastInsertId();
                    // Crear registres recursos per defecte
                    $cats = $pdo->query("SELECT id FROM categories WHERE activa=1")->fetchAll(PDO::FETCH_COLUMN);
                    $ins  = $pdo->prepare("INSERT IGNORE INTO recursos_barri (barri_id,categoria_id,estat) VALUES (?,?,'missing')");
                    foreach($cats as $cid) $ins->execute([$id,$cid]);
                }
                echo json_encode(['ok'=>true,'id'=>(int)$id]);
                break;

            // Esborrar barri
            case 'barri_delete':
                $id = (int)($body['id'] ?? 0);
                if (!$id) { echo json_encode(['ok'=>false,'error'=>'ID invàlid']); break; }
                $pdo->prepare("UPDATE barris SET actiu=0 WHERE id=?")->execute([$id]);
                echo json_encode(['ok'=>true]);
                break;

            // Recursos d'un barri
            case 'recursos_barri':
                $id = (int)($_GET['id'] ?? 0);
                $rows = $pdo->prepare("
                    SELECT rb.id, rb.estat, rb.notes,
                           c.id AS cat_id, c.slug, c.nom AS cat_nom, c.icona, c.color
                    FROM recursos_barri rb
                    JOIN categories c ON c.id = rb.categoria_id
                    WHERE rb.barri_id = ? AND c.activa=1
                    ORDER BY c.ordre
                ");
                $rows->execute([$id]);
                echo json_encode(['ok'=>true,'data'=>$rows->fetchAll()]);
                break;

            // Actualitzar estat recurs
            case 'recurs_update':
                $barri_id    = (int)($body['barri_id'] ?? 0);
                $categoria_id= (int)($body['categoria_id'] ?? 0);
                $estat        = $body['estat'] ?? 'missing';
                $notes        = trim($body['notes'] ?? '');
                if (!in_array($estat,['ok','partial','missing'])) { echo json_encode(['ok'=>false,'error'=>'Estat invàlid']); break; }
                $pdo->prepare("INSERT INTO recursos_barri (barri_id,categoria_id,estat,notes) VALUES (?,?,?,?)
                    ON DUPLICATE KEY UPDATE estat=VALUES(estat),notes=VALUES(notes)")
                    ->execute([$barri_id,$categoria_id,$estat,$notes]);
                echo json_encode(['ok'=>true]);
                break;

            // Llista districtes
            case 'districtes_list':
                $rows = $pdo->query("SELECT * FROM districtes ORDER BY numero")->fetchAll();
                echo json_encode(['ok'=>true,'data'=>$rows]);
                break;

            // Guardar districte
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

            // Llista categories
            case 'categories_list':
                $rows = $pdo->query("SELECT * FROM categories ORDER BY ordre")->fetchAll();
                echo json_encode(['ok'=>true,'data'=>$rows]);
                break;

            // Guardar categoria
            case 'categoria_save':
                $id    = (int)($body['id'] ?? 0);
                $slug  = preg_replace('/[^a-z0-9_]/','',strtolower(trim($body['slug'] ?? '')));
                $nom   = trim($body['nom'] ?? '');
                $icona = trim($body['icona'] ?? '📌');
                $color = trim($body['color'] ?? '#3b82f6');
                $ordre = (int)($body['ordre'] ?? 0);
                if (!$nom || !$slug) { echo json_encode(['ok'=>false,'error'=>'Nom i slug obligatoris']); break; }
                if ($id) {
                    $pdo->prepare("UPDATE categories SET slug=?,nom=?,icona=?,color=?,ordre=? WHERE id=?")->execute([$slug,$nom,$icona,$color,$ordre,$id]);
                } else {
                    $pdo->prepare("INSERT INTO categories (slug,nom,icona,color,ordre) VALUES (?,?,?,?,?)")->execute([$slug,$nom,$icona,$color,$ordre]);
                    $id = $pdo->lastInsertId();
                }
                echo json_encode(['ok'=>true,'id'=>(int)$id]);
                break;

            // Llista peticions
            case 'peticions_list':
                $page  = max(1,(int)($_GET['page']??1));
                $st    = $_GET['st'] ?? '';
                $where = $st ? "WHERE p.estat='".htmlspecialchars($st)."'" : '';
                $total = $pdo->query("SELECT COUNT(*) FROM peticions p $where")->fetchColumn();
                $rows  = $pdo->query("
                    SELECT p.*, b.nom AS barri_nom, c.nom AS cat_nom, c.icona AS cat_icona
                    FROM peticions p
                    LEFT JOIN barris b ON b.id=p.barri_id
                    LEFT JOIN categories c ON c.id=p.categoria_id
                    $where
                    ORDER BY FIELD(p.estat,'pendent','process','resolt','rebutjat'),
                             FIELD(p.prioritat,'alta','mitja','baixa'), p.creat_en DESC
                    LIMIT 25 OFFSET ".(($page-1)*25)
                )->fetchAll();
                echo json_encode(['ok'=>true,'data'=>$rows,'total'=>(int)$total,'page'=>$page]);
                break;

            // Actualitzar estat petició
            case 'peticio_estat':
                $id    = (int)($body['id'] ?? 0);
                $estat = $body['estat'] ?? '';
                if (!$id || !in_array($estat,['pendent','process','resolt','rebutjat'])) { echo json_encode(['ok'=>false,'error'=>'Invàlid']); break; }
                $pdo->prepare("UPDATE peticions SET estat=? WHERE id=?")->execute([$estat,$id]);
                echo json_encode(['ok'=>true]);
                break;

            // Esborrar petició
            case 'peticio_delete':
                $id = (int)($body['id'] ?? 0);
                if (!$id) { echo json_encode(['ok'=>false,'error'=>'ID invàlid']); break; }
                $pdo->prepare("DELETE FROM peticions WHERE id=?")->execute([$id]);
                echo json_encode(['ok'=>true]);
                break;

            // Estadístiques
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
                $s['cobertura'] = $s['total_r'] > 0
                    ? round(($s['ok_total'] + ($s['total_r']-$s['ok_total']-$s['missing_total'])*0.5) / $s['total_r'] * 100)
                    : 0;
                echo json_encode(['ok'=>true,'data'=>$s]);
                break;

            default:
                echo json_encode(['ok'=>false,'error'=>'Acció no trobada']);
        }
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

// Helper transliteració bàsica
function transliterate(string $s): string {
    $map = ['à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'ae','ç'=>'c',
            'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
            'ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o',
            'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','ÿ'=>'y',
            'À'=>'a','Á'=>'a','È'=>'e','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u',
            "\xc2\xb7"=>'',"\xe2\x80\x99"=>'','l'."\xc2\xb7".'l'=>'ll'];
    return strtr($s, $map);
}

// ────────────────────────────────────────────────
//  HTML PRINCIPAL DEL PANELL
// ────────────────────────────────────────────────
function showLogin($err='') {
?><!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin · Alacant Barris</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0a0e1a;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:'DM Sans',system-ui,sans-serif}
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@800&family=DM+Sans:wght@400;500&display=swap');
.card{background:#1e2d42;border:1px solid #2a3f5f;border-radius:20px;padding:40px;width:340px;box-shadow:0 8px 48px rgba(0,0,0,.6)}
.logo{width:56px;height:56px;background:linear-gradient(135deg,#3b82f6,#06b6d4);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 20px}
h1{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:#e8edf5;text-align:center;margin-bottom:6px}
p{font-size:13px;color:#8da0bb;text-align:center;margin-bottom:28px}
label{display:block;font-size:12px;color:#8da0bb;font-weight:500;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
input[type=password]{width:100%;background:#111827;border:1px solid #2a3f5f;border-radius:8px;color:#e8edf5;font-size:15px;padding:13px 16px;outline:none;transition:border .2s}
input:focus{border-color:#3b82f6}
.err{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.4);color:#fca5a5;padding:10px 14px;border-radius:8px;font-size:13px;margin:12px 0}
button{width:100%;height:48px;background:linear-gradient(135deg,#3b82f6,#06b6d4);color:#fff;border:none;border-radius:8px;font-family:'Syne',sans-serif;font-size:15px;font-weight:700;cursor:pointer;margin-top:16px;transition:opacity .2s}
button:hover{opacity:.9}
</style>
</head><body>
<div class="card">
  <div class="logo">🏛️</div>
  <h1>Alacant Barris</h1>
  <p>Panell d'Administració</p>
  <?php if($err): ?><div class="err">⚠️ <?= htmlspecialchars($err) ?></div><?php endif ?>
  <form method="POST">
    <label>Contrasenya</label>
    <input type="password" name="login_pass" placeholder="••••••••" autofocus>
    <button type="submit">Entrar →</button>
  </form>
</div>
</body></html>
<?php exit; }

// Carrega datos inicials pel HTML
$pdo_init = db();
$districtes_init = $pdo_init->query("SELECT * FROM districtes ORDER BY numero")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin · Alacant Barris</title>
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
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--text);font-family:var(--fb);min-height:100vh;display:flex;overflow:hidden}

/* ── SIDEBAR ── */
.sidebar{
  width:var(--sidebar);flex-shrink:0;
  background:var(--bg2);border-right:1px solid var(--border);
  display:flex;flex-direction:column;height:100vh;overflow-y:auto;
  transition:transform .3s;
}
.sb-logo{
  padding:20px;display:flex;align-items:center;gap:12px;
  border-bottom:1px solid var(--border);
}
.sb-logo-icon{
  width:40px;height:40px;border-radius:12px;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;
}
.sb-logo-text .t1{font-family:var(--fd);font-weight:800;font-size:15px;line-height:1}
.sb-logo-text .t2{font-size:10px;color:var(--text2);text-transform:uppercase;letter-spacing:.8px;margin-top:2px}
.sb-nav{flex:1;padding:12px 8px}
.sb-section{
  font-size:10px;font-weight:700;color:var(--text3);
  text-transform:uppercase;letter-spacing:1px;
  padding:16px 10px 6px;
}
.sb-item{
  display:flex;align-items:center;gap:10px;
  padding:10px 12px;border-radius:var(--radius-sm);
  cursor:pointer;transition:all .15s;
  font-size:13px;font-weight:500;color:var(--text2);
  border:none;background:none;width:100%;text-align:left;
  position:relative;
}
.sb-item:hover{background:rgba(255,255,255,.04);color:var(--text)}
.sb-item.act{background:rgba(59,130,246,.15);color:var(--accent)}
.sb-item .ico{font-size:17px;flex-shrink:0}
.sb-badge{
  margin-left:auto;background:var(--danger);color:#fff;
  font-size:9px;font-weight:700;border-radius:20px;
  padding:1px 6px;
}
.sb-footer{padding:16px;border-top:1px solid var(--border)}
.btn-logout{
  width:100%;padding:9px;border-radius:var(--radius-sm);
  background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);
  color:#fca5a5;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;
}
.btn-logout:hover{background:rgba(239,68,68,.2)}

/* ── MAIN AREA ── */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;height:100vh}
.topbar{
  height:60px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;padding:0 24px;gap:16px;
  background:var(--bg2);flex-shrink:0;
}
.topbar-title{font-family:var(--fd);font-size:18px;font-weight:800;flex:1}
.topbar-right{display:flex;gap:8px}
.content{flex:1;overflow-y:auto;padding:24px}

/* ── PANELS ── */
.panel{display:none}.panel.act{display:block}

/* ── STATS ROW ── */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
.scard{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--radius);padding:18px;position:relative;overflow:hidden;
}
.scard::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--c,var(--accent))}
.scard .si{font-size:28px;margin-bottom:10px;display:block}
.scard .sn{font-family:var(--fd);font-size:32px;font-weight:800;line-height:1;margin-bottom:4px}
.scard .sl{font-size:11px;color:var(--text2);text-transform:uppercase;letter-spacing:.5px}

/* ── TABLE ── */
.table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.table-head{
  display:flex;align-items:center;justify-content:space-between;
  padding:16px 20px;border-bottom:1px solid var(--border);gap:12px;
}
.table-head h3{font-family:var(--fd);font-size:16px;font-weight:700}
table{width:100%;border-collapse:collapse}
thead th{
  background:var(--bg3);padding:11px 16px;
  text-align:left;font-size:11px;font-weight:600;
  color:var(--text2);text-transform:uppercase;letter-spacing:.5px;
  border-bottom:1px solid var(--border);white-space:nowrap;
}
tbody tr{border-bottom:1px solid var(--border);transition:background .15s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,.02)}
tbody td{padding:12px 16px;font-size:13px;vertical-align:middle}

/* ── INPUTS ── */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.fg{display:flex;flex-direction:column;gap:5px}
.fg.full{grid-column:1/-1}
.fl{font-size:11px;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:.5px}
.fc{
  background:var(--bg3);border:1px solid var(--border);
  border-radius:var(--radius-sm);color:var(--text);
  font-family:var(--fb);font-size:13px;padding:9px 12px;
  outline:none;transition:border .2s;appearance:none;width:100%;
}
.fc:focus{border-color:var(--accent)}
.fc option{background:var(--bg2)}
textarea.fc{resize:vertical;min-height:60px}
select.fc{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%238da0bb' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px}

/* ── BUTTONS ── */
.btn{
  display:inline-flex;align-items:center;gap:6px;
  padding:8px 16px;border-radius:var(--radius-sm);
  font-family:var(--fb);font-size:13px;font-weight:600;
  cursor:pointer;border:none;transition:all .2s;white-space:nowrap;
}
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
.modal-overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.7);
  backdrop-filter:blur(4px);z-index:2000;
  display:none;align-items:center;justify-content:center;padding:20px;
}
.modal-overlay.open{display:flex}
.modal{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:20px;width:100%;max-width:620px;max-height:90vh;
  overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,.8);
}
.modal-head{
  display:flex;align-items:center;justify-content:space-between;
  padding:20px 24px;border-bottom:1px solid var(--border);position:sticky;top:0;
  background:var(--bg2);z-index:1;
}
.modal-head h2{font-family:var(--fd);font-size:18px;font-weight:800}
.modal-body{padding:24px}
.modal-foot{
  padding:16px 24px;border-top:1px solid var(--border);
  display:flex;justify-content:flex-end;gap:10px;
  position:sticky;bottom:0;background:var(--bg2);
}

/* ── MAP (dins modal) ── */
#barri-map-edit{height:340px;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border)}
.map-hint{font-size:12px;color:var(--accent);display:flex;align-items:center;gap:6px;margin-bottom:8px}

/* ── COLOR DOT ── */
.cdot{width:14px;height:14px;border-radius:50%;display:inline-block;border:2px solid rgba(255,255,255,.2);flex-shrink:0}

/* ── ESTAT BADGES ── */
.est{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.est.ok{background:rgba(16,185,129,.15);color:#6ee7b7;border:1px solid rgba(16,185,129,.3)}
.est.partial{background:rgba(245,158,11,.15);color:#fcd34d;border:1px solid rgba(245,158,11,.3)}
.est.missing{background:rgba(239,68,68,.15);color:#fca5a5;border:1px solid rgba(239,68,68,.3)}
.est.pendent{background:rgba(59,130,246,.15);color:#93c5fd;border:1px solid rgba(59,130,246,.3)}
.est.process{background:rgba(245,158,11,.15);color:#fcd34d;border:1px solid rgba(245,158,11,.3)}
.est.resolt{background:rgba(16,185,129,.15);color:#6ee7b7;border:1px solid rgba(16,185,129,.3)}
.est.rebutjat{background:rgba(100,116,139,.15);color:#94a3b8;border:1px solid rgba(100,116,139,.3)}

/* ── RECURSOS EDIT ── */
.rec-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
.rec-item{
  background:var(--bg3);border:1px solid var(--border);
  border-radius:var(--radius-sm);padding:12px;display:flex;align-items:center;gap:10px;
}
.rec-item .ricn{font-size:22px;flex-shrink:0}
.rec-info{flex:1;min-width:0}
.rec-lbl{font-size:12px;font-weight:600;margin-bottom:6px}
.rec-sel select{width:100%}

/* ── TOAST ── */
.toast{
  position:fixed;bottom:24px;right:24px;z-index:9999;
  background:var(--success);color:#fff;
  padding:12px 20px;border-radius:50px;font-size:13px;font-weight:600;
  box-shadow:var(--shadow);opacity:0;transform:translateY(10px);
  transition:all .3s;pointer-events:none;
}
.toast.show{opacity:1;transform:translateY(0)}

/* ── SEARCH ── */
.search-wrap{position:relative}
.search-ico{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text2);font-size:14px}
.search-wrap .fc{padding-left:33px}

/* ── PILLS ── */
.pills{display:flex;gap:8px;margin-bottom:16px}
.pill{padding:6px 14px;border-radius:20px;border:1px solid var(--border);background:var(--surface);color:var(--text2);font-size:12px;font-weight:500;cursor:pointer;transition:all .2s}
.pill.act{background:var(--accent);border-color:var(--accent);color:#fff}

/* ── PROGRESS ── */
.prog-bar{background:var(--border);border-radius:4px;height:6px;overflow:hidden}
.prog-fill{height:100%;border-radius:4px;transition:width .6s}

/* ── PAGINACIÓ ── */
.pagination{display:flex;align-items:center;gap:8px;justify-content:center;margin-top:16px}

/* ── MOBILE MENU BTN ── */
.menu-btn{display:none;position:fixed;bottom:20px;right:20px;z-index:500;width:52px;height:52px;border-radius:50%;background:var(--accent);color:#fff;border:none;font-size:22px;cursor:pointer;box-shadow:var(--shadow)}

/* ── LEAFLET ── */
.leaflet-container{background:#0d1b2e}
.leaflet-popup-content-wrapper{background:var(--bg2);border:1px solid var(--border);border-radius:10px;color:var(--text);font-family:var(--fb)}
.leaflet-popup-tip{background:var(--bg2)}

@media(max-width:900px){
  .sidebar{position:fixed;left:0;top:0;bottom:0;z-index:400;transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main{width:100%}
  .menu-btn{display:flex;align-items:center;justify-content:center}
  .stats-row{grid-template-columns:repeat(2,1fr)}
  .form-grid{grid-template-columns:1fr}
  .rec-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-icon">🏛️</div>
    <div class="sb-logo-text">
      <div class="t1">Alacant Barris</div>
      <div class="t2">Administració</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">Visió general</div>
    <button class="sb-item act" onclick="goPanel('dash',this)"><span class="ico">📊</span>Dashboard</button>

    <div class="sb-section">Gestió territorial</div>
    <button class="sb-item" onclick="goPanel('mapa',this)"><span class="ico">🗺️</span>Mapa interactiu</button>
    <button class="sb-item" onclick="goPanel('barris',this)"><span class="ico">🏘️</span>Barris</button>
    <button class="sb-item" onclick="goPanel('districtes',this)"><span class="ico">🗂️</span>Districtes</button>
    <button class="sb-item" onclick="goPanel('categories',this)"><span class="ico">📋</span>Categories</button>

    <div class="sb-section">Sol·licituds</div>
    <button class="sb-item" onclick="goPanel('peticions',this)">
      <span class="ico">📝</span>Peticions
      <span class="sb-badge" id="sb-badge">—</span>
    </button>
  </nav>
  <div class="sb-footer">
    <form method="POST">
      <button class="btn-logout" type="submit" name="logout" value="1">🚪 Tancar sessió</button>
    </form>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <button class="btn btn-secondary btn-sm" onclick="toggleSidebar()" style="display:none" id="mob-menu">☰</button>
    <div class="topbar-title" id="topbar-title">Dashboard</div>
    <div class="topbar-right" id="topbar-actions"></div>
  </div>
  <div class="content">

    <!-- ═══ DASHBOARD ═══ -->
    <div id="p-dash" class="panel act">
      <div class="stats-row" id="dash-stats">
        <div class="scard" style="--c:var(--accent)"><span class="si">🏘️</span><div class="sn" id="ds-barris">—</div><div class="sl">Barris actius</div></div>
        <div class="scard" style="--c:var(--danger)"><span class="si">⚠️</span><div class="sn" id="ds-miss">—</div><div class="sl">Mancances</div></div>
        <div class="scard" style="--c:var(--success)"><span class="si">📊</span><div class="sn" id="ds-cov">—%</div><div class="sl">Cobertura</div></div>
        <div class="scard" style="--c:var(--warn)"><span class="si">📝</span><div class="sn" id="ds-pet">—</div><div class="sl">Peticions pendents</div></div>
      </div>
      <div class="table-wrap">
        <div class="table-head"><h3>⚡ Accions ràpides</h3></div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;padding:20px">
          <button class="btn btn-primary" onclick="goPanel('barris',null);openBarriModal(0)">➕ Nou barri</button>
          <button class="btn btn-secondary" onclick="goPanel('mapa',null)">🗺️ Editar mapa</button>
          <button class="btn btn-secondary" onclick="goPanel('peticions',null)">📝 Gestionar peticions</button>
          <button class="btn btn-secondary" onclick="goPanel('categories',null)">📋 Editar categories</button>
          <button class="btn btn-secondary" onclick="goPanel('districtes',null)">🗂️ Editar districtes</button>
        </div>
      </div>
    </div>

    <!-- ═══ MAPA ═══ -->
    <div id="p-mapa" class="panel">
      <div style="background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.3);border-radius:var(--radius);padding:12px 16px;margin-bottom:16px;font-size:13px;color:#93c5fd">
        📌 <strong>Arrossega</strong> els marcadors per reposicionar els barris. Els canvis es guarden automàticament a la BD.
        Fes <strong>doble clic</strong> en un marcador per editar-lo.
      </div>
      <div id="admin-map" style="height:calc(100vh - 200px);border-radius:var(--radius);overflow:hidden;border:1px solid var(--border)"></div>
    </div>

    <!-- ═══ BARRIS ═══ -->
    <div id="p-barris" class="panel">
      <div class="table-wrap">
        <div class="table-head">
          <h3>Gestió de Barris</h3>
          <div style="display:flex;gap:8px;align-items:center">
            <div class="search-wrap"><span class="search-ico">🔍</span><input class="fc" type="search" placeholder="Cercar..." id="barri-search" oninput="filterBarriTable(this.value)" style="width:200px"></div>
            <button class="btn btn-primary" onclick="openBarriModal(0)">➕ Nou barri</button>
          </div>
        </div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr>
              <th>ID</th><th>Color</th><th>Nom</th><th>Districte</th>
              <th>Lat / Lng</th><th>Població</th><th>Score</th><th>Actiu</th><th>Accions</th>
            </tr></thead>
            <tbody id="barri-tbody">
              <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text2)">Carregant...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══ DISTRICTES ═══ -->
    <div id="p-districtes" class="panel">
      <div class="table-wrap">
        <div class="table-head">
          <h3>Gestió de Districtes</h3>
          <button class="btn btn-primary" onclick="openDistricteModal(0)">➕ Nou districte</button>
        </div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr><th>Nº</th><th>Color</th><th>Nom</th><th>Barris</th><th>Accions</th></tr></thead>
            <tbody id="dist-tbody"><tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text2)">Carregant...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══ CATEGORIES ═══ -->
    <div id="p-categories" class="panel">
      <div class="table-wrap">
        <div class="table-head">
          <h3>Gestió de Categories</h3>
          <button class="btn btn-primary" onclick="openCatModal(0)">➕ Nova categoria</button>
        </div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr><th>Icona</th><th>Nom</th><th>Slug</th><th>Color</th><th>Ordre</th><th>Accions</th></tr></thead>
            <tbody id="cat-tbody"><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text2)">Carregant...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══ PETICIONS ═══ -->
    <div id="p-peticions" class="panel">
      <div class="pills" id="pet-pills">
        <div class="pill act" onclick="filterPets('',this)">Totes</div>
        <div class="pill" onclick="filterPets('pendent',this)">⏳ Pendents</div>
        <div class="pill" onclick="filterPets('process',this)">🔄 En procés</div>
        <div class="pill" onclick="filterPets('resolt',this)">✅ Resoltes</div>
        <div class="pill" onclick="filterPets('rebutjat',this)">🚫 Rebutjades</div>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr>
            <th>ID</th><th>Barri</th><th>Categoria</th><th>Títol</th>
            <th>Prioritat</th><th>Estat</th><th>Vots</th><th>Data</th><th>Accions</th>
          </tr></thead>
          <tbody id="pet-tbody"><tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text2)">Carregant...</td></tr></tbody>
        </table>
      </div>
      <div class="pagination" id="pet-pag"></div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<!-- ════════════════════════════════════
     MODAL BARRI
════════════════════════════════════ -->
<div class="modal-overlay" id="modal-barri">
<div class="modal">
  <div class="modal-head">
    <h2 id="modal-barri-title">Barri</h2>
    <button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modal-barri')">✕</button>
  </div>
  <div class="modal-body">
    <input type="hidden" id="b-id">

    <!-- TABS del modal -->
    <div class="pills" id="modal-tabs" style="margin-bottom:20px">
      <div class="pill act" onclick="modalTab('info',this)">ℹ️ Informació</div>
      <div class="pill" onclick="modalTab('pos',this)">📍 Posició</div>
      <div class="pill" onclick="modalTab('recursos',this)">📋 Recursos</div>
    </div>

    <!-- TAB: INFO -->
    <div id="mt-info">
      <div class="form-grid">
        <div class="fg"><label class="fl">Nom *</label><input class="fc" id="b-nom" placeholder="Ex: El Pla"></div>
        <div class="fg"><label class="fl">Districte *</label>
          <select class="fc" id="b-districte">
            <?php foreach($districtes_init as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="fg"><label class="fl">Color</label>
          <div style="display:flex;gap:8px;align-items:center">
            <input type="color" id="b-color" value="#3b82f6" style="width:44px;height:36px;border:none;background:none;cursor:pointer;padding:0">
            <input class="fc" id="b-color-hex" value="#3b82f6" placeholder="#3b82f6" style="flex:1;font-family:monospace" oninput="syncColorHex(this.value)">
          </div>
        </div>
        <div class="fg"><label class="fl">Població</label><input class="fc" type="number" id="b-pob" placeholder="0"></div>
        <div class="fg full"><label class="fl">Actiu</label>
          <select class="fc" id="b-actiu"><option value="1">✅ Sí</option><option value="0">❌ No</option></select>
        </div>
      </div>
    </div>

    <!-- TAB: POSICIÓ -->
    <div id="mt-pos" style="display:none">
      <div class="map-hint">💡 Fes clic al mapa per col·locar el barri, o arrossega el marcador</div>
      <div id="barri-map-edit"></div>
      <div class="form-grid" style="margin-top:14px">
        <div class="fg"><label class="fl">Latitud</label><input class="fc" id="b-lat" type="number" step="0.000001" placeholder="38.345000"></div>
        <div class="fg"><label class="fl">Longitud</label><input class="fc" id="b-lng" type="number" step="0.000001" placeholder="-0.490000"></div>
      </div>
    </div>

    <!-- TAB: RECURSOS -->
    <div id="mt-recursos" style="display:none">
      <div id="rec-grid-edit"><p style="color:var(--text2);font-size:13px">Guarda primer la informació bàsica per editar els recursos.</p></div>
    </div>
  </div>
  <div class="modal-foot">
    <button class="btn btn-secondary" onclick="closeModal('modal-barri')">Cancel·lar</button>
    <button class="btn btn-danger btn-sm" id="btn-del-barri" onclick="deleteBarri()" style="margin-right:auto;display:none">🗑️ Eliminar</button>
    <button class="btn btn-primary" onclick="saveBarri()">💾 Guardar</button>
  </div>
</div>
</div>

<!-- ════════════════════════════════════
     MODAL DISTRICTE
════════════════════════════════════ -->
<div class="modal-overlay" id="modal-dist">
<div class="modal" style="max-width:440px">
  <div class="modal-head">
    <h2 id="modal-dist-title">Districte</h2>
    <button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modal-dist')">✕</button>
  </div>
  <div class="modal-body">
    <input type="hidden" id="d-id">
    <div class="form-grid">
      <div class="fg"><label class="fl">Número</label><input class="fc" type="number" id="d-num" min="1" max="20"></div>
      <div class="fg"><label class="fl">Color</label>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="color" id="d-color" style="width:44px;height:36px;border:none;background:none;cursor:pointer;padding:0">
          <input class="fc" id="d-color-hex" style="flex:1;font-family:monospace" oninput="document.getElementById('d-color').value=this.value">
        </div>
      </div>
      <div class="fg full"><label class="fl">Nom *</label><input class="fc" id="d-nom" placeholder="Ex: Districte 1 · Centre"></div>
    </div>
  </div>
  <div class="modal-foot">
    <button class="btn btn-secondary" onclick="closeModal('modal-dist')">Cancel·lar</button>
    <button class="btn btn-primary" onclick="saveDistricte()">💾 Guardar</button>
  </div>
</div>
</div>

<!-- ════════════════════════════════════
     MODAL CATEGORIA
════════════════════════════════════ -->
<div class="modal-overlay" id="modal-cat">
<div class="modal" style="max-width:480px">
  <div class="modal-head">
    <h2 id="modal-cat-title">Categoria</h2>
    <button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modal-cat')">✕</button>
  </div>
  <div class="modal-body">
    <input type="hidden" id="c-id">
    <div class="form-grid">
      <div class="fg"><label class="fl">Icona (emoji)</label><input class="fc" id="c-icona" placeholder="🏥" style="font-size:20px;text-align:center"></div>
      <div class="fg"><label class="fl">Ordre</label><input class="fc" type="number" id="c-ordre" min="0" max="99"></div>
      <div class="fg full"><label class="fl">Nom *</label><input class="fc" id="c-nom" placeholder="Ex: Centre de Salut"></div>
      <div class="fg"><label class="fl">Slug *</label><input class="fc" id="c-slug" placeholder="salut" style="font-family:monospace"></div>
      <div class="fg"><label class="fl">Color</label>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="color" id="c-color" style="width:44px;height:36px;border:none;background:none;cursor:pointer;padding:0">
          <input class="fc" id="c-color-hex" style="flex:1;font-family:monospace" oninput="document.getElementById('c-color').value=this.value">
        </div>
      </div>
    </div>
  </div>
  <div class="modal-foot">
    <button class="btn btn-secondary" onclick="closeModal('modal-cat')">Cancel·lar</button>
    <button class="btn btn-primary" onclick="saveCat()">💾 Guardar</button>
  </div>
</div>
</div>

<!-- MOBILE MENU BTN -->
<button class="menu-btn" id="mob-menu" onclick="toggleSidebar()">☰</button>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
// ─── UTILS ───────────────────────────────────────
const $ = id => document.getElementById(id);
const api = async (action, params='') => (await fetch(`index.php?ajax=${action}${params}`)).json();
const post = async (action, data) => (await fetch(`index.php?ajax=${action}`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)})).json();

function toast(msg, color='var(--success)', dur=3000) {
  const t = $('toast');
  t.textContent=msg; t.style.background=color;
  t.classList.add('show');
  clearTimeout(t._t);
  t._t=setTimeout(()=>t.classList.remove('show'), dur);
}
function confirm2(msg) { return window.confirm(msg); }

// ─── PANELS ──────────────────────────────────────
const PANELS = ['dash','mapa','barris','districtes','categories','peticions'];
const TITLES = {dash:'Dashboard',mapa:'Mapa Interactiu',barris:'Barris',districtes:'Districtes',categories:'Categories',peticions:'Peticions'};
let currentPanel = 'dash';
let adminMap, mapMarkers={};

function goPanel(name, btn) {
  PANELS.forEach(p=>$('p-'+p)?.classList.remove('act'));
  document.querySelectorAll('.sb-item').forEach(b=>b.classList.remove('act'));
  $('p-'+name)?.classList.add('act');
  btn?.classList.add('act');
  $('topbar-title').textContent = TITLES[name]||name;
  currentPanel = name;
  closeSidebar();

  if(name==='mapa') initAdminMap();
  if(name==='barris') loadBarris();
  if(name==='districtes') loadDistrictes();
  if(name==='categories') loadCategories();
  if(name==='peticions') loadPeticions();
}

// ─── SIDEBAR ─────────────────────────────────────
function toggleSidebar(){ $('sidebar').classList.toggle('open'); }
function closeSidebar(){ $('sidebar').classList.remove('open'); }

// ─── MODAL ───────────────────────────────────────
function openModal(id){ $(id).classList.add('open'); }
function closeModal(id){ $(id).classList.remove('open'); if(id==='modal-barri') destroyBarriMap(); }
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open')}));

// ═══════════════════════════════════════════
//  DASHBOARD
// ═══════════════════════════════════════════
async function loadDash() {
  const res = await api('stats');
  if(!res.ok) return;
  const d=res.data;
  $('ds-barris').textContent = d.barris;
  $('ds-miss').textContent   = d.missing_total;
  $('ds-cov').textContent    = d.cobertura+'%';
  $('ds-pet').textContent    = d.pendents;
  $('sb-badge').textContent  = d.pendents||'0';
}

// ═══════════════════════════════════════════
//  MAPA ADMIN (arrossegar per moure barris)
// ═══════════════════════════════════════════
let adminMapInit = false;
async function initAdminMap(){
  if(adminMapInit && adminMap){ adminMap.invalidateSize(); return; }
  adminMapInit=true;
  adminMap = L.map('admin-map',{zoomControl:true}).setView([38.345,-0.490],13);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{
    attribution:'&copy; OpenStreetMap &copy; Carto',subdomains:'abcd',maxZoom:19
  }).addTo(adminMap);

  const res = await api('barris_list');
  if(!res.ok) return;

  res.data.forEach(b=>{
    const mk = L.marker([+b.lat,+b.lng],{
      draggable:true,
      icon: L.divIcon({
        html:`<div style="
          background:${b.color};border:3px solid rgba(255,255,255,.5);
          border-radius:50%;width:36px;height:36px;
          display:flex;align-items:center;justify-content:center;
          font-family:'Syne',sans-serif;font-size:10px;font-weight:800;color:#fff;
          box-shadow:0 2px 12px rgba(0,0,0,.5);cursor:grab;
        ">✎</div>`,
        iconSize:[36,36],iconAnchor:[18,18],className:''
      })
    }).addTo(adminMap);

    mk.bindTooltip(`<strong>${b.nom}</strong><br><small>${b.districte_nom}</small>`, {permanent:false,direction:'top'});

    // Guardar coordenades quan s'arrossega
    mk.on('dragend', async e=>{
      const ll=e.target.getLatLng();
      const res=await post('barri_coords',{id:b.id,lat:ll.lat.toFixed(6),lng:ll.lng.toFixed(6)});
      if(res.ok) toast(`📍 ${b.nom} mogut i guardat!`);
      else toast('❌ Error guardant posició','var(--danger)');
    });

    // Doble clic per editar
    mk.on('dblclick', ()=>{ openBarriModal(b.id); });

    mapMarkers[b.id]=mk;
  });
}

// ═══════════════════════════════════════════
//  BARRIS TABLE
// ═══════════════════════════════════════════
let barrisData = [];
async function loadBarris(){
  const res=await api('barris_list');
  if(!res.ok) return;
  barrisData=res.data;
  renderBarrisTable(barrisData);
}
function renderBarrisTable(data){
  const score = b => {
    // Calculem score si hi ha recursos, si no mostrem '—'
    return b.score!==undefined ? b.score+'%' : '—';
  };
  $('barri-tbody').innerHTML = data.length ? data.map(b=>`
    <tr>
      <td style="color:var(--text2);font-family:monospace">${b.id}</td>
      <td><div style="display:flex;align-items:center;gap:6px"><div class="cdot" style="background:${b.color}"></div><code style="font-size:11px;color:var(--text2)">${b.color}</code></div></td>
      <td><strong>${b.nom}</strong></td>
      <td><span class="est pendent" style="background:${b.districte_color||'#2a3f5f'}22;color:${b.color};border-color:${b.color}44">${b.districte_nom}</span></td>
      <td style="font-family:monospace;font-size:11px;color:var(--text2)">${(+b.lat).toFixed(5)}, ${(+b.lng).toFixed(5)}</td>
      <td>${b.poblacio?Number(b.poblacio).toLocaleString():'—'}</td>
      <td>—</td>
      <td>${b.actiu=='1'?'<span class="est ok">✅ Sí</span>':'<span class="est rebutjat">❌ No</span>'}</td>
      <td>
        <div style="display:flex;gap:6px">
          <button class="btn btn-secondary btn-sm btn-icon" onclick="openBarriModal(${b.id})" title="Editar">✏️</button>
          <button class="btn btn-secondary btn-sm btn-icon" onclick="centerMapOnBarri(${b.id},${b.lat},${b.lng})" title="Veure al mapa">🗺️</button>
        </div>
      </td>
    </tr>`).join('') : '<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text2)">Cap barri trobat</td></tr>';
}
function filterBarriTable(v){
  renderBarrisTable(v?barrisData.filter(b=>b.nom.toLowerCase().includes(v.toLowerCase())||b.districte_nom.toLowerCase().includes(v.toLowerCase())):barrisData);
}
function centerMapOnBarri(id,lat,lng){
  goPanel('mapa',document.querySelector('.sb-item:nth-child(3)'));
  setTimeout(()=>{ if(adminMap){ adminMap.setView([+lat,+lng],16); mapMarkers[id]?.openTooltip(); }},200);
}

// ── Modal barri ──────────────────────────
let barriEditMap=null, barriEditMk=null;

async function openBarriModal(id){
  $('modal-barri-title').textContent = id ? 'Editar Barri' : 'Nou Barri';
  $('btn-del-barri').style.display   = id ? '' : 'none';
  $('b-id').value=''; $('b-nom').value=''; $('b-pob').value='';
  $('b-lat').value='38.345000'; $('b-lng').value='-0.490000';
  $('b-actiu').value='1';
  setColor('b-color','b-color-hex','#3b82f6');
  modalTab('info', document.querySelector('#modal-tabs .pill'));

  if(id){
    $('b-id').value=id;
    const found=barrisData.find(b=>b.id==id);
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

function modalTab(tab, el){
  ['info','pos','recursos'].forEach(t=>$('mt-'+t).style.display='none');
  document.querySelectorAll('#modal-tabs .pill').forEach(p=>p.classList.remove('act'));
  $('mt-'+tab).style.display='';
  el?.classList.add('act');
  if(tab==='pos') initBarriEditMap();
  if(tab==='recursos'){
    const id=parseInt($('b-id').value);
    if(id) loadRecursosEdit(id);
    else $('rec-grid-edit').innerHTML='<p style="color:var(--text2);font-size:13px">Guarda primer la informació bàsica per editar els recursos.</p>';
  }
}

function initBarriEditMap(){
  const lat=parseFloat($('b-lat').value)||38.345;
  const lng=parseFloat($('b-lng').value)||-0.490;
  if(barriEditMap){ barriEditMap.setView([lat,lng],15); barriEditMk?.setLatLng([lat,lng]); return; }
  setTimeout(()=>{
    barriEditMap=L.map('barri-map-edit').setView([lat,lng],15);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{subdomains:'abcd',maxZoom:19}).addTo(barriEditMap);
    barriEditMk=L.marker([lat,lng],{draggable:true}).addTo(barriEditMap);
    barriEditMk.on('drag',e=>{
      const l=e.target.getLatLng();
      $('b-lat').value=l.lat.toFixed(6);
      $('b-lng').value=l.lng.toFixed(6);
    });
    barriEditMap.on('click',e=>{
      $('b-lat').value=e.latlng.lat.toFixed(6);
      $('b-lng').value=e.latlng.lng.toFixed(6);
      barriEditMk.setLatLng(e.latlng);
    });
    ['b-lat','b-lng'].forEach(id=>$(id).addEventListener('input',()=>{
      const la=parseFloat($('b-lat').value), ln=parseFloat($('b-lng').value);
      if(la&&ln){ barriEditMk.setLatLng([la,ln]); barriEditMap.setView([la,ln]); }
    }));
  },100);
}
function destroyBarriMap(){ if(barriEditMap){barriEditMap.remove();barriEditMap=null;barriEditMk=null;} }

async function saveBarri(){
  const id=parseInt($('b-id').value)||0;
  const data={id,nom:$('b-nom').value.trim(),districte_id:+$('b-districte').value,color:$('b-color-hex').value,lat:+$('b-lat').value,lng:+$('b-lng').value,poblacio:+$('b-pob').value,actiu:+$('b-actiu').value};
  if(!data.nom){toast('⚠️ El nom és obligatori','var(--warn)');return;}
  const res=await post('barri_save',data);
  if(res.ok){
    toast('✅ Barri guardat!');
    $('b-id').value=res.id;
    $('btn-del-barri').style.display='';
    $('modal-barri-title').textContent='Editar Barri';
    loadBarris();
    loadDash();
  } else toast('❌ '+res.error,'var(--danger)');
}
async function deleteBarri(){
  const id=parseInt($('b-id').value);
  if(!id||!confirm2('Segur que vols desactivar aquest barri?')) return;
  const res=await post('barri_delete',{id});
  if(res.ok){toast('🗑️ Barri desactivat');closeModal('modal-barri');loadBarris();loadDash();}
  else toast('❌ '+res.error,'var(--danger)');
}

// ── Recursos d'un barri ──────────────────
async function loadRecursosEdit(barriId){
  const el=$('rec-grid-edit');
  el.innerHTML='<p style="color:var(--text2)">Carregant...</p>';
  const res=await api(`recursos_barri&id=${barriId}`);
  if(!res.ok){el.innerHTML='<p style="color:var(--danger)">Error carregant recursos</p>';return;}
  el.className='rec-grid';
  el.innerHTML=res.data.map(r=>`
    <div class="rec-item">
      <div class="ricn">${r.icona}</div>
      <div class="rec-info">
        <div class="rec-lbl">${r.cat_nom}</div>
        <div class="rec-sel">
          <select class="fc" onchange="updateRecurs(${barriId},${r.cat_id},this.value)">
            <option value="ok" ${r.estat==='ok'?'selected':''}>✅ Cobert</option>
            <option value="partial" ${r.estat==='partial'?'selected':''}>🟡 Parcial</option>
            <option value="missing" ${r.estat==='missing'?'selected':''}>❌ Mancat</option>
          </select>
        </div>
      </div>
    </div>`).join('');
}
async function updateRecurs(barriId,catId,estat){
  const res=await post('recurs_update',{barri_id:barriId,categoria_id:catId,estat});
  if(res.ok) toast('✅ Recurs actualitzat!');
  else toast('❌ '+res.error,'var(--danger)');
}

// ═══════════════════════════════════════════
//  DISTRICTES
// ═══════════════════════════════════════════
let districteData=[];
async function loadDistrictes(){
  const res=await api('districtes_list');
  if(!res.ok) return;
  districteData=res.data;
  $('dist-tbody').innerHTML=res.data.map(d=>`
    <tr>
      <td><strong>D${d.numero}</strong></td>
      <td><div style="display:flex;align-items:center;gap:6px"><div class="cdot" style="background:${d.color}"></div><code style="font-size:11px;color:var(--text2)">${d.color}</code></div></td>
      <td>${d.nom}</td>
      <td>—</td>
      <td><button class="btn btn-secondary btn-sm btn-icon" onclick="openDistricteModal(${d.id})">✏️</button></td>
    </tr>`).join('');
}
function openDistricteModal(id){
  $('modal-dist-title').textContent=id?'Editar Districte':'Nou Districte';
  $('d-id').value=''; $('d-nom').value=''; $('d-num').value='';
  setColor('d-color','d-color-hex','#3b82f6');
  if(id){
    const d=districteData.find(x=>x.id==id);
    if(d){ $('d-id').value=d.id; $('d-nom').value=d.nom; $('d-num').value=d.numero; setColor('d-color','d-color-hex',d.color); }
  }
  openModal('modal-dist');
}
async function saveDistricte(){
  const data={id:+$('d-id').value||0,nom:$('d-nom').value.trim(),numero:+$('d-num').value,color:$('d-color-hex').value};
  if(!data.nom||!data.numero){toast('⚠️ Nom i número obligatoris','var(--warn)');return;}
  const res=await post('districte_save',data);
  if(res.ok){toast('✅ Districte guardat!');closeModal('modal-dist');loadDistrictes();}
  else toast('❌ '+res.error,'var(--danger)');
}

// ═══════════════════════════════════════════
//  CATEGORIES
// ═══════════════════════════════════════════
let catData=[];
async function loadCategories(){
  const res=await api('categories_list');
  if(!res.ok) return;
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
  if(id){
    const c=catData.find(x=>x.id==id);
    if(c){ $('c-id').value=c.id; $('c-nom').value=c.nom; $('c-slug').value=c.slug; $('c-icona').value=c.icona; $('c-ordre').value=c.ordre; setColor('c-color','c-color-hex',c.color); }
  }
  openModal('modal-cat');
}
async function saveCat(){
  const data={id:+$('c-id').value||0,nom:$('c-nom').value.trim(),slug:$('c-slug').value.trim(),icona:$('c-icona').value,color:$('c-color-hex').value,ordre:+$('c-ordre').value};
  if(!data.nom||!data.slug){toast('⚠️ Nom i slug obligatoris','var(--warn)');return;}
  const res=await post('categoria_save',data);
  if(res.ok){toast('✅ Categoria guardada!');closeModal('modal-cat');loadCategories();}
  else toast('❌ '+res.error,'var(--danger)');
}
// Auto-genera slug del nom
$('c-nom')?.addEventListener('input',e=>{
  if(!$('c-id').value){
    $('c-slug').value=e.target.value.toLowerCase()
      .replace(/[àáâãäå]/g,'a').replace(/[èéêë]/g,'e').replace(/[ìíîï]/g,'i')
      .replace(/[òóôõö]/g,'o').replace(/[ùúûü]/g,'u').replace(/[·'']/g,'')
      .replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'');
  }
});

// ═══════════════════════════════════════════
//  PETICIONS
// ═══════════════════════════════════════════
let petStatus='', petPage=1;
function filterPets(st,el){
  petStatus=st; petPage=1;
  document.querySelectorAll('#pet-pills .pill').forEach(p=>p.classList.remove('act'));
  el?.classList.add('act');
  loadPeticions();
}
async function loadPeticions(){
  $('pet-tbody').innerHTML='<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text2)">Carregant...</td></tr>';
  const res=await api(`peticions_list&page=${petPage}&st=${petStatus}`);
  if(!res.ok) return;
  const prioColor=p=>p==='alta'?'var(--danger)':p==='mitja'?'var(--warn)':'var(--success)';
  $('pet-tbody').innerHTML=res.data.length?res.data.map(p=>`
    <tr>
      <td style="color:var(--text2);font-family:monospace">${p.id}</td>
      <td>${p.barri_nom||'—'}</td>
      <td>${p.cat_icona||''} ${p.cat_nom||'—'}</td>
      <td style="max-width:200px"><div style="font-weight:500;margin-bottom:2px">${p.titol}</div><div style="font-size:11px;color:var(--text2)">${p.descripcio.substring(0,60)}...</div></td>
      <td><span style="color:${prioColor(p.prioritat)};font-weight:700;font-size:12px">${p.prioritat.toUpperCase()}</span></td>
      <td><span class="est ${p.estat}">${{pendent:'⏳ Pendent',process:'🔄 En procés',resolt:'✅ Resolt',rebutjat:'🚫 Rebutjat'}[p.estat]||p.estat}</span></td>
      <td>👍 ${p.votos}</td>
      <td style="font-size:11px;color:var(--text2)">${new Date(p.creat_en).toLocaleDateString('ca-ES')}</td>
      <td>
        <div style="display:flex;gap:4px">
          ${p.estat!=='resolt'?`<button class="btn btn-success btn-sm" onclick="updPet(${p.id},'resolt')">✓</button>`:''}
          ${p.estat==='pendent'?`<button class="btn btn-warn btn-sm" onclick="updPet(${p.id},'process')">🔄</button>`:''}
          ${p.estat!=='rebutjat'?`<button class="btn btn-secondary btn-sm" onclick="updPet(${p.id},'rebutjat')">🚫</button>`:''}
          <button class="btn btn-danger btn-sm btn-icon" onclick="delPet(${p.id})">🗑️</button>
        </div>
      </td>
    </tr>`).join(''):'<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text2)">Cap petició trobada</td></tr>';

  // Paginació
  const totalPg=Math.ceil(res.total/25);
  $('pet-pag').innerHTML=totalPg>1?`
    ${petPage>1?`<button class="btn btn-secondary btn-sm" onclick="petPage--;loadPeticions()">‹ Anterior</button>`:''}
    <span style="font-size:13px;color:var(--text2)">Pàgina ${petPage} de ${totalPg} (${res.total} total)</span>
    ${petPage<totalPg?`<button class="btn btn-secondary btn-sm" onclick="petPage++;loadPeticions()">Següent ›</button>`:''}
  `:'';
}
async function updPet(id,estat){
  const res=await post('peticio_estat',{id,estat});
  if(res.ok){toast('✅ Estat actualitzat!');loadPeticions();loadDash();}
  else toast('❌ '+res.error,'var(--danger)');
}
async function delPet(id){
  if(!confirm2('Eliminar aquesta petició?')) return;
  const res=await post('peticio_delete',{id});
  if(res.ok){toast('🗑️ Petició eliminada');loadPeticions();loadDash();}
  else toast('❌ '+res.error,'var(--danger)');
}

// ─── COLOR HELPERS ───────────────────────
function setColor(pickerId, hexId, val){
  $(pickerId).value=val; $(hexId).value=val;
}
function syncColorHex(val){
  if(/^#[0-9A-Fa-f]{6}$/.test(val)) $('b-color').value=val;
}
document.getElementById('b-color')?.addEventListener('input',e=>{
  $('b-color-hex').value=e.target.value;
});
document.getElementById('d-color')?.addEventListener('input',e=>{ $('d-color-hex').value=e.target.value; });
document.getElementById('c-color')?.addEventListener('input',e=>{ $('c-color-hex').value=e.target.value; });

// ─── INIT ────────────────────────────────
document.addEventListener('DOMContentLoaded',()=>{
  loadDash();
  // Mobile: show menu btn
  if(window.innerWidth<=900) $('mob-menu').style.display='flex';
  window.addEventListener('resize',()=>{ $('mob-menu').style.display=window.innerWidth<=900?'flex':'none'; });
});
</script>
</body>
</html>

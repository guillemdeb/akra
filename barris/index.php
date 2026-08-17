<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/includes/config.php';

// ── IDIOMA (cal abans del bloc API i del SSR) ──────────────────
$lang = isset($_COOKIE['lang']) && $_COOKIE['lang'] === 'es' ? 'es' : 'ca';

// ── API ──────────────────────────────────────────────────────────
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo = db();
        switch ($_GET['ajax']) {

            case 'peticions':
                $page   = max(1, (int)($_GET['page'] ?? 1));
                $st     = $_GET['st'] ?? '';
                $valid  = ['pendent','process','resolt','rebutjat'];
                $where  = in_array($st, $valid) ? 'AND p.estat = ?' : '';
                $params = in_array($st, $valid) ? [$st] : [];
                $limit  = 20;
                $offset = ($page - 1) * $limit;

                $q = $pdo->prepare("SELECT COUNT(*) FROM peticions p WHERE 1=1 $where");
                $q->execute($params);
                $total = (int)$q->fetchColumn();

                $q2 = $pdo->prepare("
                    SELECT p.id, p.titol, p.descripcio, p.prioritat, p.estat, p.votos, p.creat_en,
                           b.nom AS barri_nom_ca, IFNULL(b.nom_es, b.nom) AS barri_nom_es, c.nom AS cat_nom, c.icona AS cat_icona
                    FROM peticions p
                    LEFT JOIN barris b ON b.id = p.barri_id
                    LEFT JOIN categories c ON c.id = p.categoria_id
                    WHERE 1=1 $where
                    ORDER BY p.creat_en DESC
                    LIMIT $limit OFFSET $offset
                ");
                $q2->execute($params);
                $rows = $q2->fetchAll(PDO::FETCH_ASSOC);
                // Seleccionem el nom del barri segons idioma
                foreach ($rows as &$row) {
                    $row['barri_nom'] = ($lang === 'es' && !empty($row['barri_nom_es']))
                        ? $row['barri_nom_es'] : $row['barri_nom_ca'];
                    unset($row['barri_nom_ca'], $row['barri_nom_es']);
                }
                unset($row);
                echo json_encode(['ok'=>true, 'data'=>$rows, 'total'=>$total, 'page'=>$page]);
                break;

            case 'peticio_create':
                $body        = json_decode(file_get_contents('php://input'), true) ?? [];
                $barri_id    = (int)($body['barri_id'] ?? 0);
                $cat_id      = (int)($body['categoria_id'] ?? 0);
                $titol       = trim($body['titol'] ?? '');
                $descripcio  = trim($body['descripcio'] ?? '');
                $prioritat   = in_array($body['prioritat']??'', ['alta','mitja','baixa']) ? $body['prioritat'] : 'mitja';
                $email       = trim($body['email'] ?? '');
                if (!$barri_id || !$cat_id || strlen($titol) < 3 || strlen($descripcio) < 5) {
                    echo json_encode(['ok'=>false, 'error'=>'Falten camps obligatoris']);
                    break;
                }
                $pdo->prepare("INSERT INTO peticions (barri_id,categoria_id,titol,descripcio,prioritat,email,estat) VALUES (?,?,?,?,?,?,'pendent')")
                    ->execute([$barri_id, $cat_id, $titol, $descripcio, $prioritat, $email ?: null]);
                echo json_encode(['ok'=>true, 'id'=>(int)$pdo->lastInsertId()]);
                break;

            case 'peticio_to_mancanca':
                // Converteix una petició en mancança oficial (recurs missing)
                $body2       = json_decode(file_get_contents('php://input'), true) ?? [];
                $pet_id      = (int)($body2['peticio_id'] ?? 0);
                if (!$pet_id) { echo json_encode(['ok'=>false,'error'=>'ID invàlid']); break; }
                $pet = $pdo->prepare("SELECT barri_id, categoria_id FROM peticions WHERE id=?");
                $pet->execute([$pet_id]);
                $row = $pet->fetch(PDO::FETCH_ASSOC);
                if (!$row) { echo json_encode(['ok'=>false,'error'=>'Petició no trobada']); break; }
                $pdo->prepare("INSERT INTO recursos_barri (barri_id,categoria_id,estat) VALUES (?,?,'missing')
                    ON DUPLICATE KEY UPDATE estat='missing'")
                    ->execute([$row['barri_id'],$row['categoria_id']]);
                $pdo->prepare("UPDATE peticions SET estat='process' WHERE id=?")->execute([$pet_id]);
                echo json_encode(['ok'=>true]);
                break;

            default:
                echo json_encode(['ok'=>false, 'error'=>'Accio desconeguda']);
        }
    } catch (Exception $e) {
        echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
    }
    exit;
}

// ── SSR: dades de la BD ───────────────────────────────────────────
$BARRIS_JSON = '[]';
$CATS_JSON   = '[]';
try {
    $pdo = db();

    $cats = $pdo->query("SELECT id, slug, nom, IFNULL(nom_es, nom) AS nom_es, icona, color FROM categories WHERE activa=1 ORDER BY ordre")->fetchAll(PDO::FETCH_ASSOC);

    $barris = $pdo->query("
        SELECT b.id, b.nom, IFNULL(b.nom_es, b.nom) AS nom_es, b.lat, b.lng, b.poblacio,
               CONCAT('Districte ', d.numero) AS area, d.color
        FROM barris b
        JOIN districtes d ON d.id = b.districte_id
        WHERE b.actiu = 1
        ORDER BY d.numero, b.nom
    ")->fetchAll(PDO::FETCH_ASSOC);

    $recursos = $pdo->query("
        SELECT rb.barri_id, c.slug, rb.estat
        FROM recursos_barri rb
        JOIN categories c ON c.id = rb.categoria_id
        WHERE c.activa = 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    $recMap = [];
    foreach ($recursos as $r) {
        $recMap[(int)$r['barri_id']][$r['slug']] = $r['estat'];
    }

    // Compte de peticions pendents per barri
    $petMap = [];
    $petRows = $pdo->query("SELECT barri_id, COUNT(*) AS n FROM peticions WHERE estat IN ('pendent','process') GROUP BY barri_id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($petRows as $pr) {
        $petMap[(int)$pr['barri_id']] = (int)$pr['n'];
    }

    foreach ($barris as &$b) {
        $b['nom']       = ($lang === 'es' && !empty($b['nom_es'])) ? $b['nom_es'] : $b['nom'];
        unset($b['nom_es']); // no enviem al JS, ja tenim el nom correcte
        $b['recursos']  = (object)($recMap[(int)$b['id']] ?? []);
        $b['npeticions'] = $petMap[(int)$b['id']] ?? 0;
    }
    unset($b);

    $BARRIS_JSON = json_encode(array_values($barris), JSON_UNESCAPED_UNICODE);
    // Seleccionem nom correcte per $lang i netegem nom_es
    foreach ($cats as &$cat) {
        $cat['nom'] = ($lang === 'es' && !empty($cat['nom_es'])) ? $cat['nom_es'] : $cat['nom'];
        unset($cat['nom_es']);
    }
    unset($cat);
    $CATS_JSON   = json_encode($cats, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // continua amb arrays buits
}
?>
<?php
$t = $lang === 'ca' ? [
  'title'      => 'Recursos Urbans',
  'mapa'       => 'Mapa',
  'resum'      => 'Resum',
  'barris'     => 'Barris',
  'recursos'   => 'Recursos',
  'afegir'     => "Sol·licitar",
  'peticions'  => 'Peticions',
  'cerca'      => 'Cercar barri...',
  'desc_dash'  => "Estat actual dels recursos a la ciutat d'Alacant",
  'desc_barris'=> "Fes clic en un barri per veure els detalls",
  'desc_rec'   => 'Estat de cobertura per categoria',
  'form_barri' => 'Barri afectat *',
  'form_tipus' => 'Tipus de recurs *',
  'form_titol' => "Títol breu *",
  'form_desc'  => "Descripció *",
  'form_prio'  => 'Prioritat',
  'form_email' => "El teu email (opcional)",
  'form_send'  => "✉️ Enviar Sol·licitud",
  'prio_alta'  => "🔴 Alta",
  'prio_mitja' => "🟡 Mitja",
  'prio_baixa' => "🟢 Baixa",
  'pet_title'  => 'Peticions Rebudes',
  'pet_sub'    => "Sol·licituds ciutadanes",
  'pet_tot'    => 'Tot',
  'pet_pend'   => "⏳ Pendents",
  'pet_proc'   => "🔄 En procés",
  'pet_res'    => "✅ Resolts",
  'toast_ok'   => "✅ Sol·licitud enviada correctament!",
  'toast_err'  => "❌ Error de connexió",
  'toast_camps'=> "⚠️ Emplena tots els camps obligatoris",
  'barris_lbl' => 'barris',
  'manc_lbl'   => 'mancances',
  'leg_estat'    => 'Estat cobertura',
  'leg_cobert'   => 'Cobert',
  'leg_parcial'  => 'Parcial',
  'leg_mancat'   => 'Mancat',
  'leg_pet'      => 'Peticions',
  'leg_cap'      => 'Cap petició',
  'leg_amb'      => 'Amb peticions',
  'vis_general'  => 'Visió General',
  'vis_sub'      => "Estat actual dels recursos a la ciutat d'Alacant",
  'barris_title' => "Barris d'Alacant",
  'barris_sub'   => 'Fes clic en un barri per veure els detalls',
  'cerca_ph'     => 'Cercar barri...',
  'titol_ph'     => 'Ex: Falta un CAP al barri',
  'form_sel_barri'  => 'Selecciona barri...',
  'form_sel_cat'    => 'Selecciona categoria...',
  'form_title'   => "Sol·licitar Recurs",
  'form_sub'     => "Informa d'una mancança al teu barri",
  'form_desc_ph' => 'Descriu la mancança que has detectat...',
  'form_email_ph'=> 'per rebre actualitzacions',
  'form_info'    => "Informació bàsica",
  'stat_barris'  => 'Barris',
  'stat_miss'    => 'Mancançes',
  'stat_cob'     => 'Cobertura',
  'stat_pet'     => 'Pendents',
  'manc_mes'     => 'Barris Amb Més Mancançes',
  'cob_cat'      => 'Cobertura per Categoria',
  'evol_title'   => 'Evolució Peticions',
  'dist_title'   => 'Cobertura per Districte',
  'topmiss_title'=> 'Barris amb més mancançes',
  'export_csv'   => 'Exportar peticions CSV',
  'copy_link'    => 'Copiar link',
  'loading'      => 'Carregant...',
  'cap_pet'      => 'Cap petició trobada',
  'cap_barri'    => 'Cap barri trobat',
  'mancat_lbl'   => 'Mancat',
  'parcial_lbl'  => 'Parcial',
  'tot_cobert'   => 'Tot cobert',
  'rec_title'    => 'Recursos Urbans',
  'rec_sub'      => 'Estat de cobertura per categoria',
  'pet_stat_sub' => "Gestió de sol·licituds ciutadanes",
] : [
  'title'      => 'Recursos Urbanos',
  'mapa'       => 'Mapa',
  'resum'      => 'Resumen',
  'barris'     => 'Barrios',
  'recursos'   => 'Recursos',
  'afegir'     => 'Solicitar',
  'peticions'  => 'Peticiones',
  'cerca'      => 'Buscar barrio...',
  'desc_dash'  => 'Estado actual de los recursos en la ciudad de Alicante',
  'desc_barris'=> 'Haz clic en un barrio para ver los detalles',
  'desc_rec'   => 'Estado de cobertura por categoria',
  'form_barri' => 'Barrio afectado *',
  'form_tipus' => 'Tipo de recurso *',
  'form_titol' => "Título breve *",
  'form_desc'  => "Descripción *",
  'form_prio'  => 'Prioridad',
  'form_email' => 'Tu email (opcional)',
  'form_send'  => "✉️ Enviar Solicitud",
  'prio_alta'  => "🔴 Alta",
  'prio_mitja' => "🟡 Media",
  'prio_baixa' => "🟢 Baja",
  'pet_title'  => 'Peticiones Recibidas',
  'pet_sub'    => 'Solicitudes ciudadanas',
  'pet_tot'    => 'Todo',
  'pet_pend'   => "⏳ Pendientes",
  'pet_proc'   => "🔄 En proceso",
  'pet_res'    => "✅ Resueltas",
  'toast_ok'   => "✅ ¡Solicitud enviada correctamente!",
  'toast_err'  => "❌ Error de conexión",
  'toast_camps'=> "⚠️ Rellena todos los campos obligatorios",
  'barris_lbl' => 'barrios',
  'manc_lbl'   => 'carencias',
  'leg_estat'    => 'Estado cobertura',
  'leg_cobert'   => 'Cubierto',
  'leg_parcial'  => 'Parcial',
  'leg_mancat'   => 'Carencia',
  'leg_pet'      => 'Peticiones',
  'leg_cap'      => 'Sin peticiones',
  'leg_amb'      => 'Con peticiones',
  'vis_general'  => 'Visión General',
  'vis_sub'      => 'Estado actual de los recursos en la ciudad de Alicante',
  'barris_title' => 'Barrios de Alicante',
  'barris_sub'   => 'Haz clic en un barrio para ver los detalles',
  'cerca_ph'     => 'Buscar barrio...',
  'titol_ph'     => 'Ej: Falta un CAP en el barrio',
  'form_sel_barri'  => 'Selecciona barrio...',
  'form_sel_cat'    => 'Selecciona categoría...',
  'form_title'   => 'Solicitar Recurso',
  'form_sub'     => 'Informa de una carencia en tu barrio',
  'form_desc_ph' => 'Describe la carencia que has detectado...',
  'form_email_ph'=> 'para recibir actualizaciones',
  'form_info'    => 'Información básica',
  'stat_barris'  => 'Barrios',
  'stat_miss'    => 'Carencias',
  'stat_cob'     => 'Cobertura',
  'stat_pet'     => 'Pendientes',
  'manc_mes'     => 'Barrios Con Más Carencias',
  'cob_cat'      => 'Cobertura por Categoría',
  'evol_title'   => 'Evolución Peticiones',
  'dist_title'   => 'Cobertura por Distrito',
  'topmiss_title'=> 'Barrios con más carencias',
  'export_csv'   => 'Exportar peticiones CSV',
  'copy_link'    => 'Copiar enlace',
  'loading'      => 'Cargando...',
  'cap_pet'      => 'No se han encontrado peticiones',
  'cap_barri'    => 'No se han encontrado barrios',
  'mancat_lbl'   => 'Carencia',
  'parcial_lbl'  => 'Parcial',
  'tot_cobert'   => 'Todo cubierto',
  'rec_title'    => 'Recursos Urbanos',
  'rec_sub'      => 'Estado de cobertura por categoría',
  'pet_stat_sub' => 'Gestión de solicitudes ciudadanas',
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="application-name" content="Alacant Barris">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Alacant Barris">
<meta name="theme-color" content="#0a0e1a" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#f0f4f8" media="(prefers-color-scheme: light)">
<link rel="manifest" href="/barris/manifest.json">
<link rel="apple-touch-icon" href="/barris/icons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="/barris/icons/icon-192x192.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Alacant · Recursos per Barri</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  :root {
    --bg: #0a0e1a;
    --bg2: #111827;
    --bg3: #1a2235;
    --surface: #1e2d42;
    --border: #2a3f5f;
    --text: #e8edf5;
    --text2: #8da0bb;
    --accent: #3b82f6;
    --accent2: #06b6d4;
    --success: #10b981;
    --warn: #f59e0b;
    --danger: #ef4444;
    --radius: 16px;
    --radius-sm: 8px;
    --shadow: 0 4px 24px rgba(0,0,0,0.4);
    --shadow-lg: 0 8px 48px rgba(0,0,0,0.6);
    --font-display: 'Syne', sans-serif;
    --font-body: 'DM Sans', sans-serif;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--font-body);
    min-height: 100vh;
    overflow-x: hidden;
  }
  .header {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 1000;
    background: rgba(10,14,26,0.9);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    padding: 0 16px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }
  .header-brand { display: flex; align-items: center; gap: 10px; }
  .header-logo {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
  }
  .header-title {
    font-family: var(--font-display);
    font-weight: 800; font-size: 18px;
    color: var(--text); letter-spacing: -0.5px; line-height: 1;
  }
  .header-sub {
    font-size: 10px; color: var(--text2);
    font-weight: 400; letter-spacing: 1px; text-transform: uppercase;
  }
  .header-actions { display: flex; gap: 8px; align-items: center; }
  .btn-icon {
    width: 38px; height: 38px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text2);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; cursor: pointer; transition: all 0.2s;
    position: relative;
  }
  .btn-icon:hover, .btn-icon.active {
    border-color: var(--accent); color: var(--accent);
    background: rgba(59,130,246,0.1);
  }
  .badge {
    position: absolute; top: -4px; right: -4px;
    background: var(--danger); color: #fff;
    font-size: 9px; font-weight: 700;
    border-radius: 20px; padding: 1px 5px;
    min-width: 16px; text-align: center;
  }
  .tab-nav {
    position: fixed; top: 60px; left: 0; right: 0;
    z-index: 999; background: var(--bg2);
    border-bottom: 1px solid var(--border);
    display: flex; overflow-x: auto; scrollbar-width: none;
  }
  .tab-nav::-webkit-scrollbar { display: none; }
  .tab-btn {
    flex: none; padding: 0 18px; height: 44px;
    font-family: var(--font-body); font-size: 13px; font-weight: 500;
    color: var(--text2); border: none; background: none;
    cursor: pointer; border-bottom: 2px solid transparent;
    transition: all 0.2s; white-space: nowrap;
    display: flex; align-items: center; gap: 6px;
  }
  .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
  .tab-btn:hover { color: var(--text); }
  .main { padding-top: 104px; min-height: 100vh; }
  .tab-panel { display: none; }
  .tab-panel.active { display: block; }
  #map { height: calc(100vh - 104px); width: 100%; background: var(--bg3); }
  .map-legend {
    position: absolute; bottom: 90px; left: 12px;
    z-index: 800;
    background: rgba(17,24,39,0.92);
    backdrop-filter: blur(12px);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 14px; max-width: 190px;
    box-shadow: var(--shadow);
  }
  .map-legend h4 {
    font-family: var(--font-display); font-size: 11px;
    font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; color: var(--text2); margin-bottom: 10px;
  }
  .legend-item {
    display: flex; align-items: center; gap: 8px;
    font-size: 12px; color: var(--text); margin-bottom: 6px;
  }
  .legend-dot {
    width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0;
    border: 2px solid rgba(255,255,255,0.3);
  }
  .map-stats {
    position: absolute; top: 12px; right: 12px;
    z-index: 800; display: flex; flex-direction: column; gap: 8px;
  }
  .stat-chip {
    background: rgba(17,24,39,0.92);
    backdrop-filter: blur(12px);
    border: 1px solid var(--border);
    border-radius: 50px; padding: 6px 14px;
    font-size: 12px; font-weight: 500; color: var(--text);
    display: flex; align-items: center; gap: 6px; box-shadow: var(--shadow);
  }
  .panel-content {
    padding: 16px; max-width: 768px; margin: 0 auto;
    padding-bottom: 90px;
  }
  .section-title {
    font-family: var(--font-display); font-size: 22px;
    font-weight: 800; color: var(--text);
    margin-bottom: 4px; letter-spacing: -0.5px;
  }
  .section-subtitle { font-size: 13px; color: var(--text2); margin-bottom: 20px; }
  .stats-grid {
    display: grid; grid-template-columns: repeat(2, 1fr);
    gap: 10px; margin-bottom: 20px;
  }
  .stat-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 16px;
    position: relative; overflow: hidden;
  }
  .stat-card::before {
    content: ''; position: absolute;
    top: 0; left: 0; right: 0; height: 3px;
    background: var(--color, var(--accent));
  }
  .stat-card .stat-icon { font-size: 24px; margin-bottom: 8px; display: block; }
  .stat-card .stat-num {
    font-family: var(--font-display); font-size: 28px; font-weight: 800;
    color: var(--text); line-height: 1; margin-bottom: 4px;
  }
  .stat-card .stat-label {
    font-size: 11px; color: var(--text2);
    text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;
  }
  .barri-list { display: flex; flex-direction: column; gap: 10px; }
  .barri-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); overflow: hidden;
    cursor: pointer; transition: all 0.2s; position: relative;
  }
  .barri-card:hover { border-color: var(--accent); transform: translateY(-1px); box-shadow: var(--shadow); }
  .barri-card-header {
    display: flex; align-items: center; gap: 12px; padding: 14px 16px;
  }
  .barri-color-bar {
    width: 4px; height: 44px; border-radius: 2px; flex-shrink: 0;
  }
  .barri-info { flex: 1; min-width: 0; }
  .barri-name {
    font-family: var(--font-display); font-size: 15px;
    font-weight: 700; color: var(--text); margin-bottom: 2px;
  }
  .barri-area { font-size: 11px; color: var(--text2); }
  .score-ring { width: 44px; height: 44px; position: relative; flex-shrink: 0; }
  .score-ring svg { width: 44px; height: 44px; transform: rotate(-90deg); }
  .score-ring circle { fill: none; stroke-width: 3; stroke-linecap: round; }
  .score-ring .track { stroke: var(--border); }
  .score-ring .fill { stroke: var(--color, var(--accent)); transition: stroke-dashoffset 0.6s ease; }
  .score-num {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    font-family: var(--font-display); font-size: 11px;
    font-weight: 800; color: var(--text);
  }
  .barri-needs {
    padding: 0 16px 14px 36px;
    display: flex; flex-wrap: wrap; gap: 6px; display: none;
  }
  .barri-card.expanded .barri-needs { display: flex; }
  .need-tag {
    display: flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 500; border: 1px solid;
  }
  .need-tag.missing {
    background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.3); color: #fca5a5;
  }
  .need-tag.partial {
    background: rgba(245,158,11,0.1); border-color: rgba(245,158,11,0.3); color: #fcd34d;
  }
  .need-tag.ok {
    background: rgba(16,185,129,0.1); border-color: rgba(16,185,129,0.3); color: #6ee7b7;
  }
  .barri-chevron { font-size: 14px; color: var(--text2); transition: transform 0.3s; flex-shrink: 0; }
  .barri-card.expanded .barri-chevron { transform: rotate(180deg); }
  .recursos-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 12px;
  }
  .recurs-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 16px;
    cursor: pointer; transition: all 0.2s; position: relative; overflow: hidden;
  }
  .recurs-card:hover { border-color: var(--accent); transform: translateY(-2px); box-shadow: var(--shadow); }
  .recurs-icon { font-size: 28px; margin-bottom: 10px; display: block; }
  .recurs-name {
    font-family: var(--font-display); font-size: 13px;
    font-weight: 700; color: var(--text); margin-bottom: 4px; line-height: 1.2;
  }
  .recurs-bar {
    flex: 1; height: 4px; background: var(--border);
    border-radius: 2px; overflow: hidden;
  }
  .recurs-bar-fill { height: 100%; border-radius: 2px; transition: width 0.6s ease; }
  .form-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 20px; margin-bottom: 16px;
  }
  .form-card h3 {
    font-family: var(--font-display); font-size: 16px;
    font-weight: 700; margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
  }
  .form-group { margin-bottom: 14px; }
  .form-label {
    display: block; font-size: 12px; font-weight: 500;
    color: var(--text2); text-transform: uppercase;
    letter-spacing: 0.5px; margin-bottom: 6px;
  }
  .form-control {
    width: 100%; background: var(--bg3);
    border: 1px solid var(--border); border-radius: var(--radius-sm);
    color: var(--text); font-family: var(--font-body);
    font-size: 14px; padding: 11px 14px; outline: none;
    transition: border-color 0.2s; appearance: none; -webkit-appearance: none;
  }
  .form-control:focus { border-color: var(--accent); }
  .form-control option { background: var(--bg2); }
  textarea.form-control { resize: vertical; min-height: 80px; }
  .priority-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
  .priority-opt {
    padding: 10px; border-radius: var(--radius-sm);
    border: 1px solid var(--border); background: var(--bg3);
    cursor: pointer; text-align: center;
    font-size: 11px; font-weight: 600; transition: all 0.2s;
  }
  .priority-opt.alta { color: var(--danger); }
  .priority-opt.mitja { color: var(--warn); }
  .priority-opt.baixa { color: var(--success); }
  .priority-opt.selected.alta { background: rgba(239,68,68,0.1); border-color: var(--danger); }
  .priority-opt.selected.mitja { background: rgba(245,158,11,0.1); border-color: var(--warn); }
  .priority-opt.selected.baixa { background: rgba(16,185,129,0.1); border-color: var(--success); }
  .btn-primary {
    width: 100%; height: 48px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff; border: none; border-radius: var(--radius-sm);
    font-family: var(--font-display); font-size: 15px;
    font-weight: 700; cursor: pointer; transition: all 0.2s; letter-spacing: 0.3px;
  }
  .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 4px 20px rgba(59,130,246,0.4); }
  .peticio-item {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 14px 16px;
    margin-bottom: 10px; display: flex; gap: 12px; align-items: flex-start;
  }
  .peticio-prio { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
  .peticio-content { flex: 1; min-width: 0; }
  .peticio-title { font-size: 14px; font-weight: 500; color: var(--text); margin-bottom: 4px; }
  .peticio-tag {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 20px;
    font-size: 10px; font-weight: 600;
    background: var(--bg3); border: 1px solid var(--border); color: var(--text2);
  }
  .peticio-actions { display: flex; gap: 6px; flex-shrink: 0; }
  .btn-sm {
    padding: 5px 10px; border-radius: 6px;
    font-size: 11px; font-weight: 600;
    border: 1px solid var(--border); background: var(--bg3);
    color: var(--text2); cursor: pointer; transition: all 0.2s;
  }
  .btn-sm:hover { border-color: var(--accent); color: var(--accent); }
  .btn-sm.success:hover { border-color: var(--success); color: var(--success); }
  .search-wrap { position: relative; margin-bottom: 16px; }
  .search-icon {
    position: absolute; left: 13px; top: 50%;
    transform: translateY(-50%); color: var(--text2);
    font-size: 16px; pointer-events: none;
  }
  .search-wrap .form-control { padding-left: 40px; }
  .filter-pills {
    display: flex; gap: 8px; overflow-x: auto;
    scrollbar-width: none; padding-bottom: 4px; margin-bottom: 16px;
  }
  .filter-pills::-webkit-scrollbar { display: none; }
  .pill {
    flex: none; padding: 6px 14px; border-radius: 20px;
    border: 1px solid var(--border); background: var(--surface);
    color: var(--text2); font-size: 12px; font-weight: 500;
    cursor: pointer; transition: all 0.2s; white-space: nowrap;
  }
  .pill.active { background: var(--accent); border-color: var(--accent); color: #fff; }
  .toast {
    position: fixed; bottom: 80px; left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: var(--success); color: #fff;
    padding: 12px 24px; border-radius: 50px;
    font-size: 13px; font-weight: 600;
    z-index: 9999; opacity: 0; transition: all 0.3s;
    pointer-events: none; box-shadow: var(--shadow-lg); white-space: nowrap;
  }
  .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
  .bottom-nav {
    position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;
    background: rgba(10,14,26,0.97);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid var(--border);
    display: flex; padding: 8px 0 calc(8px + env(safe-area-inset-bottom));
  }
  .nav-item {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; gap: 3px; padding: 4px 0;
    cursor: pointer; border: none; background: none;
    color: var(--text2); font-family: var(--font-body);
    font-size: 10px; font-weight: 500; transition: all 0.2s;
  }
  .nav-item.active { color: var(--accent); }
  .nav-item .nav-icon { font-size: 22px; line-height: 1; }
  .leaflet-container { background: #0d1b2e; }
  .leaflet-popup-content-wrapper {
    background: var(--bg2); border: 1px solid var(--border);
    border-radius: 12px; box-shadow: var(--shadow-lg);
    color: var(--text); font-family: var(--font-body);
  }
  .leaflet-popup-tip { background: var(--bg2); }
  .leaflet-popup-close-button { color: var(--text2) !important; }
  .popup-name {
    font-family: var(--font-display); font-size: 16px;
    font-weight: 800; margin-bottom: 8px;
    display: flex; align-items: center; gap: 8px;
  }
  .popup-badge {
    display: inline-block; padding: 2px 8px;
    border-radius: 20px; font-size: 10px;
    font-weight: 600; font-family: var(--font-body);
  }
  .popup-row {
    display: flex; align-items: center; gap: 8px;
    font-size: 12px; color: var(--text2); margin-bottom: 5px;
  }
  .popup-row.miss { color: #fca5a5; }
  .progress-bar-wrap {
    background: var(--border); border-radius: 4px;
    height: 6px; overflow: hidden; margin-top: 10px;
  }
  .progress-bar-fill {
    height: 100%; border-radius: 4px;
    background: linear-gradient(90deg, var(--accent), var(--accent2));
    transition: width 1s ease;
  }
  .empty-state { text-align: center; padding: 48px 24px; color: var(--text2); }
  .empty-state .empty-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
  .tab-panel.active.scrollable {
    overflow-y: auto; height: calc(100vh - 104px - 68px);
  }
  @media (min-width: 480px) {
    .stats-grid { grid-template-columns: repeat(4, 1fr); }
    .recursos-grid { grid-template-columns: repeat(3, 1fr); }
  }

  /* MODE CLAR */
  html.light {
    --bg:#f0f4f8; --bg2:#ffffff; --bg3:#e8edf5; --surface:#ffffff;
    --border:#d1dbe8; --text:#1a2235; --text2:#4a6080;
    --shadow:0 4px 24px rgba(0,0,0,.1);
  }
  html.light .header { background:rgba(240,244,248,0.95); }
  html.light .tab-nav { background:#fff; }
  html.light .bottom-nav { background:rgba(240,244,248,0.97); }
  html.light .btn-icon { background:#fff; color:#4a6080; }
  html.light .pill { background:#fff; color:#4a6080; }
  html.light .pill.active { background:var(--accent); color:#fff; }
  html.light .stat-card { background:#fff; }
  html.light .barri-card { background:#fff; }
  html.light .recurs-card { background:#fff; }
  html.light .form-card { background:#fff; }
  html.light .form-control { background:#f8fafc; color:#1a2235; }
  html.light .peticio-item { background:#fff; }
  html.light .modal-overlay { background:rgba(0,0,0,.3); }
  html.light .leaflet-popup-content-wrapper { background:#fff; color:#1a2235; }
  html.light .leaflet-popup-tip { background:#fff; }
  html.light .need-tag.missing { background:rgba(239,68,68,.08); }
  html.light .need-tag.partial { background:rgba(245,158,11,.08); }
  html.light .need-tag.ok { background:rgba(16,185,129,.08); }
  html.light .dist-filter-bar-inner { background:rgba(240,244,248,0.95); }

  /* Splash */
  @keyframes splashPulse {
    0%,100% { transform:scale(1); opacity:1; }
    50%      { transform:scale(1.08); opacity:.85; }
  }
  html.light #splash { background:#f0f4f8; }
</style>
</head>
<body>

<!-- PWA SPLASH SCREEN -->
<div id="splash" style="
  position:fixed;inset:0;z-index:9999;
  background:#0a0e1a;
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0;
  transition:opacity .5s ease;
">
  <!-- Logo AKRA -->
  <a href="https://akratechstudio.es" target="_blank" rel="noopener"
     style="display:block;margin-bottom:32px;">
    <img src="data:image/png;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCAQABAADASIAAhEBAxEB/8QAHAABAAIDAQEBAAAAAAAAAAAAAAUGAQQHAwII/8QAURAAAgECBAQCBQYICggGAwEAAAECAxEEBSExBhJBUWFxEyKBkaEUFjKxwfAHI0JScpLR0hUzNFNigpOy0+EkNUNUY3Oi8SU2g6OzwkTD4uP/xAAaAQEAAwEBAQAAAAAAAAAAAAAABAUGAwIB/8QANxEBAAEDAgMDCwQCAwEBAQAAAAECAwQFERIhMRNBcRQVIjRRUmGBkaGxMsHR8ELhMzVTI0Ni/9oADAMBAAIRAxEAPwD8ZAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAANrLsBicfWVLDwu31d7X7aFkw3B7tP09bmdrxSqKLXnpL6yVYwr9+N6Kd4R72XZsztXVsqIJjOeH8ZltL0sn6SmlrJK3u8Pc99LK5DnG7Zrs1cNcbS6WrtF2nioneAAHN0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAm+Ect+XZgqk+ZU6TvdNp3Wt0/DTru1urnWzaqvVxRT1lzu3abVE11dISuQcM0Z4WOIxvrSnaSST0X1e/tppq5SXDWUu3NTlGy3TS+zUmE42skrJWSWiijwr47CUKipVcRGE2r8rjJ/UvFe9GwowsazREVUx4zsyNWdlXq5miZ8IRS4Uym3NyVWvGbHzVytPWlNrzlp8SSeY4HSUsTH9SX7D6WYYNtJVlfpajJL6h2GH7tP2O2zvbV90dHhbKrfxEe7u56f9Rj5r5W9sPSVu7qfvkpHG4WTVqruuipz9+xiOPwj3m11/ip/uicfDn/ABp+z7F/Pjvq+6Llwvlju1QjFXtb19P+s1anCOCmrQxE4S/owb+tlghjMPLaU5a9KUrfUY+VUHJLnk7L+bn+6eJxMOf8Yeoy8+O+fp/pXHwZh/8AfqvhemjK4Mwz1WNqv+qixwxWHd7OTs9fxM7v4H16eC2VT+xnf+6PIsL3YJzM/wBs/T/StLg7D6P5TU9rS+w+lwbhVo61Vv8A5qX/ANCx+mi9L1H0t6Gpp/0mZVqd3pV6L+Jn+weQ4c/4weXZsd8/RXPmfgrK1Ws9NbV9v/bD4PwS/wBtU1dv4/b/ANssfp6TaVpxS/4U/tRhV6Tktal33py/YfPN2H7sfX/Z5wzfj9Gtk2W0Muwao0eVyesnu39/vpoRuN4pwGGruhCLrJScZNaKPjttr56PQnY8tSnzJpxkmrpaNaprzKPnPD2YTzOdSlT541puTkk2k3q27LRNt2W/1v5m13rFqmMan933Cos5F2qcmef0XOhOlj8FGooN0asVeMtNHqvsaZzriHBfIc0qUk4tNuStbu1skktU2l2aOg5PhJYHA0sKnHmSXM+m1n310v5t2KPxlWpV87nOlJtKNndeLtbzVn7SJq8RONTVX+rl+OaXpM8ORXTRO9P92QoAM00YAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA+6NOdWpGnTjzSk7JHS8iwEMvwVOiopTavLe79nR7trXVsrPA+WupWePqwTpx+hfW7v5d18GnuXbVpXSu9dm2abRsTgo7arrPTwZrWcviq7GnpHV4Y3EQwuFq4io16ivbe9lf3ddOiZzHM8VPG42piJyk+Z6X7e/S+/m2WDjnMlUr/IaTi4Q+k1Zp6/DVeGivtIqxC1jL7W52VPSPz/pO0nE7K32lXWr8AAKZbgAAAAAAAAAAAACwcN8QTwK+T4mTnQ/Jb/Jei89vPa3irRDPsplCMvlkISlFPlb19urSfgc3BaY2rXrFPB1j4q3I0uzfq4+k/Bcc/4npqlUw2AteV4ylvp11Wnuve7260+UnKTlJtybu23qzAImVl3MmrirlKxsW3j08NEAAIyQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAbOW4SpjcXChTT1erS2X+bsvNo1i78F5UqGH+W1qdqzbUVJar7ry1bT2RMwcWcm7FPd3+CLmZMY9qa5693isGBw8cJhKeHg7KKs3dvpbS/RLRX6JGtnmPjl+BnXbtNp8ib18+vglo9WuhvtxjdtxikryfVebOe8X5k8bmMqcOZU6bas7rXs14a9N3LpY0+fkxi2fR69I/vwZjAxpyr+9XTrKHr1Z1qsqtR3lJ3Z8AGMmd2xACz5Lwv8rwccRiqk4ekScFF2svc7+9W+rvYx7mRVw243lxv37dinirnaFYBdvmdhLa1639ov3TPzOwl1+NqtPtWX7hM80ZXs+6HGrYvvKQC7/NDA/ztb+3X+GHwhgtPxtXb+fX+GfPNGV7v3h98643vKQC7LhDCNpKpV/t1/hh8H4Rv1atS3/P/AP8AMeacr3fvD750xfeUkFznwdRduSvJd71L/wD0R8VODotR9HiYp9eao3/9D55qy/d+8fy++c8X3/yp4LWuDK+t8dQul4r7D4+ZmL6Y3Dv2M+ea8v3PvH8vvnLF9/8AKrgtEuDcVGTTxlH3b/Ez8zMTu8XC3hH/ADHmvL9z7x/J5yxffVmjSqVqqp0ouU3skS9DhnNa1JThSjfW8XdNfCz9jZacg4fo5ZL0s2qtf859Ne32a9+yW/iM0wOHrejxOKjCVrvmaV1rrrvqmtOpY4+kUU0cWRO0+xX5GrVzXw48b/Fz3M8nx2Xv8fT0sm2k/qaT9u22pHnWKlOhi8LKnK9SlO6tZqz8u6sc3z/AfwdmU8Ovo2vFXu0v87X8miJqOnRjRFdE70ylafqHlO9FcbVQjwAVK0AAAAAAAAAAAAJLLckzDMKLrYemuRdZO1/L3P3HS3aru1cNEby8XLlNuOKudoRoJz5rZv8AzdJ/1x81s2/m6f6x38gyfclw8tx/fj6oME3LhfNowcnSg0ld2kQ1WEqVWdOatKEnGSvfVHK7j3bO3HTtu6279u7+iqJfIAOLqAAAAAABuZXluJzKc4YVRcoK7TdtD3RRVcq4aY3l5rrpojiqnaGmCcXC2bvalTf9cfNbN/5qn+uSPIMn3JR/Lcf34+qDBMVuG80o03Uq06cYppN8213YhzjdsXLU7Vxs7W7tF2N6J3AAcnQAAAA3MsyzGZjOUMLS5uVatuyPdFFVyrhpjeXmuumiOKqdoaYJv5r5t/NU/wBcyuFs3av6Onb9MkeQZPuSj+W4/vx9UGCclwtm8YOcqVNRSu25q1iIxVB4eq6UpwlJfSUb+q+z8fvvdHO7jXbUb107Otu/bu/oqiXkAbuV5ZisylKOFjGTi0nd23Tf2M50UVXKuGmN5e666aI4qp2hpAnPmtm/81T/AF0PmrnF2nRpq3/ERI8gyfclH8tx/fj6oME4uFs3tf0VNec0Pmtm97eip3/TPvkGT7knluP78fVBgnHwtm/81T/XC4Vzf+ap/rf5HzyDJ9yTy3H9+PqgwT0OFM0luqUdP6T+qLNfFcPZpQtbDzq629WElb9ZI81YWRT1on6PVOXYq6Vx9USDdr5Xj6Ci6mHd5PlUYyUpX8k7mpOMoTlCcXGUXaUWrNPscK6KqJ2qjZ2pqpqjeJ3fIAPL0AAAAAAAAAAADfyvKMbmUZSwsFJRdm2/L9q+9zd+a2ba/i6em/rEmjDv3KeKmmZhwryrNueGqqIlBgnFwtm1voUv1wuFs2bsqdP9Y9eQZPuS8eW4/vx9UGD1xVCphq8qFZWnHdXv4nkRZiYnaUmJiY3gAB8fQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPqEZTnGEIuUpO0YpXbfYCT4Zy6WY5jGOnJTalK6TXhdPdae3bqdGpQhCEYRhaCSUVe782RnC2XwwGXw2lOa5pO7aadtV52XRaJdSSr1Y0qU6s+ZwSvaPV9F9hsdNxfJrO9XWecshqeVORe4aekcoRHF+aRwGBVGHK6tXZK2nZtPdXXws/pHPCQz3MJ5jjp1JSThFtQs3Z+KT8l7EuxHmd1HL8pvTMdI5Q0On4vk1mKZ6z1AAQE4Ol8O5l/CWCVaSgqif4yK/76a30/ac0JjhbMamBzGEeZunUfK4+L9nXbpra+xY6ZlRj3vS6TylX6li+UWeXWOjo0dbK1ur0uauLx+Dwk1HF4iFNy1ipRlZ2tfZGxCUJqM4WlFq8WtmuhDcW5X8vwKrRT9LTWjtvvprsu7vpu9jV5FddFuarcbzDKY1u3Xdim5O0S3o5jgHa2KjyyWlqc7NeGh6Qx+Fdkq9//Rm//qcqBQefbnuQ0E6HZ96ft/DrCr0G7Kc5WW/oZ6/AyqtLm+lLz9DNX+ByYH3z7X7kfV58xW/fn7OtuvSdvWqLon6Kd/qEsRSildtXf0eSVvbockB9jXqvc+/+nzzDR78/R1h4rC/l16duzbV/GzHyzCN2eLwz8HUj9rOTg++fp/8AP7/6fPMNPv8A2dYjicLe3ynD3er5asf2n16eho/lNHX/AIybfx0OVUcRiKLvRr1abX5s2jYWa5olb+EsZ/by/aeo16O+39/9PM6F7K/s6fFqUlKNkuji9zn/ABLluMWaOcKU6qnFW5Vdrlik20unW/XzTtvcLcQKlfDY+q7NuXpZzu2+931+tbar1rcsVh5J3r0nZ3anJJx809V7SXVFnU7MbTtMI1HbaZdn0d4lo8MUMRhcppU8Upc9rWu2/DfsrLyiVz8IFanPHUqUY8s4Xb00atFb9dYyXsLBnOfYXL6DdOUalZ/RXm3qu+z1Wnj0fPcXXqYnETr1Xec3rp9/eRNUv27diMeid55fZL0yxcrv1ZNcbb/u8gAZ1fgAAAAAAAAAA+qVOVWrClBJynJRim7avxZ1DJcLHB5bRowsk1zO8NdVpe3W1r+NykcHYFYzNYylZxp7q+977+Fk15tHQpX5m29U+iuaXRMfaibs9/KGc1u/vNNqPGRO2q2XYWs73Xj3MLV7Jr7TKv0uvG6u34F8oGV3baa0V9TnvGWBjg81coaQqapbbbadrWXmmdAWj0VpeDRC8YYB4rK+eEJOUNVu2300W7vdL9JldqeP22PO3WOay0u/2ORET0nk56ADGtgAAAAABaPweO2YV/0O9u5Vy0fg9/l9f9Bbe0sNL9bo+f4lB1L1WvwXZdlF+Gm9/ifOyunbporWCa3T18L6ftCavvKLS67e82bFtXOFJ5XX5Vdcq2Wm6OWnUs4u8sxHq6cq06W5luctMzrv/JR4NPof/FV4/sAAol2AG1luBrY7EKlRjJq6Umle37X4fYmz7TTNU7R1fJmKY3l6ZLl1XMsZGjCMnBNc7W6X2efQ6Pl+Co4LDqlQUUlu1D4ff26tnjkmW08uwqpwSUmvX7L2/W/LZJI3rS0S5Vrs2bDTsCMajer9U/3ZktSz5yKuGmfRj7sq7aSckuiS0MW7O/e7MdHs9ezsyucW578mpLC4XklUldSlukvt+/iiXkX6LFE119ELHx68iuKKGtxnnUoyWCwlS35U5p6vs19a9/ZlPPqcpTnKc5OUpO8pN3bfc+TGZWVXk18dXy+DZ42NRj24opC1fg+/jsR+lD+5UKqWv8Hv8bib7c0P7lQ66b61R/e5y1H1WvwXJ2tdvS33shp2egVtHZ36JaIbu9+b4eyxtWKGm2tZJvZbh7Wb5dem5hNpapST+IutXe4GfB3j12WvvMPlsn9G/Z2MrR2irdXrf3sNpat3v1bAaNNxdo97aMwrWuuay8fuxur6vyMp+FuifK9AErONpy5o9muZe40sZlOAxdHkrUVBdOVWUb72WyfitTcd1Lq+99Gwk9GuZa77LzPFdFNcbVRvD3RcqtzvTOyi53wziMKpV8NadHV2676JfsfvbdiunXLKUWpespXTu9yl8W5FHDqWNw0WovWUEr+f7fK/Va53UdLi3TNyz074/ho9O1Sbs9nd690quAChXgAAAAAAElw1hJYzNqME7cr5rpu68V5b+SZ7t25uVxRHWXi5XFFM1T0hd+GcGsJlNOm1HnlvJ2ae/Vbq7bXg0Sbv1b19wSStCCjGMElGOisugV+Xd27LY3lq3FuiKI6Qwt65N2ua572dtfV8rDb8pt+ERZq11y6dVZ28A001dWfizo5KXx7gnDEwxcdpKzXRddNO92/0kVY6dn2DWNyurSlq4rmbSdl7t7b27pHM6kJU5yhOLjKLakmrNPsZHV8fs7/HHSr897XaTkdrY4Z608nyACqWgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFg4MytY3Fyr1YKVKlvdJr77Lbq2tiDw1GeIrwow+lJ2vZ6eOh0/KcFDA4Gnh4xs0ryV7tO+119931LXScTtrvHV0p/Kr1TL7C1w09ZbT11eq3d9iq8dZnyU1gaU5Xf0tenXr7Oq1l1RYc0xcMDgqmInOMfzXJrf79OpzHG15YrFVK8tHN313t0u+r8epa6vl9lb7OnrV+FXo+J2lfa1dI6eLxABlWoADchlmPnQ9PHDy9Ha97pezz023enc9U0VV/pjd5qqinrLTAB5el84NzP5Xg/k1SUnVpfHfq929X7+iLC9VJTlzX6NbrscuyjGywGOhiFey0lZK9v+6Xnt1Om4WtDEYenXh9Ga6STSfXVb+exrdKy+2tcFXWn8Mnq2J2Nzjp6T+VE4xy2WDx7rwT9FVe7d7vv97u6be6IE6hnOBhmGBqUZK82tEpWb1vb4LwukczxNGdCvOjPeL3s1ddGr9GtSm1XE7C7xU/pqXOl5fb2uGr9UPMAFWswAAAAABPZVwzi8bQVac/RRdrXj46p9vYn42aN/G8HzUU8JV1UW3GU+Zt9FrGKXxJtOnZNdPFFCHXn49FXDNcbqkbFDG42hBQoYvEUoq9owqNLXfY+cZhquExEqFZWlF79Gu6PEiTFVE7TylK5VR7YAAeXoAAAAAAAAAAAA3MmwksbmNKjGm5q95Kztbs7bJuyv4nqiia6opjrLzVVFMTVPcu3BuDlhMsUpLlnUd2uZ+3To9k1/RJq1lpex80aap0o04ybjGKjqrt/9z706NXb9iN3YtRZtxRHcw2Rem9dqrnvN1rqvK/uMWt9GydtV2XtPivNU6M6iTk4rRX+lJ6Je12RpZFmkM0w86isuSVlG+ttlfTfS/tR6m7TFcUTPOXmmzXNubkRyhIeTvftvYxUpwq05U5r1ZRafgj69bq1233G7emnij25xOzmGeYSeDzKrTlFRUm5RSSS3eyT0V00vCxol049wLnQp4yCty6NfDtq7JeCUX3KWYnPx+wv1Ud3c22Ff7ezTX394ACGlgAAFo/B8v8AS8Q/CKta/wCcVctP4PdMViH+iu/5MyfpfrVHz/EoOpeq1rq+Zq2t/DWxh6O2tm9ULu30rX2V2hZ25VKy2bNoxbUzhf8AhuI31SX/AFL3nLTqecr/AMLrqOi5Vo1f8pHLDM67/wAlHg0+h/8ADV4/sAH3RpVK1WNOlFynLZIo4iZnaF3M7c5emCwtbGYiNChByk/cl3f38DouQZVTyzCRi9a2vNLor9vHQ1+F8o/g7CXq8rrz9Z6fR+6v8e7Jl8vVftNXpmnxYp7Sv9U/ZldT1Cb09nbn0Y+7Er21vfpdmVd6tfUF5q+9l0IfiTNYZdhZcsoutNWXe/b77ad1ezu3abVE11ztEKyzZqvVxRR1l5cT55SwFKWHoyvWelk/t6Lx9i1u40KtVqVqsqtWTlOT1ZirUnVqOpUk5Slu2fJjM3Mqyq956d0Nlh4lGLRwx175AAQ0sLX+Dx2rYi/50f7lQqha/wAHv8dX/Th/cqk7TfWqEPUPVq/Bcnrbmlv4h7pO76b6iOmsXbpcJW1+p2bNqxLzxM3TpSmlt4q9ijPivMelKgvKVX98u+N/kdVptafacoKHWb9y1NHBVMdf2X2jWLd2mvjjfosHzszH+ZofrVP3x87Mx39FQv356v75XwUvl2T78/VdeRWPchPvizNPyFRg7305n9cmb2B4vlzxji6LatrJPeV12Ssuv5TKkD1RqOTRO8VvNen41UbTRDquX47DY2jGrRndcqdm9Vf7/Zue6a1tv06s5tw7mFTAY+DjJKE3aV3ZeGvRd/DxSOj05RnTjUg7wklKLva6equabT82Mq3vPKY6s1qGF5LXy6T0ff5X+Z8VqccTSnRndRktX28V2sz7s07NNO3ZXRhu6e9vEnzG/KVfEzE7w5hnWDlgcwqUXHlV24rotWmlq3ZNNXe9r9TSLb+EHCxjUpYqKppyspWWrbTW/Zcn/UVIw+bY7C/VRHRuMO929mmv2gAIqSAAAXfgTBOlhZ4qUZKU9Nbry38Nb9plNwlF4jE06K5vWerirtLq7dbI6jgaCw2Fp0IxjFRjqo7X628C70XH47s3J7vyp9ZyOC1FuOtX4ey+jfkfm9Byq997b2f7QtPWSlZaXMScYR55u0IpuTbtZLVvxNOyzNurfsvuZs3ok776akZlGaxxuIxFFQUJUpOLUtXdb6Lzsv0WyTW1re1rQ8W7lNynipneHu7artVcNXU1td7La5zvi7ArB5rLkXqT20S1Xh5NXfV3Ohpq6aXm2QXGuDWJyz0qVpUtd/N66fpLzkiDqmP22PO3WOafpV/sr8RPSeSgAAxzXgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAG5lGBnmGPp4aCvd62dna/T3/b0PVFE11RTT1l5qqimJqnpCycDZY43x9eEop25Lpq/Vea2e3SPctul7cvv0X+Z54WhToYeFCCTUY9I8q8XZbK/Q0OIsxjl+WSkuVVJxaivh3Xw13fQ2li1RhY+093OWNv3K83I5d/KPBW+N80lXxXyOm/xcEuZq6b6+1PR9fyfErJ9VJyqVJVJu8pNtu3VnyZDJv1X7k3Ku9rsezTYtxbp7gA+qUJVKkacLc0moq7S1fizg7JThjLnmGYJNL0dP1pXSa9q7fDZdTosIQp0o0Ul6NR5eVp2tsR/D2Wwy7ARhd880m24pO2+tuvvey6EmvW19VabXNlpuJ5Pa59Z6sdqeX5Rd5fpjo5/xjlrwmYSxEE/Q1XdNtXu7/s1erurvdECdQzjAxzDAVKMk5S5fVSdm/D76XSOZ4mjKhXnRnvF2vZpNdGr9HuUOq4nYXeKn9NX5X2l5fb2uGr9UPMt3A2aPXAV53u1yXfsXT2avrG2iKiemHqyoV4Vobxd7Xav4O2tmRMTInHuxXHz8EvKx6ci1NEus6LXS/Tf6iEzbhzCZji/lDlKnJrVRko/Y/P2+VpDJsbDHYCGIjJNtLmureTt91e/Y2+27l0Wps66LWTRHFG8TzY2mu7i3J4Z2mOSrrg/CX/jqrXS1X/8AzMrg/CcqfPW8X6fb/wBsnqmNwlKt6GVVRmldq0nZexfewWOwkpWVVy7L0c39hDnBwt9piPqmxmZ0xvG/0QUuD8In9KtZ6L8ev8MR4QwT/wBpW/t/j/FlgWIw7dk6vspT/dPr09O3rOp5einb6hGBhT/jH1/2TnZse36K780MFe3PX/t1/hntguFsDhsXCs3KpGL2nPmV+v5K9/T3NTnp6enM53fenL9hmnWpVdITjKXXRp/Hc6U6fixMTTS51ahl8MxMy88bisNgMH6WtaNOC0ilZJGpleeYLH4mVHDTs0rRjd3l46pfD22NHjbDVsTlsXRtLkfM9baK91bu7r9XTWycZwRl+JpY6eJq05UoqPL68N9bvyd0tOzft5Xcq/GXTapp9F1tYlicSq7VPpJLjfAKvl6xMI3nC79XwV29fBO/e0exQzpHFdanRyOrGXO4zi0uVbaWT8ruK9pzcqNaoppyImOsxzW2jV1VY+1XSJ5AAKdbAAAAAAAAAAAFv4CwP8ZjpxTt9C681o/1rrwRUqUJVasKcLOU5KKu7avxOoZRhaeDy6lQjFX5eZpx120vbS9rX8S40bH7S9xz0p/Kp1jI7OzwR1q/Da85LyM3d9tXr5COr0du3cylpo2vG6NWyav8b4xYfLHh4O0qmnR73Wz6W59e6RA8E46WHzD5PKT9HV6avzstl0bf9E8OLscsZmbjBr0dPa1nq7dV4KN10dyJo1HSrQqqMZOElK0tnbozJZebPlnaR0p5fy1+LhRGJ2VXWqObrOjd5Si3ttqF5PbzNfKcSsVl1Go03eKu5aN6KzaXdNP2mxpfWWvRJae81dNUVRvDJV0zRVNM9zyx+GjisJVw7UXzRel2r+Demj28mctxVF0MROi7+q9G42uujt4qzOsJ9k3r238ij8d4FUMdHE00uWpu7Jau783rzeS5Sm1rH47cXY7vwutEyOGubU96tAAy7TAAAFr/AAe39NiWu8P7tQqhavwffxuJdk/Whv8AoVCfpnrVH97kHUvVa/Bc739ul4r7Rru3fu7LQdNW34LRGHZu6fK2rX+BtGLauct/wZiGk78q0T8VY5adRztWyzEr6N4rZa/SRy4zOu/8lHg0+h/8NXj+zMU5SUYptt2SXUvvCmTQwWGjiK8F6eok9Xr3Xs8Pb2trcKZDClShjsVBTm9Unsvv367bb2i7S1Tfd9CRpencEReuRz7o9iPqmo8W9m3PLvn9mLq9mo97XvYzrsuvZ6sa2taXi+hpZ1mWHyzDyq1Wud/Rju2/v/3ReV1RRTNVU8oUdFFVyqKaY5y88/zOlluCnJJelt6kWuvj8bfsTOdY3FV8ZiHXxE3KT0XZLsj1zXMK+Y4l1qzsvyY9v2vx8tkklpmP1DOqyq9o/THT+WwwMGnFo/8A6nqAArk8AAAtX4Pv47Ea29eH9yoVUtP4P3/pVZf0o6f1KhN071qjxQ9Q9Wr8FzVtHdX6W+wPZXel7tr9pne17v26swr/ANJNb3+w2zEvPG3eDqtPTlvZaJHJzq+Ov8ir2b/i5aew5QZzXutv5/s0ehfpr+QADPr8AAA6fkU6lTKKEqs+aq4KTdrfSSlf/qOYHT8hjOOUYeNRclRRUZRe6cUoWf6peaFv2tXgpdc/4afFuppXW/kxe9kmr9kzNpL1ba727C/RvTtc07Lq7x5RjUyyFRv1qV5LpvKC+1lEL3x3X9FlkaLV/S3gmne3rQl/9WUQyOs7eUz4Q1+kb+Sx8/yAAqlmAACycCYJVsc8TOHNGm9Nnrv5p35deq5i8y1vza6a2SuRnDWDWDyqlTcrykr3umnvs+122vMknyt2aSS7m10/H7CxFM9Z5yxmo3+2yJmOkcj3LUjOJ8csJlU5ppzkrJWT28H0vyprs2Sfq3Vr36p3aKRx3jlWxkcLCacae9rPVXXmndy9lhqGR2FiqqOs8oNNsdtkRE9I5o/hfGywea02m+WbStd6vpot29YrtzHRotSSfNo9VbsclTaaabTWzR0nhzGRxmVUp2XNFaq2iXgvNNLwRWaHkcqrM+Mfus9bx94i7HhKTs+i1S2T2PirTVSlOM3ycy36rxXkZ0/KsreOxmyvaz+tpeJoOrPRO07uXZvhZYPMKtFw5EneKs7W7K+9ndX8DULfx7gU1DHU1+m+W19lu/ZZeLZUDD5uP2F6qju7vBuMO/F+zTX/AHcABFSQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAvvBuWfI8E8TUUlWqbprbdddmtV+t0sVjhfLnj8xjzQ5qUNXdXT8/u1eye50anGMIxhBKEIKyV7pJF/ouJvPb1fJRazl8NPY09/UnOEYycpuMYpuV1pZd2c54ozH+EMxk4P8AFQ0ik7r7+9Xu1uWfjLNVhMIsLR5lVn+VqrPfdbNXT/V3VyhDWsveYs0/N80bE4Y7arv6AAKBfBaeB8q9NVeNrxXo1pHZ33028O+yataRAZXhJ47G08PCLld3aTs2vDfX77HTMDho4TDU6EGuWCtd31draffayLnR8Ptbna1dI/Ko1bL7K32dM85/DYbTbbstL+LMWTd20/JJfAzdu6vy979EYe+2nTsaplDXtZdtin8b5Z6yx1Cm7O/Mkr+L6+3bq+xcLtrTps1okeeJo08Rh50a8bxkrWsnbtoRsvGjJtTRPy8UrDyZxrsV/VyYG5nGCnl+Onh5dNVdpu338rqz6mmYeuiaKppq6w21NUVRFUdJT/B2ZxwWNdCq7Uqu70+72VvKy3L/AMz11a6brU5FCUoTU4ScZRd007NM6JwpmPy7L4xfKqlNWaXbtt008k0r7mh0XL3jsKvkoNaxP/3p+bS41yr0+G+XUoqVSNlJrd/Ht56pW3ZRzrc6cJwlConyyVpLms/Z4nOOJMull+YzjZck23G1l8Fstfs6M5azicNXbU9J6uujZfHT2NXWOngiwAUK8DeyXMKmXYxVYuXI/pJP422b1fsb2vc0QeqK6rdUVU9Yea6YrpmmrpLp+CzXB4qj6WFeKi3aXM7RX9b7Hr4HricwwOHpNzr03BWTUGrX9mi9rRyyLcWmm01qmuh9Vq1WvPnrVZ1Z2tzTk2/iXca7Xw7TRzUs6HbmreKp2SnEmczzSvyxSjQg/UX38/29EogApr12q9XNdfWVxatU2qIoojlAADm6AAAAAAAAAAAneDMF8pzRVZX5Kad7dVbXpqraf1kdAkr7yV3q7LdkJwjgfkmWRlKPLOf0k7rXrdPZ7J/ok1Z9rLu9jZ6Zj9jjxv1nmx+qX+2yJ26RyZs7apy89jRz3FwweWVaj5dU4pc3LfTVJ9Ha6Xi0bu+7v01VtCo8fY27p4OE9F9JKVuz1XVfR9sWdc2/2Fiqvv8A3ccCx29+mmenVU6tSdWrOrUd5zk5Sfds+QDDtsuPAWNTozwUpK6fqp2V92vF/lX8olrT3Ukr37aHMshxUsJmlGpGahzNRbckktdG30Sdm/BHS6VSNSEatOWkoqSfn9prNHyO0scE9afx3MrrGP2d7jjpV+X2m0tHa/RKxHcSYL5dldWnfklHVN6aab6PRNJvwRI6vvzddL2DS1uotPRqX5S7e0s7luLlE0VdJVdq5NquK46w5G002mmmt0zBLcU4J4PNamkuSo7qTT1fXV7t3Tf6REmEu25tVzRV1hu7dyLlEVx0kABzewtX4Pv43ELvOH9yoVUtX4P/AOMxCTafPD+5VJ2m+tUIeoerV+C569tX0Gzdmr9bajZefRyMX1tp7TasS1c5fLlde7slFXdv6SKvwhkca8VjsVCTgtYq2i7Pz+rpq9LrTnGFanP0NCrySU1TrUo1IO2vrRmnGS8Gmn1R5UacaVOEIpcsVpfo+vt318SHexKb16m5X0p/KdZzarNiq3T1mX0rRSSgopJWV9jOu1rh2bSuvJi7vbXx6XJiCzZ9Nb7aFK48w2JWJjiOVvD2vdapa/5pW6f1i5r6Nrb9Gre9nhmOEpYzDSw1aEWpa6rr9fuImbjeU2Zo35pmDk+TXorno5UDczfA1cvxk6FSLirvl1vp4+P/AH2aNMxNdM0VTTV1htKaoqiKo6SAA8vQAABaOAJcuKrbayjv+hUKuWbgBv8AhCa0s97/AKEybp3rNHiiZ/q1fgu7vzJWu32MOVo2v6ttnr/2M3Tjq2o7u/UK71132ibZiHjmHN8hrvb8XJ267HKDquP9bA4jeT9FLZeDOXuhXW9Gov6rM9rtM1TRtHt/Zo9DmIpr3+DyB6OhWW9Gp+qx6Gt/NVP1WUHZV+yV9xR7XmDZw2BxWIly06Lv/Saive7Enl/DOY4mcXUp+ihzWlftbdPa3TdvwOlvFvXJ2pplzuX7duN6qoho5HgZ47H06apucFJc2mj7J6rfw1Su+h02jT9FShCLk4xSim3q7dTSyfLMLl1JQpU7zSs5rr9+v2KyN5300172vbzNVpuF5Lbni/VPVlNSzYya4in9MM9Hdt3etjLur88Xbe7bMX31XjZ/WeeJrU8Nh5V6klaKurysm+l2/HqWMzERvKuiJmdoU/j/ABTni6eFjVuoJc8bdUtHf+tJewqxtZpi543HVMRKTkm3y3008ru19W0tLtmqYbMvdveqr9rc4tnsbNNHsAARkgJThjBvF5tSWqjB3ctVZ9Gmuq1f9VkWXjgbAeiwksTUhadS1rx6PX6rNP8ApMnadj9vkUx3RzlDz7/YWKqu/uWRJJJRiopaK3RfYY1T2Tvtrt4mXZ9LryWplLvu+ltjasTu8sZXVDCzrNpcq+lJpRvsrvtfqcux9f5TjKtb1rSfq81rqK0V7dbWLnxzjnQwawtOSUqi1SlZ2d17VbmT/SRRTL63f4rkWo7vzLUaLj8Fqbk9/wCAsvAmN9DjJYWUko1Ntlrt5t35dPMrR7YKvLDYqnXjd8r1Sdm09Gr9NL6lZi35sXaa/Ys8mzF61VRPe6tqtLu4s7tOPgzywVf5ThoVlOM3JayjLRtb28Lnq1bfTTZs3UTExvDDVUzTMxPVq5vhoYzAVqDg3dXSVua/h0Taur9LnMK1N0q06Tak4ScbrZ27HWo3Wic11KFxtgZYfMvTxi/R1F9LV+XguqS/oFHrePxURdju6+C80TI2qm1PfzhXwAZlpAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPqlCVWrGnBXlOSile2rPks3BOVutXeNqxahC/Luk+ntT1XXaXgd8axVfuRbp73HIvU2Lc11dyycO5esvy6EElzzV5N6N/D299l0N7E16eGws605RtFXtJpK+yV39Z63u780vZcqPHeZvmjgaU+/O116P9mj6S7mvv3aMLH3ju6MjYt15uR6XfzlW81xk8fjqmInJyu2otqztfqagBi665rqmqrrLZU0xTEUx0gAJrhPLZY7MIzkmqVN35td11vptp7XHSzPVm1VeriinrLzduU2qJrq6QsXBuV/JcI8RVSVSr0d1b2PqtVt1lurFh0Vk9W/HVmIKMYKMIcsUrJbIza+i19u5ucezTYtxRT3MRk36r9ya6ha7Wa7W3Hth382YfK1rt4GY8uiW+7u9GdnAs29OZrovEzqpJtK61V+nsF07aN9PAxa1rd9W0BBcZZZLG4JVaUeatT7fX9nuu7I5+ddmoyjKE0pRaaknpdHPOLMsngMwnNJulUd07Pd933dm+mqdlZGc1rE2nt6Y8Wj0bL3jsavkhSR4ex8svzKFXmtBu0r7e3X2X1sm+pHAordyq3VFdPWF5XRFymaaukutxmqsIzjdxe11rfs/Ei+KsuWYZfJK0J0/Wu7+997Xfsb0I3gjNPSUfkNVrmh9DZdNNPJdt0usi0LmattffTc2tuu3m2N56SxtyivByOXd08HI5xlCbhOLjKLs01ZpmCxcaZWsLi/lOHptUZpuTS0WvXXxt7urK6Y3IsVWLk26u5r7F6m9biunvAAcXYAAAAAAAAAAAAAAAAN3JcJPGZjSpRhzJNNpq6euifg20vaaRcOAsA+SeOnDTaDsm+q0e6/Kuv0SXg4/b36aO7v8ABFzL8WLNVf8Ad1qowhThCnTelOKim3rofWsl31MvWys732tqvIavR7/mpO5t2Inm+a1SNOjOtKPOoJu3V2/bscvzfFSxmYVaznzK9ou7t5q+ybu7eJ07F0ViMPOhKTUZaXTa922xEfNfK/5im9O1T/EKrU8W9kxTTb6QttLyrGNxVXJ5y56DoPzZyvph6S7t+ksv/cMvhjK7fxNFW6/jP8QqPM2T8Pqt/PGN7Z+jnp0Pg/GvF5XGM5Nzp3Um29X1u3u+v9ZGPmxld/5PR/8Ac/xDcy7KcNl0m8PGMedaqLlb4yfh7kTtPwMjGu8VW209UHUM/GybPDE8+7k3umvusfSjfWzavfT7bnzrs3d72YV9e73fVF8z6vccYL5RgI4mCi5U1urbLxfS3Ntu0iiHW61JVaM6bajdaPs+jt4OxyzMcO8LjatCzSjLRN3aXZuyu1s/FMzOt4/DXF2O/r4tPouRx25tz3fhrgAo12Fp/B+7V661+lH+5UKsWjgB2xFfX8qOn9WoTdO9ao8UTP8AVq/BdfFOWui7hJ9W9HZJ7Bxez/6v2hLWyevZI2zEGvRX138Qr9fLUx1tzKL2vfcNxScnJLTW/RdwPr1mretbcx8ddSncU8Q8zlhMDNpLSU199/q8/oy3CucQzDD+gnFxrQsteum1/Y7X1sutruFRn2a702Ynn/eSdXp163Y7aY+X7ptq65rJ362+As27Jeqnq2hpe915JWGmmq06y1sTUFDcUZVHMcG504wVeC0lK7ain4e39jaRzycXCbhJWlF2aOuXfVtrpdXv7Nin8bZQof6fh0lGyUoxX36a+V9rJFFq+Dxx21HWOq/0jO4Z7Cv5fwqQAMy0YAABYuA3bNrP6Nm2v6siulg4ElbOku8X1t+SyZp/rNHii53q1fhK+u+krq/jqFZ9dWYW99WZbe13pstrG3YcvLxa30Q5m/ypLydmzLevrOOmqTexjpaTW2iAJ3tyyaXn8TP9Z6dbmPGysY0TS0v0utEB9OWt+drxd/rMOXM7yvJb6mFbunvrYy3fs7eAGG30kr+WrDWltPJ6jVd772I/NM5weWpqrLnqWbUVpff7VbS5zruU244q52h0t2q7k8NEbykJ1FCEpzk4QitW0UPifPpY6csLhrLDrS/WX37/AGXv4Z3n+KzGbjFulRs48q6r7NPF7vWzsQxm9R1Tto7O10759rS6dpfYz2lz9X4AAUi5AAB7YKhLE4qnQjf1nra17bu199Oh1LCUlhsLChFRTirNRVlffTstfdYpvAeCVbFzxUkmqezbVlaz23vfls/Bl3313u92anRcfgtTcnv/AAzOtX+K5FqOkMW6OTXsCUd27Jdf+4vbRuTd7b28zFSKqU5QaunFp69GXKkc64qxnyvNZuLfJDRK+l/Lo7WT/RIk6JU4ay2pOVSUITnJuUpS9I2231fpNT5XDOVtK2HpPx/GW/8AkMvd0rKu1zXO3P4tTb1XEt0RREzy+DnoOh/NjKr/AMnp+6p/iGHwzlX8zS8mqn+Ic/MuT8Pq9+eMX2z9GrwHjZVsLPCSk3KG17vT26baJdoFkV0mrW83cjsuyXBYHEqvhoKE7Wsub7ZPx9jZIu19Vd/UaLCt3LdmKLnWGezrlq7emu30llLqm/q+JEcV4H5dlclGMfS0/ou13bsrvTW2vRNktza9NPvoJRjNOlUhGamrSTXR6M7XbUXaJonpLhZuzZuRXHc5GCR4iwjwea1aTavJ83Trvotr7pdmiOMJcom3XNFXWG7oriumKo6SAA8PQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPbBYeWKxdPDx0c5W0te3W3d+HU6dleGhgsDToUqaio6truV/gXLOWi8dVhrJ+pfTy/b2d49UWlP1d0r9bWNVo+J2VvtKutX4ZfWMvtK+yp6R+Wpm+Mp4LA1MRJuNlZdWvHx/bZdTmOIqyr151Z7yd7Xbt4K+tkTfGWaLG4z5PSadKm91bV/du/sTWhAFVq2X213gp6U/la6Xidha4qusgAKpZvujTnWqKnTi5SeyOlZDgI5dl8KShyykvWk0lf79dXv2sVzgjKlUl/CFVXjF2imk1v9d14ad1Iuidv22NPo+JwUdtV1np4M1rOXxVdjT0jqxZb3T7JoWVknBNLo9PaZV3q01pqjHXSMVf+jqXiiE9U9NNkNXfVvzexlJX1d3tv+w+bNxStp0X+QH077bX6XszD0dm5aLvr8DGi0d3ffTYzeyX5K7L7QGl21ZEfxBl8cxwM6TSc4Xkna7Xl4+HXbqb+q2Xx6mddOVOy0Wmh4uUU3KZpq6S927lVquK6esOSVYSpVJU5pKUXZ2d/ifJaON8s9FWWNpRXJJetbpr5dG++zSWzKuYbJsVY92bc9zcY9+m/bi5T3vfA4mphMVTr03JOLu7OzaOn5fio4zBwrqUJOS1aTtfyevZ+TOUln4HzP0Nd4KrO0Jax8Pj0326tvYsdIy+yudnV0q/KBq2J21rjpjnT+FszTCQx2CnhalryXqpW0dvvrbTR9DmWMoSw2KqUJ7wdvZ08vLodW0StfTt0KrxzlvPTWOpx9aOkkrvu3pb29tJX3LHV8Ttbfa09Y/Ct0fL7Ovsquk/lTQAZZqAAAAAAAAAAAAAAAAH3RpurWhSUoxc5KKcnorvqdQynDfI8BSoKMopRV+ZJvbRPTdKy9hS+CsE8RmfppRvTpp3d2vPwenqtf0kX6TV97Pa/Q02iY/DRN2e/kzet396qbUd3OR8zve/m0Y9W9rrsklewvZ25Yuyt5GG4xTk2lyrWTekUXqifS1W716JfaY+krJXb00f2I8flmCevyzDLzrRDxuDbt8swy8fTRVvifN4euCv2Pd+D0T3tZBX19Vt9zweNwif8swzS2/Gx0+I+W4G38sw0vD00b/WN4Ozq9j2/S1beqQ1tdQVr6N6L2Hj8swd3/peGX/rR/aHjcHe/wAswyb/AOLG31jeDgq9j316N2XZ6C7d1Zvq7HlTxGGrtxo16VVxV7U6idl3smeng1o111DzMTHKWbNNcy17dSn8f4JKpDGwUm3pLRvTr4JJ283MuH0bLlaXa+rNLOsJTxeXVKTgnZOStFN7a+2zdvGxFzsft7NVHf3JeBkdhfpq7nLwfVWEqVWVOaSlCTi7O+q8j5MO2wWbgB2xtWOmrW/6Myslj4Cds1a5rKzv+rImaf6zR4omd6tX4Svel2ktPPoY73lf2b+At527JbjW9uXl79X7zbsQavfVe4qvGOdU40XgsJUTnL6UodF5/V332teyYx2wlbW75dn1OUyk5ScpNuTd229WU2sZddmiKKP8u9daPi0Xa5uVf4sHvgcVVweJjXozlFpq/K7XV72+B4Ay1NU0zvDTzETG0uoZJmNHMsL6alNNx0krcvwvp999zd/Zt9Zzbh7NauXYyH41qg5eumrr/tte3bZtI6JhcRSxNCFei1KEtnf7TZafmxlW+f6o6/yx+o4U41zeP0z0/h63etpS8Xc+atONSnKnN6Pd82qfdeP1H0m7aWbv1Wwvo3zNW0W+hP6q6JmJ3hzbiHK55bjJRjCfoXa0ntrfr7H7mtbXIs6hnGX08fgXRnFt2fL61m+tr+dn5pX7HNcZhq2FrujWjyyXxMhqeF5Pc4qf0z/dmx07NjJt7T+qOv8ALxABWLEJ7gW38PQur+pLXt6rIEnOB3biCnrb1J/UTMD1mjxRc31evwl0G13bVJb6ahb3Tko+BhNXs5J93e5l91e9uvibZh3xXk6eHq1Yx5vRwlL3K5TXxli7/wAkoryky347+R4i3K2qM/L6LOUy+k/MptXyrtiaOznbff8AZeaRi2r9NU3Kd9tlmfGWMe+Eoexs9IcaYn0i5sJSUG/Wabbt8CqApvOmV7/2j+Fx5sxfcdXwOJp4zCwxEG7SV7Po7XX2PyPZ62uo29pRuC80WGxHyStK1Oezd3b7FbV9N5b6F5Tk1ZX7WRp8LKjJtRX39/iy+dizjXZp7u42avdvzKfxrlKV8woQik23U5U7t/e79+uiRb02rO61116HzVpxqU3TnzJNbp2a8U+99fM95WPTkWpoqecTJqxrsVx83JQSXEGXSy7HSp8tqcm+TW+mnw1+y90yNMRct1W65oq6w2tuum5TFVPSQAHh7DMU5SUYptt2SXUwSvC+CeNzSmrNRg781nZPpqtmkm1+idLVubtcUR3vFy5Fuia56Qu/D2DWEyqjT3m1o7tq291e2jbbXmSHgk2m9dNxFaJJcqtordDPL2Vr9jeW7cW6Ypp6Qwl25Nyua6u8u3tp030RjfVrXzErJXlL2t2SR4fLMFt8sw67XrR/aet9nmKZnpD3Su9rLsZdvz1f3WR4LHYN74vDf20QsbhL/wAswz8PTR/aN4feCv2Pdta2fIutt7GNlbRXetmeTxmDbv8ALMOl4Vo3fxMLG4NbY3DN+NeP7RvB2dfse6VlZ/R+NjD0etrvXyPH5bg7v/TcLq9X6aOvxMwxWFnNQjisPOb2iqqbfxG8HBV7Hsm76Pru2/eYSa2ur93YPVWu38PYG3fpr7D68Kzx1gnWwlPFQjJuG61fnZeWrfaJSTq2NoxxGEq0fUk5R0Ul6qe6v4f5nL8bQlhsVUoSu+V6N6NrdPd206GX1rH4LkXY6T+Wp0bI47U256x+HiACkXIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAb+RYCpmGYU6MIKUeZc172fg7d7Pxsm+hoHQeEMr+Q4L0tVRVWpu7rRddVutu/Vrcnafi+U3oiekdULPyvJrM1R1nomaNNUaSpRasla71fi34vcjOK8wjgcukt51FypNW36ad7PtopdSUqVIQg6lR8sYrb76tnN+I8xnmOYSm2nCLajb469V0Xgk92zR6lleTWdqes8oZ7TMWci9xVdI6+KOnOVScpzlKU5O8pN3bfdnyAY5rg28pwU8fjqeHgrpu8rOzt4fdmrFOUlGKbbdkluzoHCOVrBYH01RL0tTW91p7V7lq+rX0idgYk5N3bujqh52VGNamrv7kvgsPHC4anRjoorXlVrvv99lZHq3b1bWfbqFfZaPxPmc4U4c85Wj3bsbSIiI2hi5ma6t55zLL79L9tDPMukl7diDzHiXAYSbhCUqs09bK9ld3076bNpkFiOL8wnKLpU6dOKVnG+j8U42fxZAvanj2uU1bz8E+zpeRdjfbaPivXrWVm1f4eQ5JJfRaT623OZvPM1bbljJTv0nFT+tMw87zR7Ypw/QhGP1IiefLHuz9v5SvMV33o+7pm3Xzt1fiY21+i37fgUPDcWZhTlH0iU4KNrczbk+7cub4EzgOLcLW5aeIpunJtJvZXb8dLeLaJNrVca5y328Ue7pOTb5xG/gsjbW+ng2NL62b7M8sPWoYiCnh6ilFpPxs9nbt9Z6Jpp3b87XLGJiY3hWzTNM7S8cfhoYvC1KMkm5RsrtpX26dLXT8GzmOPwtTB4qdCopJp6Nq1199PNM6qnZ7We+xWON8tjWw7x1PWcPpOyWy+qy960WpUavidrb7SnrT+FxpGX2Vzsquk/lST6pTlTqRqQspQakrq+q8GfIMo1LpnD2YU8fl0Jxd6kFyyTfM12v4/9+qN+tShWpTpSfqyXhfvf2aHPuE8yeBzCFOfNKlUduVN7vt56e1LodCjJNKSkpJ6xa2t3Nnp2VGTZ59Y5Sx2o4s417enpPOHMs8wFTL8fOlKHLBtuNr2XgvK/XW1n1NA6Dxflfy3Bemgl6WmvD2avz11W6b2OfGa1DF8mvTEdJ5w0mBlRk2Yq746gAIKaAAAAAAAAAAAAb2RYSeMzOlShGMrSUmpJNPVWTT3V2r+F2e6KJrqimnrLzXVFFM1T0hduEcA8FlsZOElVqayvo/HR7Wd148qJlX/ACb3b0vZu5ilT9FRhGkpKMYqMW/cZbbe/Ml8Td2bUWrcUR3MJfuzeuTXPex7n4EFxpjXhsrdGKs6uj0TWt0r9dlJ37pE6t9OXXZq7Rzzi7GrF5q1Bp06atG1mvY99ktOjuQtUv8AY487dZ5J2lY/a34mekc0MADHNeAAAAAJHh3GTwWaUqkXbmfLv3230S6N9mzpcJxqU41IXcZrmjrumcjOi8J4547LU6kk5wfrK/v2SW+tuiaRoNDyOdVmfGP3UOt4+9MXo7uUpi+j09v33CdpK9m73v4mGl+V17mfpO9r+SNGzbn/ABngPkeZ88foVFp7Fp02tZeaZBHQ+L8CsXlblCM3Onqkk3d9NFu76f1mc8MbqmP2N+duk82y03I7bHiZ6xyCw8CNfwu1dJuL/usrxO8Du2f097ck9E/BnLT52yaPF2zvV6/CXQH62l/Pu39gldRf5EUurVjN3tdPwRjR3Sj+3Q27DvLGO2GqXavy728UcnOsYyyw01G6Vt35o5OZzXutHz/Zo9C/TX8gAGfX4WThHOZYerHBV3H0Mtu/l4v49NdEq2Dtj36rFyK6XK/ZpvUTRV0l1yDi0pR5ZcyurdvqCd9m7orfB+cvE0VgK0VzU0rScr36ef8Am/FJWW7vpFLt90bbHv0X7cV0MVk49WPcmio1vZO/tK7xdk6xdH5VQhBV07zb6q3f3b/BXZYnZ33S8VoYkovm0TT01juv2H3IsU37c0VdJfMa/VYuRXS5GCxcY5T8lr/K6K/FTeqS28W/g791u27V0xGRYqsXJoq7m2sXqb1EV09JCb4JduIKX6EvqIQnOCGln8G3b8XL6jrges0eLlm+r1+Eugp2e+vZq5htPpLXZdDOu3MntdPcy+urdt3srm4Yd44rm+SYhLVeiltr+SzlVZWqzWitJ7HVcZb5HiNVpRn0a/JZyvEq2IqLtN/WZ3Xv/wA/n+zR6F+mv5fu8wAZ5fswlKE4zhJxlF3TTs0+50ThfM45hgIwkoqrTVuWLeiXn2076Na3uc6JDIswnl+PhUUrQbSlduy8Xb29Nmyw07L8mu8/0z1/lB1DEjJtbR1jo6Yk9LW809BpsuVve/Y+KFaGJoQrQl6s1tfbw9h6dvHq9NPsNlHNjJiYnaUZxDl1PMMunH/aRV01Zy8vv4rS5zitTnRqyp1I8sovVffc611ut+ltyn8aZS0/l2Hho786jHXu/OyV9tr9Eij1jD46e2p6x18F7o+Zwz2NXSeipAAzLSBeuCcCsPl7xVRevV2sktGk9+qtZ+F2U3LsO8VjaVCzanLVKSTa3dm+tjqOFpRw+HhQjytRjZ8iSTfWy2Wty80TH4rk3Z7lLrWRwW4tx3/h9q2q67W/a+gu9W7R7mbzekr2Wur0MKzd/ct/eadl0RxXjlg8qfLJqrP6PrWlddV4p2duyZzosXHGO9Pj1h4TThT3UZaXV1qu9+Z37SRXTH6rf7XImI6U8v5bHS7HY48b9Z5gAKxYgAAHvgMQ8LjaVdOS5JauKTdutr9bHgD7TVNM7w+TETG0usYWusThaVaMotSV3yu6v1s+ut9T0abu+27tf4lc4Gx6r4OWEnL16drXvqtu1tklb+i2WPT8rXwZu8a9F+1TcjvYfKsTYu1Uewu9PWbfcpfH2DjSxcMXFxtU0su7u3bTXW7b/pIuiV1zK1vE0OIcIsbldWEm7pNq7a06t27aO39E459jt7FVPf1h10+/2F+me6eUuZA+pxlCcoTi4yi7NNWafY+TEtoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB90qc6tSNOmrylt0ERuJjhHLJ47MI1GpKnSd7q61XVPw09rjurnQ0oxSjGMIxS0UdopdNdSPyLBU8uy+FFRlGUleT2ftWuvW19Ls28TVpYejOrUfLGPVyt7G2bPT8WMazz6zzljtQyZyr21PSOUPnG0IYrDVKFTVVI2d76+f39xRM74dxOBlz0VKtRdkna7b207+W/mlcuWVZrgsxgvRVUpu3quWvX9j0307am84qUHFqLg1aSa0d+mu4yMSznURVv4TBj5d7BrmmY8YlyMF9zrhjDYpurhn6KpfXrfW7eu733fbWysVDH5VjsFJqtRlZK7lFNpLS77pa2vt2MzlYF7Hn0o3j2w0uNnWciPRnn7Gvg6yw+JhWdNVFF6xfVfY/HoWylxlRVKHpcHOU0vWUXZLXpv9SKaDxj5t7H3i3PV7yMS1kbdpG+y2Y3jCU4r5PQcW073SXK+mut17EQOYZtjsbNyq1pJNW5YtpWaSa720227GibeCy7GYuUVRoytJXUmrJq9rrvr2uermXk5PozMz8I/0828XHx43piI+LUBasBwhXlyyxlTkTT9XbW+ni1bvyvUnsDkOW4VqUaPM+bmWtnF+DXrLTxJNnR8i5zq9HxR72rY9vlE7+DnMITnLlhGUn2SuzNSlVppOpTnC+3NFo6osHhHJTeGpynbWU4Jv3vVmXhMJK18Hh/D8SvrsS/MM/8Ap9v9ofn2nf8AR93JwdOxuUZfibqrRV+XkUr35U+yd7ewgcy4Ri4ueBm72uo+S218d3f2Ea9o1+3G9PpflKs6xYuTtVy8Vay/MMVgainh6rVm2ot6XdtfPRfVsXjIeIMPmKdOd6VSNr3sr38vHS/ltdIoeMwlfCVXTr05Rs2k7NJ2ttfzXjqeMJShJShJxktmnZoj4udexKuHu74n+8nfKwrWXTvPXul1zq1Fvxs9f8j5nBVYShKN1JWbTba8dOv2lW4b4kjNUcFjFLm+jGUer6WX2d9t0laYSpzhGUORpq6cXdew1mPk28mjiollMjFuY1e1UfNzbiLASy/Mp0+W0JO8dNPq9ttbJrqRp0TirL1jsvm42VWCutdrfs132TfWxzycZQnKE4uMouzTVmn2MpqOL5Pe5dJ6NXp+V5RZiZ6x1YL9wbmaxWCWGqSfpaS3t/m/P36WRQTdyXGyy/MKeIi7JO0nbp9/errqeMDK8muxVPSer1nYsZNqae/udP0lFxlqmrNPVPuc+4tyuWAzCVSPNKnVfMpNt3v1b8dfan0sX7C1qeKw8K9LklGavff2XNTPcvp4/ATpuLdRJuLs/glu9Lpd0uhptQxYybPLrHOP78Wa0/KnFvbVdJ5S5iD6q050qjhUi4yW6Pkxkxs2IAAAAAAAAAABc+AcClRqY2WqldRtZq+qXTR/S96KYWHC8UYjC0Y0aFFwpx2XNF6dFdxb0SS8kidp921Zvdpd7kPPtXbtmaLXWV8sn2b8WHFrf3dkUf53422tP4x/dMfO7G2a5NX19X900HnjG9s/RQeZcn4fVbc4xcMHl9WtKoopxsrSSltrbxte3jY5jWqTq1Z1ajvOcnKTtbVknm+fYvMcOqNRuMVutNdU+iXVIiSk1PMpya44OkLrTcOcWieLrIACsWIAAAAAE5wdj3hcxVGTfo6u/h3fgravT8lEGfVKcqVWFSDSlCSkrq+q8GdbF2bNyK47nK9ai7bmie91u/L9JWv7WzDV93d+Cvb7CjU+LcZGEYcrajFRu3G+i/RM/O/Hfme5x/dNT55xvbP0ZnzLk/D6/wCl2nCNWnKnJPkknF+Ke9jmmfYSWDzOrSaSTfMrJJb9Etle9vCxKri/HK14J/q/ukZnOaTzPklVptTi97q21tkl2XuK3UszHybccPWFlpuFfxa549tpRxO8DJviGklb6E9/IgjcyjHTy7GLFUo3motLbr5plXi3KbV6murpErPJtzcs1UU9Zh1DRra63veyFrO9vfuUj53421uT4x/dHzuxlrKn1vf1f3TTeecb2z9Ga8y5Pw+q5Ytf6LPTWy6+K0OUFinxZjpw5JJ8vVLlV/8ApK6U+qZlvJmns+7f9lvpmHcxYqivvAAVK0AAB90KkqNWNSDtKLvv8DpPD2ZU8ywSqOLjJXTTd7+fwv7Hpc5mbmVZhXy7EemoS16rp+zv72WGn5s4tfP9M9UDPwoyre0fqjo6jbTRLVbeHcXT0vp006dyjLi7HWs4X/V/dMfO7HW9aN/1f3S+8843tn6KLzLk/D6/6XXF0I4mhOjUfNzX9a7dna23Xt4q6OaZzgKmX42dGUZqF7QlJb7XV+u/1OyuS/ztxtknBO36Fv7poZtnNXMqShiIaxS5Wmklr1SSvo37yt1HKxcqjemfSj4fZZ6diZOLVtVtwz8UUTvA3/mCn+hIgjcyfHzy7GfKacW5qLSs0t/NMq8S5TavU11dIlZZNublmqinrMOo+G7b67/5mXfezv5FFXF2OtrC7/qfumVxfjU78m+/0df+k03nnG9s/RmvMuT8Pr/pc8ZzfI8R6r/ip/3X1OWYvXFVrfny+sn6nFuNqQlCUPVknF25b2e/5JXas3UqzqNJOUm7LZXKjVcy1k8HZ92/7LfTMO5ixVFe3Pbo+QAVC1AABbuCM25W8DXqLVr0bk7vsrfV7tLJlvs77tezp9pyWhVnRqxq05OMo/e3kWGPF2MjGK9G3ZWveF35+qaDT9VotWuC73dFFn6VXeucdrbn1Xlu2/Xpy2PPFUqeKozo17OE1ytaO3+ZSlxdjF/s/wC7+6ZlxdjXa0LNK28f3SbOsYs98/RBjRsmJ3jb6/6ROeZfUy/HSpSilFtuNk0l4a+z2NGgS2b51VzOiqdem7x+i00tb9bRV9G/eRJmcjs+0nsv0tNY7TgjtOq0cB4LnxFTFyfqxVrXtfVNX072a/RZdHdp35u25QMu4iq4DCww+HouMIpX9aLu7avWLau7u1+rNj5343pD4x/dLzB1DGx7MUTPPv5KTO0/Jyb01xtt3c130crbPs0eWOxCw2FqYicoR5I+rJ7J9P2lM+d2O1fJr48n7prZnxFisdg5YeUeRPqml9SXS68myRc1mxwzw77o1vRb3HHHtsicXWeIxNSs7rmldJyvZdFfwWh5AGVmZmd5aiI2jYAB8fQAAAABJ8NYyeDzWlKLdpSSt3fT2va76NnSlJSSlFuUWrp33T7HIiw0eKsbToxp8iairbQt7PVLrTNQox6ZoudO5T6np9WTVFVvbdfLXu9bLdvYcrTfNFq299blG+d2M/Mfvj+6Z+d+N0fI7rxj+6WnnjG9s/RV+Zcn4fVpcWYD5FmkuVNU5/Ruktre/Rpt92+xDkpnOc1MzpxjVp2cWrP1el+0V3IszOVVbqu1VW+ktNj0102qYudYAAR3YAAAAAAAAAAAAAAAAAAAAAAAAAAAtPA2Vyq1/l1WH4uP0X7fLw77J33RXsuwlXG4uGHpKTb3aV7L72Xm0dPwGHjhsJChT2ikpWk7dra62SVlfWyRcaPidrc7SrpT+VRq+X2Nrgp61fh7Ws7xVpPay3ZUuOsyaUMDRajfWbj1Wz6ea07SvuWLN8bTwGAqVZSjGVny2Wq8V992u5zHE1p4jETrVPpTd7Xbt4a62RYaxl9nb7KnrPXw/wBq/RsTjr7arpHTxMPXq4epz0ajg+ttnqnZrqrpaMtWS8V/RoY6OlrKd/Pq/Ytb9dUVEFBj5d3Hneifl3L/ACMW1kRtXDq+HxFDE01OjWjNJKVnvZ+H2npOEKseWpBVI3+jLVHLcDjsVgpqVCrKKTvy306e56bqzLHguL5pKOLoc7tq1rd376WVv0jQ4+s2bkbXPRn7KDI0a7RO9qd4+6dxuQ5Zi1KUqTi3Lmk42u34yevsua3zXyxzX4qFn+n++e2G4iyus5RVZLktdtqMX5czTfuPZ59k6a/02lfrfX/IkTRhXfS9Gfoj8efa9H0vy+cJw/luFnzU6N3zc0W3rF9LPf4khSjTptqlCNPq1GNr+1EdiM/yyhQ9N8oVRbNQqQb9yd/gRWL4wo05SWFo+k2abu0/fazXkz15TiY8bRMR4f6efJszJn0omfFabOzb5VFa36I0Mdm+Awa5qlZSfK5JRaV1tpeyfsuUbMc/zDGN3qOnG+nLule616PxSVyLlJyk5Sbcm7tt6srr+uRHK1T9VhY0Pvu1fKF1xfF2Fi5Ro0ueyvGSTcZeDvyte5nhhuMt/lGFtZaciu2/evtKgCunVsqZ34vtCwjSsWI24XRsv4gy7GyjBT9FJpere+r6apNvyTXiS0GpwjKMlKEvouD3ORkzk/EGMwNSMZ1HUpXXNfV2+329rJon4ut8+G9Hzj+EDJ0WNt7M/KV5zHAYbH0uSvSi30la7W/+fj2sULPckxGWScm1Ok3o+qXTz7X772ur3zLMxw2Y0vSYecW9eaOu/htfde9Xse9alTrUXCrFSjLo+nl2LHKwrWbRxRPPulAxc27h18FUcu+HJye4e4irZcvRVuarSb6vb79/PfS3vxFw5Vo1J4nCRjKlu4x7+C6eW3bdIrRmZi/g3fZP2lpYmxm2vbDp2AzXL8dSvTqpLqpOyV77+dnZOz8CmcY4CGBzVqnKPLUXMkt/Pb2dW2m+pDU5zpzjOnKUJxd4yi7NPujNWrVq/wAZUnO350myRlal5TZ4K6ecd6Pi6d5NdmqirlPc+AAVayXDgjNJSjLL60rxSTi22/D2dFv+arbltT122XRnKMHiJ4XEwr0/pQd/2/8Ac6blWNhj8FCvB811aWmt7e3X26aroanR8vtLfZVdY/DMazicFfa0xynr4qtxxlfo6qx9GFqcvpaJdfru/HR9kVU6tjcNHE4SdCtJLnTWnR28Pd5XOZZnhJ4LG1MPNNcr0v287K9tu10yv1jE7O52tPSfz/tYaTl9rb7OqecfhrAApluAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABJcO5fLMMyhT5bwi7y0dvBPTzduqTPdu3VcqiinrLxXXFumaqukLLwRlsaGG+X1I3qTV4beyz16P3va8SzJt6czeng0j5pQjShGlBNRWl29/F9z6d5SV7vw6G5xrFNi1FunuYjKyJyLs1yheJMmxObKiqWIhTpw1anbe3Tt1666diAxXCWMpuMaFVVX+XzRSUfc237i8deVKKa9/wDkZemmy316ke/pti/VNVUc5+KRY1O/YpiinbaPg5lisnzHDycZYeUrSaXLq3bry/St4tEedck4VItVFGUXvGa5l7mReZZBgsZFuUFTm1va9tLLXfTor202ZV39DmOdqr5StLGt0zyu07eDm4JzNuHMXhL1KKdak3pbV9evXS3ZtvREGUl2xcs1cNcbSurV6i7TxUTvAADk6AB9QhKpOMIRcpSdoxSu2+yA+QTOXcO47FpSklSptfSettNPDfRq912LDg+EsFGL9PKU3o9G24+3Ra+KJ9jTMi9G8U7R8eSFe1DHs8qqufwUUHT6WT5bTqc8cJCMmrNxvBtP9Gx94jLsDiKahXoKpFaxjKcml72TvMVzbnXCD58s7/pn7OWg6JiuG8srVIyVHlcVyqMVZe1Rs2/aQmO4RqwXNhazmkl9LW7vq9FdadEpEa7pGRbjeI38EmzquPc5b7eKvYHG4nBVVUw9WUHdNpPRtbfW9fFl8yPPcLmMVRk+SqknaXXpZ9P26bXsUHFYXEYafLXpSg7212vZO1+6urrofNCrUoVY1aUnGcdn9nivA5Ymbdw69p6d8OmXhW8unfv7pdZ5W04OKad78yuivZ5w3Rxr9Nhn6Ora1tFfX49d2vOysaeRcUQ9FDDY9apJekb3d7b9N+vZ67ItWHrUsRTVSjKNSNk24rXurmlprx8+3t1/MM3VRk6fc36fiXMswyzGYKco1qTtFXlKKdltv1Wrtrv0NI63Up0qkbVKcaivdcy6+BoYjJcsqqbnh4RlOTlJ2i5Nvxab+JV3tDnfe3V9VpZ1ymY2uU/RzM+6VKrVk40qc6jSu1GLdl3Ok08iytQVOeFpyiurhBP3qN/ibOHwWFoQgoUacVD6N1zcvlfY8UaFcn9dUfL+w9V65aj9NMyo+U8NYzFy5qy9FST1lffxT2a32vt7S75bgqGX4ZYeirRu9u+9u/Xrc2XzXs7u/Roj81zjB5fSlOVSE6ivaMXpddL99tPFXstS2sYmPhU8f3lVX8vIzquCI5eyEhHTaSi/MrnGuWfKMGsTRXNUp2ukt+nfrp7opbsk8jzSlmmHdSnBpp/Rf31/7dzflFTi6c4ylCSacb2unodrlFvLs7RPKXG3Xcw7+8xzjq5GCU4ky6WX5jOOnJNtxtZeOy2Wq+royLMVdt1Wq5oq6w2lu5TcpiunpIADm9gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAzFOTUYptvRJdTofCeXRwWWxqTS9LUV2m9r+172Xmkn1ZWOD8t+WY/01Rfiqd7t9/d+xptNbM6ArNWjypLw6mj0XE2/+9XyZ/WcvpZp+bFScYQlOTtCKcpN62RzviTNquNzKc6NSUKcPVioy0969nV63a3LDxnm3yah8joVFGrK13F2a8fvbVq2zKMeNYzZ4uxonp1/h60fD4ae2rjnPR7fKsVa3ymtbtzs28JnWYYbkVOt6sHflty83m42b95HApKb9ymd4qn6rubVFUbTELZl3GFbnhDG04yS3nbrfw1SS/SehZ8uzDCY6nGWHqpt6uPW9r28dGr9VfWxyw9MPXrYep6SjUcJeHX9pZ42sXrc7XPSj7qzJ0izdjej0Z+zrDimuVpNbNW+BEZrw/gcdeXL6KbtqvZ18lbW6XREPlPFrhCNHGQ0X5Wr79d9rb8zfdFlwOZYLFU1OnWg1/SdrOydm9r+Fy7ov42bTtyn4T1UlWPlYVXFG/jCpYrhHFwX+j1Y1Jc2zskl53u/1UeL4TzXntanJdWm1b3pF/jeUfxceaL6rr7txaV0nF3aOU6NjTPSfq6xrWTHsVHC8HKM3LEYi8LrlvZX7ppN39jRPYHJ8BhIOFGjHW611TV769/bc3Z1KdK/pKkIaflTSb95oZjnGBwVvSVUpNJpdLPr3a8Umd6MXFxY4toj4z/txqysvKnhiZnwSHLflTWy0sfNavRo3VWrFO1+W95WX9FalLzHi3E1lKOFg6UZK129dV4dU/G3gQGKxmJxLfpq0pJy5uXaN7WvZaEO/rVqjlbji/CXY0W5Xzuzt+V9xHEeV0qjgq0W+W6d7xftjzfGx8R4pyvlf4yCfnP8AcOfArJ1rImd42WUaNjbbTv8AV0zAZ3l+LjD0da0p39Vv1tN72baXW7SJCEoSgpwkpR6OLuvecjJbKs+x2BqX9JKrF3upPVu3fr039ltyZj63vO12n5x/CJf0SNt7VXylf8Zg8NjaMqVempKUeVyS18r9ddeuqKTxBw9VwTdbDJ1KcpfQim+W/RdfZvtvra3ZNm2GzKC9FUSqKyaadrtX/bp4Ozdrm/NRcGppyjLdNaPz7ljfxbGbRxR8phXWMq/g18E9PZLkZtYHMMXg5xlRqySi9It6bpu3VXt0syzcScOOrVqYzBzSuryjJWu+7f2+/rIqEoyjJxlFxknZpqzTMvfx7uJc2nl7JhqLF+1lUb08/gtOD4wrLkjiqSa155RSflZafFsk6HFmXSpekqp05X+gruVv1bfEoQO9vVsmjv38Ue5pWNX/AI7eC/x4rynRc1b2wPPFcW4GElGlH0kWtXBSbXmmo395RAdKtZyJjaNo+TnTo+NE77TPzT+P4px+IhyU4woqyTS1W/a2q8JXIKpUnUnz1JynKyV5O700R8gr72RcvTvcq3WFqxbtRtRGyU4azGWX5jF3XJUaUrtJeF29lq/LfodIhUhUhGdOSkmk012exyM6FwhisZVwLp4uFRSp9ZvV9dVur367u7LrRcmd5sz4wptaxYqpi9HWOr24oyz5fl0na06a5k1dJLXV2Wtrt+TdtTnM4yhOUJxcZRdmmrNPsdccnHWyi/GOxQ+NMrWDxaxFGK9FU3stL/fTRdurOmtYm8Rep7urnouXzmzV8ldABm2iAAAAAHrhKMsTiqWHg4qdWcYRctk27akzDhXMpU41FKjaUVJaT2f9UicurRw2YYbETTcaVWM2lvZNMttLi3BQowhyV/Vgo39Cuit/OFhhW8WuJ7edvYg5leTTt2Mbon5p5ndLmo37Wn+6Fwnmbduehfyn+6WXKOIMNmWIeHpQqRaSk3KnbS6X5z7r4m7m+PpZZg/lNVc8eZRaivDe119ZbU6fg125uUzO0KqrPzabkW5jnKmvhPM07OVFeyf7pH5vlOJyyUY4lwblquW/2pdizvi7A3/iqy8qC/xCB4nzSjmlalOgqkVCPK1KHL1/Sfcr8q1g02pm1VvUn4tzMquRF2naEOSWVZNisyourh5U7KTTUua+luyf5yI0u/4P1/4bUd7fjZ/VTI2BYov3oor6JGdfqsWZrp6od8J5knZzo+6p+6PmpmX85Q90/wB0tGeZ1h8qr06dalNuceaLjHm0v4yRHvi/AN6Uay06UV/iFpcxNPtVTRXVtMf32Ky3lZ9ymKqaeUof5qZl+fR90/3SNzXLa+XVI068oNy25b9k+qXdFq+eGCtZUq3n6Ba/9ZAcS5pRzOtTnRjUXK3fmio9IpaXfYh5drCpt72at5TMS7l1XNr1O0IcA2sswdTH4yGGpOzlu7X0+/3S1KymmapimnrKyqqimN56PHD0KtepyUYOT69kr2u3sldrVk1guF8xrOEqkXTpyV20tY+FpNJ+xlvyjLcLluGioqKcE3KpLpprr9v2WS0My4oweGrujBSm1dNxjdp6dLr67pp3SL2jTLFiiKsmr+/mVJVqV6/XNONTvt3yha/CWKjBegqupPTSSil8JN/AiMwyvG4KUlWpPljvKKdraa90tVq0rlopcX4aU16WjJc0raw0iu97vbyJijjMuzHBX5lVpreP5SbttbW+q211s0fYwcLIiexr2n++18nNzLEx21G8fBzIG5nDwrzCq8HpTbeita9+ltO22l720saZQ1U8NUxvuvKZ3jdJZTk2LzOk6mGlTspOLUua/Tsn3PPNssxGWVIQxEoOU02uW/2pFo/B9ZYGtff0krX8oGl+ETXG4V96bfxRbV4VqnCi/wD5f7VVGbcnNmxPRVgAU62S+X8P43HYSGJoypck1dX5r7tdE/zWaWZ4Gtl+J+T13Fzsn6t+vmkXng3/AFHh9fyPo9/xlQrPHP8A5gqa/wCzh9Rb38K1Rh03o6zsqrGZcry6rM9I3QQAKhagAAG9l2VY3HOPoKL5ZbTaduvbW101fa59cP4FZhmdPDy+ju1e1/vu9tE9ToVSpg8oy9TSjTpQXKr6X03f37JLZFpgafGRTNy5O1MK3Oz5sTFu3G9UqnS4RxTpKVWpOE+0YQa97mvqNOvw1mlKnKp6NSSdlFat+PZe1kvV4zTqw5MKlTf0+ZXa8vW1+BuYTizAVavJNTpK6ScrRvfwu0l5slxj6bXPDFe39+MI039RojeaIn++KjV6NWhPkqwcH0vs1e10+q03PMtfG2Pw8lHD0KcJOouaU3Fpuz389HG+/wBJdiqFRlWabN2aKat9lpj3artuK6qdt0vl3D+Ox2GhiKMqXJJXV+bvJdE/zWbHzTzP86j7p/umzkPEeFy/LqeHqRrOcY8rtSUl9Kb/ADl+cvcb74wwT3p1/wCwX+IWNizp824m5Vz71ffu50XJi3Ty7kOuE8z6zorzVT90LhTM27KVH3T/AHS9YSoq2Hp1eRpzvaL3um17NiBxfFGEw1eVGpSrcySdlSTWqutefxRMu6fg2qYqrmYiUO1n5t2qaaY5wgavC+Y06U6kpUuWCu9J7fqkEXPEcV4Cph6lKNOtHni036Bdf/UKYVOdRjUcPYTv7Vth15FUT20bex74HDVMZiY4em4qTUneV7aJt7eRK4jhjMaFKVSpKkox30n+6a3C/wDrql/y6v8A8cjoGef6txC1Vraf1kScDCtX7NddfWP4R83MuWb9FFPSf5csABTrVt5Xl9fMcR6DDuPP2lf7E+xuZjw/jsBhZ4iu6fJFXdua71iuqX5yPfgZv+HoJJNOEr38iycaX/gOrd/kO/8AaUi3sYVqvDqvT1jdV3sy5RmU2Y6T/tz0AFQtE7HhbMZRUlOjZ7P1/wB0yuFczbspUvdP90veGcfQR5mmkm3737yv1uLcJTqSpSo1rwbi0qd1fz5/sNNd07CsxE3JmN2ctahmXqpi3G+yFjwpmTbTnRVvCo/qiR+LyjMMNJqeHlJc/JFxWsn4Lf4FnXGOEurYapv+bZf3mS+AzfAZjzU6VWFS75eWS0k/JpfFL6zjGDg3vRtXObtObm2fSuW94cyBdeKcgjXh8qwahGcV6y5tGvF/a9vL6NLaabTTTW6ZU5eJXjV8NXyWmLlUZNHFQwbWWYGtmGJWHoOKm02ua/2JmqTnA7tn9P8A5c/qPOLbpu3qaKuky9ZNybdqquOsQ8sx4fx2Aws8RXlS5Iq+ilrqlpeNupEHQuNU1klZO30b6fpwOekjUsajHvcFHTZw07IryLPHX13AC1cJZHCrFY3FJST+hG3f7fHpfTX6MbGxq8ivgod8jIosUcdaFwGT4/Gyj6Ki1GXWSfa6dld2elnaxK4bhLFTp3rVJ059oxhJP3zT+Basfj8FlVBKo1Tjp6sVtfv8e73fQh1xhg9b0qsXfpSUl7+dfUXVWDg4/o3q+f8AfYqKc7Nv+lZo5ITFcMZlRjKcYKcU1y9G79dLxXtZC1ac6U3CpCUJLo1Yv2XcTYHFTUJc1FuVorq9tbeN9k29z2zzJ8PmeEUocsalnOEo2tJ2+3TXrpfZW8XNLs3qJrxqt/h/ej3RqV21XFGTTtv3ucg9sXhq2FrOjXg4yXxPEoqqZpnaeq6iYmN4AAfH0AAAAAAAAAAAAAAAAAAAAAD7oUp1qsacFeUn7u78j4LTwPlnpKzx9WKcIfRu13+Gq+FtpEjFx6si7FuHDJv02Lc3J7llyXBLL8tp0Gkpcqclrfy1em7du7ZvJvaPuiZV7/STa7O6R81KkFFyqSSgt+Z2Tf36G4oopt0xTT0hiLldV2uaqusvGvgMJWrutWoRqVbW5uZ7XvbTxufNXK8DWp8lWhGUHq05ya91zyr5vl1GVSE8QlKm7SjzRhJPyk02amH4nymrUUITrRlLdzUYr2ttIjVXcWJ2qmnf5JVFrM4fRirb5vrF8N5ZXUeWjGnypqKUbK772s2/NlfzHhPEUeaWFqelitUt9LeGt2+iVtd9C34fH4LEwUqOJpyjzcvM3a77J7P2GypWe7Ttt0Od3Axb8b7R4w929Qysedpn5S5NiKFXD1OStBwl07PW2j67M8zqOZZdhcfTlCtTjJt3vb6TW17Wvvbvq7WKPneQYnL581NSq0rNtrW1vr018LPorugzNLuY/pU86V/h6nbyPRnlUhj6pVJ0qkalKcoTi7xlF2afmfIKtZN3D5pj6HM6de7k7tzhGb98kz1/hzM+uIg/OjB/YRoOsZF2I2iqfq5zatzO80w2Z4/GTlUbxNWPpb86jLljK/Sy0sawBzmqaucy9xER0ASGByfH4ucY06LjfrJO60unZa2fe1iWp8H4yVODlXhTm1eUZJWXk03f3Ik2sK/djeimUe7l2LXKuqIVkFu+ZsvRJfKYKrbV8zcfO3L9p4y4OxMYv/SVOX5KhFWfvkvqOs6XlR/h94/lyjUsWf8ANVwTOK4bzTDwTlSi3ezSbXL4uTSiveRNalVoyUatOUG1dcytddyLcsXLX66ZhKt3aLkb0TuzRrVaM+elOUJeD31vr32Lvw5xFTxko4bFLkq2um3e/fz7+C3vZsohmEpQnGcJOMou6admn3O2JmXMWrenp7HHKw7eTTw1dfa67dpLRKOmiejfe/Ug894foY+PPShGnUV2vRpav9l+n1XbIbhziWVBQwuLSlHmdpydkl28O3bbbVu4YevRxNNTpTjOLSkkuiequvjc1Nu9j59vbr8O+GYuWcjT7nFH17pc0zDKsdgW/T0XypXclqltv2V3a+ze1zROuVaUKtlUpxklqlvZ+HZ+JE4rh3La9SMnQSab01Sd+ras37WVV/Q6t97VX1WdjW6Jja7G0/BzkF4rcIYKbvDEypd+WDsvfJn3h+EsBTg/SzdW/WUWmvdJfURY0fJ36R9Uvzvi7dftKipNtJJtvRJEtlmQY7Gy1g6MNm2tVvuumq62dndJl3wOUZfhI8tOinpy3aV5Ls7b+25IK8YpX9VK0VzWS9hOx9DiOd2rf4R/KDka53WqfnKEyjh7B4H16i56j2d/t3620smt0yZhGNNKMYKCWiUVZI8cXi8NhYN4irGFkna+u9k32V7K7stSsZlxcleOBp620na3RWeq96t00ZZVXcbCp25R8O9XU2crOq4uvx7luVraXNXNcHHMMFUw8lzNr1dVv7futH0K7wpn1XE42WGxkoydV+rKT27LXV79bu3kWyTd7N69r/E6WL9vLtcUdJ5OV+xcw7sRPWOcOT4uhPDYidCduaL6HkXDjjLHNRx9KOqXrJXei1envl0/Kv0KeZDMxpxrs0T8vBr8XIjItRXAACKkAAAAACwcCW/hepd2/FLX/wBSBPcdW/gJ/Ru6sXe2r3IHgX/W9Rd6S/8AkgT/AB0ksg3jd1Y6dTR4X/XXPn+Gfy/+wt/JQAAZxoAu/wCD9/8AhlX/AJk+vhTKQXf8H7X8HVLxvarNpdHpTLPSPWo+at1b1aWnx7Rq1cbh3SozmlBp8sXZfexW/keL/wB1r/2bOl5ljcBhpQhj6lGDavFVaabt4XRpvNOHv5zAPTf0K/YWWZp1u9eqrm5Eb93y8Vfh6hct2aaItzO3f/Yc/wDkuK/3at+ozzqQnTm4VIyhJbqSszoizbh66vUwK/8AQX7CjZ7UpVc3xNSg4ulKo3Dl2sVeZg0Y9EVU1xVz/vetMXMrv1TTVRNPi0i7cA4SMMHPFuzc3pZvxWvS6s/ZIpJ0bhD0X8C0/RuNrq68eWN/jc66NRFWRvPdG7jrFc0407d8ovjrM500sBSaXN9PXXp09v1+ymk/x1/rmO30Hby9JMgCPqN2q5kVb93L6JGn2qbePTFPfzD0p1qtOM405yipq0knuvvde19zzBB32TAAAXb8H1ngaq6+ll9UDV4/oVJ4zDKjTnUSg9YwfgbP4Pv5DW2/jJb+UCdzHF5fhpQjjalGLs+T0kE/O1zV2rMXtPpomrbfv+bL3L02c+a4jf4fJzP5Liv92rfqMPCYpK7w1ZL9BnQP4U4euk6uD/s1+ww8z4et/G4R3/4cf2EDzRa/9o/vzT/Otz/xn+/J88HJwySgpRaajazX/EqFY44/8wVLNNckNvIvmBr4etQU8HKmqTWjhZJ9L6eTXsZQ+OP/ADBUX9CP1ErUbcW8GmmJ3225omnXJuZ1dcxtvE/sgwAZlpAAAWHgbFRo5m6MkvxrVm5Wd7Sikl11l8C18RYKvj8qq4ei0p7Wl2unb3xXxObUKtShVjVpTcJx2a++xb8r4ujycuOi01+Vdvt11v137bsvNOy7PYzj3p2if3UuoYl6btORZ5zCuYnKMww8+WWGnK8nGPIruXjy7280aM4yhNwnFxlF2aas0zp9HNMuxfNBVqc1FJzUrSjFPu1eK94xWW5bi8PyujTcLPldNpxV1q0tr+J7r0amuOKzXv8A32w8U6xVRO163MOYSbk7ybbtbUwT/EnD9TAS9NhoynRabdvyevnZLz2vfe0AU1+xXYr4K45rizeovUcdE7wAA4urqeT2+Q0Fr9KXT+mznWe/6zn+hT/uROjZPpgKNpNetJOy29dnOM7/ANZT/Rh/cRotY9Xt/wB7lFpXrF7x/eWkADOr1J8L6Z3S/Qq//HI6BnMv/Dq7bsm1/eRz/hh8udU2/wCbq/8AxyOgZ5/qvEtO7dtf6yNHpHq1z+9zP6r61a+X5csABnGgTnBCvn0EvzJfUWbjX/U1Z33p7W/4lIrXAybz2DsmlCW/kWXjNt5LWb/m9bbX9JSNFi/9bX81Dlf9lb+X7uegAzq+dYoJvCxfLe0ZeXU5bj/5dX/5kvrOo0NMH3XLLV+05fj/AOXYj/my+s0muf8AHQz2if8AJceB90ak6NSNSnJxnF3TR8AzkTMTvDQzG/KXTshxix+V06r105Zc27/b1XjZlH4rwscJnNWEHdS9b6NrdvNtWbfdlm4Fc/4JcaifK2nC/Vc09vbcjvwhW9Lh/wA7nnf9SmaLOntsCi5V15fwz+FHY59dunp/ZVQneBnbiGl+hP6iCJ3gV24hp/8ALn9RUYHrNHits71evwlZuNHfIq70vy7f+pTOeHQ+NLvI697aR2vr/GU/cc8Jet+sR4R+6Hovq3zltZTQWJzGjQkoyUpfRk2lKyvy6a62t7TpWIqQy7ASnfSlFrmm9W+7766v2lE4OdJZ5R9I0m3FQ8+eP2XLpxBplGIjpb0dS93/AMOZM0najGuXI68/tCLqu9zIt256f7c8zLG1cfipV6reuyveyNUAz1VU1TNVXWV/TTFMbR0C78E5jVxVGeGruc5Qf0/NPTxbs37L63KQTvA8uXPY6v6D0v5J/BsnaZdqt5NMR38kLUbVNzHq37uaR49wa/F4uCu/yml00Tbfm42833KidA43uskmr2293NH/ACOfnTWLcUZO8d8buek3Jrxo37uQACrWQAAAAAAAAAAAAAAAAAAAAA2MuwlTG4uFCmndvVpXsv8AvZebR07A4eGFwkMPTXqwXR3Te276dF4JEFwXlUcLRWMxFNqtK6XNHWPS33tq3fZFjXTfsazScTsbfaVdavwyur5fa3Ozp6R+WtmmNo5fhZ1qt2oq6inv0u/DVe9Lqc/znOsXmNVuU5Qp2tyqX39y00W71Jji2hmuPxyhSwdR0IL1XFaPt7rv3vpYhf4Dzb/cK3uIOp3cm9XNuimeGPhPNP02xYs0RXVVHFPxjkjgSP8AAebf7hW9w/gTNf8AcavuKnyS/wC5P0la+UWfej6w0aNWpRqKpRqTpzW0oys17SayfiTGYLlp1H6SkrLRapaLbZ6J9m29Waf8CZr/ALlU+A/gTNf9zn71+062aMuzVxUU1R8pc7tWNdp4a5ifnC/5ZmmGx9KPoJau9lfe2/j7NH12sbdWnCpTdOpBOD3T2XsOd4XK88wtT0lDDVYScbN3Wq++vsTLjkOMzGvF08dhHRcY6SlL6T09r6vwS3bNJh5ly76N2iYnwnZnMzBotenZriY8eaNzjhOlVn6TBTVN/mWXRexdu3tZXMZkWZYWU+ejzRh+Unbm8lKzfsR0q9mlzNd23exl3a5b6b2T+9keL+kWLs7xyn4PtjWL9qNqvShyj5Hi7X+S17f8tnth8rx+Ig5UqF7fkucVJ+UW7s6eoq6doX6+qNU9ZNPvaySI0aFb765SJ12vuo+6i4HhTG1ZReIvTi7O1mn4p31XmkyyZXw9gsC1NR9JNflN279d9nbSyfVEt1218TKsretLXrJ/exPx9Ox7HOKd59soN/U8i9y32j4MQUYwVOEY0oJ6KKSXuCXg7JdtWzZy6hTxOKhRq4/B5fTeksTi/SOnTXdqnGU320T+0hfwiUMTguIq+B4ZzalneVU1SdLMVQdBV26cXNeiqO6ipNx9ZXfImtHY738iLMfpmfCN3DHxpvz+qI8Zb9Wvh4NqriKMZLeMppNefYU6lCpaNKtTqN7qM0zn1XJ87xHI6y9JyxtHnxEXZdtWe9DA8S0KNPD06slQpT9JCk8TB01J2u+Vu2tlfuV/nK9vzsVbfP8AhYzpdnblejf5fyvui1Xs5Vr5mnj8twmNhKFelHXVtWV3Zq/a+r137WNXhOebV8XDA5jVweEpunJrEVq3qOUYtqL5FJpya5U7Wu1eyvJS129N77p6Wf2lhRXRkUc6Z8JhXXKK8av0avnEqNnHC9ehzVcJadO+19te729ulluV2pCdOfJUhKEtHaSs9dTrkXrZSv3aev8AkRmbZLgswhKUqUYTs3ePTR/t8r7plTl6LTV6VnlPsW2JrMx6N6Pm5obWAx+KwNSM8PVlFJ35bu3S+217WurOxt5vkWMy+Tbi6kPzoroldu3bf2LWxFGfqouWK9p3iYX9NVu9RvHOJW7AcXvlUMZSbfqrnv73dbLws2TNDiDK61NzeIjFKVnzySv5JtO3sOcAsLWsZFHKrafFAu6Tj3OcRt4OqUMdg68ealUdRPqqc2vgj5xGZYDDTXyjEunzbc9OSXsujloJHn25t+iEfzHa96fs6NieIsrocq9PzuV7NSTXt5bte4gsdxfVmuXCYdU07O70fitHe1uqaZVj6pwnUmoQjKUm7JRV2yNd1fIuconbwSbWlY1rnMb+L0xWKxGKlzV6sptaq+17JN27uyu+p4k3lnDeOxsFNpUoSWjav0uvD3NtW2LNl/DGXYdc1WLrS1tezWvTVWfg0kzxZ03JyJ4pjbfvn+7vd7UMfH9Hfn7IUGjVqUasatKTjOOzR0vIMwo5hl8Zxbcor11e79r+/fqj3w2BwtCmqdGnKnBacqqyS+DPdRs/VTstlKba+LLzAwLmJVMzVvE9yjz9QtZVMRFMxMd7GJowxFGdCrZwmrO6Wn3ZzLO8DPAY+pRlBxje8e3lu/jq1Z9Tp6Tt26u6ILi7LFjsC61NL0tO1np7LvounTo3sfdVxO3tcVP6oedJy+xu8FXSfy5+ADItaAAAAALBwJ/rar/yl0/4kCf47/1Bu/42PkQHAivm9T/lL/5IE/x3b+AdLJKrHS2++po8L/rrnz/DP5f/AGFv5KAADONAF24Ausum07fjZ6+ymUku/wCD/wD1dPVL8bPzelMs9I9aj5q3VvVpaH4Q3fH4fVP8W9ttyrnReI8jjm2Jp1XVUFCLja9r/BkWuDot2VVu+1qv/wDBJz9PyL2RVXRTyn4x7EfBz8e3j001VbTCnAuC4OTaSrr+1/8A4IviHI45Xho1VV53Kajbmvo+bwX5pAu6dk2qZrrp5R8Y/lPt52PcqimmreUGXbgLFxqYOeEk4pwfqpR33lq9ru79kSkm1leNq5fjI4ikk2tGn2+z73uro84OT5Peiuene+5uP5RZmjv7vFZ+PculLlx8EtFael2/Ptt9ftpx03LMwwmZ4OMvUbndOD6teHfbTpv2bj8w4VwWIqOpSk6PRJPl1t10e3kvFsts3TpyKu2sTvuqsLUIx6ewvxtMKEfdGlUrVFCnHmk/G3x6Ftw/Bluf5RjIyV7J05Wt7GtfeidwGAwOWU4yhGENXZuN279EtW373bwItjR71U73fRhKvatZoja36UuZtNOzVmYJDP6mCqZhUlgoOMeZ82qafl8f893HlXcoiiuaYnfZZUVcVMVbbLr+D+3yGqmv9pLp4QNP8If8sw1kkuWX2G5+D6/yKpb+clf3QNP8Iati8Lr+Q/sNDc/6uPl+VDa/7Ofn+FWABm2hdD4LV8koXat6Pa1/9pUKxxx/5hq/oR+os/BibyShdXiqf/7KhWOOHfiCp+hD6jRZf/W0fJQYn/ZXPn+yDABnV+AAD6pQnVqRpwV5SdkjNelUoVZUqseWcd19914kvwjVwVHNITxbd9orlTV9191r01vYt+PyjLszh6dqLcnpOOz38nu3pffcs8bTZybPHRVG/sV2TqFONdimumdva5sTGSZzjMPjYqdWVWNWVpc8ru7srt9V4PTfZ6k3Pg6laPJiJeLlO1/Zyae9m9lPDWFwNd1uaU5NJJSadu+tlv2t7bN364+m5dF2J6fHdyv6liVW5iZ3+CTzOnGeWV1OlBx5HPlltprb7DlZ0TinNI4HLpUoVYutJ+quqd0+3k2u2+6vzs6a5coquU0x1jq56JbqptVVT0meQACjXTqmTaYCg2lo5f32c5z3/Wc/0If3InRsmv8Awdh+ZK/rf33c51n3+tKn6MP7iNFq/q9v+9yh0r1i94/vLQABnV82Mtly4+h+MVNOok5N2STdnf2HTqz+V5e6nJz89ONRxb32ly+eljlJe+Es5p4nCRwuIq/joLeXXXv1vffu7aaXvNGv0xVVaq/yU2sWa6qabtHWlRqsJUqs6crc0JOLs7rQ+ToGd8N4fH1vTU5+iquLXaLfRtpO68PZe1rReF4On6V/KMTGULacl07+7X4eZHuaRkU17UxvHt5O9vVceqjiqnafY+fwf0ZSrV6vJ6q/Luuiaa/6k/Ybf4QK0VhKFGnUjGTd5QT1lF/scF8Cco08FlGCUVanSpq7bteVvv8AW31ZQeIsy/hLMJVYt+jX0V9/JLxteyJ+VFOHhdjM+lP9lBxZqzMzt4j0YRgAM40Dq9B/6Gtvoy+tnL8f/LsR/wA2X1nUcKk8PFfRvzX0u931K7V4So1a06rrP125X9Lbf+o/rNXqmLdyKKItxvsy+mZNqxXXNydt1JPTD0amIrRo0YOU5PRffZeJcfmfhWremqRfR+lv8ORfWSuV5Lgcuj6SEbyjH1qklZLbV38r6uyeqSKuzo9+qr0+ULO9q9iin0OcvTIcF8iyylRejtd3vp79urt0uyj8WYqGLzmpUp/RStfmun28tLJruiw8U5/SoUpYPCO9WUVzT30fn3+K8GUmcpTm5zk5Sk7tt3bZ21XJoiinHt9IctLx7nHVkXOssE5wO7cQU9WvUn9RBk5wP/5hpW/Mn9RXYHrNHisM31evwlZeM7LIqyX5l9rf7Smc+Og8a2/gStbpHtb8uBz4l616xHhH7omjerfOW3lFdYbMqNZ8qUZWvK9o3VubTte/sOlYynDH5fUpuL5KsPou6SfZ9Vro/acqLXwjnvo3DAYqyjoqbS67JW7/AF+e/rScqmiZs3OlX9+7zquLVciLtvrSrePw08JiqlCal6rdnJWuu54HTMyyzBZpR9JNRnderVirp/ey69Cv4ng2q6n+j4qnGFl9O7d++kVY+ZGj3qKv/lG8PuPq1mun/wCk7SqZduCcsq4enPFVouEpbJ9HZq3dOzd/NdUz3yzhjB4WtCvOcqko2f0r2avs7L6rqys0SGZZngsrw8oudOM4qygktPC3u0+pXalYOnzjT29+dtkbNz/KI7CxG+6E4/xqUKeEhUfM/ppWs1o2n22g/eU02MxxdTG4ueIqOXrPROV7L769NWzXKfNyPKL019y2w8fyezFv2AAIqSAAAAAAAAAAAAAAAAAAAbOV06dTHU41ZxhFXleSum0m0reLSXtNY+oTlTnGcJOM4u8ZJ2afdHqmYiqJl8mN42dZp0o06cIRWkY8sdeh9dOiVrHPcFxNmWHUYOalTTbcUkr+9NJeCSNv54Yy+lN++O36pqqdZxpjnvHyZarRcjflMSu19Em2/v1MuV1bm9ifXxKU+MMS/wDYz8fXh+4fL4uxNtKMl/Wj+4evPGN7Z+jz5lyPh9V21V+77GU7a666a6FKXF9a93hpPT8+H7gjxfiE1+Inb9OH7g88Y3tn6HmbJ+H1XV9knr47GVo/p8r7lJ+eGI/mZ79Jw/cHzwr3v6Cpt/OR/cHnjF9s/Q8zZPw+q7Xb0i213T0XmNb8zbjfwKT88MRzc3oJt2/Ph+4FxfXV36Cpd/8AEh+4PPGN7Z+h5lyfh9V18L/Z8DC5LXu/CzTZS1xhiNvQTt1XPD9wfPCvZL0FTT/iQ/cHnjF9s/Q8zZPw+q663avr9RmN78qV/LT6ilPjCs0l8nqaf8SH7g+eFa1lh6lv+ZD9w++eMX2z9HzzNk/D6rrd9OZ9rq3uMXvZdN9SlrjGuv8A8ef68P3B88a+n+j1P7SH7h888Yvtn6HmbJ+H1XTWy/7GVJpq1lbq2rlLXGNbb5NOy2XpIfuGFxjXS/iKn9pD/DHnjF9s/Q8zZPw+q7czv9J26NIw20t5Lxuyl/PCr/u9T+0h/hhcYVF/+LP+0h/hn3zxi+2foeZsn4fVdHLq2rvbW2h8811s37dPaU353yt/JKvi/TQ/wzPzyqdMLV/tYP8A/WPO+L7fs+eZsn2R9VxTT0crJ9+pnTTdLppuUyPGeJU/5JT5b7tttL2WNuHGOG5HzUqik1+TS0/vnuNVxZ/y+0vM6TlR/j94WeUY1IclWMJxf5D1XuIDOeF8PilKrhpejqN9er8X19uvieVHi/BVKvJOjOnD86WiXsV2TWX5lg8bGLw9ZK+0Xa7dr28/DfuepuYmZHBvFX5eabeXhTxxEx+FAx+R5hhJtOjKcb2TitW+yXV+V0r7kdOMoTlCcXGUXZpqzTOt6ytBq99OW2jPGOHowTcKapt6v0bcL+fKV93QqZneirbx5rC1rs7enT9HKDap5djp1IwWFqRc1ePOuRNebsjqEsPSkrTi5JPXnk5e5N2EKdOn9CnCj3cIJHmjQo39Kv7PVWux/jR91Hy3hXF17SxMnSi0na1n4rXr5Jp9y2ZZk2DwMEqdCEp9W1f4dfbexvq21pNdu5qY/McHgqbqVqybV7qNnZ2vbe19Hpv2LKzhY+LHFt85V17OycueGPpDabb1bktNH+wynd6OztrpfQq2N4wpRclhaClJNWvH1ZLvd2a8rGrHjKst8LJ916SFv7h4r1XFpnbi+0vtOk5VUb8Oy5pcz2btqk3f2htd4395TZcY1GrfJaiXb0sP8Mx88an+61PbUh/hnnzxi+2fo9+Zsn2R9VzVntzNr3IxZNOLtJNNNPr7CmvjCo1/Jqvtqw/wzzxPF+MnGKw9N0mnd80otNf1Yxa87nydYxdus/QjRsn4fVGcTYelh83rQpVVUTlJvVtpqTWrfV25v6xGH3VqTq1HUqScpPds+DKXaoqrmqI2iZaq3TNNERM7yAA8PYAANvLMfXy6tKth+TnlFRvJXt6yl9aRsZjnmPzDC/JsRKDp3T0jbVEYDtTkXaKJopq5T3OVVm3VVFcxzgABxdQksrzrG5bRdLCuCTk5O8b72/YiNB7t3K7dXFRO0vFdum5HDVG8J351Zt+fS/V/zD4qzb8+l+r/AJkECR5fk+/Lh5Dj+5H0T3zrzf8APpfqv9ppZpnONzKkqWKcHGLTVla1r/vMjgea8y/XTNNVUzD1RiWaJ4qaYiQAEZIemHr1aFTnozcJaX7PW9muquloydwPFWOoKMK0fSQjG1lKzb7tyUvhYrwO1rIu2f0VTDldsW7v66YlZsRxfip0ZRo0nTm3o3KLS9nKn8SGzDNMbjpSdeq+WW8U3a2mjb1auk7NuxpA93cy/djauqZh4tYtm1zopiAAEZISGV5xjctpyhhXBKTbfNG/b9h8ZpmeKzKcJ4pxbgmlyq25pA7TkXZo7OavR9jlFi3FfacPP2gAOLqlsBn+YYHCww+HdKMIx5VeN2/WlL/7M0sxxtfH4p4nENOo0k2l2NYHarIu1URbmrl7HKmzbprmuI5z3gAOLqAAAb+AzfH4OUXSrSairJSb7WWq1sui2NAHui5VbnipnaXmuimuNqo3haKXGGJjShGWGU6i+lJyjq/BcunxPDGcV42q36CLpRcWpKUk3funFRf1leBKnUcmY245RowMamd4oh6V61WvPnqzc30vstb2S6K7eh5gEOZmZ3lLiNugAD4JujxPmtGkqVOdOMI3tFR21v3InF154mu61RRUmknbbRJfYeQO1zIu3IimureIcqLNu3MzTG0yAA4uofVOc6cuenOUJWavF2eujPkAT2X8T4/DJRqfjYJPS9ru+m6aSS0skjZlxfiHGSWG5W1o1NaP9UrAJtOo5NNPDFcolWBj1VcU0Ru38yzfHY9cter6rSTjHr7/AGababGgARa7lVyriqneUmiimiOGmNoAAeHpOR4ozWMeWMqUY3eii+rv3Hzpzb8+n+r/AJkGCX5fk+/KNOFjz/hCdXFWa3vzUv1WvtNTG53mOKm5SrOmnayi36tuqbba9jI0HmvMv1xtVXP1faMWzRO9NMAAIyQGzl2NrYDErEYflVRJpNruawPVFdVFUVUztMPNVMVRNM9Erj8+zDHYaWHxEqcoSVnaNuqf2IigD1du13Z4q53l8t2qLccNEbQAA5vaSwGd5jg/4utzq1rTv73bf23JdcY11FJ4eTl+U3OGvl6mhVgSrWdkWo2prlGuYdi7O9dMSn8dxTj66nCl+KhKNtWuZe2Kin7UyExFerXqc9abm+nZa3sl0V29EeYOd3Iu3v11TLpasW7UbUUxAADi6gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB90atWjJypVJQbVnZ7rs/A+AInbnAnsDxRmGHhyVLVY2fhd38U0l0skiUpcZUlTi6uGk5reMI2Xv5vsKaCdb1LJtxtFX15oVzT8a5O80rp89KH+41LLReuj5rcZ0/QuVHCv0t9FOOnvT+xlNB1nV8qY23+0OcaTixO/D95TmO4mx+IvGm1Tg2nZ2l5pqyTXmiGq1atVp1ak6jiuVOUm7LsfAIN2/cvTvXVumW7Nu1G1EbAAOTqAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD//2Q=="
         alt="AKRA TECH STUDIO"
         style="width:220px;height:auto;filter:drop-shadow(0 4px 24px rgba(59,130,246,0.4));">
  </a>

  <!-- Nom app -->
  <div style="text-align:center;margin-bottom:8px;">
    <div style="font-family:'Syne',sans-serif;font-size:32px;font-weight:800;
                color:#fff;letter-spacing:-1px;">Alacant Barris</div>
    <div style="font-size:13px;color:rgba(255,255,255,0.5);margin-top:6px;letter-spacing:.3px;">
      <?= $lang==='ca' ? "Recursos urbans per barri" : "Recursos urbanos por barrio" ?>
    </div>
  </div>

  <!-- Fet per -->
  <div style="margin-top:24px;text-align:center;">
    <div style="font-size:10px;color:rgba(255,255,255,0.35);
                letter-spacing:2px;text-transform:uppercase;margin-bottom:6px;">
      <?= $lang==='ca' ? 'Fet per' : 'Hecho por' ?>
    </div>
    <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700;
                color:#3b82f6;letter-spacing:1.5px;">AKRA TECH STUDIO</div>
  </div>

  <!-- Barra de progrés -->
  <div style="position:absolute;bottom:48px;width:140px;height:2px;
              background:rgba(255,255,255,0.1);border-radius:2px;overflow:hidden;">
    <div id="splash-bar" style="height:100%;width:0%;background:#3b82f6;
                                border-radius:2px;transition:width 1.6s linear;"></div>
  </div>
</div>

<header class="header">
  <div class="header-brand">
    <div class="header-logo">🏛️</div>
    <div>
      <div class="header-title">Alacant Barris</div>
      <div class="header-sub"><?= $t['title'] ?></div>
    </div>
  </div>
  <div class="header-actions">
    <a href="https://akratechstudio.es" target="_blank" rel="noopener"
       style="font-family:var(--font-display);font-size:9px;font-weight:800;
              color:var(--text2);text-decoration:none;letter-spacing:.8px;
              opacity:.7;white-space:nowrap;display:none;" id="akra-header-link">
      AKRA<br>TECH
    </a>
    <button class="btn-icon" onclick="toggleTheme()" id="btn-theme" title="Tema">🌙</button>
    <button class="btn-icon" onclick="toggleLang()" id="btn-lang"><?= $lang==='ca'?'ES':'CA' ?></button>
    <button class="btn-icon" onclick="switchTab('peticions');switchNav(null);" title="<?= $t['peticions'] ?>" id="btn-bell">
      🔔<span class="badge" id="notif-badge" style="display:none">0</span>
    </button>
  </div>
</header>

<nav class="tab-nav">
  <button class="tab-btn active" onclick="switchTab('mapa');switchNav(0)">🗺️ <?= $t['mapa'] ?></button>
  <button class="tab-btn" onclick="switchTab('dashboard');switchNav(1)">📊 <?= $t['resum'] ?></button>
  <button class="tab-btn" onclick="switchTab('barris');switchNav(2)">🏘️ <?= $t['barris'] ?></button>
  <button class="tab-btn" onclick="switchTab('recursos');switchNav(3)">📋 <?= $t['recursos'] ?></button>
  <button class="tab-btn" onclick="switchTab('afegir');switchNav(4)">➕ <?= $t['afegir'] ?></button>
  <button class="tab-btn" onclick="switchTab('peticions');switchNav(5)">📝 <?= $t['peticions'] ?></button>
</nav>

<main class="main">

  <div id="tab-mapa" class="tab-panel active">
    <div style="position:relative;">
      <div id="map"></div>
      <div class="map-stats">
        <div class="stat-chip"><span>🏘️</span> <span id="map-barri-count">—</span> <?= $t['barris_lbl'] ?></div>
        <div class="stat-chip" style="color:var(--danger)"><span>⚠️</span> <span id="map-miss-count">—</span> <?= $t['manc_lbl'] ?></div>
      </div>
      <div id="map-dist-filter" style="display:none"></div>
      <div class="map-legend" id="map-legend">
        <h4><?= $t['leg_estat'] ?></h4>
        <div class="legend-item"><div class="legend-dot" style="background:#10b981"></div> <?= $t['leg_cobert'] ?></div>
        <div class="legend-item"><div class="legend-dot" style="background:#f59e0b"></div> <?= $t['leg_parcial'] ?></div>
        <div class="legend-item"><div class="legend-dot" style="background:#ef4444"></div> <?= $t['leg_mancat'] ?></div>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border);">
          <h4 style="margin-bottom:6px;"><?= $t['leg_pet'] ?></h4>
          <div class="legend-item"><div style="width:12px;height:12px;border-radius:50%;background:#475569;flex-shrink:0;border:2px solid rgba(255,255,255,0.3)"></div> <?= $t['leg_cap'] ?></div>
          <div class="legend-item"><div style="width:12px;height:12px;border-radius:50%;background:#ef4444;flex-shrink:0;border:2px solid rgba(255,255,255,0.3)"></div> <?= $t['leg_amb'] ?></div>
        </div>
      </div>
    </div>
  </div>

  <div id="tab-dashboard" class="tab-panel scrollable">
    <div class="panel-content">
      <h2 class="section-title"><?= $t['vis_general'] ?></h2>
      <p class="section-subtitle"><?= $t['vis_sub'] ?></p>
      <div class="stats-grid">
        <div class="stat-card" style="--color:var(--accent)">
          <span class="stat-icon">🏘️</span>
          <div class="stat-num">19</div>
          <div class="stat-label"><?= $t['stat_barris'] ?></div>
        </div>
        <div class="stat-card" style="--color:var(--danger)">
          <span class="stat-icon">⚠️</span>
          <div class="stat-num" id="total-miss">—</div>
          <div class="stat-label"><?= $t['stat_miss'] ?></div>
        </div>
        <div class="stat-card" style="--color:var(--success)">
          <span class="stat-icon">✅</span>
          <div class="stat-num" id="total-cov">—</div>
          <div class="stat-label"><?= $t['stat_cob'] ?></div>
        </div>
        <div class="stat-card" style="--color:var(--warn)">
          <span class="stat-icon">📝</span>
          <div class="stat-num" id="total-pet">—</div>
          <div class="stat-label"><?= $t['peticions'] ?></div>
        </div>
      </div>
      <h3 style="font-family:var(--font-display);font-size:16px;font-weight:700;margin-bottom:12px;"><?= $t['cob_cat'] ?></h3>
      <div id="category-overview"></div>
      <h3 style="font-family:var(--font-display);font-size:16px;font-weight:700;margin:20px 0 12px;"><?= $t['manc_mes'] ?></h3>
      <div id="worst-barris"></div>
    </div>
  </div>

  <div id="tab-barris" class="tab-panel scrollable">
    <div class="panel-content">
      <h2 class="section-title"><?= $t['barris_title'] ?></h2>
      <p class="section-subtitle"><?= $t['barris_sub'] ?></p>
      <div class="search-wrap">
        <span class="search-icon">🔍</span>
        <input class="form-control" type="text" placeholder="<?= $t['cerca_ph'] ?>" id="search-barri" oninput="filterBarris(this.value)">
      </div>
      <div class="barri-list" id="barri-list"></div>
    </div>
  </div>

  <div id="tab-recursos" class="tab-panel scrollable">
    <div class="panel-content">
      <h2 class="section-title"><?= $t['rec_title'] ?></h2>
      <p class="section-subtitle"><?= $t['rec_sub'] ?></p>
      <div class="filter-pills" id="filter-pills"></div>
      <div class="recursos-grid" id="recursos-grid"></div>
    </div>
  </div>

  <div id="tab-afegir" class="tab-panel scrollable">
    <div class="panel-content">
      <h2 class="section-title"><?= $t['form_title'] ?></h2>
      <p class="section-subtitle"><?= $t['form_sub'] ?></p>
      <div class="form-card">
        <h3>📍 <?= $t['form_info'] ?></h3>
        <div class="form-group">
          <label class="form-label"><?= $t['form_barri'] ?></label>
          <select class="form-control" id="form-barri"><option value=""><?= $t['form_sel_barri'] ?></option></select>
        </div>
        <div class="form-group">
          <label class="form-label"><?= $t['form_tipus'] ?></label>
          <select class="form-control" id="form-tipus"><option value=""><?= $t['form_sel_cat'] ?></option></select>
        </div>
        <div class="form-group">
          <label class="form-label">Títol breu</label>
          <input class="form-control" type="text" id="form-titol" placeholder="<?= $t['titol_ph'] ?>" maxlength="200">
        </div>
        <div class="form-group">
          <label class="form-label">Descripció</label>
          <textarea class="form-control" id="form-desc" placeholder="<?= $t['form_desc_ph'] ?>"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label"><?= $t['form_prio'] ?></label>
          <div class="priority-grid">
            <div class="priority-opt alta" onclick="selectPrio(this,'alta')"><?= $t['prio_alta'] ?></div>
            <div class="priority-opt mitja selected" onclick="selectPrio(this,'mitja')"><?= $t['prio_mitja'] ?></div>
            <div class="priority-opt baixa" onclick="selectPrio(this,'baixa')"><?= $t['prio_baixa'] ?></div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label"><?= $t['form_email'] ?></label>
          <input class="form-control" type="email" id="form-email" placeholder="<?= $t['form_email_ph'] ?>">
        </div>
      </div>
      <button class="btn-primary" id="btn-submit" onclick="submitPeticio()"><?= $t['form_send'] ?></button>
    </div>
  </div>

  <div id="tab-peticions" class="tab-panel scrollable">
    <div class="panel-content">
      <h2 class="section-title"><?= $t['pet_title'] ?></h2>
      <p class="section-subtitle"><?= $t['pet_stat_sub'] ?></p>
      <div class="filter-pills" id="status-pills">
        <div class="pill active" onclick="filterStatus('tot',this)"><?= $t['pet_tot'] ?></div>
        <div class="pill" onclick="filterStatus('pendent',this)"><?= $t['pet_pend'] ?></div>
        <div class="pill" onclick="filterStatus('process',this)"><?= $t['pet_proc'] ?></div>
        <div class="pill" onclick="filterStatus('resolt',this)"><?= $t['pet_res'] ?></div>
      </div>
      <div id="peticions-list"></div>
    </div>
  </div>

</main>

<nav class="bottom-nav">
  <button class="nav-item active" id="nav-0" onclick="switchTab('mapa');switchNav(0)">
    <span class="nav-icon">🗺️</span>Mapa
  </button>
  <button class="nav-item" id="nav-1" onclick="switchTab('dashboard');switchNav(1)">
    <span class="nav-icon">📊</span>Resum
  </button>
  <button class="nav-item" id="nav-2" onclick="switchTab('barris');switchNav(2)">
    <span class="nav-icon">🏘️</span>Barris
  </button>
  <button class="nav-item" id="nav-3" onclick="switchTab('recursos');switchNav(3)">
    <span class="nav-icon">📋</span>Recursos
  </button>
  <button class="nav-item" id="nav-4" onclick="switchTab('afegir');switchNav(4)">
    <span class="nav-icon">➕</span><?= $t['afegir'] ?>
  </button>
</nav>

<div class="toast" id="toast"></div>

<script>
const CATEGORIES = <?= $CATS_JSON ?>;

const BARRIS = <?= $BARRIS_JSON ?>;

// peticions carregades des de BD via API


const LANG = '<?= $lang ?>';
const T = {
  toast_ok:    '<?= $t['toast_ok'] ?>',
  toast_err:   '<?= $t['toast_err'] ?>',
  toast_camps: '<?= $t['toast_camps'] ?>',
  barris:      '<?= $t['barris_lbl'] ?>',
  mancances:   '<?= $t['manc_lbl'] ?>',
  pet_tot:     '<?= $t['pet_tot'] ?>',
  loading:     '<?= $t['loading'] ?>',
  cap_pet:     '<?= $t['cap_pet'] ?>',
  cap_barri:   '<?= $t['cap_barri'] ?>',
  mancat:      '<?= $t['mancat_lbl'] ?>',
  parcial:     '<?= $t['parcial_lbl'] ?>',
  tot_cobert:  '<?= $t['tot_cobert'] ?>',
  evol_title:  '<?= $t['evol_title'] ?>',
  dist_title:  '<?= $t['dist_title'] ?>',
  topmiss:     '<?= $t['topmiss_title'] ?>',
  export_csv:  '<?= $t['export_csv'] ?>',
  copy_link:   '<?= $t['copy_link'] ?>',
  cobertura:   '<?= $lang==="ca"?"Cobertura":"Cobertura" ?>',
  peticions_n: '<?= $t['peticions'] ?>',
  llegenda_tot:'<?= $lang==="ca"?"Tots":"Todos" ?>',
};
// Normalitzem propietats BD vs hardcoded
CATEGORIES.forEach(function(c){
  c.icon  = c.icon  || c.icona || '📌';
  c.label = c.label || c.nom   || '';
});
BARRIS.forEach(function(b){
  b.recursos = b.recursos || {};
});
// variables inicialitzades al JS

// ── UTILS ──
function getScore(b) {
  const vals = Object.values(b.recursos);
  const s = vals.reduce((a,v)=>a+(v==='ok'?1:v==='partial'?0.5:0),0);
  return Math.round(s/vals.length*100);
}
function getMissing(b) {
  return Object.entries(b.recursos)
    .filter(([,v])=>v==='missing'||v==='partial')
    .map(([k,v])=>({cat:CATEGORIES.find(c=>c.id===k),status:v}));
}
function scoreColor(s) {
  return s>=70?'var(--success)':s>=40?'var(--warn)':'var(--danger)';
}
function statusLabel(s) {
  return s==='pendent'?'⏳ Pendent':s==='process'?'🔄 En procés':'✅ Resolt';
}
function showToast(msg,color='var(--success)') {
  const t=document.getElementById('toast');
  t.textContent=msg; t.style.background=color;
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),3000);
}
function totalMissing() {
  return BARRIS.reduce((a,b)=>a+Object.values(b.recursos).filter(v=>v==='missing').length,0);
}
function avgCoverage() {
  return Math.round(BARRIS.reduce((a,b)=>a+getScore(b),0)/BARRIS.length);
}

// ── NAV ──
function switchTab(name) {
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  const tabs=['mapa','dashboard','barris','recursos','afegir','peticions'];
  const idx=tabs.indexOf(name);
  if(idx!==-1) document.querySelectorAll('.tab-btn')[idx].classList.add('active');
  if(name==='mapa'&&map) setTimeout(()=>map.invalidateSize(),100);
  if(name==='peticions') renderPeticions();
  const bar = document.getElementById('dist-filter-bar');
  if(bar) bar.style.display = name==='mapa' ? 'flex' : 'none';
}
function switchNav(i) {
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  const nav=document.getElementById('nav-'+i);
  if(nav) nav.classList.add('active');
}

// ── UTILS ──
function getScore(b) {
  const vals = Object.values(b.recursos||{});
  if (!vals.length) return null;
  const s = vals.reduce((a,v)=>a+(v==='ok'?1:v==='partial'?0.5:0),0);
  return Math.round(s/vals.length*100);
}
function getMissing(b) {
  return Object.entries(b.recursos||{})
    .filter(([,v])=>v==='missing'||v==='partial')
    .map(([slug,status])=>({cat:CATEGORIES.find(c=>c.slug===slug), status}))
    .filter(x=>x.cat);
}
function scoreColor(s) {
  if (s===null) return 'var(--text2)';
  return s>=70?'var(--success)':s>=40?'var(--warn)':'var(--danger)';
}
function statusLabel(s) {
  return {pendent:'⏳ Pendent',process:'🔄 En procés',resolt:'✅ Resolt',rebutjat:'🚫 Rebutjat'}[s]||s;
}
function showToast(msg,color) {
  const t=document.getElementById('toast');
  t.textContent=msg; t.style.background=color||'var(--success)';
  t.classList.add('show'); setTimeout(()=>t.classList.remove('show'),3000);
}

// ── NAV ──
function switchTab(name) {
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  const panel = document.getElementById('tab-'+name);
  if (panel) panel.classList.add('active');
  const idx = ['mapa','dashboard','barris','recursos','afegir','peticions'].indexOf(name);
  const btns = document.querySelectorAll('.tab-btn');
  if (btns[idx]) btns[idx].classList.add('active');
  if (name==='mapa' && map) setTimeout(()=>map.invalidateSize(),100);
  if (name==='peticions') renderPeticions();
  const bar = document.getElementById('dist-filter-bar');
  if (bar) bar.style.display = name==='mapa' ? 'flex' : 'none';
}
function switchNav(i) {
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  const nav = document.getElementById('nav-'+i);
  if (nav) nav.classList.add('active');
}

// ── MAP ──
let map, allMapMarkers = [];

function initMap() {
  map = L.map('map',{zoomControl:false,tap:true}).setView([38.345,-0.490],13);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{
    attribution:'© OpenStreetMap © CARTO',subdomains:'abcd',maxZoom:19
  }).addTo(map);
  L.control.zoom({position:'bottomright'}).addTo(map);

  let totalMiss = 0;

  BARRIS.forEach(function(b) {
    const score    = getScore(b);
    const missing  = getMissing(b);
    const missCount = Object.values(b.recursos||{}).filter(v=>v==='missing').length;
    const nPet     = b.npeticions || 0;
    totalMiss += missCount;

    const ring = L.circleMarker([b.lat,b.lng],{
      radius:24, fillColor:b.color,
      color:b.color, weight:2, opacity:0.6, fillOpacity:0.15
    }).addTo(map);

    const label = score===null
      ? b.nom.split(' ').map(function(w){return w[0]||'';}).join('').substring(0,3).toUpperCase()
      : score;

    const badgeHtml = '<div style="position:absolute;top:-6px;right:-6px;min-width:17px;height:17px;background:'
      +(nPet>0?'#ef4444':'#475569')+';border-radius:50%;display:flex;align-items:center;justify-content:center;'
      +'font-size:8px;color:#fff;font-weight:800;box-shadow:0 1px 8px rgba(0,0,0,0.5);'
      +'border:2px solid rgba(255,255,255,0.8);pointer-events:none;padding:0 3px;">'+nPet+'</div>';

    const icon = L.divIcon({
      html: '<div style="position:relative;width:34px;height:34px;">'
        +'<div style="width:34px;height:34px;background:linear-gradient(135deg,'+b.color+'dd,'+b.color+'99);'
        +'border:2px solid '+b.color+';border-radius:50%;display:flex;align-items:center;justify-content:center;'
        +'font-family:\'Syne\',sans-serif;font-size:9px;font-weight:800;color:#fff;'
        +'box-shadow:0 2px 16px '+b.color+'66;cursor:pointer;">'+label+'</div>'
        +badgeHtml+'</div>',
      iconSize:[34,34], iconAnchor:[17,17], className:''
    });

    const marker = L.marker([b.lat,b.lng],{icon}).addTo(map);
    allMapMarkers.push({marker:marker, ring:ring, area:b.area});

    const missRows = missing.slice(0,5).map(function(m){
      return '<div class="popup-row '+(m.status==='missing'?'miss':'')+'">'
        +'<span>'+(m.cat.icona||'')+'</span> '+(m.cat.nom||'')
        +'<span style="margin-left:auto;font-size:10px;font-weight:600;color:'
        +(m.status==='missing'?'#ef4444':'#f59e0b')+'">'
        +(m.status==='missing'?T.mancat:T.parcial)+'</span></div>';
    }).join('');

    const pop = '<div style="min-width:220px;">'
      +'<div class="popup-name"><span style="width:12px;height:12px;background:'+b.color
      +';border-radius:50%;display:inline-block;flex-shrink:0;"></span> '+b.nom
      +'<span class="popup-badge" style="background:'+b.color+'22;color:'+b.color
      +';border:1px solid '+b.color+'44;">'+b.area+'</span></div>'
      +(score!==null?'<div class="popup-row"><span>📊</span>Cobertura: <strong style="color:'+scoreColor(score)+';margin-left:auto;">'+score+'%</strong></div>'
        +'<div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:'+score+'%;background:'+b.color+'"></div></div>':'')
      +(nPet>0?'<div class="popup-row" style="margin-top:6px;"><span>📝</span>Peticions: <strong style="margin-left:auto;color:#f59e0b">'+nPet+'</strong></div>':'')
      +(missing.length?'<div style="margin-top:8px;font-size:11px;color:var(--text2);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Mancances</div>'+missRows
        :'<div style="color:#6ee7b7;font-size:12px;margin-top:8px;">✅ '+T.tot_cobert+'</div>')
      +'</div>';

    marker.bindPopup(pop,{maxWidth:280,offset:[0,-10]});
  });

  document.getElementById('map-barri-count').textContent = BARRIS.length;
  document.getElementById('map-miss-count').textContent  = totalMiss;

  // Filtre districtes
  const districtes = {};
  BARRIS.forEach(function(b) {
    if (!districtes[b.area]) districtes[b.area] = b.color;
  });

  const bar = document.getElementById('dist-filter-bar');

  function makePill(text, color, onClick) {
    const p = document.createElement('div');
    p.className = 'pill';
    p.style.cssText = 'cursor:pointer;font-size:11px;padding:5px 12px;flex-shrink:0;'
      +(color?'border-color:'+color+';':'');
    p.innerHTML = color
      ? '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:'+color+';margin-right:5px;vertical-align:middle;"></span>'+text
      : text;
    p.onclick = onClick;
    return p;
  }

  const allPill = makePill(T.llegenda_tot, null, function() {
    bar.querySelectorAll('.pill').forEach(p=>p.classList.remove('active'));
    allPill.classList.add('active');
    allMapMarkers.forEach(function(m){
      m.marker.setOpacity(1);
      m.ring.setStyle({opacity:0.6,fillOpacity:0.15});
    });
  });
  allPill.classList.add('active');
  bar.appendChild(allPill);

  Object.keys(districtes).sort(function(a,b){
    return parseInt(a.replace(/\D/g,''))-parseInt(b.replace(/\D/g,''));
  }).forEach(function(nom) {
    const color = districtes[nom];
    const num   = nom.replace('Districte ','D');
    const pill  = makePill(num, color, function() {
      bar.querySelectorAll('.pill').forEach(p=>p.classList.remove('active'));
      pill.classList.add('active');
      allMapMarkers.forEach(function(m){
        const vis = m.area === nom;
        m.marker.setOpacity(vis?1:0.12);
        m.ring.setStyle({opacity:vis?0.6:0.04,fillOpacity:vis?0.15:0.02});
      });
      const pts = allMapMarkers.filter(m=>m.area===nom).map(m=>m.marker.getLatLng());
      if (pts.length) map.fitBounds(L.latLngBounds(pts),{padding:[50,50]});
    });
    bar.appendChild(pill);
  });

  bar.style.display = 'flex';
}

// ── DASHBOARD ──
function renderDashboard() {
  const totalBarris = BARRIS.length;
  let totalMiss=0, totalOk=0, totalRec=0;
  BARRIS.forEach(function(b){
    Object.values(b.recursos||{}).forEach(function(v){
      totalRec++;
      if(v==='ok') totalOk++;
      if(v==='missing') totalMiss++;
    });
  });
  const cov = totalRec>0 ? Math.round(totalOk/totalRec*100) : 0;

  const elMiss = document.getElementById('total-miss');
  const elCov  = document.getElementById('total-cov');
  const elBar  = document.getElementById('total-barris');
  if(elMiss) elMiss.textContent = totalMiss;
  if(elCov)  elCov.textContent  = cov+'%';
  if(elBar)  elBar.textContent  = totalBarris;

  // Categories overview
  const catEl = document.getElementById('category-overview');
  if(catEl) catEl.innerHTML = CATEGORIES.map(function(cat){
    const counts={ok:0,partial:0,missing:0};
    BARRIS.forEach(function(b){
      const v=(b.recursos||{})[cat.slug];
      if(v) counts[v]=(counts[v]||0)+1;
    });
    const total=counts.ok+counts.partial+counts.missing||1;
    const pct=Math.round((counts.ok+counts.partial*0.5)/total*100);
    return '<div style="margin-bottom:14px;">'
      +'<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">'
      +'<div style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;">'
      +'<span>'+(cat.icona||'')+'</span>'+(cat.nom||'')+'</div>'
      +'<div style="font-size:12px;">'
      +'<span style="color:var(--success)">✓'+counts.ok+'</span> '
      +'<span style="color:var(--warn)">~'+counts.partial+'</span> '
      +'<span style="color:var(--danger)">✗'+counts.missing+'</span></div></div>'
      +'<div style="background:var(--border);border-radius:4px;height:8px;overflow:hidden;">'
      +'<div style="height:100%;border-radius:4px;background:'+cat.color+';width:'+pct+'%;"></div></div></div>';
  }).join('');

  // Pitjors barris
  const worstEl = document.getElementById('worst-barris');
  if(worstEl) {
    const sorted=[...BARRIS].filter(b=>getScore(b)!==null).sort((a,b)=>getScore(a)-getScore(b)).slice(0,5);
    worstEl.innerHTML = sorted.map(function(b){
      const s=getScore(b);
      return '<div class="peticio-item" style="cursor:pointer;" onclick="switchTab(\'barris\');switchNav(2);">'
        +'<div style="width:10px;height:10px;background:'+b.color+';border-radius:50%;flex-shrink:0;margin-top:4px;"></div>'
        +'<div class="peticio-content">'
        +'<div class="peticio-title">'+b.nom+' <span style="font-size:11px;color:var(--text2);">'+b.area+'</span></div>'
        +'<div style="margin-top:6px;background:var(--border);border-radius:4px;height:5px;overflow:hidden;">'
        +'<div style="height:100%;border-radius:4px;background:'+scoreColor(s)+';width:'+s+'%;"></div></div></div>'
        +'<div style="font-family:var(--font-display);font-size:14px;font-weight:800;color:'+scoreColor(s)+'">'+s+'%</div></div>';
    }).join('') || '<div style="color:var(--text2);font-size:13px;">No hi ha dades de cobertura</div>';
  }
}

// ── BARRIS ──
function renderBarriList(filter) {
  filter = filter||'';
  const list=document.getElementById('barri-list');
  if(!list) return;
  const filtered=BARRIS.filter(function(b){
    return b.nom.toLowerCase().includes(filter.toLowerCase())||b.area.toLowerCase().includes(filter.toLowerCase());
  });
  if(!filtered.length){
    list.innerHTML='<div class="empty-state"><div class="empty-icon">🔍</div><p>'+T.cap_barri+'</p></div>';
    return;
  }
  list.innerHTML=filtered.map(function(b){
    const score=getScore(b);
    const missing=getMissing(b);
    const missCount=missing.filter(function(m){return m.status==='missing';}).length;
    const circ=44*2*Math.PI;
    const offset=score!==null?circ-(score/100)*circ:circ;
    return '<div class="barri-card" id="barri-'+b.id+'" onclick="toggleBarri(\''+b.id+'\')">'
      +'<div class="barri-card-header">'
      +'<div class="barri-color-bar" style="background:'+b.color+'"></div>'
      +'<div class="barri-info">'
      +'<div class="barri-name">'+b.nom+'</div>'
      +'<div class="barri-area">'+b.area+' · '+missCount+' manc.</div></div>'
      +'<div class="score-ring">'
      +'<svg viewBox="0 0 48 48">'
      +'<circle class="track" cx="24" cy="24" r="20" stroke-dasharray="'+circ+'" stroke-dashoffset="0"/>'
      +'<circle class="fill" cx="24" cy="24" r="20" stroke-dasharray="'+circ+'" stroke-dashoffset="'+offset+'" style="stroke:'+b.color+'"/>'
      +'</svg>'
      +'<div class="score-num">'+(score!==null?score+'%':'—')+'</div></div>'
      +'<div class="barri-chevron">▼</div></div>'
      +'<div class="barri-needs">'
      +missing.map(function(m){return '<div class="need-tag '+m.status+'">'+(m.cat.icona||'')+' '+(m.cat.nom||'')+'</div>';}).join('')
      +(missing.length===0?'<div class="need-tag ok">✅ '+T.tot_cobert+'</div>':'')
      +'</div></div>';
  }).join('');
}
function toggleBarri(id){document.getElementById('barri-'+id).classList.toggle('expanded');}
function filterBarris(v){renderBarriList(v);}

// ── RECURSOS ──
let currentRecursFilter='tot';
function renderRecursos() {
  const fp=document.getElementById('filter-pills');
  if(!fp) return;
  fp.innerHTML='<div class="pill active" onclick="filterRecursos(\'tot\',this)">'+T.pet_tot+'</div>'
    +CATEGORIES.map(function(c){
      return '<div class="pill" onclick="filterRecursos(\''+c.slug+'\',this)">'+(c.icona||'')+' '+(c.nom||'')+'</div>';
    }).join('');
  renderRecursosGrid();
}
function renderRecursosGrid() {
  const el=document.getElementById('recursos-grid');
  if(!el) return;
  const cats=currentRecursFilter==='tot'?CATEGORIES:CATEGORIES.filter(function(c){return c.slug===currentRecursFilter;});
  el.innerHTML=cats.map(function(cat){
    const counts={ok:0,partial:0,missing:0};
    BARRIS.forEach(function(b){
      const v=(b.recursos||{})[cat.slug];
      if(v) counts[v]=(counts[v]||0)+1;
    });
    const total=counts.ok+counts.partial+counts.missing||1;
    const pct=Math.round((counts.ok+counts.partial*0.5)/total*100);
    return '<div class="recurs-card">'
      +'<span class="recurs-icon">'+(cat.icona||'')+'</span>'
      +'<div class="recurs-name">'+(cat.nom||'')+'</div>'
      +'<div style="margin:8px 0 6px;font-size:12px;color:var(--text2);">'
      +'<span style="color:var(--success)">✓'+counts.ok+'</span> '
      +'<span style="color:var(--warn)">~'+counts.partial+'</span> '
      +'<span style="color:var(--danger)">✗'+counts.missing+'</span></div>'
      +'<div style="display:flex;align-items:center;gap:6px;">'
      +'<div class="recurs-bar"><div class="recurs-bar-fill" style="width:'+pct+'%;background:'+cat.color+'"></div></div>'
      +'<span style="flex-shrink:0;font-weight:600;font-size:12px;color:'+cat.color+'">'+pct+'%</span>'
      +'</div></div>';
  }).join('');
}
function filterRecursos(cat,el){
  currentRecursFilter=cat;
  document.querySelectorAll('#filter-pills .pill').forEach(function(p){p.classList.remove('active');});
  if(el) el.classList.add('active');
  renderRecursosGrid();
}

// ── FORM ──
let currentPrio='mitja';
function initForm() {
  const sel=document.getElementById('form-barri');
  if(!sel) return;
  BARRIS.forEach(function(b){
    const opt=document.createElement('option');
    opt.value=b.id; opt.textContent=b.nom; sel.appendChild(opt);
  });
  const ts=document.getElementById('form-tipus');
  CATEGORIES.forEach(function(c){
    const opt=document.createElement('option');
    opt.value=c.id; opt.textContent=(c.icona||'')+' '+(c.nom||''); ts.appendChild(opt);
  });
}
function selectPrio(el,prio){
  document.querySelectorAll('.priority-opt').forEach(function(p){p.classList.remove('selected');});
  el.classList.add('selected'); currentPrio=prio;
}
function submitPeticio(){
  const barriSel=document.getElementById('form-barri');
  const tipusSel=document.getElementById('form-tipus');
  const titolEl=document.getElementById('form-titol');
  const titol=(titolEl?titolEl.value.trim():'')||'Nova petició';
  const desc=document.getElementById('form-desc').value.trim();
  const email=document.getElementById('form-email').value.trim();
  if(!barriSel.value||!tipusSel.value||desc.length<5){
    showToast('⚠️ Emplena tots els camps obligatoris','var(--warn)');return;
  }
  const btn=document.getElementById('btn-submit');
  if(btn) btn.disabled=true;
  fetch('index.php?ajax=peticio_create',{
    method:'POST', headers:{'Content-Type':'application/json'},
    body:JSON.stringify({
      barri_id:parseInt(barriSel.value), categoria_id:parseInt(tipusSel.value),
      titol:titol, descripcio:desc, prioritat:currentPrio, email:email
    })
  })
  .then(function(r){return r.json();})
  .then(function(res){
    if(res.ok){
      barriSel.value=''; tipusSel.value='';
      document.getElementById('form-desc').value='';
      document.getElementById('form-email').value='';
      if(titolEl) titolEl.value='';
      document.querySelectorAll('.priority-opt').forEach(function(p){p.classList.remove('selected');});
      document.querySelector('.priority-opt.mitja').classList.add('selected');
      currentPrio='mitja';
      showToast('✅ Sol·licitud enviada correctament!');
      setTimeout(function(){switchTab('peticions');switchNav(null);},1500);
    } else {
      showToast('❌ '+(res.error||'Error'),'var(--danger)');
    }
  })
  .catch(function(){showToast(T.toast_err,'var(--danger)');})
  .finally(function(){if(btn) btn.disabled=false;});
}

// ── PETICIONS ──
let _petPage=1, _petSt='';
function renderPeticions(){
  const list=document.getElementById('peticions-list');
  if(!list) return;
  list.innerHTML='<div style="padding:40px;text-align:center;color:var(--text2)">'+T.loading+'</div>';
  fetch('index.php?ajax=peticions&page='+_petPage+'&st='+encodeURIComponent(_petSt))
  .then(function(r){return r.json();})
  .then(function(res){
    if(!res.ok||!res.data||!res.data.length){
      list.innerHTML='<div class="empty-state"><div class="empty-icon">📭</div><p>'+T.cap_pet+'</p></div>';
      return;
    }
    var html='';
    for(var i=0;i<res.data.length;i++){
      var p=res.data[i];
      var pc=p.prioritat==='alta'?'var(--danger)':p.prioritat==='mitja'?'var(--warn)':'var(--success)';
      var dt=new Date(p.creat_en).toLocaleDateString('ca-ES');
      html+='<div class="peticio-item">'
        +'<div class="peticio-prio" style="background:'+pc+';margin-top:3px;"></div>'
        +'<div class="peticio-content">'
        +'<div class="peticio-title">'+(p.cat_icona||'')+' '+(p.cat_nom||'')+' — '+(p.barri_nom||'')+'</div>'
        +'<div style="font-size:13px;font-weight:500;margin:2px 0 4px;">'+(p.titol||'')+'</div>'
        +'<div style="font-size:12px;color:var(--text2);margin-bottom:8px;">'+(p.descripcio||'').substring(0,90)+'</div>'
        +'<div style="display:flex;flex-wrap:wrap;gap:6px;">'
        +'<span class="peticio-tag">'+statusLabel(p.estat)+'</span>'
        +'<span class="peticio-tag">📅 '+dt+'</span>'
        +'<span class="peticio-tag" style="color:'+pc+'">'+p.prioritat.toUpperCase()+'</span>'
        +(p.votos>0?'<span class="peticio-tag">👍 '+p.votos+'</span>':'')
        +'</div></div></div>';
    }
    list.innerHTML=html;
    const totalPages=Math.ceil(res.total/20);
    const pag=document.getElementById('pet-pagination');
    if(pag) pag.innerHTML=totalPages>1
      ?(_petPage>1?'<button class="pill" onclick="_petPage--;renderPeticions()">← Anterior</button>':'')
       +'<span class="pill active">'+_petPage+' / '+totalPages+'</span>'
       +(_petPage<totalPages?'<button class="pill" onclick="_petPage++;renderPeticions()">Següent →</button>':'')
      :'';
  })
  .catch(function(){
    list.innerHTML='<div class="empty-state"><div class="empty-icon">❌</div><p>'+T.toast_err+'</p></div>';
  });
}
function filterStatus(status,el){
  _petSt=(status==='tot'||!status)?'':status;
  _petPage=1;
  document.querySelectorAll('#status-pills .pill').forEach(function(p){p.classList.remove('active');});
  if(el) el.classList.add('active');
  renderPeticions();
}

// ── TEMA I IDIOMA ──
function toggleTheme() {
  const isLight = document.documentElement.classList.toggle('light');
  localStorage.setItem('theme', isLight?'light':'dark');
  document.getElementById('btn-theme').textContent = isLight?'☀️':'🌙';
  if (map) {
    // Canviem els tiles del mapa
    map.eachLayer(function(l){ if(l._url) map.removeLayer(l); });
    L.tileLayer(isLight
      ? 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png'
      : 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
      {attribution:'© OpenStreetMap © CARTO',subdomains:'abcd',maxZoom:19}
    ).addTo(map);
  }
}
function toggleLang() {
  const next = document.cookie.includes('lang=es') ? 'ca' : 'es';
  document.cookie = 'lang='+next+';path=/;max-age=31536000';
  location.reload();
}

// ── INIT ──
document.addEventListener('DOMContentLoaded',function(){
  // Tema guardat
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme==='light') {
    document.documentElement.classList.add('light');
    document.getElementById('btn-theme').textContent='☀️';
  }
  initMap();
  renderDashboard();
  renderBarriList();

  // Splash: mostrem 1.8s i desapareixem
  const splash = document.getElementById('splash');
  const bar    = document.getElementById('splash-bar');
  if (splash) {
    setTimeout(function(){ if(bar) bar.style.width='100%'; }, 50);
    setTimeout(function(){
      splash.style.opacity='0';
      setTimeout(function(){ splash.style.display='none'; }, 600);
    }, 1900);
  }
  renderRecursos();
  initForm();
  document.querySelector('.priority-opt.mitja').classList.add('selected');
});
</script>
<div id="dist-filter-bar" style="
  position:fixed;
  bottom:68px;
  left:0;right:0;
  z-index:900;
  display:none;
  padding:6px 12px;
  background:rgba(10,14,26,0.85);
  backdrop-filter:blur(12px);
  border-top:1px solid rgba(42,63,95,0.6);
  overflow-x:auto;
  scrollbar-width:none;
  white-space:nowrap;
  gap:6px;
" id="dist-pills-bar">
</div>
  <div style="position:fixed;bottom:68px;left:0;right:0;text-align:center;
    padding:4px 0;font-size:10px;color:var(--text2);
    background:rgba(10,14,26,0.75);backdrop-filter:blur(6px);
    z-index:800;border-top:1px solid rgba(255,255,255,0.04);">
    © <?= date('Y') ?> &nbsp;
    <a href="https://akratechstudio.es" target="_blank" rel="noopener"
      style="color:var(--accent);text-decoration:none;font-weight:800;
             letter-spacing:.8px;font-family:var(--font-display);">AKRA TECH STUDIO</a>
  </div>
<script>
  // ── PWA: Service Worker ──
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
      navigator.serviceWorker.register('/barris/sw.js', {scope: '/barris/'})
        .then(r => console.log('[SW] Registrat:', r.scope))
        .catch(e => console.warn('[SW] Error:', e));
    });
  }

  // ── Splash ──
  (function() {
    const splash = document.getElementById('splash');
    const bar    = document.getElementById('splash-bar');
    if (!splash) return;
    // Apliquem tema guardat immediatament (evita flash)
    if (localStorage.getItem('theme') === 'light') {
      splash.style.background = '#f0f4f8';
    }
    setTimeout(function(){ if(bar) bar.style.width = '100%'; }, 60);
    setTimeout(function(){
      splash.style.opacity = '0';
      splash.style.pointerEvents = 'none';
      setTimeout(function(){ splash.remove(); }, 520);
    }, 1800);
  })();
</script>
</body>
</html>

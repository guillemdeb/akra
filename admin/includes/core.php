<?php
// admin/includes/core.php
// Motor del panel d'administració — sense base de dades MySQL
if (defined('ADMIN_CORE_LOADED')) return;
define('ADMIN_CORE_LOADED', true);
// Tota la informació s'emmagatzema en fitxers JSON a /admin/data/

if (session_status() === PHP_SESSION_NONE) session_start();

// Carrega config principal del lloc (SITE_URL, constants, getSlots, etc.)
if (file_exists(dirname(__DIR__, 2) . '/includes/config.php')) {
    require_once dirname(__DIR__, 2) . '/includes/config.php';
}

define('ADMIN_ROOT',    dirname(__DIR__));
define('DATA_DIR',      ADMIN_ROOT . '/data/');
define('UPLOADS_DIR',   ADMIN_ROOT . '/uploads/');
define('AKRA_ROOT',     dirname(ADMIN_ROOT));
define('ADMIN_VERSION', '2.0');

// ─── SEGURETAT ──────────────────────────────────────────────────────────────
define('ADMIN_PASSWORD_HASH', password_hash('akra2024admin', PASSWORD_DEFAULT));
// ↑ CANVIA 'akra2024admin' per la teua contrasenya segura ABANS de pujar al servidor

define('DOMAIN_SECRET_KEY', 'canvia-esta-clau-per-una-de-llarga-i-secreta-abans-de-pujar');
// ↑ Clau usada per xifrar els usuaris/contrasenyes de dominis guardats a les fitxes
//   de client. CANVIA-LA per un text llarg i aleatori abans de pujar al servidor.
//   Si la canvies DESPRÉS d'haver guardat credencials, eixes credencials antigues
//   ja no es podran desxifrar (hauràs de tornar a introduir-les).

function requireLogin() {
    if (!isset($_SESSION['akra_admin']) || $_SESSION['akra_admin'] !== true) {
        header('Location: ' . ADMIN_ROOT . '/login.php');
        exit;
    }
}

function login($password, $username = '') {
    $username = trim($username);
    $users = readData('users');

    // Si hi ha usuaris donats d'alta, comprovem contra la llista d'usuaris.
    if (!empty($users)) {
        foreach ($users as $u) {
            if (strcasecmp($u['username'], $username) === 0 && password_verify($password, $u['password_hash'])) {
                $_SESSION['akra_admin']      = true;
                $_SESSION['akra_admin_ip']   = $_SERVER['REMOTE_ADDR'];
                $_SESSION['akra_login_time'] = time();
                $_SESSION['akra_user_id']    = $u['id'];
                $_SESSION['akra_user_name']  = $u['name'] ?: $u['username'];
                $_SESSION['akra_user_role']  = $u['role'] ?? 'admin';
                return true;
            }
        }
        return false;
    }

    // Compatibilitat: si encara no s'ha creat cap usuari, seguim admetent la
    // contrasenya única de sempre (sense usuari), com fins ara.
    $auth = readData('auth');
    $hash = $auth['password_hash'] ?? ADMIN_PASSWORD_HASH;

    if (password_verify($password, $hash)) {
        $_SESSION['akra_admin']      = true;
        $_SESSION['akra_admin_ip']   = $_SERVER['REMOTE_ADDR'];
        $_SESSION['akra_login_time'] = time();
        $_SESSION['akra_user_name']  = 'Admin';
        $_SESSION['akra_user_role']  = 'admin';
        return true;
    }
    return false;
}

function getCurrentUser() {
    return ['name' => $_SESSION['akra_user_name'] ?? 'Admin', 'role' => $_SESSION['akra_user_role'] ?? 'admin'];
}

function getUsers() { return readData('users'); }

function getUser($id) {
    foreach (readData('users') as $u) if ($u['id'] === $id) return $u;
    return null;
}

function saveUser($user) {
    $users = readData('users');
    if (!empty($user['password_plain'])) $user['password_hash'] = password_hash($user['password_plain'], PASSWORD_DEFAULT);
    unset($user['password_plain']);
    $idx = array_search($user['id'], array_column($users, 'id'));
    if ($idx !== false) $users[$idx] = array_merge($users[$idx], $user);
    else $users[] = $user;
    writeData('users', $users);
    return $user;
}

function deleteUser($id) {
    writeData('users', array_values(array_filter(readData('users'), fn($u) => $u['id'] !== $id)));
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['akra_admin']) && $_SESSION['akra_admin'] === true;
}

// ─── GESTIÓ DE FITXERS JSON ─────────────────────────────────────────────────
function readData($key) {
    $file = DATA_DIR . $key . '.json';
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

function writeData($key, $data) {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    $file = DATA_DIR . $key . '.json';
    $result = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($result === false) {
        error_log("AKRA writeData ERROR: no s'ha pogut escriure $file");
    }
    return $result !== false;
}

// ─── PAPERERA (SOFT DELETE) ──────────────────────────────────────────────────
// En compte d'esborrar els registres de veres, els marquem amb 'deleted_at'.
// Les llistes normals (getClients, getInvoices...) ja no els mostren, però
// resten recuperables des de la Paperera durant 30 dies abans de purgar-se.

// Tipus d'entitat que tenen paperera, amb com mostrar-los a la llista de la paperera.
function trashableTypes() {
    return [
        'clients'   => ['label' => 'Client',    'name_field' => 'name',        'url' => 'clients.php?id='],
        'invoices'  => ['label' => 'Factura',   'name_field' => 'number',      'url' => 'invoices.php?id='],
        'proposals' => ['label' => 'Proposta',  'name_field' => 'description', 'url' => 'proposals.php?id='],
        'audits'    => ['label' => 'Auditoria', 'name_field' => 'client_id',   'url' => 'audits.php?id='],
        'jobs'      => ['label' => 'Treball',   'name_field' => 'title',       'url' => null],
        'domains'   => ['label' => 'Domini',    'name_field' => 'domain',      'url' => null],
        'contacts'  => ['label' => 'Contacte',  'name_field' => 'message',     'url' => null],
        'payments'  => ['label' => 'Pagament',  'name_field' => 'amount',      'url' => null],
        'time_entries' => ['label' => 'Registre d\'hores', 'name_field' => 'hours', 'url' => null],
    ];
}

// Llig un magatzem de dades ocultant els registres esborrats (paperera).
function readActiveData($key) {
    return array_values(array_filter(readData($key), fn($r) => empty($r['deleted_at'])));
}

function softDeleteRecord($key, $id) {
    $items = readData($key);
    foreach ($items as &$item) {
        if ($item['id'] === $id) { $item['deleted_at'] = date('Y-m-d H:i:s'); break; }
    }
    unset($item);
    writeData($key, $items);
}

function restoreRecord($key, $id) {
    $items = readData($key);
    foreach ($items as &$item) {
        if ($item['id'] === $id) { unset($item['deleted_at']); break; }
    }
    unset($item);
    writeData($key, $items);
}

function purgeRecord($key, $id) {
    writeData($key, array_values(array_filter(readData($key), fn($r) => $r['id'] !== $id)));
}

// Recopila tots els registres esborrats de tots els tipus, per mostrar-los a la Paperera.
function getTrashedRecords() {
    $all = [];
    foreach (trashableTypes() as $key => $meta) {
        foreach (readData($key) as $r) {
            if (empty($r['deleted_at'])) continue;
            $client = !empty($r['client_id']) ? getClient($r['client_id']) : null;
            $label = $r[$meta['name_field']] ?? '(sense nom)';
            if ($key === 'audits') $label = ($client['name'] ?? 'Client') . ' — ' . ($r['date'] ?? '');
            if ($key === 'payments') $label = number_format($r['amount'] ?? 0, 2, ',', '.') . ' €';
            if ($key === 'contacts' || $key === 'proposals') $label = mb_substr((string)$label, 0, 60);
            $all[] = [
                'type' => $key, 'type_label' => $meta['label'], 'id' => $r['id'],
                'label' => $label, 'client_name' => $client['name'] ?? '',
                'deleted_at' => $r['deleted_at'],
            ];
        }
    }
    usort($all, fn($a, $b) => strcmp($b['deleted_at'], $a['deleted_at']));
    return $all;
}

// Purga definitivament tot allò que porte més de $days dies a la paperera
// (pensat per executar-se des d'un cron diari).
function purgeOldTrash($days = 30) {
    $limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $purged = 0;
    foreach (array_keys(trashableTypes()) as $key) {
        $items = readData($key);
        $before = count($items);
        $items = array_values(array_filter($items, fn($r) => empty($r['deleted_at']) || $r['deleted_at'] > $limit));
        $purged += $before - count($items);
        writeData($key, $items);
    }
    return $purged;
}

function generateId() {
    return time() . '_' . bin2hex(random_bytes(4));
}

// ─── SERVEIS ────────────────────────────────────────────────────────────────
function getAdminServices() {
    $data = readData('services');
    if (empty($data)) {
        // Inicialitza amb els serveis per defecte si és la primera vegada
        $data = getDefaultServices();
        writeData('services', $data);
    }
    usort($data, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
    return $data;
}

function saveService($service) {
    $services = getAdminServices();
    $idx = array_search($service['id'], array_column($services, 'id'));
    if ($idx !== false) {
        $services[$idx] = $service;
    } else {
        $services[] = $service;
    }
    writeData('services', $services);
    syncServicesToConfig();
    return true;
}

function deleteService($id) {
    $services = getAdminServices();
    $services = array_values(array_filter($services, fn($s) => $s['id'] !== $id));
    writeData('services', $services);
    syncServicesToConfig();
}

function reorderServices($ids) {
    $services = getAdminServices();
    $indexed = array_column($services, null, 'id');
    $reordered = [];
    foreach ($ids as $i => $id) {
        if (isset($indexed[$id])) {
            $indexed[$id]['order'] = $i + 1;
            $reordered[] = $indexed[$id];
        }
    }
    writeData('services', $reordered);
    syncServicesToConfig();
}

// ─── PROJECTES ──────────────────────────────────────────────────────────────
function getProjectTypes() {
    $data = readData('project_types');
    if (empty($data)) {
        // Tipus per defecte (compatibles amb els projectes existents)
        $data = [
            ['id'=>'web',       'order'=>1, 'active'=>true, 'label'=>['ca'=>'Webs',       'es'=>'Webs',        'en'=>'Web',      'fr'=>'Sites web',  'it'=>'Siti web']],
            ['id'=>'ecommerce', 'order'=>2, 'active'=>true, 'label'=>['ca'=>'E-commerce', 'es'=>'E-commerce',  'en'=>'E-commerce','fr'=>'E-commerce', 'it'=>'E-commerce']],
            ['id'=>'marketing', 'order'=>3, 'active'=>true, 'label'=>['ca'=>'Màrqueting', 'es'=>'Marketing',   'en'=>'Marketing', 'fr'=>'Marketing',  'it'=>'Marketing']],
            ['id'=>'design',    'order'=>4, 'active'=>true, 'label'=>['ca'=>'Disseny',    'es'=>'Diseño',      'en'=>'Design',    'fr'=>'Design',     'it'=>'Design']],
            ['id'=>'app',       'order'=>5, 'active'=>true, 'label'=>['ca'=>'Apps',       'es'=>'Apps',        'en'=>'Apps',      'fr'=>'Apps',       'it'=>'App']],
        ];
        writeData('project_types', $data);
    }
    usort($data, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
    return $data;
}

function getActiveProjectTypes() {
    return array_values(array_filter(getProjectTypes(), fn($t) => $t['active'] ?? true));
}

function saveProjectType($type) {
    $types = getProjectTypes();
    $idx = array_search($type['id'], array_column($types, 'id'));
    if ($idx !== false) $types[$idx] = $type;
    else $types[] = $type;
    writeData('project_types', $types);
}

function deleteProjectType($id) {
    $types = getProjectTypes();
    $types = array_values(array_filter($types, fn($t) => $t['id'] !== $id));
    writeData('project_types', $types);
}

function reorderProjectTypes($ids) {
    $types = getProjectTypes();
    $indexed = array_column($types, null, 'id');
    $reordered = [];
    foreach ($ids as $i => $id) {
        if (isset($indexed[$id])) {
            $indexed[$id]['order'] = $i + 1;
            $reordered[] = $indexed[$id];
        }
    }
    writeData('project_types', $reordered);
}

function getAdminProjects($category = null) {
    $projects = readData('projects');
    if ($category) $projects = array_filter($projects, fn($p) => $p['category'] === $category);
    usort($projects, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return array_values($projects);
}

function saveProject($project) {
    $projects = readData('projects');
    $idx = array_search($project['id'], array_column($projects, 'id'));
    if ($idx !== false) {
        $projects[$idx] = $project;
    } else {
        $projects[] = $project;
    }
    writeData('projects', $projects);
    syncProjectsToConfig();
    return true;
}

function deleteProject($id) {
    $projects = readData('projects');
    // Eliminar imatge si existeix
    $project = array_filter($projects, fn($p) => $p['id'] === $id);
    $project = array_values($project)[0] ?? null;
    if ($project && !empty($project['thumbnail']) && file_exists(AKRA_ROOT . '/' . $project['thumbnail'])) {
        @unlink(AKRA_ROOT . '/' . $project['thumbnail']);
    }
    $projects = array_values(array_filter($projects, fn($p) => $p['id'] !== $id));
    writeData('projects', $projects);
    syncProjectsToConfig();
}

function getProjectById($id) {
    $projects = readData('projects');
    foreach ($projects as $p) if ($p['id'] === $id) return $p;
    return null;
}

// ─── TESTIMONIS ─────────────────────────────────────────────────────────────
function getAdminTestimonials() {
    $data = readData('testimonials');
    if (empty($data)) {
        $data = getDefaultTestimonials();
        writeData('testimonials', $data);
    }
    return $data;
}

function saveTestimonial($t) {
    $all = getAdminTestimonials();
    $idx = array_search($t['id'], array_column($all, 'id'));
    if ($idx !== false) $all[$idx] = $t;
    else $all[] = $t;
    writeData('testimonials', $all);
    syncTestimonialsToConfig();
    return true;
}

function deleteTestimonial($id) {
    $all = getAdminTestimonials();
    $all = array_values(array_filter($all, fn($t) => $t['id'] !== $id));
    writeData('testimonials', $all);
    syncTestimonialsToConfig();
}

// ─── CONFIGURACIÓ GENERAL ────────────────────────────────────────────────────
function getAdminConfig() {
    $data = readData('site_config');
    $defaults = [
        'site_name'    => 'AKRA Tech Studio',
        'site_url'     => 'https://akratechstudio.es',
        'phone'        => '+34 600 000 000',
        'email'        => 'hola@akratechstudio.es',
        'address'      => '',
        'maps_url'     => 'https://maps.google.com/?q=Alacant',
        'instagram'    => '#',
        'linkedin'     => '#',
        'facebook'     => '#',
        'tiktok'       => '#',
        'ga_id'        => '',
        'gtm_id'       => '',
        'hero_title_1' => 'Webs que',
        'hero_title_2' => 'venen.',
        'hero_title_3' => 'Marques que',
        'hero_title_4' => 'es recorden.',
        'hero_subtitle'=> 'Disseny web professional, SEO local i màrqueting digital per a empreses d\'Alacant i la Costa Blanca.',
        'hero_image'   => '',
        'stat_projects'=> '50',
        'stat_years'   => '5',
        'maintenance'  => false,
        'slots_total'  => 5,
        'slots_used'   => 0,
        'slots_show'   => true,
        'payment_link' => '',
        'whatsapp_number'       => '34683279162',
        'whatsapp_float_public' => true,
        'whatsapp_float_hub'    => true,
        'whatsapp_float_message'=> 'Hola! Tinc una consulta',
        'notify_client_email'   => true,
        'wa_notify_provider'    => '', // '', 'twilio' o 'meta' — buit = encara no connectat
        'wa_notify_twilio_sid'      => '',
        'wa_notify_twilio_token'    => '',
        'wa_notify_twilio_from'     => '',
        'wa_notify_meta_token'      => '',
        'wa_notify_meta_phone_id'   => '',
        'cookie_banner_enabled' => true,
        'cookie_consent_days'   => 365,
        'auto_backup_enabled'         => false,
        'auto_backup_retention_days'  => 30,
        'auto_backup_sections'        => [], // buit = totes les seccions
    ];
    return array_merge($defaults, $data);
}

function saveAdminConfig($data) {
    writeData('site_config', $data);
    syncConfigToPhp($data);
}

// ─── MISSATGES DE CONTACTE ──────────────────────────────────────────────────
function getMessages($unread_only = false) {
    $msgs = readData('messages');
    if ($unread_only) $msgs = array_filter($msgs, fn($m) => !($m['read'] ?? false));
    usort($msgs, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return array_values($msgs);
}

function saveMessage($data) {
    $msgs = readData('messages');
    $msgs[] = array_merge($data, ['id' => generateId(), 'date' => date('c'), 'read' => false]);
    writeData('messages', $msgs);
}

function markMessageRead($id) {
    $msgs = readData('messages');
    foreach ($msgs as &$m) if ($m['id'] === $id) $m['read'] = true;
    writeData('messages', $msgs);
}

function deleteMessage($id) {
    $msgs = readData('messages');
    $msgs = array_values(array_filter($msgs, fn($m) => $m['id'] !== $id));
    writeData('messages', $msgs);
}

// ─── PUJADA D'IMATGES ────────────────────────────────────────────────────────
function uploadGalleryImages($files, $existing_gallery = []) {
    $gallery = $existing_gallery;
    if (empty($files['name'][0])) return $gallery;
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if (empty($files['name'][$i])) continue;
        $file = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];
        $result = uploadImage($file, 'gallery');
        if ($result['ok']) $gallery[] = $result['path'];
    }
    return $gallery;
}

function deleteGalleryImage($project_id, $img_path) {
    $project = getProjectById($project_id);
    if (!$project) return;
    $gallery = $project['gallery'] ?? [];
    $gallery = array_values(array_filter($gallery, fn($g) => $g !== $img_path));
    $project['gallery'] = $gallery;
    // Delete file
    $full = AKRA_ROOT . '/' . $img_path;
    if (file_exists($full)) @unlink($full);
    saveProject($project);
}

function uploadImage($file, $subfolder = 'projects') {
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
    if (!in_array($file['type'], $allowed)) return ['ok' => false, 'msg' => 'Format no permès'];
    if ($file['size'] > 5 * 1024 * 1024) return ['ok' => false, 'msg' => 'Mida màxima 5MB'];

    $dir = UPLOADS_DIR . $subfolder . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateId() . '.' . strtolower($ext);
    $dest     = $dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => true, 'path' => 'admin/uploads/' . $subfolder . '/' . $filename];
    }
    return ['ok' => false, 'msg' => 'Error en pujar el fitxer'];
}

// Pujada genèrica de documents (PDF o imatge), per exemple la factura de
// compra d'un domini/hosting al registrador.
function uploadDocument($file, $subfolder = 'documents') {
    $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed)) return ['ok' => false, 'msg' => 'Format no permès (només PDF o imatge)'];
    if ($file['size'] > 8 * 1024 * 1024) return ['ok' => false, 'msg' => 'Mida màxima 8MB'];

    $dir = UPLOADS_DIR . $subfolder . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateId() . '.' . strtolower($ext);
    $dest     = $dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => true, 'path' => 'admin/uploads/' . $subfolder . '/' . $filename];
    }
    return ['ok' => false, 'msg' => 'Error en pujar el fitxer'];
}

// ─── SINCRONITZACIÓ AMB config.php ──────────────────────────────────────────
// Quan es modifica algo al panel, s'actualitza el config.php automàticament
// perquè el frontend el lliga en temps real

function syncProjectsToConfig() {
    $projects = readData('projects');
    $activeProjects = array_values(array_filter($projects, fn($p) => $p['active'] ?? true));
    $config_file = AKRA_ROOT . '/includes/config.php';
    
    if (!file_exists($config_file)) return;
    $content = file_get_contents($config_file);
    
    // Genera el PHP array dels projectes
    $php = "\n\$projects_db = [\n";
    foreach ($activeProjects as $p) {
        $title_ca = addslashes($p['title']['ca'] ?? '');
        $title_es = addslashes($p['title']['es'] ?? '');
        $desc_ca  = addslashes($p['description']['ca'] ?? '');
        $desc_es  = addslashes($p['description']['es'] ?? '');
        $results_ca = addslashes($p['results']['ca'] ?? '');
        $results_es = addslashes($p['results']['es'] ?? '');
        $thumb    = addslashes($p['thumbnail'] ?? '');
        $url      = addslashes($p['url'] ?? '');
        $video    = addslashes($p['video'] ?? '');
        $date     = addslashes($p['date'] ?? '');
        $cat      = addslashes($p['category'] ?? 'web');
        $status   = addslashes($p['status'] ?? 'active');
        $featured = ($p['featured'] ?? false) ? 'true' : 'false';
        $tech_str = "'" . implode("','", array_map('addslashes', $p['tech'] ?? [])) . "'";
        
        $php .= "    [\n";
        $php .= "        'id' => '{$p['id']}',\n";
        $php .= "        'slug' => '" . addslashes($p['slug'] ?? '') . "',\n";
        $php .= "        'category' => '{$cat}',\n";
        $php .= "        'status' => '{$status}',\n";
        $php .= "        'featured' => {$featured},\n";
        $php .= "        'title' => ['ca' => '{$title_ca}', 'es' => '{$title_es}'],\n";
        $php .= "        'description' => ['ca' => '{$desc_ca}', 'es' => '{$desc_es}'],\n";
        $php .= "        'results' => ['ca' => '{$results_ca}', 'es' => '{$results_es}'],\n";
        $php .= "        'thumbnail' => '{$thumb}',\n";
        $php .= "        'url' => '{$url}',\n";
        $php .= "        'video' => " . ($video ? "'{$video}'" : 'null') . ",\n";
        $php .= "        'tech' => [{$tech_str}],\n";
        $php .= "        'date' => '{$date}',\n";
        $php .= "        'active' => true,\n";
        $php .= "    ],\n";
    }
    $php .= "];\n";
    
    // Substitueix el bloc $projects_db existent
    $content = preg_replace(
        '/\n\$projects_db\s*=\s*\[.*?\];\n/s',
        $php,
        $content
    );
    file_put_contents($config_file, $content);
}

function syncTestimonialsToConfig() {
    $testimonials = readData('testimonials');
    $active = array_values(array_filter($testimonials, fn($t) => $t['active'] ?? true));
    $config_file = AKRA_ROOT . '/includes/config.php';
    if (!file_exists($config_file)) return;
    $content = file_get_contents($config_file);
    
    $php = "\n\$testimonials_db = [\n";
    foreach ($active as $i => $t) {
        $name_ca = addslashes($t['name']['ca'] ?? $t['name']['es'] ?? '');
        $name_es = addslashes($t['name']['es'] ?? '');
        $company_ca = addslashes($t['company']['ca'] ?? $t['company']['es'] ?? '');
        $company_es = addslashes($t['company']['es'] ?? '');
        $text_ca = addslashes($t['text']['ca'] ?? $t['text']['es'] ?? '');
        $text_es = addslashes($t['text']['es'] ?? '');
        
        $php .= "    [\n";
        $php .= "        'id' => " . ($i+1) . ",\n";
        $php .= "        'name' => ['ca' => '{$name_ca}', 'es' => '{$name_es}'],\n";
        $php .= "        'company' => ['ca' => '{$company_ca}', 'es' => '{$company_es}'],\n";
        $php .= "        'text' => ['ca' => '{$text_ca}', 'es' => '{$text_es}'],\n";
        $php .= "        'rating' => 5,\n";
        $php .= "        'active' => true,\n";
        $php .= "    ],\n";
    }
    $php .= "];\n";
    
    $content = preg_replace('/\n\$testimonials_db\s*=\s*\[.*?\];\n/s', $php, $content);
    file_put_contents($config_file, $content);
}

function syncServicesToConfig() {
    $services = getAdminServices();
    $config_file = AKRA_ROOT . '/includes/config.php';
    if (!file_exists($config_file)) return;
    $content = file_get_contents($config_file);
    
    $php = "\n\$services_db = [\n";
    foreach ($services as $s) {
        if (!($s['active'] ?? true)) continue;
        $icon = addslashes($s['icon_svg'] ?? '');
        $t_ca = addslashes($s['title']['ca'] ?? '');
        $t_es = addslashes($s['title']['es'] ?? '');
        $t_en = addslashes($s['title']['en'] ?? '');
        $d_ca = addslashes($s['desc_short']['ca'] ?? '');
        $d_es = addslashes($s['desc_short']['es'] ?? '');
        $d_en = addslashes($s['desc_short']['en'] ?? '');
        $h_ca = !empty($s['highlight']['ca']) ? "'ca' => '" . addslashes($s['highlight']['ca']) . "', 'es' => '" . addslashes($s['highlight']['es'] ?? '') . "'" : null;
        
        $php .= "    [\n";
        $php .= "        'id' => " . ($s['id']) . ",\n";
        $php .= "        'slug' => '" . addslashes($s['slug'] ?? '') . "',\n";
        $php .= "        'icon_svg' => '" . $icon . "',\n";
        $php .= "        'title' => ['ca' => '{$t_ca}', 'es' => '{$t_es}', 'en' => '{$t_en}'],\n";
        $php .= "        'desc_short' => ['ca' => '{$d_ca}', 'es' => '{$d_es}', 'en' => '{$d_en}'],\n";
        $php .= "        'highlight' => " . ($h_ca ? "[{$h_ca}]" : 'null') . ",\n";
        $php .= "        'order' => " . ($s['order'] ?? 99) . ",\n";
        $php .= "        'active' => true,\n";
        $php .= "    ],\n";
    }
    $php .= "];\n";
    
    $content = preg_replace('/\n\$services_db\s*=\s*\[.*?\];\n/s', $php, $content);
    file_put_contents($config_file, $content);
}

function syncConfigToPhp($cfg) {
    // Ja no cal reescriure config.php — les constants es llegeixen
    // directament de site_config.json en cada petició.
    return true;
}

// ─── DADES PER DEFECTE ──────────────────────────────────────────────────────
function getDefaultServices() {
    return [
        ['id' => 1, 'slug' => 'disseny-web', 'order' => 1, 'active' => true,
         'icon_svg' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
         'title' => ['ca' => 'Disseny Web Professional', 'es' => 'Diseño Web Profesional', 'en' => 'Professional Web Design'],
         'desc_short' => ['ca' => 'Webs modernes, ràpides i optimitzades per convertir visitants en clients. SEO-first des del primer dia.', 'es' => 'Webs modernas, rápidas y optimizadas para convertir visitantes en clientes. SEO-first desde el primer día.', 'en' => 'Modern, fast websites optimized to convert visitors into clients.'],
         'highlight' => ['ca' => 'Més demanat', 'es' => 'Más solicitado', 'en' => 'Most requested']],
        ['id' => 2, 'slug' => 'seo-local', 'order' => 2, 'active' => true,
         'icon_svg' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
         'title' => ['ca' => 'SEO Local a Alacant', 'es' => 'SEO Local en Alicante', 'en' => 'Local SEO in Alicante'],
         'desc_short' => ['ca' => 'Apareix el primer a Google quan els teus clients busquen el que ofereixes.', 'es' => 'Aparece primero en Google cuando tus clientes buscan lo que ofreces.', 'en' => 'Appear first on Google when your clients search.'],
         'highlight' => ['ca' => 'Resultats visibles', 'es' => 'Resultados visibles', 'en' => 'Visible results']],
        ['id' => 3, 'slug' => 'marketing-digital', 'order' => 3, 'active' => true,
         'icon_svg' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
         'title' => ['ca' => 'Màrqueting Digital', 'es' => 'Marketing Digital', 'en' => 'Digital Marketing'],
         'desc_short' => ['ca' => 'Google Ads, Meta Ads i email màrqueting orientats a resultats mesurables.', 'es' => 'Google Ads, Meta Ads y email marketing orientados a resultados medibles.', 'en' => 'Google Ads, Meta Ads and email marketing focused on measurable results.'],
         'highlight' => null],
        ['id' => 4, 'slug' => 'disseny-grafic', 'order' => 4, 'active' => true,
         'icon_svg' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>',
         'title' => ['ca' => 'Disseny Gràfic i Branding', 'es' => 'Diseño Gráfico y Branding', 'en' => 'Graphic Design & Branding'],
         'desc_short' => ['ca' => 'Identitat visual que fa destacar la teua marca al mercat.', 'es' => 'Identidad visual que hace destacar tu marca en el mercado.', 'en' => 'Visual identity that makes your brand stand out.'],
         'highlight' => null],
        ['id' => 5, 'slug' => 'xarxes-socials', 'order' => 5, 'active' => true,
         'icon_svg' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
         'title' => ['ca' => 'Xarxes Socials', 'es' => 'Redes Sociales', 'en' => 'Social Media'],
         'desc_short' => ['ca' => 'Gestió professional de xarxes que connecta amb la teua audiència.', 'es' => 'Gestión profesional de redes que conecta con tu audiencia.', 'en' => 'Professional social media management.'],
         'highlight' => null],
        ['id' => 6, 'slug' => 'ecommerce', 'order' => 6, 'active' => true,
         'icon_svg' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>',
         'title' => ['ca' => 'E-commerce', 'es' => 'E-commerce', 'en' => 'E-commerce'],
         'desc_short' => ['ca' => 'Botigues online que venen les 24h. WooCommerce, Shopify o solució a mida.', 'es' => 'Tiendas online que venden 24h. WooCommerce, Shopify o solución a medida.', 'en' => 'Online stores that sell 24/7. WooCommerce, Shopify or custom solution.'],
         'highlight' => null],
    ];
}

function getDefaultTestimonials() {
    return [
        ['id' => generateId(), 'active' => true,
         'name' => ['ca' => 'Maria García', 'es' => 'Maria García'],
         'company' => ['ca' => 'Clínica Dental Alacant', 'es' => 'Clínica Dental Alicante'],
         'text' => ['ca' => 'Des que AKRA ens va redissenyar la web i va optimitzar el SEO local, hem triplicat les trucades mensuals.', 'es' => 'Desde que AKRA nos rediseñó la web y optimizó el SEO local, hemos triplicado las llamadas mensuales.']],
        ['id' => generateId(), 'active' => true,
         'name' => ['ca' => 'Joan Martínez', 'es' => 'Juan Martínez'],
         'company' => ['ca' => 'Restaurant Sa Cuina · Benidorm', 'es' => 'Restaurante Sa Cuina · Benidorm'],
         'text' => ['ca' => 'La nostra botiga online va facturar 40.000€ el primer any. AKRA no és una agència, és un soci de negoci.', 'es' => 'Nuestra tienda online facturó 40.000€ el primer año. AKRA no es una agencia, es un socio de negocio.']],
    ];
}
// ─── NOVES FUNCIONS PER A SEO I CONTINGUT ───────────────────────────────────

function syncSeoToConfig($seo_data) {
    $config_file = AKRA_ROOT . '/includes/seo-config.php';
    $php = "<?php\n// SEO Config generat automàticament\nreturn " . var_export($seo_data, true) . ";\n";
    file_put_contents($config_file, $php);
}

function syncContentToConfig($content) {
    $config_file = AKRA_ROOT . '/includes/content-config.php';
    $php = "<?php\n// Content Config generat automàticament\nreturn " . var_export($content, true) . ";\n";
    file_put_contents($config_file, $php);
}

function getSeoForPage($page_key, $lang = 'ca') {
    $seo_file = AKRA_ROOT . '/includes/seo-config.php';
    if (!file_exists($seo_file)) return null;
    $seo = require $seo_file;
    return $seo[$page_key] ?? null;
}

function getContentSection($section, $lang = 'ca') {
    $content_file = AKRA_ROOT . '/includes/content-config.php';
    if (!file_exists($content_file)) return null;
    $content = require $content_file;
    return $content[$section] ?? null;
}

// ─── BLOG ────────────────────────────────────────────────────────────────────

function getAdminPosts() {
    $posts = readData('blog_posts');
    usort($posts, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return $posts;
}

function getPosts($published_only = true, $lang = null, $limit = null) {
    $posts = readData('blog_posts');
    if ($published_only) $posts = array_filter($posts, fn($p) => $p['published'] ?? false);
    usort($posts, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    if ($limit) $posts = array_slice($posts, 0, $limit);
    return array_values($posts);
}

function getPost($slug) {
    $posts = readData('blog_posts');
    foreach ($posts as $p) if ($p['slug'] === $slug) return $p;
    return null;
}

function savePost($post) {
    $posts = readData('blog_posts');
    $idx = array_search($post['id'], array_column($posts, 'id'));
    if ($idx !== false) $posts[$idx] = $post;
    else $posts[] = $post;
    writeData('blog_posts', $posts);
}

function deletePost($id) {
    $posts = readData('blog_posts');
    // Eliminar imatge destacada
    $post = current(array_filter($posts, fn($p) => $p['id'] === $id));
    if ($post && !empty($post['cover']) && file_exists(AKRA_ROOT . '/' . $post['cover'])) {
        @unlink(AKRA_ROOT . '/' . $post['cover']);
    }
    writeData('blog_posts', array_values(array_filter($posts, fn($p) => $p['id'] !== $id)));
}

function slugify($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $from = ['à','á','â','ä','å','è','é','ê','ë','ì','í','î','ï','ò','ó','ô','ö','ù','ú','û','ü','ý','ñ','ç','ł','ð','æ','œ','·','ã','õ'];
    $to   = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','u','u','u','u','y','n','c','l','d','ae','oe','-','a','o'];
    $text = str_replace($from, $to, $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return $text;
}

function getCategories() {
    return [
        'disseny-web'  => ['ca' => 'Disseny Web',       'es' => 'Diseño Web',       'en' => 'Web Design'],
        'seo'          => ['ca' => 'SEO Local',          'es' => 'SEO Local',        'en' => 'Local SEO'],
        'marketing'    => ['ca' => 'Màrqueting Digital', 'es' => 'Marketing Digital','en' => 'Digital Marketing'],
        'negocis'      => ['ca' => 'Negocis Online',     'es' => 'Negocios Online',  'en' => 'Online Business'],
        'eines'        => ['ca' => 'Eines i Recursos',   'es' => 'Herramientas',     'en' => 'Tools & Resources'],
    ];
}

// ─── FACTURACIÓ ──────────────────────────────────────────────────────────────

function getInvoices($client_id = null) {
    $invoices = readActiveData('invoices');
    if ($client_id) $invoices = array_filter($invoices, fn($i) => $i['client_id'] === $client_id);
    usort($invoices, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return array_values($invoices);
}

function getInvoice($id) {
    foreach (readData('invoices') as $i) if ($i['id'] === $id) return $i;
    return null;
}

function saveInvoice($invoice) {
    $invoices = readData('invoices');
    $idx = array_search($invoice['id'], array_column($invoices, 'id'));
    if ($idx !== false) $invoices[$idx] = $invoice;
    else $invoices[] = $invoice;
    writeData('invoices', $invoices);
}

function deleteInvoice($id) {
    softDeleteRecord('invoices', $id);
    // esborra (a la paperera) també l'historial de pagaments lligat a esta factura
    foreach (readData('payments') as $p) {
        if ($p['invoice_id'] === $id && empty($p['deleted_at'])) softDeleteRecord('payments', $p['id']);
    }
}

// ─── HISTORIAL DE PAGAMENTS (per factura) ───────────────────────────────────
// Cada pagament: data, import, mètode i referència, lligat a una factura.
// El total pagat i el pendent es calculen sempre a partir d'estes entrades,
// mai es guarden com a valor fix, per evitar descuadraments.

function getPayments($invoice_id = null) {
    $payments = readActiveData('payments');
    if ($invoice_id) $payments = array_filter($payments, fn($p) => $p['invoice_id'] === $invoice_id);
    usort($payments, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return array_values($payments);
}

// Tots els pagaments registrats (assignats o no a una factura), opcionalment
// filtrats per client. Útil per a la pantalla general de Pagaments.
function getAllPayments($client_id = null) {
    $payments = readActiveData('payments');
    if ($client_id) $payments = array_filter($payments, fn($p) => ($p['client_id'] ?? '') === $client_id);
    usort($payments, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return array_values($payments);
}

// Pagaments rebuts però encara sense assignar a cap factura concreta
// (p. ex. una transferència que has cobrat però encara no saps a quina
// factura correspon, o un pagament a compte).
function getUnassignedPayments($client_id = null) {
    $payments = getAllPayments($client_id);
    return array_values(array_filter($payments, fn($p) => empty($p['invoice_id'])));
}

function getPayment($id) {
    foreach (readData('payments') as $p) if ($p['id'] === $id) return $p;
    return null;
}

function savePayment($payment) {
    $payments = readData('payments');
    $idx = array_search($payment['id'], array_column($payments, 'id'));
    if ($idx !== false) $payments[$idx] = array_merge($payments[$idx], $payment);
    else $payments[] = $payment;
    writeData('payments', $payments);
    if (!empty($payment['invoice_id'])) syncInvoicePaidStatus($payment['invoice_id']);
    return $payment;
}

// Assigna un pagament (fins ara sense factura) a una factura concreta.
function assignPaymentToInvoice($payment_id, $invoice_id) {
    $payments = readData('payments');
    $idx = array_search($payment_id, array_column($payments, 'id'));
    if ($idx === false) return false;
    $payments[$idx]['invoice_id'] = $invoice_id;
    writeData('payments', $payments);
    syncInvoicePaidStatus($invoice_id);
    return true;
}

// Desfà l'assignació (per si t'has equivocat de factura) i recalcula
// l'estat de cobrament de la factura que deixa de tindre este pagament.
function unassignPaymentFromInvoice($payment_id) {
    $payments = readData('payments');
    $idx = array_search($payment_id, array_column($payments, 'id'));
    if ($idx === false) return false;
    $prev_invoice_id = $payments[$idx]['invoice_id'] ?? '';
    $payments[$idx]['invoice_id'] = '';
    writeData('payments', $payments);
    if ($prev_invoice_id) {
        $inv = getInvoice($prev_invoice_id);
        // Si la factura s'havia marcat "paid" gràcies a este pagament, la tornem a "sent"
        if ($inv && ($inv['status'] ?? '') === 'paid') {
            $summary = invoicePaymentSummary($inv);
            if ($summary['status'] !== 'paid') { $inv['status'] = 'sent'; saveInvoice($inv); }
        }
    }
    return true;
}

function deletePayment($id) {
    $p = getPayment($id);
    softDeleteRecord('payments', $id);
    if ($p && !empty($p['invoice_id'])) syncInvoicePaidStatus($p['invoice_id']);
}

function getPaymentMethodOptions() {
    return [
        'transferencia' => 'Transferència bancària',
        'targeta'       => 'Targeta',
        'bizum'         => 'Bizum',
        'efectiu'       => 'Efectiu',
        'paypal'        => 'PayPal',
        'altres'        => 'Altres',
    ];
}

// Retorna el resum econòmic d'una factura: total, pagat, pendent, % i estat de cobrament.
function invoicePaymentSummary($invoice) {
    $total = (float)($invoice['total'] ?? 0);
    $paid  = array_sum(array_map(fn($p) => (float)($p['amount'] ?? 0), getPayments($invoice['id'])));
    $paid  = round($paid, 2);
    $due   = round($total - $paid, 2);
    $pct   = $total > 0 ? min(100, round(($paid / $total) * 100)) : 0;
    $status = $due <= 0 && $total > 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
    return compact('total', 'paid', 'due', 'pct', 'status');
}

// Si la factura ja està totalment cobrada amb els pagaments registrats,
// actualitza automàticament el seu estat a "paid" (llevat que estiga cancel·lada).
function syncInvoicePaidStatus($invoice_id) {
    $inv = getInvoice($invoice_id);
    if (!$inv || ($inv['status'] ?? '') === 'cancelled') return;
    $summary = invoicePaymentSummary($inv);
    if ($summary['status'] === 'paid' && $inv['status'] !== 'paid') {
        $inv['status'] = 'paid';
        saveInvoice($inv);
    }
}

function getClients() {
    $clients = readActiveData('clients');
    usort($clients, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
    return array_values($clients);
}

function getClient($id) {
    foreach (readData('clients') as $c) if ($c['id'] === $id) return $c;
    return null;
}

function saveClient($client) {
    $clients = readData('clients');
    $idx = array_search($client['id'], array_column($clients, 'id'));
    if ($idx !== false) $clients[$idx] = $client;
    else $clients[] = $client;
    writeData('clients', $clients);
}

function deleteClient($id) {
    softDeleteRecord('clients', $id);
    // envia també a la paperera l'historial de contactes, treballs i dominis lligats a este client
    foreach (readData('contacts') as $r) if ($r['client_id'] === $id && empty($r['deleted_at'])) softDeleteRecord('contacts', $r['id']);
    foreach (readData('jobs') as $r)     if ($r['client_id'] === $id && empty($r['deleted_at'])) softDeleteRecord('jobs', $r['id']);
    foreach (readData('domains') as $r)  if ($r['client_id'] === $id && empty($r['deleted_at'])) softDeleteRecord('domains', $r['id']);
}

// ─── CONTACTES (historial de comunicacions amb el client) ──────────────────
// Cada entrada guarda: data del contacte, mitjà emprat, què s'ha dit i quina
// ha estat la resposta del client, a més d'un estat i (opcional) data de
// seguiment per no perdre el fil de les converses.

function getContacts($client_id = null) {
    $contacts = readActiveData('contacts');
    if ($client_id) $contacts = array_filter($contacts, fn($c) => $c['client_id'] === $client_id);
    usort($contacts, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return array_values($contacts);
}

function getContact($id) {
    foreach (readData('contacts') as $c) if ($c['id'] === $id) return $c;
    return null;
}

function saveContact($contact) {
    $contacts = readData('contacts');
    $idx = array_search($contact['id'], array_column($contacts, 'id'));
    if ($idx !== false) $contacts[$idx] = array_merge($contacts[$idx], $contact);
    else $contacts[] = $contact;
    writeData('contacts', $contacts);
    return $contact;
}

function deleteContact($id) {
    softDeleteRecord('contacts', $id);
}

// Marca com a llegides (pel client, al Hub) totes les comunicacions que
// l'agència li ha enviat. S'invoca en carregar hub/comunicacions.php.
function markContactsReadByClient($client_id) {
    $contacts = readData('contacts');
    $changed = false;
    foreach ($contacts as &$c) {
        if (($c['client_id'] ?? '') === $client_id && ($c['direction'] ?? '') === 'jo_client' && empty($c['read_by_client'])) {
            $c['read_by_client'] = true;
            $changed = true;
        }
    }
    unset($c);
    if ($changed) writeData('contacts', $contacts);
}

function getContactChannelOptions() {
    return [
        'telefon'    => 'Telèfon',
        'email'      => 'Email',
        'whatsapp'   => 'WhatsApp',
        'presencial' => 'Presencial',
        'video'      => 'Videotrucada',
        'hub'        => 'Portal del client (Hub)',
        'altres'     => 'Altres',
    ];
}

// Contacte bilateral: qui envia el missatge principal d'esta entrada.
// 'jo_client'   → l'has iniciat tu (jo → client)
// 'client_jo'   → l'ha iniciat el client (client → jo)
function getContactDirectionOptions() {
    return [
        'jo_client' => 'Jo → Client',
        'client_jo' => 'Client → Jo',
    ];
}

function contactDirectionLabel($direction) {
    return match($direction) {
        'client_jo' => ['label' => 'Client → Jo', 'icon' => '📥', 'class' => 'badge-blue'],
        default     => ['label' => 'Jo → Client', 'icon' => '📤', 'class' => 'badge-gray'],
    };
}

function getContactStatusOptions() {
    return [
        'pendent'   => 'Pendent de resposta',
        'respost'   => 'Respost',
        'sense_resposta' => 'Sense resposta',
        'tancat'    => 'Tancat',
    ];
}

function contactStatusLabel($status) {
    return match($status) {
        'pendent'        => ['label' => 'Pendent de resposta', 'class' => 'badge-gold'],
        'respost'        => ['label' => 'Respost',             'class' => 'badge-green'],
        'sense_resposta' => ['label' => 'Sense resposta',       'class' => 'badge-red'],
        'tancat'         => ['label' => 'Tancat',               'class' => 'badge-gray'],
        default          => ['label' => $status,                'class' => 'badge-gray'],
    };
}

function nextInvoiceNumber() {
    $invoices = readData('invoices');
    if (empty($invoices)) return 'AKRA-' . date('Y') . '-001';
    $numbers = array_filter(array_map(fn($i) => (int)substr($i['number'] ?? '', -3), $invoices));
    $next = empty($numbers) ? 1 : max($numbers) + 1;
    return 'AKRA-' . date('Y') . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

function invoiceTotals($lines, $tax_pct = 21, $irpf_pct = 0) {
    $subtotal = array_sum(array_map(fn($l) => ($l['qty'] ?? 1) * ($l['price'] ?? 0), $lines));
    $tax      = round($subtotal * ($tax_pct / 100), 2);
    $irpf     = round($subtotal * ($irpf_pct / 100), 2);
    $total    = round($subtotal + $tax - $irpf, 2);
    return compact('subtotal', 'tax', 'irpf', 'total', 'tax_pct', 'irpf_pct');
}

function invoiceStatusLabel($status) {
    return match($status) {
        'draft'   => ['text' => 'Esborrany',  'class' => 'badge-gray'],
        'sent'    => ['text' => 'Enviada',     'class' => 'badge-blue'],
        'paid'    => ['text' => 'Cobrada',     'class' => 'badge-green'],
        'overdue' => ['text' => 'Vençuda',     'class' => 'badge-red'],
        'cancelled'=> ['text'=> 'Cancel·lada', 'class' => 'badge-gray'],
        default   => ['text' => $status,       'class' => 'badge-gray'],
    };
}

// ─── PDF DE FACTURA (Dompdf) ─────────────────────────────────────────────────
// Requereix "composer require dompdf/dompdf" executat a l'arrel del projecte
// (mateixa carpeta on hi ha /admin). Genera un PDF a partir d'una plantilla
// pròpia (admin/invoice_pdf_template.php), separada de la vista d'impressió
// del navegador perquè Dompdf no suporta flexbox ni CSS grid.
function generateInvoicePdf($invoice_id, $lang = 'ca') {
    $autoload = AKRA_ROOT . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        return ['ok' => false, 'error' => 'Falta instal·lar la llibreria PDF. Des d\'una terminal, a la carpeta arrel del projecte (on hi ha la carpeta "admin"), executa: composer require dompdf/dompdf'];
    }
    require_once $autoload;

    $inv    = getInvoice($invoice_id);
    $client = $inv ? getClient($inv['client_id']) : null;
    $cfg    = getAdminConfig();
    if (!$inv || !$client) return ['ok' => false, 'error' => 'Factura o client no trobat'];

    ob_start();
    include ADMIN_ROOT . '/invoice_pdf_template.php';
    $html = ob_get_clean();

    try {
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();
    } catch (\Throwable $e) {
        return ['ok' => false, 'error' => 'Error generant el PDF: ' . $e->getMessage()];
    }

    return [
        'ok'       => true,
        'pdf'      => $dompdf->output(),
        'filename' => 'Factura-' . preg_replace('/[^a-zA-Z0-9-]/', '', $inv['number']) . '.pdf',
    ];
}

// ─── EMAIL DE FACTURA ────────────────────────────────────────────────────────
function sendInvoiceEmail($invoice_id, $to_email, $lang = 'ca') {
    $inv    = getInvoice($invoice_id);
    $client = $inv ? getClient($inv['client_id']) : null;
    $cfg    = getAdminConfig();
    if (!$inv || !$client) return ['ok' => false, 'error' => 'Factura o client no trobat'];

    // Genera el PDF adjunt (mateix idioma que l'email). Si Dompdf no està
    // instal·lat, avortem amb un missatge clar en lloc d'enviar sense adjunt.
    $pdfResult = generateInvoicePdf($invoice_id, $lang);
    if (!$pdfResult['ok']) return $pdfResult;

    $t = invoiceTotals($inv['lines'], $inv['tax_pct'] ?? 21, $inv['irpf_pct'] ?? 0);

    $labels = $lang === 'es' ? [
        'factura'    => 'FACTURA',
        'de'         => 'De',
        'para'       => 'Para',
        'fecha'      => 'Fecha de emisión',
        'venc'       => 'Vencimiento',
        'desc'       => 'Descripción',
        'uds'        => 'Uds.',
        'precio'     => 'Precio unit.',
        'total'      => 'Total',
        'base'       => 'Base imponible',
        'iva'        => 'IVA',
        'irpf'       => 'IRPF',
        'total_f'    => 'TOTAL',
        'pagament'   => 'Forma de pago',
        'obs'        => 'Observaciones',
        'subject'    => 'Factura ' . $inv['number'] . ' de ' . ($cfg['site_name'] ?? 'AKRA Tech Studio'),
        'greeting'   => 'Estimado/a ' . $client['name'] . ',',
        'body'       => 'Adjunto encontrará la factura ' . $inv['number'] . '. Quedo a su disposición para cualquier consulta.',
        'greetings'  => 'Un cordial saludo,',
    ] : [
        'factura'    => 'FACTURA',
        'de'         => 'De',
        'para'       => 'Per a',
        'fecha'      => 'Data d\'emissió',
        'venc'       => 'Venciment',
        'desc'       => 'Descripció',
        'uds'        => 'Ut.',
        'precio'     => 'Preu unit.',
        'total'      => 'Total',
        'base'       => 'Base imposable',
        'iva'        => 'IVA',
        'irpf'       => 'IRPF',
        'total_f'    => 'TOTAL',
        'pagament'   => 'Forma de pagament',
        'obs'        => 'Observacions',
        'subject'    => 'Factura ' . $inv['number'] . ' de ' . ($cfg['site_name'] ?? 'AKRA Tech Studio'),
        'greeting'   => 'Estimat/da ' . $client['name'] . ',',
        'body'       => 'Adjuntem la factura ' . $inv['number'] . '. Quedo a la teua disposició per a qualsevol consulta.',
        'greetings'  => 'Una cordial salutació,',
    ];

    // Construeix HTML de la factura per email
    ob_start();
    include ADMIN_ROOT . '/invoice_email_template.php';
    $html = ob_get_clean();

    $boundary = md5(uniqid());
    $from_name = $cfg['site_name'] ?? 'AKRA Tech Studio';
    $from_email = $cfg['email'] ?? 'hola@akratechstudio.es';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    $headers .= "From: {$from_name} <{$from_email}>\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";
    $headers .= "X-Mailer: AKRA Tech Studio\r\n";

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($html)) . "\r\n";

    // Adjunt: factura en PDF
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: application/pdf; name=\"{$pdfResult['filename']}\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"{$pdfResult['filename']}\"\r\n\r\n";
    $body .= chunk_split(base64_encode($pdfResult['pdf'])) . "\r\n";

    $body .= "--{$boundary}--\r\n";

    $sent = mail($to_email, $labels['subject'], $body, $headers);
    return ['ok' => $sent, 'error' => $sent ? null : 'Error al enviar. Comprova la configuració SMTP del servidor.'];
}

// ─── AUDITORIES ─────────────────────────────────────────────────────────────
// Mòdul CRM intern: auditories web professionals (14 seccions) + accés privat
// del client per enllaç + usuari/contrasenya. Mateix patró de fitxers JSON
// (readData/writeData) que la resta del panell (sense MySQL).

function getAudits($client_id = null) {
    $audits = readActiveData('audits');
    if ($client_id) $audits = array_filter($audits, fn($a) => $a['client_id'] === $client_id);
    usort($audits, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return array_values($audits);
}

function getAudit($id) {
    foreach (readData('audits') as $a) if ($a['id'] === $id) return $a;
    return null;
}

function saveAudit($audit) {
    $audits = readData('audits');
    $idx = array_search($audit['id'], array_column($audits, 'id'));
    if ($idx !== false) $audits[$idx] = array_merge($audits[$idx], $audit);
    else $audits[] = $audit;
    writeData('audits', $audits);
    return $audit;
}

function deleteAudit($id) {
    softDeleteRecord('audits', $id);
    // envia també a la paperera les propostes lligades a esta auditoria
    foreach (readData('proposals') as $p) if (($p['audit_id'] ?? '') === $id && empty($p['deleted_at'])) softDeleteRecord('proposals', $p['id']);
}

// Les 9 categories de la "Valoració final" (secció 14). La nota global és la
// mitjana d'estes 9 puntuacions 0-10 que el consultor assigna a cada àrea.
function getAuditScoreCategories($lang = 'ca') {
    if ($lang === 'es') {
        return [
            'disseny'            => 'Diseño / Primera impresión',
            'seo'                => 'SEO',
            'ux'                 => 'Experiencia de usuario (UX)',
            'accessibilitat'     => 'Accesibilidad',
            'velocitat'          => 'Rendimiento / Velocidad',
            'seguretat'          => 'Seguridad',
            'contingut'          => 'Contenido',
            'conversio'          => 'Conversión / Rendimiento comercial',
            'imatge_corporativa' => 'Imagen corporativa',
        ];
    }
    return [
        'disseny'            => 'Disseny / Primera impressió',
        'seo'                => 'SEO',
        'ux'                 => 'Experiència d\'usuari (UX)',
        'accessibilitat'     => 'Accessibilitat',
        'velocitat'          => 'Rendiment / Velocitat',
        'seguretat'          => 'Seguretat',
        'contingut'          => 'Contingut',
        'conversio'          => 'Conversió / Rendiment comercial',
        'imatge_corporativa' => 'Imatge corporativa',
    ];
}

function auditScoreAvg($audit) {
    $keys = array_keys(getAuditScoreCategories());
    $vals = array_filter(array_map(fn($k) => isset($audit['score_' . $k]) ? (float)$audit['score_' . $k] : null, $keys), fn($v) => $v !== null);
    return $vals ? round(array_sum($vals) / count($vals), 1) : 0;
}

function auditScoreLabel($avg) {
    if ($avg >= 8) return ['label' => 'Excel·lent', 'class' => 'badge-green'];
    if ($avg >= 6) return ['label' => 'Correcte',   'class' => 'badge-blue'];
    if ($avg >= 4) return ['label' => 'Millorable',  'class' => 'badge-gold'];
    return ['label' => 'Crític', 'class' => 'badge-red'];
}

function getAuditCmsOptions($lang = 'ca') {
    if ($lang === 'es') {
        return ['wordpress' => 'WordPress', 'shopify' => 'Shopify', 'prestashop' => 'Prestashop', 'custom' => 'Desarrollo propio', 'other' => 'Otro / Sin CMS'];
    }
    return ['wordpress' => 'WordPress', 'shopify' => 'Shopify', 'prestashop' => 'Prestashop', 'custom' => 'Desenvolupament propi', 'other' => 'Altre / Sense CMS'];
}

function getAuditProblemOptions() {
    return [
        'lenta'         => 'Web lenta',
        'errores'       => 'Errors tècnics',
        'plugins'       => 'Plugins obsolets',
        'mantenimiento' => 'Falta de manteniment',
        'seo'           => 'SEO deficient',
        'seguridad'     => 'Problemes de seguretat',
        'ux'            => 'UX millorable',
        'reservas'      => 'Sense sistema de reserves',
        'blog'          => 'Sense blog / contingut',
    ];
}

function getAuditStatusOptions() {
    return ['pendiente' => 'Pendent', 'en_proceso' => 'En procés', 'completada' => 'Completada'];
}

function auditStatusLabel($status) {
    $map = [
        'pendiente'  => ['label' => 'Pendent',    'class' => 'badge-gray'],
        'en_proceso' => ['label' => 'En procés',  'class' => 'badge-blue'],
        'completada' => ['label' => 'Completada', 'class' => 'badge-green'],
    ];
    return $map[$status] ?? $map['pendiente'];
}

// Prioritats del pla d'acció (secció 12)
function getAuditActionBuckets($lang = 'ca') {
    if ($lang === 'es') {
        return [
            'accions_critiques'    => ['label' => 'Críticas',            'sub' => 'Hay que solucionarlas de inmediato', 'class' => 'badge-red'],
            'accions_importants'   => ['label' => 'Importantes',         'sub' => 'Hay que solucionarlas el próximo mes', 'class' => 'badge-gold'],
            'accions_recomanables' => ['label' => 'Recomendables',       'sub' => 'Mejoras a medio plazo',              'class' => 'badge-blue'],
            'accions_creixement'   => ['label' => 'Opciones de crecimiento', 'sub' => 'Aumentar visitas, conversiones y ventas', 'class' => 'badge-green'],
        ];
    }
    return [
        'accions_critiques'    => ['label' => 'Crítiques',       'sub' => 'Cal solucionar immediatament', 'class' => 'badge-red'],
        'accions_importants'   => ['label' => 'Importants',      'sub' => 'Cal solucionar el pròxim mes',  'class' => 'badge-gold'],
        'accions_recomanables' => ['label' => 'Recomanables',    'sub' => 'Millores a mig termini',        'class' => 'badge-blue'],
        'accions_creixement'   => ['label' => 'Opcions de creixement', 'sub' => 'Augmentar visites, conversions i vendes', 'class' => 'badge-green'],
    ];
}

// Parseja un textarea "una línia per fila" en un array net, ometent buides
function parseLines($text) {
    $lines = preg_split('/\r\n|\r|\n/', (string)$text);
    return array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
}

// Parseja la taula resum (secció 13): una fila per línia, camps separats per "|"
// Problema | Impacte | Dificultat | Prioritat | Solució
function parseAuditTable($text) {
    $rows = [];
    foreach (parseLines($text) as $line) {
        $cols = array_map('trim', explode('|', $line));
        $rows[] = [
            'problema'   => $cols[0] ?? '',
            'impacte'    => $cols[1] ?? '',
            'dificultat' => $cols[2] ?? '',
            'prioritat'  => $cols[3] ?? '',
            'solucio'    => $cols[4] ?? '',
        ];
    }
    return $rows;
}

// ─── ACCÉS PRIVAT DEL CLIENT (enllaç + usuari + contrasenya per auditoria) ──
// El client i tu sou els únics que podeu veure l'informe: cal el token de
// l'enllaç I les credencials. La contrasenya només es mostra en clar el
// moment que es genera/regenera — a partir d'ahí només es guarda el hash.

function slugUsername($name) {
    $s = strtolower(trim((string)$name));
    $s = preg_replace('/[^a-z0-9]+/', '.', $s);
    return trim($s, '.') ?: 'client';
}

function generateAuditAccess($id, $client_name = '') {
    $audit = getAudit($id);
    if (!$audit) return null;
    $password = substr(bin2hex(random_bytes(5)), 0, 8);
    $audit['access_token']         = bin2hex(random_bytes(16));
    $audit['access_username']      = slugUsername($client_name) . '.' . substr($id, 0, 4);
    $audit['access_password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    $audit['access_enabled']       = true;
    $audit['access_created']       = date('Y-m-d H:i');
    saveAudit($audit);
    return ['username' => $audit['access_username'], 'password' => $password, 'token' => $audit['access_token']];
}

function regenerateAuditPassword($id) {
    $audit = getAudit($id);
    if (!$audit || empty($audit['access_token'])) return null;
    $password = substr(bin2hex(random_bytes(5)), 0, 8);
    $audit['access_password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    saveAudit($audit);
    return $password;
}

function toggleAuditAccess($id, $enabled) {
    $audit = getAudit($id);
    if (!$audit) return;
    $audit['access_enabled'] = (bool)$enabled;
    saveAudit($audit);
}

function getAuditByToken($token) {
    if (!$token) return null;
    foreach (readData('audits') as $a) {
        if (!empty($a['access_token']) && hash_equals($a['access_token'], $token)) return $a;
    }
    return null;
}

function verifyAuditAccess($audit, $username, $password) {
    if (empty($audit['access_enabled']) || empty($audit['access_password_hash'])) return false;
    if (!hash_equals($audit['access_username'] ?? '', (string)$username)) return false;
    return password_verify((string)$password, $audit['access_password_hash']);
}

function auditPublicUrl($audit) {
    $base = defined('SITE_URL') ? SITE_URL : '';
    return rtrim($base, '/') . '/informe-client.php?a=' . ($audit['access_token'] ?? '');
}

// Idiomes disponibles per mostrar l'informe al client (es tria per auditoria, no pel navegador)
function getAuditLangOptions() {
    return ['ca' => 'Valencià', 'es' => 'Castellà'];
}

// Diccionari de l'informe (vista client + intern). Clau curta → text ca/es.
function auditT($key, $lang = 'ca') {
    $dict = [
        'tagline'        => ['ca' => 'Auditoria & consultoria web',                       'es' => 'Auditoría & consultoría web'],
        'logout'         => ['ca' => 'Tancar sessió',                                      'es' => 'Cerrar sesión'],
        'print_btn'      => ['ca' => '🖨 Imprimir / Guardar PDF',                          'es' => '🖨 Imprimir / Guardar PDF'],
        'download_pdf'   => ['ca' => '⬇ Baixar PDF',                                       'es' => '⬇ Descargar PDF'],
        'print_hint'     => ['ca' => 'Tria "Guardar com a PDF" com a destinació d\'impressió per descarregar-lo', 'es' => 'Elige "Guardar como PDF" como destino de impresión para descargarlo'],
        'confidential'   => ['ca' => 'DOCUMENT CONFIDENCIAL',                              'es' => 'DOCUMENTO CONFIDENCIAL'],
        'page_of'        => ['ca' => 'Pàgina',                                             'es' => 'Página'],
        'doc_title'      => ['ca' => 'Informe d\'auditoria web professional',              'es' => 'Informe de auditoría web profesional'],
        'doc_sub'        => ['ca' => 'Anàlisi de desenvolupament, SEO, màrqueting digital, UX, accessibilitat i ciberseguretat', 'es' => 'Análisis de desarrollo, SEO, marketing digital, UX, accesibilidad y ciberseguridad'],
        'lbl_empresa'    => ['ca' => 'Empresa',                                            'es' => 'Empresa'],
        'lbl_contacte'   => ['ca' => 'Persona de contacte',                                'es' => 'Persona de contacto'],
        'lbl_web'        => ['ca' => 'Web analitzada',                                     'es' => 'Web analizada'],
        'lbl_cms'        => ['ca' => 'CMS detectat',                                       'es' => 'CMS detectado'],
        'lbl_sector'     => ['ca' => 'Sector',                                             'es' => 'Sector'],
        'lbl_consultor'  => ['ca' => 'Consultor',                                          'es' => 'Consultor'],
        'sec_1'          => ['ca' => 'Resum executiu',                                     'es' => 'Resumen ejecutivo'],
        'sec_2'          => ['ca' => 'Primera impressió',                                  'es' => 'Primera impresión'],
        'sec_3'          => ['ca' => 'Experiència d\'usuari (UX)',                         'es' => 'Experiencia de usuario (UX)'],
        'sec_4'          => ['ca' => 'Adaptació a dispositius mòbils',                     'es' => 'Adaptación a dispositivos móviles'],
        'sec_5'          => ['ca' => 'Velocitat',                                          'es' => 'Velocidad'],
        'sec_6'          => ['ca' => 'SEO',                                                'es' => 'SEO'],
        'sec_7'          => ['ca' => 'Contingut',                                          'es' => 'Contenido'],
        'sec_8'          => ['ca' => 'Accessibilitat (WCAG)',                              'es' => 'Accesibilidad (WCAG)'],
        'sec_9'          => ['ca' => 'Seguretat',                                          'es' => 'Seguridad'],
        'sec_10'         => ['ca' => 'Rendiment comercial',                                'es' => 'Rendimiento comercial'],
        'sec_11'         => ['ca' => 'Competència',                                        'es' => 'Competencia'],
        'sec_12'         => ['ca' => 'Pla d\'acció',                                       'es' => 'Plan de acción'],
        'sec_13'         => ['ca' => 'Taula resum',                                        'es' => 'Tabla resumen'],
        'sec_14'         => ['ca' => 'Valoració final',                                    'es' => 'Valoración final'],
        'sec_prop'       => ['ca' => 'Proposta econòmica',                                 'es' => 'Propuesta económica'],
        'fortaleses'     => ['ca' => 'Principals fortaleses',                              'es' => 'Principales fortalezas'],
        'debilitats'     => ['ca' => 'Principals debilitats',                              'es' => 'Principales debilidades'],
        'prioritats'     => ['ca' => 'Prioritats immediates',                              'es' => 'Prioridades inmediatas'],
        'abandonament'   => ['ca' => 'Possibles punts d\'abandonament',                    'es' => 'Posibles puntos de abandono'],
        'sense_obs'      => ['ca' => 'Sense observacions addicionals.',                    'es' => 'Sin observaciones adicionales.'],
        'th_problema'    => ['ca' => 'Problema',                                           'es' => 'Problema'],
        'th_impacte'     => ['ca' => 'Impacte',                                            'es' => 'Impacto'],
        'th_dificultat'  => ['ca' => 'Dificultat',                                         'es' => 'Dificultad'],
        'th_prioritat'   => ['ca' => 'Prioritat',                                          'es' => 'Prioridad'],
        'th_solucio'     => ['ca' => 'Solució',                                            'es' => 'Solución'],
        'nota_global'    => ['ca' => 'Nota global',                                        'es' => 'Nota global'],
        'conclusio'      => ['ca' => 'Conclusió professional',                             'es' => 'Conclusión profesional'],
        'prop_no'        => ['ca' => 'Encara no hi ha cap proposta econòmica generada per a esta auditoria.', 'es' => 'Todavía no hay ninguna propuesta económica generada para esta auditoría.'],
        'prop_no_client' => ['ca' => 'Encara no s\'ha generat cap proposta econòmica. AKRA Tech Studio es posarà en contacte amb tu prompte.', 'es' => 'Todavía no se ha generado ninguna propuesta económica. AKRA Tech Studio se pondrá en contacto contigo pronto.'],
        'prop_crear'     => ['ca' => 'Crear proposta →',                                   'es' => 'Crear propuesta →'],
        'th_tipus'       => ['ca' => 'Tipus de servei',                                    'es' => 'Tipo de servicio'],
        'th_desc'        => ['ca' => 'Descripció',                                         'es' => 'Descripción'],
        'th_import'      => ['ca' => 'Import',                                             'es' => 'Importe'],
        'total_proposat' => ['ca' => 'Total proposat',                                     'es' => 'Total propuesto'],
        'foot_doc'       => ['ca' => 'Document confidencial · Generat el',                 'es' => 'Documento confidencial · Generado el'],
        'login_h1'       => ['ca' => 'Accés privat a l\'informe',                          'es' => 'Acceso privado al informe'],
        'login_sub'      => ['ca' => 'Este informe és confidencial. Introdueix les credencials que t\'ha facilitat AKRA Tech Studio.', 'es' => 'Este informe es confidencial. Introduce las credenciales que te ha facilitado AKRA Tech Studio.'],
        'login_user'     => ['ca' => 'Usuari',                                             'es' => 'Usuario'],
        'login_pass'     => ['ca' => 'Contrasenya',                                        'es' => 'Contraseña'],
        'login_btn'      => ['ca' => 'Veure el meu informe',                               'es' => 'Ver mi informe'],
        'login_error'    => ['ca' => 'Usuari o contrasenya incorrectes.',                  'es' => 'Usuario o contraseña incorrectos.'],
        'link_invalid_h' => ['ca' => 'Enllaç no vàlid',                                    'es' => 'Enlace no válido'],
        'link_invalid_p' => ['ca' => 'Este enllaç d\'auditoria no existeix o ja no és disponible. Contacta amb AKRA Tech Studio si creus que és un error.', 'es' => 'Este enlace de auditoría no existe o ya no está disponible. Contacta con AKRA Tech Studio si crees que es un error.'],
        'access_off_h'   => ['ca' => 'Accés desactivat',                                   'es' => 'Acceso desactivado'],
        'access_off_p'   => ['ca' => 'L\'accés a esta auditoria ha sigut desactivat temporalment. Contacta amb AKRA Tech Studio per a més informació.', 'es' => 'El acceso a esta auditoría ha sido desactivado temporalmente. Contacta con AKRA Tech Studio para más información.'],
    ];
    return $dict[$key][$lang] ?? $dict[$key]['ca'] ?? $key;
}

// ─── PROPOSTES COMERCIALS ───────────────────────────────────────────────────

function getProposals($client_id = null) {
    $proposals = readActiveData('proposals');
    if ($client_id) $proposals = array_filter($proposals, fn($p) => $p['client_id'] === $client_id);
    usort($proposals, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return array_values($proposals);
}

function getProposal($id) {
    foreach (readData('proposals') as $p) if ($p['id'] === $id) return $p;
    return null;
}

function saveProposal($proposal) {
    $proposals = readData('proposals');
    $idx = array_search($proposal['id'], array_column($proposals, 'id'));
    if ($idx !== false) $proposals[$idx] = $proposal;
    else $proposals[] = $proposal;
    writeData('proposals', $proposals);
    return $proposal;
}

function deleteProposal($id) {
    softDeleteRecord('proposals', $id);
}

function getProposalTypeOptions() {
    return [
        'optimizacion' => 'Optimització WordPress',
        'migracion'    => 'Migració híbrida',
        'medida'       => 'Plataforma a mida',
        'rediseno'     => 'Redisseny complet',
    ];
}

function getProposalStatusOptions() {
    return ['borrador' => 'Esborrany', 'enviada' => 'Enviada', 'aceptada' => 'Acceptada', 'rechazada' => 'Rebutjada'];
}

function proposalStatusLabel($status) {
    $map = [
        'borrador'  => ['label' => 'Esborrany', 'class' => 'badge-gray'],
        'enviada'   => ['label' => 'Enviada',   'class' => 'badge-blue'],
        'aceptada'  => ['label' => 'Acceptada', 'class' => 'badge-green'],
        'rechazada' => ['label' => 'Rebutjada', 'class' => 'badge-red'],
    ];
    return $map[$status] ?? $map['borrador'];
}

// Estadístiques per al dashboard comercial
function getCrmStats() {
    $proposals = readData('proposals');
    $sent      = array_filter($proposals, fn($p) => in_array($p['status'] ?? '', ['enviada', 'aceptada', 'rechazada']));
    $accepted  = array_filter($proposals, fn($p) => ($p['status'] ?? '') === 'aceptada');
    $conversio = count($sent) > 0 ? round(count($accepted) / count($sent) * 100) : 0;
    return [
        'clients'   => count(readData('clients')),
        'audits'    => count(readData('audits')),
        'sent'      => count($sent),
        'accepted'  => count($accepted),
        'conversio' => $conversio,
    ];
}

// ─── RESUM FINANCER PER CLIENT ───────────────────────────────────────────────
// Suma totes les factures d'un client (facturat / cobrat / pendent), sempre
// calculat en viu a partir de invoicePaymentSummary() per no descuadrar mai.
function getClientFinancialSummary($client_id) {
    $invoices = getInvoices($client_id);
    $total = $paid = $due = 0;
    $n_overdue = 0;
    foreach ($invoices as $inv) {
        if ($inv['status'] === 'cancelled') continue;
        $s = invoicePaymentSummary($inv);
        $total += $s['total'];
        $paid  += $s['paid'];
        $due   += max(0, $s['due']);
        if ($inv['status'] === 'sent' && !empty($inv['due_date']) && $inv['due_date'] < date('Y-m-d')) $n_overdue++;
    }
    return [
        'total' => round($total, 2), 'paid' => round($paid, 2), 'due' => round($due, 2),
        'count' => count($invoices), 'overdue' => $n_overdue,
    ];
}

// ─── AVISOS PER AL DASHBOARD ─────────────────────────────────────────────────
// Factures vençudes (enviades i amb data de venciment passada).
function getOverdueInvoices() {
    $invoices = getInvoices();
    return array_values(array_filter($invoices, fn($i) =>
        $i['status'] === 'sent' && !empty($i['due_date']) && $i['due_date'] < date('Y-m-d')
    ));
}

// Contactes amb seguiment pendent en els pròxims dies (o ja vençut), que no
// estiguen ja tancats o respostos.
function getUpcomingFollowUps($days_ahead = 7) {
    $contacts = readData('contacts');
    $limit = date('Y-m-d', strtotime("+{$days_ahead} days"));
    $pending = array_filter($contacts, fn($c) =>
        !empty($c['follow_up']) && $c['follow_up'] <= $limit && !in_array($c['status'] ?? '', ['tancat', 'respost'])
    );
    usort($pending, fn($a, $b) => strcmp($a['follow_up'], $b['follow_up']));
    return array_values($pending);
}

// ─── PIPELINE DE POSSIBLES CLIENTS (LEADS) ──────────────────────────────────
// Cada client té un camp 'stage' que indica en quina fase comercial està.
// Els leads (encara no clients de veritat) poden tindre auditories i
// propostes igual que qualsevol client; quan se'ls factura per primera
// vegada, el sistema els marca automàticament com a "guanyat".
function getLeadStageOptions() {
    return [
        'lead'       => ['label' => 'Lead nou',         'class' => 'badge-gray',  'order' => 1],
        'contactat'  => ['label' => 'Contactat',        'class' => 'badge-blue',  'order' => 2],
        'auditoria'  => ['label' => 'Auditoria feta',    'class' => 'badge-gold',  'order' => 3],
        'proposta'   => ['label' => 'Proposta enviada', 'class' => 'badge-gold',  'order' => 4],
        'guanyat'    => ['label' => 'Guanyat (client)',  'class' => 'badge-green', 'order' => 5],
        'perdut'     => ['label' => 'Perdut',            'class' => 'badge-red',   'order' => 6],
    ];
}

function getLeadSourceOptions() {
    return [
        'google'      => 'Google / cerca',
        'referencia'  => 'Referència / boca-orella',
        'xarxes'      => 'Xarxes socials',
        'web'         => 'Formulari de la web',
        'event'       => 'Esdeveniment / networking',
        'altres'      => 'Altres',
    ];
}

function getLostReasonOptions() {
    return [
        'preu'        => 'Preu massa alt',
        'competencia'  => 'Ha triat la competència',
        'no_respon'   => 'No ha respost / silenci',
        'no_pressupost'=> 'No tenia pressupost',
        'timing'      => 'No era el moment (potser més avant)',
        'altres'      => 'Altres',
    ];
}

function leadStageLabel($stage) {
    $opts = getLeadStageOptions();
    return $opts[$stage] ?? $opts['lead'];
}

// Avança la fase del pipeline d'un client, però mai cap arrere (llevat que
// $force=true, per exemple per marcar-lo com a "perdut" explícitament).
// Si el client ja està "guanyat", no el tornem arrere per accions automàtiques.
function advanceClientStage($client_id, $new_stage, $force = false) {
    $client = getClient($client_id);
    if (!$client) return;
    $stages = getLeadStageOptions();
    $current = $client['stage'] ?? 'lead';
    if ($current === 'guanyat' && !$force) return;
    if (!$force && isset($stages[$current], $stages[$new_stage]) && $stages[$new_stage]['order'] <= $stages[$current]['order']) return;
    $client['stage'] = $new_stage;
    saveClient($client);
}


function getPipelineFunnel() {
    $stages = getLeadStageOptions();
    $counts = array_fill_keys(array_keys($stages), 0);
    foreach (getClients() as $c) {
        $s = $c['stage'] ?? 'lead';
        if (isset($counts[$s])) $counts[$s]++;
    }
    return $counts;
}


// ─── XIFRAT SENZILL PER A CREDENCIALS (dominis) ─────────────────────────────
// AES-256-CBC amb la clau DOMAIN_SECRET_KEY. No és un gestor de contrasenyes
// professional (tipus Bitwarden), però evita guardar-les en text pla al JSON.
function encryptSecret($plain) {
    if ($plain === '' || $plain === null) return '';
    $iv = openssl_random_pseudo_bytes(16);
    $cipher = openssl_encrypt($plain, 'AES-256-CBC', hash('sha256', DOMAIN_SECRET_KEY, true), OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cipher);
}
function decryptSecret($encoded) {
    if ($encoded === '' || $encoded === null) return '';
    $raw = base64_decode($encoded);
    if ($raw === false || strlen($raw) < 17) return '';
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', hash('sha256', DOMAIN_SECRET_KEY, true), OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}

// ─── TREBALLS DEL CLIENT (projectes/encàrrecs interns, no el portfoli públic) ─
function getJobs($client_id = null) {
    $jobs = readActiveData('jobs');
    if ($client_id) $jobs = array_filter($jobs, fn($j) => $j['client_id'] === $client_id);
    usort($jobs, fn($a, $b) => strcmp($b['start_date'] ?? '', $a['start_date'] ?? ''));
    return array_values($jobs);
}
function getJob($id) {
    foreach (readData('jobs') as $j) if ($j['id'] === $id) return $j;
    return null;
}
function saveJob($job) {
    $jobs = readData('jobs');
    $idx = array_search($job['id'], array_column($jobs, 'id'));
    if ($idx !== false) $jobs[$idx] = array_merge($jobs[$idx], $job);
    else $jobs[] = $job;
    writeData('jobs', $jobs);
    return $job;
}
function deleteJob($id) {
    softDeleteRecord('jobs', $id);
    foreach (readData('time_entries') as $e) if ($e['job_id'] === $id && empty($e['deleted_at'])) softDeleteRecord('time_entries', $e['id']);
}
function getJobTypeOptions() {
    return ['web'=>'Disseny/desenvolupament web','seo'=>'SEO','manteniment'=>'Manteniment','disseny'=>'Disseny gràfic','marketing'=>'Marketing/xarxes','altres'=>'Altres'];
}
function getJobStatusOptions() {
    return [
        'pressupostat' => ['label' => 'Pressupostat',  'class' => 'badge-gray'],
        'en_curs'      => ['label' => 'En curs',        'class' => 'badge-blue'],
        'en_pausa'     => ['label' => 'En pausa',       'class' => 'badge-gold'],
        'acabat'       => ['label' => 'Acabat',         'class' => 'badge-green'],
        'cancelat'     => ['label' => 'Cancel·lat',     'class' => 'badge-red'],
    ];
}

// ─── REGISTRE D'HORES PER TREBALL ────────────────────────────────────────────
function getTimeEntries($job_id = null) {
    $entries = readActiveData('time_entries');
    if ($job_id) $entries = array_filter($entries, fn($e) => $e['job_id'] === $job_id);
    usort($entries, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return array_values($entries);
}

function saveTimeEntry($entry) {
    $entries = readData('time_entries');
    $idx = array_search($entry['id'], array_column($entries, 'id'));
    if ($idx !== false) $entries[$idx] = array_merge($entries[$idx], $entry);
    else $entries[] = $entry;
    writeData('time_entries', $entries);
    return $entry;
}

function deleteTimeEntry($id) {
    softDeleteRecord('time_entries', $id);
}

function getJobTotalHours($job_id) {
    return round(array_sum(array_map(fn($e) => (float)($e['hours'] ?? 0), getTimeEntries($job_id))), 2);
}

// ─── ENQUESTA DE SATISFACCIÓ (quan un treball passa a "Acabat") ─────────────
function getSatisfaction($job_id) {
    foreach (readData('satisfaction') as $s) if ($s['job_id'] === $job_id) return $s;
    return null;
}

function saveSatisfactionRating($job_id, $rating, $comment = '') {
    $all = readData('satisfaction');
    $idx = null;
    foreach ($all as $i => $s) if ($s['job_id'] === $job_id) { $idx = $i; break; }
    $entry = ['job_id' => $job_id, 'rating' => (int)$rating, 'comment' => sanitize($comment), 'date' => date('Y-m-d H:i:s')];
    if ($idx !== null) $all[$idx] = array_merge($all[$idx], $entry);
    else $all[] = $entry;
    writeData('satisfaction', $all);
}

function sendSatisfactionSurvey($job) {
    $client = getClient($job['client_id']);
    if (!$client || empty($client['email'])) return;
    $cfg = getAdminConfig();
    $from_name  = $cfg['site_name'] ?? 'AKRA Tech Studio';
    $from_email = $cfg['email'] ?? 'hola@akratechstudio.es';
    $base_url = rtrim($cfg['site_url'] ?? '', '/');

    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $url = $base_url . "/feedback.php?job={$job['id']}&rating={$i}";
        $stars .= "<a href=\"{$url}\" style=\"text-decoration:none;font-size:28px;margin:0 4px\">⭐</a>";
    }

    $html = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#1a1a1a;line-height:1.6;text-align:center;max-width:480px;margin:0 auto">'
        . '<h2 style="font-family:Georgia,serif">¿Qué tal ha ido?</h2>'
        . '<p>Acabamos de terminar <strong>' . htmlspecialchars($job['title']) . '</strong>. ¿Cómo puntuarías el resultado?</p>'
        . '<div style="margin:20px 0">' . $stars . '</div>'
        . '<p style="color:#9ca3af;font-size:12px">Haz clic en el número de estrellas que quieras dar. Solo te llevará un segundo.</p>'
        . '</div>';

    $headers  = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$from_name} <{$from_email}>\r\nX-Mailer: AKRA Tech Studio\r\n";
    @mail($client['email'], '¿Qué tal ha ido? — ' . $job['title'], $html, $headers);
}


// ─── DOMINIS (gestió i alarma de renovació) ──────────────────────────────────
function getDomains($client_id = null) {
    $domains = readActiveData('domains');
    if ($client_id) $domains = array_filter($domains, fn($d) => $d['client_id'] === $client_id);
    usort($domains, fn($a, $b) => strcmp($a['renewal_date'] ?? '', $b['renewal_date'] ?? ''));
    return array_values($domains);
}
function getDomain($id) {
    foreach (readData('domains') as $d) if ($d['id'] === $id) return $d;
    return null;
}
function saveDomain($domain) {
    // El 'password' arriba en clar des del formulari i ací el xifrem abans de guardar.
    if (array_key_exists('password_plain', $domain)) {
        if ($domain['password_plain'] !== '') {
            $domain['password_enc'] = encryptSecret($domain['password_plain']);
        }
        unset($domain['password_plain']);
    }
    $domains = readData('domains');
    $idx = array_search($domain['id'], array_column($domains, 'id'));
    if ($idx !== false) $domains[$idx] = array_merge($domains[$idx], $domain);
    else $domains[] = $domain;
    writeData('domains', $domains);
    return $domain;
}
function deleteDomain($id) {
    softDeleteRecord('domains', $id);
}
function getDomainManagerOptions() {
    return ['nosaltres' => 'El gestionem nosaltres', 'client' => 'El gestiona el client', 'altres' => 'El gestiona un tercer'];
}

// Genera una factura (esborrany) quan toca renovar un domini/hosting que té
// marcada l'opció "facturar automàticament". Avança la data de renovació un
// any perquè no es torne a facturar fins a la pròxima renovació real.
function generateDueDomainRenewalInvoices() {
    $created = [];
    foreach (getDomains() as $d) {
        if (empty($d['auto_invoice'])) continue;
        $today = date('Y-m-d');

        if (!empty($d['renewal_date']) && $d['renewal_date'] <= $today && (float)($d['cost'] ?? 0) > 0) {
            $created[] = createRenewalInvoice($d['client_id'], "Renovació del domini {$d['domain']}", $d['cost']);
            $d['renewal_date'] = date('Y-m-d', strtotime($d['renewal_date'] . ' +1 year'));
        }
        if (!empty($d['hosting_renewal_date']) && $d['hosting_renewal_date'] <= $today && (float)($d['hosting_cost'] ?? 0) > 0) {
            $created[] = createRenewalInvoice($d['client_id'], "Renovació de l'hosting de {$d['domain']}", $d['hosting_cost']);
            $d['hosting_renewal_date'] = date('Y-m-d', strtotime($d['hosting_renewal_date'] . ' +1 year'));
        }
        saveDomain($d);
    }
    return array_filter($created);
}

function createRenewalInvoice($client_id, $concept, $amount) {
    $invoice = [
        'id' => generateId(), 'number' => nextInvoiceNumber(), 'client_id' => $client_id, 'status' => 'draft',
        'date' => date('Y-m-d'), 'due_date' => date('Y-m-d', strtotime('+15 days')),
        'lines' => [['desc' => $concept, 'qty' => 1, 'price' => $amount]],
        'tax_pct' => 21, 'irpf_pct' => 0, 'notes' => '', 'payment_info' => (getAdminConfig()['invoice_payment'] ?? ''),
    ];
    saveInvoice($invoice);
    return $invoice['number'];
}

// ─── DASHBOARD FINANCER GLOBAL (no per client) ───────────────────────────────
function getGlobalFinancialStats() {
    $invoices = getInvoices();
    $this_month = date('Y-m');
    $last_month = date('Y-m', strtotime('-1 month'));
    $this_year  = date('Y');

    $stats = [
        'month_billed' => 0, 'month_paid' => 0,
        'last_month_billed' => 0,
        'year_billed' => 0, 'year_paid' => 0,
        'total_pending' => 0,
    ];

    foreach ($invoices as $inv) {
        if ($inv['status'] === 'cancelled') continue;
        $s = invoicePaymentSummary($inv);
        $month = substr($inv['date'], 0, 7);
        $year  = substr($inv['date'], 0, 4);

        if ($month === $this_month) { $stats['month_billed'] += $s['total']; $stats['month_paid'] += $s['paid']; }
        if ($month === $last_month) $stats['last_month_billed'] += $s['total'];
        if ($year === $this_year) { $stats['year_billed'] += $s['total']; $stats['year_paid'] += $s['paid']; }
        $stats['total_pending'] += max(0, $s['due']);
    }

    $stats['month_vs_last_pct'] = $stats['last_month_billed'] > 0
        ? round((($stats['month_billed'] - $stats['last_month_billed']) / $stats['last_month_billed']) * 100)
        : null;

    foreach (['month_billed','month_paid','last_month_billed','year_billed','year_paid','total_pending'] as $k) {
        $stats[$k] = round($stats[$k], 2);
    }
    return $stats;
}


// Dominis (o el seu hosting) que vencen dins dels pròxims $days dies (o ja
// vençuts), per a l'avís del dashboard i de l'email diari.
function getDomainsExpiringSoon($days = 30) {
    $limit = date('Y-m-d', strtotime("+{$days} days"));
    $domains = array_filter(readData('domains'), function($d) use ($limit) {
        $dom_soon  = !empty($d['renewal_date']) && $d['renewal_date'] <= $limit;
        $host_soon = !empty($d['hosting_renewal_date']) && $d['hosting_renewal_date'] <= $limit;
        return $dom_soon || $host_soon;
    });
    usort($domains, fn($a, $b) => strcmp($a['renewal_date'] ?? '', $b['renewal_date'] ?? ''));
    return array_values($domains);
}

// ─── ALERTES DIÀRIES PER EMAIL ───────────────────────────────────────────────
// Envia un resum a l'email de l'agència (Configuració → Email) amb factures
// vençudes i dominis/hostings a punt de renovar. Pensat per executar-se cada
// dia via el Programador de tasques (vore cron_alerts.php).
function sendDailyAlertsEmail() {
    $cfg = getAdminConfig();
    $to  = $cfg['email'] ?? '';
    if (!$to) return ['ok' => false, 'error' => 'No hi ha cap email configurat a Configuració.'];

    $overdue = getOverdueInvoices();
    $domains = getDomainsExpiringSoon(30);
    $followups = getUpcomingFollowUps(7);

    if (empty($overdue) && empty($domains) && empty($followups)) {
        return ['ok' => true, 'sent' => false, 'error' => null]; // res a avisar hui
    }

    $html = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#1a1a1a;line-height:1.6;max-width:600px">';
    $html .= '<h2 style="font-family:Georgia,serif">📋 Resum diari — ' . date('d/m/Y') . '</h2>';

    if (!empty($overdue)) {
        $html .= '<h3>⚠️ Factures vençudes (' . count($overdue) . ')</h3><ul>';
        foreach ($overdue as $inv) {
            $cl = getClient($inv['client_id']);
            $html .= '<li>' . htmlspecialchars($inv['number']) . ' — ' . htmlspecialchars($cl['name'] ?? '') . ' — ' . number_format($inv['total'], 2, ',', '.') . ' € (venç ' . date('d/m/Y', strtotime($inv['due_date'])) . ')</li>';
        }
        $html .= '</ul>';
    }
    if (!empty($domains)) {
        $html .= '<h3>🌐 Dominis/hostings a renovar (' . count($domains) . ')</h3><ul>';
        foreach ($domains as $d) {
            $cl = getClient($d['client_id']);
            $html .= '<li>' . htmlspecialchars($d['domain']) . ' — ' . htmlspecialchars($cl['name'] ?? '');
            if (!empty($d['renewal_date'])) $html .= ' — domini: ' . date('d/m/Y', strtotime($d['renewal_date']));
            if (!empty($d['hosting_renewal_date'])) $html .= ' — hosting: ' . date('d/m/Y', strtotime($d['hosting_renewal_date']));
            $html .= '</li>';
        }
        $html .= '</ul>';
    }
    if (!empty($followups)) {
        $html .= '<h3>📅 Seguiments de clients pendents (' . count($followups) . ')</h3><ul>';
        foreach ($followups as $ct) {
            $cl = getClient($ct['client_id']);
            $html .= '<li>' . htmlspecialchars($cl['name'] ?? '') . ' — ' . htmlspecialchars(mb_substr($ct['message'] ?? '', 0, 60)) . ' (' . date('d/m/Y', strtotime($ct['follow_up'])) . ')</li>';
        }
        $html .= '</ul>';
    }
    $html .= '<p style="color:#9ca3af;font-size:12px">Este email s\'ha generat automàticament des del panell d\'administració.</p></div>';

    $from_name  = $cfg['site_name'] ?? 'AKRA Tech Studio';
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$from_name} <{$to}>\r\n";
    $headers .= "X-Mailer: AKRA Tech Studio\r\n";

    $sent = mail($to, '📋 Resum diari — factures i dominis pendents', $html, $headers);
    return ['ok' => $sent, 'sent' => $sent, 'error' => $sent ? null : 'Error enviant l\'email. Comprova la configuració SMTP del servidor.'];
}


// ─── ETIQUETES DE CLIENT ─────────────────────────────────────────────────────
function getClientTagOptions() {
    return [
        'actiu'     => ['label' => 'Actiu',    'class' => 'badge-green'],
        'inactiu'   => ['label' => 'Inactiu',  'class' => 'badge-gray'],
        'vip'       => ['label' => 'VIP',      'class' => 'badge-gold'],
        'moros'     => ['label' => 'Morós',    'class' => 'badge-red'],
        'potencial' => ['label' => 'Potencial','class' => 'badge-blue'],
    ];
}

// ─── FACTURES RECURRENTS ─────────────────────────────────────────────────────
// Camps opcionals a la factura: recurring (bool), recurring_freq
// (monthly|quarterly|yearly), recurring_next (Y-m-d). Cada volta que
// s'executa este procés (manual o per cron), es generen les factures
// pendents i s'avança la data de la següent.
function getRecurringInvoices() {
    return array_values(array_filter(getInvoices(), fn($i) => !empty($i['recurring'])));
}

function nextRecurringDate($date, $freq) {
    return match($freq) {
        'quarterly' => date('Y-m-d', strtotime($date . ' +3 months')),
        'yearly'    => date('Y-m-d', strtotime($date . ' +1 year')),
        default     => date('Y-m-d', strtotime($date . ' +1 month')),
    };
}

// Genera les factures recurrents que ja toquen (recurring_next <= avui) i
// retorna la llista de números de factura creats.
function generateDueRecurringInvoices() {
    $created = [];
    foreach (getRecurringInvoices() as $inv) {
        if (empty($inv['recurring_next']) || $inv['recurring_next'] > date('Y-m-d')) continue;

        $new = $inv;
        $new['id']         = generateId();
        $new['number']     = nextInvoiceNumber();
        $new['date']       = date('Y-m-d');
        $new['due_date']   = date('Y-m-d', strtotime('+' . ($inv['due_days'] ?? 30) . ' days'));
        $new['status']     = 'draft';
        $new['recurring']  = false; // la còpia generada no és ella mateixa recurrent
        unset($new['recurring_next'], $new['recurring_freq']);
        saveInvoice($new);
        $created[] = $new['number'];

        // Avança la data de la propera generació a la factura original
        $inv['recurring_next'] = nextRecurringDate($inv['recurring_next'], $inv['recurring_freq'] ?? 'monthly');
        saveInvoice($inv);
    }
    return $created;
}

// ─── PROPOSTA: PDF I EMAIL (mateix patró que les factures) ──────────────────
function generateProposalPdf($proposal_id, $lang = 'ca') {
    $autoload = AKRA_ROOT . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        return ['ok' => false, 'error' => 'Falta instal·lar la llibreria PDF. Executa "composer require dompdf/dompdf" a l\'arrel del projecte.'];
    }
    require_once $autoload;

    $prop   = getProposal($proposal_id);
    $client = $prop ? getClient($prop['client_id']) : null;
    $cfg    = getAdminConfig();
    if (!$prop || !$client) return ['ok' => false, 'error' => 'Proposta o client no trobat'];

    ob_start();
    include ADMIN_ROOT . '/proposal_pdf_template.php';
    $html = ob_get_clean();

    try {
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();
    } catch (\Throwable $e) {
        return ['ok' => false, 'error' => 'Error generant el PDF: ' . $e->getMessage()];
    }

    return ['ok' => true, 'pdf' => $dompdf->output(), 'filename' => 'Proposta-' . preg_replace('/[^a-zA-Z0-9-]/', '', substr($prop['id'], -8)) . '.pdf'];
}

function sendProposalEmail($proposal_id, $to_email, $lang = 'ca') {
    $prop   = getProposal($proposal_id);
    $client = $prop ? getClient($prop['client_id']) : null;
    $cfg    = getAdminConfig();
    if (!$prop || !$client) return ['ok' => false, 'error' => 'Proposta o client no trobat'];

    $pdfResult = generateProposalPdf($proposal_id, $lang);
    if (!$pdfResult['ok']) return $pdfResult;

    $type_opts = getProposalTypeOptions();
    $type_label = $type_opts[$prop['type']] ?? $prop['type'];
    $from_name  = $cfg['site_name'] ?? 'AKRA Tech Studio';
    $from_email = $cfg['email'] ?? 'hola@akratechstudio.es';

    $subject = $lang === 'es'
        ? 'Propuesta de ' . $from_name . ' — ' . $type_label
        : 'Proposta de ' . $from_name . ' — ' . $type_label;
    $greeting = $lang === 'es' ? ('Estimado/a ' . $client['name'] . ',') : ('Estimat/da ' . $client['name'] . ',');
    $body = $lang === 'es'
        ? 'Adjunto encontrará la propuesta comercial solicitada. Quedo a su disposición para cualquier consulta.'
        : 'Adjuntem la proposta comercial sol·licitada. Quedo a la teua disposició per a qualsevol consulta.';
    $bye = $lang === 'es' ? 'Un cordial saludo,' : 'Una cordial salutació,';

    $html = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#1a1a1a;line-height:1.6">'
        . '<p>' . htmlspecialchars($greeting) . '</p>'
        . '<p>' . htmlspecialchars($body) . '</p>'
        . '<p>' . htmlspecialchars($bye) . '<br>' . htmlspecialchars($from_name) . '</p>'
        . '</div>';

    $boundary = md5(uniqid());
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    $headers .= "From: {$from_name} <{$from_email}>\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";
    $headers .= "X-Mailer: AKRA Tech Studio\r\n";

    $mailbody  = "--{$boundary}\r\n";
    $mailbody .= "Content-Type: text/html; charset=UTF-8\r\n";
    $mailbody .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $mailbody .= chunk_split(base64_encode($html)) . "\r\n";

    $mailbody .= "--{$boundary}\r\n";
    $mailbody .= "Content-Type: application/pdf; name=\"{$pdfResult['filename']}\"\r\n";
    $mailbody .= "Content-Transfer-Encoding: base64\r\n";
    $mailbody .= "Content-Disposition: attachment; filename=\"{$pdfResult['filename']}\"\r\n\r\n";
    $mailbody .= chunk_split(base64_encode($pdfResult['pdf'])) . "\r\n";
    $mailbody .= "--{$boundary}--\r\n";

    $sent = mail($to_email, $subject, $mailbody, $headers);
    return ['ok' => $sent, 'error' => $sent ? null : 'Error al enviar. Comprova la configuració SMTP del servidor.'];
}

// ─── EXPORTACIÓ COMPTABLE (CSV) ──────────────────────────────────────────────
// Genera un CSV de factures (i el pagat/pendent de cadascuna) entre dos dates,
// llest per portar a la gestoria (IVA trimestral, model 130/303...).
function exportInvoicesCsv($from_date, $to_date) {
    $rows = [['Número','Data','Client','NIF','Base imposable','IVA','IRPF','Total','Cobrat','Pendent','Estat']];
    foreach (getInvoices() as $inv) {
        if ($inv['date'] < $from_date || $inv['date'] > $to_date) continue;
        $client = getClient($inv['client_id']);
        $s = invoicePaymentSummary($inv);
        $rows[] = [
            $inv['number'], $inv['date'], $client['name'] ?? '', $client['nif'] ?? '',
            number_format($inv['subtotal'] ?? 0, 2, ',', ''),
            number_format($inv['tax'] ?? 0, 2, ',', ''),
            number_format($inv['irpf'] ?? 0, 2, ',', ''),
            number_format($s['total'], 2, ',', ''),
            number_format($s['paid'], 2, ',', ''),
            number_format(max(0,$s['due']), 2, ',', ''),
            invoiceStatusLabel($inv['status'])['text'],
        ];
    }
    $fh = fopen('php://temp', 'w+');
    foreach ($rows as $r) fputcsv($fh, $r, ';');
    rewind($fh);
    $csv = stream_get_contents($fh);
    fclose($fh);
    return "\xEF\xBB\xBF" . $csv; // BOM UTF-8 perquè Excel l'òbriga bé
}

// ─── CÒPIA DE SEGURETAT ──────────────────────────────────────────────────────
// Empaqueta tota la carpeta /admin/data (tots els JSON) en un .zip descarregable.
function generateDataBackupZip($section_keys = null) {
    $zipPath = sys_get_temp_dir() . '/akra-backup-' . date('Ymd-His') . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return ['ok' => false, 'error' => 'No s\'ha pogut crear el fitxer ZIP.'];
    }

    $files_to_include = backupSectionKeysToFiles($section_keys);
    foreach ($files_to_include as $file) {
        if (file_exists($file)) $zip->addFile($file, 'data/' . basename($file));
    }
    $zip->close();

    $suffix = ($section_keys && count($section_keys) === 1) ? '-' . $section_keys[0] : '';
    return ['ok' => true, 'path' => $zipPath, 'filename' => 'akra-backup' . $suffix . '-' . date('Y-m-d-His') . '.zip'];
}

// ─── SECCIONS DE LA CÒPIA DE SEGURETAT ──────────────────────────────────────
// Agrupa els fitxers de dades (admin/data/*.json) en seccions amb nom humà,
// perquè es puga triar què incloure a la còpia (tot, o només algunes).
function getBackupSections() {
    return [
        'clients'      => ['label' => '👤 Clients',                     'files' => ['clients.json']],
        'invoices'     => ['label' => '🧾 Factures i pagaments',        'files' => ['invoices.json', 'payments.json']],
        'proposals'    => ['label' => '📄 Propostes',                   'files' => ['proposals.json']],
        'contacts'     => ['label' => '💬 Comunicacions amb clients',   'files' => ['contacts.json']],
        'jobs'         => ['label' => '🛠️ Treballs i satisfacció',      'files' => ['jobs.json', 'time_entries.json', 'satisfaction.json']],
        'domains'      => ['label' => '🌐 Dominis i hostings',          'files' => ['domains.json']],
        'audits'       => ['label' => '🔍 Auditories',                  'files' => ['audits.json']],
        'prompts'      => ['label' => '🧠 Prompts i resultats',         'files' => ['prompts.json', 'prompt_results.json']],
        'social'       => ['label' => '🗓️ Calendari de xarxes socials', 'files' => ['social_posts.json']],
        'blog'         => ['label' => '📰 Blog',                        'files' => ['blog_posts.json']],
        'projects'     => ['label' => '💼 Portfoli / projectes',        'files' => ['projects.json', 'project_types.json']],
        'testimonials' => ['label' => '⭐ Testimonis',                  'files' => ['testimonials.json']],
        'services'     => ['label' => '🧩 Serveis',                     'files' => ['services.json']],
        'seo'          => ['label' => '🔎 SEO',                         'files' => ['seo.json']],
        'content'      => ['label' => '📝 Contingut de la web',         'files' => ['content.json']],
        'messages'     => ['label' => '✉️ Missatges del formulari web', 'files' => ['messages.json']],
        'users'        => ['label' => '🔐 Usuaris admin',               'files' => ['users.json']],
        'settings'     => ['label' => '⚙️ Configuració',                'files' => ['site_config.json']],
    ];
}

// Converteix una llista de claus de secció en la llista de rutes de fitxer
// corresponent. $section_keys = null vol dir "totes" (còpia completa).
// Inclou també qualsevol .json no cobert per cap secció, dins d'"Altres".
function backupSectionKeysToFiles($section_keys = null) {
    $sections = getBackupSections();
    $all_mapped_files = [];
    foreach ($sections as $s) $all_mapped_files = array_merge($all_mapped_files, $s['files']);

    if ($section_keys === null || $section_keys === []) {
        // Tot: totes les seccions conegudes + qualsevol fitxer no mapat
        $files = array_map(fn($f) => DATA_DIR . $f, $all_mapped_files);
        foreach (glob(DATA_DIR . '*.json') as $f) {
            if (!in_array(basename($f), $all_mapped_files)) $files[] = $f;
        }
        return array_unique($files);
    }

    $files = [];
    foreach ($section_keys as $key) {
        if (isset($sections[$key])) {
            foreach ($sections[$key]['files'] as $f) $files[] = DATA_DIR . $f;
        }
    }
    return array_unique($files);
}

// ─── CÒPIES DE SEGURETAT AUTOMÀTIQUES (programades per cron) ───────────────
function backupsDir() {
    $dir = ADMIN_ROOT . '/backups/';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}

// Genera una còpia (segons la configuració) i la guarda a admin/backups/,
// després esborra les còpies automàtiques més antigues que la retenció
// configurada. Pensat per cridar-se des de cron_backup.php.
function runScheduledBackup() {
    $cfg = getAdminConfig();
    if (empty($cfg['auto_backup_enabled'])) return ['ok' => false, 'error' => 'Còpies automàtiques desactivades a Configuració.'];

    $sections = !empty($cfg['auto_backup_sections']) ? $cfg['auto_backup_sections'] : null;
    $result = generateDataBackupZip($sections);
    if (!$result['ok']) return $result;

    $dir = backupsDir();
    $dest = $dir . 'auto-' . date('Y-m-d-His') . '.zip';
    @rename($result['path'], $dest);

    $retention_days = max(1, (int)($cfg['auto_backup_retention_days'] ?? 30));
    $purged = purgeOldBackups($retention_days);

    return ['ok' => true, 'path' => $dest, 'purged' => $purged];
}

function listAutoBackups() {
    $dir = backupsDir();
    $files = glob($dir . 'auto-*.zip');
    $items = [];
    foreach ($files as $f) {
        $items[] = ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)];
    }
    usort($items, fn($a, $b) => $b['time'] <=> $a['time']);
    return $items;
}

function purgeOldBackups($retention_days) {
    $dir = backupsDir();
    $cutoff = time() - ($retention_days * 86400);
    $purged = 0;
    foreach (glob($dir . 'auto-*.zip') as $f) {
        if (filemtime($f) < $cutoff) { @unlink($f); $purged++; }
    }
    return $purged;
}

function deleteAutoBackup($filename) {
    // Evita path traversal: només permet noms del propi patró de fitxer
    if (!preg_match('/^auto-[\d\-]+\.zip$/', $filename)) return false;
    $path = backupsDir() . $filename;
    return file_exists($path) ? @unlink($path) : false;
}

// ─── CERCADOR GLOBAL ─────────────────────────────────────────────────────────
// Busca en clients, factures, contactes i propostes alhora. Retorna un array
// agrupat per tipus, cada resultat amb 'label', 'subtitle' i 'url'.
function globalSearch($q) {
    $q = mb_strtolower(trim($q));
    $results = ['clients' => [], 'invoices' => [], 'contacts' => [], 'proposals' => []];
    if ($q === '') return $results;

    foreach (getClients() as $c) {
        $hay = mb_strtolower($c['name'] . ' ' . ($c['company'] ?? '') . ' ' . ($c['email'] ?? '') . ' ' . ($c['nif'] ?? ''));
        if (str_contains($hay, $q)) {
            $results['clients'][] = ['label' => $c['name'], 'subtitle' => $c['company'] ?? $c['email'] ?? '', 'url' => 'clients.php?id=' . $c['id']];
        }
    }
    foreach (getInvoices() as $i) {
        $client = getClient($i['client_id']);
        $hay = mb_strtolower($i['number'] . ' ' . ($client['name'] ?? ''));
        if (str_contains($hay, $q)) {
            $results['invoices'][] = ['label' => $i['number'], 'subtitle' => $client['name'] ?? '', 'url' => 'invoices.php?id=' . $i['id']];
        }
    }
    foreach (readData('contacts') as $ct) {
        $client = getClient($ct['client_id']);
        $hay = mb_strtolower(($ct['message'] ?? '') . ' ' . ($ct['response'] ?? '') . ' ' . ($client['name'] ?? ''));
        if (str_contains($hay, $q)) {
            $results['contacts'][] = ['label' => mb_substr($ct['message'] ?? '(sense missatge)', 0, 60), 'subtitle' => ($client['name'] ?? '') . ' · ' . $ct['date'], 'url' => 'clients.php?id=' . $ct['client_id'] . '#contactsCard'];
        }
    }
    foreach (getProposals() as $p) {
        $client = getClient($p['client_id']);
        $hay = mb_strtolower(($p['description'] ?? '') . ' ' . ($client['name'] ?? ''));
        if (str_contains($hay, $q)) {
            $results['proposals'][] = ['label' => ($client['name'] ?? '') . ' · ' . number_format($p['price'], 2, ',', '.') . ' €', 'subtitle' => mb_substr($p['description'] ?? '', 0, 60), 'url' => 'proposals.php?id=' . $p['id']];
        }
    }
    return $results;
}

// ══════════════════════════════════════════════════════════════════════════
// ─── PROMPTS (biblioteca de prompts d'IA i historial de resultats) ─────────
// ══════════════════════════════════════════════════════════════════════════
// Cada "prompt" és una fitxa reutilitzable (com el prompt mestre d'un client)
// i pot tindre múltiples "resultats" (execucions guardades al llarg del temps).

function getPrompts($client_id = null) {
    $prompts = readData('prompts');
    if ($client_id) $prompts = array_filter($prompts, fn($p) => ($p['client_id'] ?? '') === $client_id);
    usort($prompts, fn($a, $b) => strcmp($b['updated_at'] ?? $b['created_at'] ?? '', $a['updated_at'] ?? $a['created_at'] ?? ''));
    return array_values($prompts);
}

function getPrompt($id) {
    foreach (readData('prompts') as $p) if ($p['id'] === $id) return $p;
    return null;
}

function savePrompt($prompt) {
    $prompts = readData('prompts');
    $now = date('Y-m-d H:i:s');
    $idx = array_search($prompt['id'], array_column($prompts, 'id'));
    if ($idx !== false) {
        $prompt['created_at'] = $prompts[$idx]['created_at'] ?? $now;
        $prompt['updated_at'] = $now;
        $prompts[$idx] = $prompt;
    } else {
        $prompt['created_at'] = $now;
        $prompt['updated_at'] = $now;
        $prompts[] = $prompt;
    }
    writeData('prompts', $prompts);
    return $prompt;
}

function deletePrompt($id) {
    writeData('prompts', array_values(array_filter(readData('prompts'), fn($p) => $p['id'] !== $id)));
    // esborra també tot l'historial de resultats lligat a este prompt
    writeData('prompt_results', array_values(array_filter(readData('prompt_results'), fn($r) => $r['prompt_id'] !== $id)));
}

function getPromptCategoryOptions() {
    return [
        'estrategia'  => 'Estratègia digital',
        'xarxes'      => 'Xarxes socials',
        'copywriting' => 'Copywriting',
        'seo'         => 'SEO / Contingut web',
        'email'       => 'Email fred / Prospecció',
        'altres'      => 'Altres',
    ];
}

// ─── RESULTATS D'UN PROMPT (historial d'execucions guardades) ──────────────
function getPromptResults($prompt_id = null) {
    $results = readData('prompt_results');
    if ($prompt_id) $results = array_filter($results, fn($r) => $r['prompt_id'] === $prompt_id);
    usort($results, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    return array_values($results);
}

function getPromptResult($id) {
    foreach (readData('prompt_results') as $r) if ($r['id'] === $id) return $r;
    return null;
}

function savePromptResult($result) {
    $results = readData('prompt_results');
    $idx = array_search($result['id'], array_column($results, 'id'));
    if ($idx !== false) {
        $result['created_at'] = $results[$idx]['created_at'] ?? date('Y-m-d H:i:s');
        $result['updated_at'] = date('Y-m-d H:i:s');
        $results[$idx] = $result;
    } else {
        $result['created_at'] = date('Y-m-d H:i:s');
        $results[] = $result;
    }
    writeData('prompt_results', $results);
    return $result;
}

function deletePromptResult($id) {
    writeData('prompt_results', array_values(array_filter(readData('prompt_results'), fn($r) => $r['id'] !== $id)));
}

// Exportació CSV senzilla de la biblioteca de prompts (sense l'historial de resultats).
function exportPromptsCsv() {
    $cats = getPromptCategoryOptions();
    $rows = [['Títol', 'Client', 'Categoria', 'Actualitzat', 'Text del prompt', 'Notes']];
    foreach (getPrompts() as $p) {
        $client = !empty($p['client_id']) ? getClient($p['client_id']) : null;
        $rows[] = [
            $p['title'] ?? '',
            $client['name'] ?? '',
            $cats[$p['category'] ?? ''] ?? ($p['category'] ?? ''),
            $p['updated_at'] ?? $p['created_at'] ?? '',
            $p['prompt_text'] ?? '',
            $p['notes'] ?? '',
        ];
    }
    $fh = fopen('php://temp', 'w+');
    foreach ($rows as $r) fputcsv($fh, $r, ';');
    rewind($fh);
    $csv = stream_get_contents($fh);
    fclose($fh);
    return "\xEF\xBB\xBF" . $csv;
}

// ══════════════════════════════════════════════════════════════════════════
// ─── CALENDARI DE PUBLICACIONS A XARXES SOCIALS (per client) ───────────────
// ══════════════════════════════════════════════════════════════════════════

// Etiqueta "Agost 2026" en valencià/català a partir d'un timestamp (sense strftime, obsolet a PHP 8+).
function monthLabelCa($timestamp) {
    $months = ['Gener','Febrer','Març','Abril','Maig','Juny','Juliol','Agost','Setembre','Octubre','Novembre','Desembre'];
    $m = $months[(int)date('n', $timestamp) - 1] ?? '';
    return $m . ' ' . date('Y', $timestamp);
}

function getSocialPosts($client_id = null) {
    $posts = readData('social_posts');
    if ($client_id) $posts = array_filter($posts, fn($p) => ($p['client_id'] ?? '') === $client_id);
    usort($posts, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? '') ?: strcmp($a['created_at'] ?? '', $b['created_at'] ?? ''));
    return array_values($posts);
}

function getSocialPost($id) {
    foreach (readData('social_posts') as $p) if ($p['id'] === $id) return $p;
    return null;
}

function saveSocialPost($post) {
    $posts = readData('social_posts');
    $now = date('Y-m-d H:i:s');
    $idx = array_search($post['id'], array_column($posts, 'id'));
    if ($idx !== false) {
        $post['created_at'] = $posts[$idx]['created_at'] ?? $now;
        $post['updated_at'] = $now;
        $posts[$idx] = $post;
    } else {
        $post['created_at'] = $now;
        $post['updated_at'] = $now;
        $posts[] = $post;
    }
    writeData('social_posts', $posts);
    return $post;
}

function deleteSocialPost($id) {
    writeData('social_posts', array_values(array_filter(readData('social_posts'), fn($p) => $p['id'] !== $id)));
}

// Elimina totes les publicacions d'un client dins d'un rang de dates (p. ex.
// un mes sencer) d'un sol colp. Retorna el nombre de publicacions eliminades.
function deleteSocialPostsInRange($client_id, $from, $to) {
    $posts = readData('social_posts');
    $kept = [];
    $deleted = 0;
    foreach ($posts as $p) {
        $matches = ($p['client_id'] ?? '') === $client_id && ($p['date'] ?? '') >= $from && ($p['date'] ?? '') <= $to;
        if ($matches) $deleted++; else $kept[] = $p;
    }
    writeData('social_posts', $kept);
    return $deleted;
}

function getSocialPlatformOptions() {
    return [
        'instagram'  => 'Instagram',
        'facebook'   => 'Facebook',
        'stories'    => 'Stories',
        'web'        => 'Web / Quadern',
        'newsletter' => 'Newsletter',
        'altres'     => 'Altres',
    ];
}

function getSocialFormatOptions() {
    return [
        'reel'       => 'Reel',
        'carrusel'   => 'Carrusel',
        'story'      => 'Story',
        'post'       => 'Post',
        'article'    => 'Article',
        'newsletter' => 'Newsletter',
        'altres'     => 'Altres',
    ];
}

function getSocialObjectiveOptions() {
    return [
        'descobriment'  => 'Descobriment',
        'autoritat'     => 'Autoritat',
        'comunitat'     => 'Comunitat',
        'connexio'      => 'Connexió',
        'transit'       => 'Trànsit',
        'conversio'     => 'Conversió',
        'fidelitzacio'  => 'Fidelització',
    ];
}

function getSocialStatusOptions() {
    return [
        'idea'       => ['label' => 'Idea',         'class' => 'badge-gray'],
        'planificat' => ['label' => 'Planificat',   'class' => 'badge-blue'],
        'produccio'  => ['label' => 'En producció', 'class' => 'badge-gold'],
        'programat'  => ['label' => 'Programat',    'class' => 'badge-gold'],
        'publicat'   => ['label' => 'Publicat',     'class' => 'badge-green'],
        'descartat'  => ['label' => 'Descartat',    'class' => 'badge-red'],
    ];
}

// ─── EXPORTACIÓ / IMPORTACIÓ CSV DEL CALENDARI DE XARXES SOCIALS ───────────
function socialPostsCsvHeader() {
    return ['Client', 'Data', 'Plataforma', 'Format', 'Sèrie', 'Tema', 'Objectiu', 'Hook', 'Contingut', 'CTA', 'Material necessari', 'Reutilització', 'Estat', 'Puntuació'];
}

function exportSocialPostsCsv($client_id = null, $from = '', $to = '') {
    $posts = getSocialPosts($client_id ?: null);
    if ($from) $posts = array_values(array_filter($posts, fn($p) => ($p['date'] ?? '') >= $from));
    if ($to)   $posts = array_values(array_filter($posts, fn($p) => ($p['date'] ?? '') <= $to));

    $platforms = getSocialPlatformOptions();
    $formats   = getSocialFormatOptions();
    $objectius = getSocialObjectiveOptions();
    $statuses  = getSocialStatusOptions();

    $rows = [socialPostsCsvHeader()];
    foreach ($posts as $p) {
        $client = getClient($p['client_id'] ?? '');
        $rows[] = [
            $client['name'] ?? '',
            $p['date'] ?? '',
            $platforms[$p['platform'] ?? ''] ?? ($p['platform'] ?? ''),
            $formats[$p['format'] ?? ''] ?? ($p['format'] ?? ''),
            $p['series'] ?? '',
            $p['theme'] ?? '',
            $objectius[$p['objective'] ?? ''] ?? ($p['objective'] ?? ''),
            $p['hook'] ?? '',
            $p['content'] ?? '',
            $p['cta'] ?? '',
            $p['material'] ?? '',
            $p['reuse_notes'] ?? '',
            $statuses[$p['status'] ?? '']['label'] ?? ($p['status'] ?? ''),
            $p['score'] ?? '',
        ];
    }
    $fh = fopen('php://temp', 'w+');
    foreach ($rows as $r) fputcsv($fh, $r, ';');
    rewind($fh);
    $csv = stream_get_contents($fh);
    fclose($fh);
    return "\xEF\xBB\xBF" . $csv;
}

// Importa un CSV (idealment amb la mateixa capçalera que genera exportSocialPostsCsv,
// però és tolerant amb l'ordre de columnes i amb el delimitador , o ;).
// Les files sense client identificable s'assignen a $default_client_id si es passa.
function importSocialPostsCsv($filepath, $default_client_id = '') {
    $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];
    $fh = fopen($filepath, 'r');
    if (!$fh) { $result['errors'][] = 'No s\'ha pogut llegir el fitxer.'; return $result; }

    $first = fgets($fh);
    rewind($fh);
    $delim = (substr_count((string)$first, ';') >= substr_count((string)$first, ',')) ? ';' : ',';

    $bom = fread($fh, 3);
    if ($bom !== "\xEF\xBB\xBF") rewind($fh);

    $header = fgetcsv($fh, 0, $delim);
    if (!$header) { $result['errors'][] = 'El fitxer està buit.'; fclose($fh); return $result; }
    $header = array_map(fn($h) => mb_strtolower(trim((string)$h)), $header);

    $map = [
        'client' => 'client', 'data' => 'date', 'date' => 'date',
        'plataforma' => 'platform', 'platform' => 'platform', 'format' => 'format',
        'sèrie' => 'series', 'serie' => 'series', 'series' => 'series',
        'tema' => 'theme', 'theme' => 'theme',
        'objectiu' => 'objective', 'objective' => 'objective',
        'hook' => 'hook', 'contingut' => 'content', 'content' => 'content',
        'cta' => 'cta',
        'material necessari' => 'material', 'material' => 'material',
        'reutilització' => 'reuse_notes', 'reutilitzacio' => 'reuse_notes', 'reutilització/reaprofitament' => 'reuse_notes',
        'estat' => 'status', 'status' => 'status',
        'puntuació' => 'score', 'puntuacio' => 'score', 'score' => 'score',
    ];

    $clientsByName = [];
    foreach (getClients() as $c) $clientsByName[mb_strtolower(trim($c['name']))] = $c['id'];

    $platformKeys = array_keys(getSocialPlatformOptions());
    $formatKeys   = array_keys(getSocialFormatOptions());
    $objKeys      = array_keys(getSocialObjectiveOptions());
    $statusKeys   = array_keys(getSocialStatusOptions());
    $platformsRev = array_flip(array_map('mb_strtolower', getSocialPlatformOptions()));
    $formatsRev   = array_flip(array_map('mb_strtolower', getSocialFormatOptions()));
    $objRev       = array_flip(array_map('mb_strtolower', getSocialObjectiveOptions()));
    $statusRev    = [];
    foreach (getSocialStatusOptions() as $key => $s) $statusRev[mb_strtolower($s['label'])] = $key;

    $row_num = 1;
    while (($row = fgetcsv($fh, 0, $delim)) !== false) {
        $row_num++;
        if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue; // fila buida

        $data = [];
        foreach ($header as $i => $h) {
            $field = $map[$h] ?? null;
            if ($field) $data[$field] = trim((string)($row[$i] ?? ''));
        }

        $client_id = $default_client_id;
        if (!empty($data['client'])) {
            $key = mb_strtolower($data['client']);
            if (isset($clientsByName[$key])) $client_id = $clientsByName[$key];
        }
        if (!$client_id) {
            $result['skipped']++;
            $result['errors'][] = "Fila $row_num: no s'ha pogut identificar el client («" . ($data['client'] ?? '') . "»).";
            continue;
        }

        $date_raw = $data['date'] ?? '';
        $ts = $date_raw ? strtotime(str_replace('/', '-', $date_raw)) : false;
        $date = $ts ? date('Y-m-d', $ts) : '';

        $platform = mb_strtolower($data['platform'] ?? '');
        $platform = in_array($platform, $platformKeys, true) ? $platform : ($platformsRev[$platform] ?? 'altres');
        $format = mb_strtolower($data['format'] ?? '');
        $format = in_array($format, $formatKeys, true) ? $format : ($formatsRev[$format] ?? 'altres');
        $objective = mb_strtolower($data['objective'] ?? '');
        $objective = in_array($objective, $objKeys, true) ? $objective : ($objRev[$objective] ?? '');
        $status = mb_strtolower($data['status'] ?? '');
        $status = in_array($status, $statusKeys, true) ? $status : ($statusRev[$status] ?? 'idea');

        $post = [
            'id'          => generateId(),
            'client_id'   => $client_id,
            'date'        => $date,
            'platform'    => $platform,
            'format'      => $format,
            'series'      => $data['series'] ?? '',
            'theme'       => $data['theme'] ?? '',
            'objective'   => $objective,
            'hook'        => $data['hook'] ?? '',
            'content'     => $data['content'] ?? '',
            'cta'         => $data['cta'] ?? '',
            'material'    => $data['material'] ?? '',
            'reuse_notes' => $data['reuse_notes'] ?? '',
            'status'      => $status,
            'score'       => is_numeric($data['score'] ?? '') ? (int)$data['score'] : '',
        ];
        saveSocialPost($post);
        $result['imported']++;
    }
    fclose($fh);
    return $result;
}

// ══════════════════════════════════════════════════════════════════════════
// ─── HUB DE CLIENTS (portal akratechstudio.es/hub) ──────────────────────────
// ══════════════════════════════════════════════════════════════════════════
// Autenticació totalment independent de la de l'admin (claus de sessió
// diferents), perquè un client només puga veure la seua pròpia informació:
// factures, estat dels seus treballs, propostes i l'historial de
// comunicacions. Reutilitza les mateixes dades (JSON) que l'admin.

function hubClearSession() {
    unset($_SESSION['akra_hub_client_id'], $_SESSION['akra_hub_login_time']);
}

function hubLogin($email, $password) {
    $email = mb_strtolower(trim($email));
    if ($email === '' || $password === '') return false;
    foreach (getClients() as $c) {
        if ($email !== '' && mb_strtolower(trim($c['email'] ?? '')) === $email
            && !empty($c['hub_enabled'])
            && !empty($c['hub_password_hash'])
            && password_verify($password, $c['hub_password_hash'])) {
            $_SESSION['akra_hub_client_id']  = $c['id'];
            $_SESSION['akra_hub_login_time'] = time();
            return true;
        }
    }
    return false;
}

function hubLogout() {
    hubClearSession();
    header('Location: login.php');
    exit;
}

function hubIsLoggedIn() {
    return !empty($_SESSION['akra_hub_client_id']);
}

function hubRequireLogin() {
    if (!hubIsLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    $client = getClient($_SESSION['akra_hub_client_id']);
    if (!$client || empty($client['hub_enabled']) || !empty($client['deleted_at'])) {
        hubClearSession();
        header('Location: login.php?disabled=1');
        exit;
    }
}

function hubCurrentClient() {
    return hubIsLoggedIn() ? getClient($_SESSION['akra_hub_client_id']) : null;
}

// Genera una contrasenya aleatòria fàcil de llegir/dictar (sense caràcters ambigus).
function generateRandomPassword($length = 10) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $pw = '';
    for ($i = 0; $i < $length; $i++) $pw .= $chars[random_int(0, strlen($chars) - 1)];
    return $pw;
}

// Activa l'accés al Hub per a un client i li assigna una contrasenya nova.
// Retorna la contrasenya EN CLAR (només este moment — no es pot recuperar
// després, només es guarda el seu hash) perquè l'admin la puga mostrar/copiar
// i passar-la-hi al client.
function setClientHubPassword($client_id, $plain_password = null) {
    $client = getClient($client_id);
    if (!$client) return null;
    $plain = $plain_password ?: generateRandomPassword();
    $client['hub_password_hash'] = password_hash($plain, PASSWORD_DEFAULT);
    $client['hub_enabled'] = true;
    saveClient($client);
    return $plain;
}

function disableClientHubAccess($client_id) {
    $client = getClient($client_id);
    if (!$client) return;
    $client['hub_enabled'] = false;
    saveClient($client);
}

// ─── AVISOS AUTOMÀTICS AL CLIENT (email + WhatsApp) ─────────────────────────
// S'invoca cada vegada que hi ha un canvi rellevant per al client: estat d'un
// treball, factura nova, proposta nova o resposta a un missatge seu. Envia
// per email si 'notify_client_email' està actiu, i per WhatsApp si hi ha un
// proveïdor (Twilio o Meta) configurat a Configuració.
function getNotificationStrings() {
    return [
        'ca' => [
            'job_status_subject' => 'Actualització del teu treball',
            'job_status_body'    => "Hola %s,\n\nEl treball «%s» ha canviat d'estat a: %s.\n\nHo pots consultar amb més detall al teu portal:\n%s\n\nSalutacions,\nAKRA Tech Studio",
            'invoice_new_subject'=> 'Nova factura disponible',
            'invoice_new_body'   => "Hola %s,\n\nTens una nova factura disponible (%s) per import de %s €.\n\nPots veure-la i descarregar-la al teu portal:\n%s\n\nSalutacions,\nAKRA Tech Studio",
            'proposal_new_subject'=> 'Nova proposta per revisar',
            'proposal_new_body'  => "Hola %s,\n\nT'hem enviat una nova proposta per revisar.\n\nPots veure-la al teu portal:\n%s\n\nSalutacions,\nAKRA Tech Studio",
            'comm_reply_subject' => 'Nova resposta al teu missatge',
            'comm_reply_body'    => "Hola %s,\n\nHem respost al teu missatge. Ho pots consultar al teu portal:\n%s\n\nSalutacions,\nAKRA Tech Studio",
            'ticket_reply_subject' => 'Nova resposta al teu tiquet',
            'ticket_reply_body'    => "Hola %s,\n\nHem respost al teu tiquet «%s». Ho pots consultar al teu portal:\n%s\n\nSalutacions,\nAKRA Tech Studio",
            'ticket_status_subject'=> 'Actualització del teu tiquet',
            'ticket_status_body'   => "Hola %s,\n\nEl tiquet «%s» ha canviat d'estat a: %s.\n\nHo pots consultar al teu portal:\n%s\n\nSalutacions,\nAKRA Tech Studio",
            'calendar_approval_subject' => 'Calendari de continguts per revisar',
            'calendar_approval_body'    => "Hola %s,\n\nJa tens llest el calendari de continguts de %s per revisar. Pots acceptar-lo o demanar canvis des del teu portal:\n%s\n\nSi no el confirmes abans que passe el termini indicat, es donarà per acceptat automàticament.\n\nSalutacions,\nAKRA Tech Studio",
        ],
        'es' => [
            'job_status_subject' => 'Actualización de tu trabajo',
            'job_status_body'    => "Hola %s,\n\nEl trabajo «%s» ha cambiado de estado a: %s.\n\nPuedes consultarlo con más detalle en tu portal:\n%s\n\nSaludos,\nAKRA Tech Studio",
            'invoice_new_subject'=> 'Nueva factura disponible',
            'invoice_new_body'   => "Hola %s,\n\nTienes una nueva factura disponible (%s) por importe de %s €.\n\nPuedes verla y descargarla en tu portal:\n%s\n\nSaludos,\nAKRA Tech Studio",
            'proposal_new_subject'=> 'Nueva propuesta para revisar',
            'proposal_new_body'  => "Hola %s,\n\nTe hemos enviado una nueva propuesta para revisar.\n\nPuedes verla en tu portal:\n%s\n\nSaludos,\nAKRA Tech Studio",
            'comm_reply_subject' => 'Nueva respuesta a tu mensaje',
            'comm_reply_body'    => "Hola %s,\n\nHemos respondido a tu mensaje. Puedes consultarlo en tu portal:\n%s\n\nSaludos,\nAKRA Tech Studio",
            'ticket_reply_subject' => 'Nueva respuesta a tu ticket',
            'ticket_reply_body'    => "Hola %s,\n\nHemos respondido a tu ticket «%s». Puedes consultarlo en tu portal:\n%s\n\nSaludos,\nAKRA Tech Studio",
            'ticket_status_subject'=> 'Actualización de tu ticket',
            'ticket_status_body'   => "Hola %s,\n\nEl ticket «%s» ha cambiado de estado a: %s.\n\nPuedes consultarlo en tu portal:\n%s\n\nSaludos,\nAKRA Tech Studio",
            'calendar_approval_subject' => 'Calendario de contenidos para revisar',
            'calendar_approval_body'    => "Hola %s,\n\nYa tienes listo el calendario de contenidos de %s para revisar. Puedes aceptarlo o pedir cambios desde tu portal:\n%s\n\nSi no lo confirmas antes de que pase el plazo indicado, se dará por aceptado automáticamente.\n\nSaludos,\nAKRA Tech Studio",
        ],
        'en' => [
            'job_status_subject' => 'Update on your project',
            'job_status_body'    => "Hi %s,\n\nThe project \"%s\" status has changed to: %s.\n\nYou can check the details on your portal:\n%s\n\nBest,\nAKRA Tech Studio",
            'invoice_new_subject'=> 'New invoice available',
            'invoice_new_body'   => "Hi %s,\n\nYou have a new invoice available (%s) for %s €.\n\nYou can view and download it on your portal:\n%s\n\nBest,\nAKRA Tech Studio",
            'proposal_new_subject'=> 'New proposal to review',
            'proposal_new_body'  => "Hi %s,\n\nWe've sent you a new proposal to review.\n\nYou can view it on your portal:\n%s\n\nBest,\nAKRA Tech Studio",
            'comm_reply_subject' => 'New reply to your message',
            'comm_reply_body'    => "Hi %s,\n\nWe've replied to your message. You can check it on your portal:\n%s\n\nBest,\nAKRA Tech Studio",
            'ticket_reply_subject' => 'New reply to your ticket',
            'ticket_reply_body'    => "Hi %s,\n\nWe've replied to your ticket \"%s\". You can check it on your portal:\n%s\n\nBest,\nAKRA Tech Studio",
            'ticket_status_subject'=> 'Update on your ticket',
            'ticket_status_body'   => "Hi %s,\n\nTicket \"%s\" status changed to: %s.\n\nYou can check it on your portal:\n%s\n\nBest,\nAKRA Tech Studio",
            'calendar_approval_subject' => 'Content calendar ready for review',
            'calendar_approval_body'    => "Hi %s,\n\nYour content calendar for %s is ready for review. You can approve it or request changes from your portal:\n%s\n\nIf you don't confirm before the deadline shown there, it will be automatically approved.\n\nBest,\nAKRA Tech Studio",
        ],
    ];
}

function notifyClientOfChange($client_id, $event, $params = []) {
    $client = getClient($client_id);
    if (!$client || empty($client['hub_enabled'])) return; // sense accés al Hub no té sentit avisar-lo

    $cfg  = getAdminConfig();
    $lang = getClientHubLang($client);
    $strings = getNotificationStrings();
    $lang = array_key_exists($lang, $strings) ? $lang : 'ca';
    $s = $strings[$lang];

    $subject_key = $event . '_subject';
    $body_key    = $event . '_body';
    if (!isset($s[$subject_key], $s[$body_key])) return;

    $hub_url = ($cfg['site_url'] ?? 'https://akratechstudio.es') . '/hub/' . ($params['hub_page'] ?? 'index.php');
    $subject = $s[$subject_key];
    $body    = vsprintf($s[$body_key], array_merge([$client['name']], $params['body_args'] ?? [], [$hub_url]));

    if (!empty($cfg['notify_client_email']) && !empty($client['email'])) {
        $from_name  = $cfg['site_name'] ?? 'AKRA Tech Studio';
        $from_email = $cfg['email'] ?? 'hola@akratechstudio.es';
        $headers  = "From: $from_name <$from_email>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        @mail($client['email'], $subject, $body, $headers);
    }

    if (!empty($cfg['wa_notify_provider']) && !empty($client['phone'])) {
        sendWhatsAppMessage($client['phone'], $subject . "\n\n" . $body, $cfg);
    }
}

// Envia un WhatsApp mitjançant el proveïdor configurat a Configuració
// (Twilio o Meta Cloud API). Retorna ['ok' => bool, 'error' => string|null].
function sendWhatsAppMessage($to_number, $message, $cfg = null) {
    $cfg = $cfg ?? getAdminConfig();
    $to  = preg_replace('/[^0-9]/', '', $to_number);
    if (!$to) return ['ok' => false, 'error' => 'Número de destinació no vàlid.'];
    if (!function_exists('curl_init')) return ['ok' => false, 'error' => 'Falta l\'extensió cURL de PHP.'];

    $provider = $cfg['wa_notify_provider'] ?? '';

    if ($provider === 'twilio') {
        $sid = $cfg['wa_notify_twilio_sid'] ?? '';
        $token = $cfg['wa_notify_twilio_token'] ?? '';
        $from = preg_replace('/[^0-9]/', '', $cfg['wa_notify_twilio_from'] ?? '');
        if (!$sid || !$token || !$from) return ['ok' => false, 'error' => 'Falta configurar Twilio a Configuració.'];

        $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['From' => 'whatsapp:+' . $from, 'To' => 'whatsapp:+' . $to, 'Body' => $message]),
            CURLOPT_USERPWD => "$sid:$token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['ok' => $code >= 200 && $code < 300, 'error' => ($code >= 200 && $code < 300) ? null : "Error Twilio ($code)"];
    }

    if ($provider === 'meta') {
        $token = $cfg['wa_notify_meta_token'] ?? '';
        $phone_id = $cfg['wa_notify_meta_phone_id'] ?? '';
        if (!$token || !$phone_id) return ['ok' => false, 'error' => 'Falta configurar Meta Cloud API a Configuració.'];

        $ch = curl_init("https://graph.facebook.com/v20.0/$phone_id/messages");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'text', 'text' => ['body' => $message]]),
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $token", "Content-Type: application/json"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['ok' => $code >= 200 && $code < 300, 'error' => ($code >= 200 && $code < 300) ? null : "Error Meta ($code)"];
    }

    return ['ok' => false, 'error' => 'No hi ha cap proveïdor de WhatsApp configurat.'];
}

// Idiomes disponibles al portal del client (subconjunt dels de la web pública,
// centrats en els idiomes reals dels clients de l'agència).
function getHubLangOptions() {
    return ['ca' => 'Català', 'es' => 'Castellano', 'en' => 'English'];
}

function getClientHubLang($client) {
    $lang = $client['hub_lang'] ?? 'ca';
    return array_key_exists($lang, getHubLangOptions()) ? $lang : 'ca';
}

// Guarda la llengua del portal per a un client (des del propi Hub o des de l'admin).
function setClientHubLang($client_id, $lang) {
    if (!array_key_exists($lang, getHubLangOptions())) return false;
    $client = getClient($client_id);
    if (!$client) return false;
    $client['hub_lang'] = $lang;
    saveClient($client);
    return true;
}

// ══════════════════════════════════════════════════════════════════════════
// ─── APROVACIÓ DEL CALENDARI DE XARXES PER PART DEL CLIENT ─────────────────
// ══════════════════════════════════════════════════════════════════════════
// Cada registre representa "el calendari del mes X per al client Y" i el seu
// estat d'aprovació. Si el client no respon abans del termini, es dona per
// acceptat automàticament (resolveCalendarApproval() ho aplica sol quan es
// consulta el registre, tant des de l'admin com des del Hub).

function getCalendarApprovalStatusOptions() {
    return [
        'pendent'             => ['label' => 'Pendent d\'aprovació',      'class' => 'badge-gold'],
        'acceptat'            => ['label' => 'Acceptat pel client',       'class' => 'badge-green'],
        'acceptat_auto'       => ['label' => 'Acceptat (per termini)',    'class' => 'badge-blue'],
        'canvis_sollicitats'  => ['label' => 'Canvis sol·licitats',       'class' => 'badge-red'],
    ];
}

// Termini límit de confirmació: el més tardà entre "3 dies abans que
// comence el mes" i "5 dies després d'haver-lo publicat" — així el client
// sempre té almenys 5 dies per revisar-lo, encara que es publique tard.
function calendarApprovalDeadline($approval) {
    $month_start = strtotime($approval['month'] . '-01');
    $rule_before_month = strtotime('-3 days', $month_start);
    $rule_after_publish = strtotime('+5 days', strtotime($approval['published_at'] ?? $approval['month'] . '-01'));
    return max($rule_before_month, $rule_after_publish);
}

// Si encara consta "pendent" però ja ha passat el termini, l'accepta sol.
function resolveCalendarApproval($approval) {
    if (($approval['status'] ?? '') === 'pendent' && time() > calendarApprovalDeadline($approval)) {
        $approval['status'] = 'acceptat_auto';
        $approval['decided_at'] = date('Y-m-d H:i:s');
        saveCalendarApproval($approval);
    }
    return $approval;
}

function saveCalendarApproval($approval) {
    $items = readData('calendar_approvals');
    $idx = array_search($approval['id'], array_column($items, 'id'));
    if ($idx !== false) $items[$idx] = $approval; else $items[] = $approval;
    writeData('calendar_approvals', $items);
    return $approval;
}

function getCalendarApproval($client_id, $month) {
    foreach (readData('calendar_approvals') as $a) {
        if (($a['client_id'] ?? '') === $client_id && ($a['month'] ?? '') === $month) return resolveCalendarApproval($a);
    }
    return null;
}

function getCalendarApprovals($client_id = null) {
    $items = readData('calendar_approvals');
    if ($client_id) $items = array_filter($items, fn($a) => ($a['client_id'] ?? '') === $client_id);
    $items = array_map('resolveCalendarApproval', $items);
    usort($items, fn($a, $b) => strcmp($b['month'] ?? '', $a['month'] ?? ''));
    return array_values($items);
}

// L'agència marca el calendari d'eixe mes com a "llest, envia'l a revisar".
function publishCalendarForApproval($client_id, $month) {
    $approval = getCalendarApprovalRaw($client_id, $month) ?: ['id' => generateId(), 'client_id' => $client_id, 'month' => $month];
    $approval['status']         = 'pendent';
    $approval['published_at']   = date('Y-m-d H:i:s');
    $approval['decided_at']     = '';
    $approval['client_comment'] = '';
    saveCalendarApproval($approval);
    notifyClientOfChange($client_id, 'calendar_approval', [
        'body_args' => [monthLabelCa(strtotime($month . '-01'))],
        'hub_page'  => 'calendari.php?month=' . $month,
    ]);
    return $approval;
}

function getCalendarApprovalRaw($client_id, $month) {
    foreach (readData('calendar_approvals') as $a) {
        if (($a['client_id'] ?? '') === $client_id && ($a['month'] ?? '') === $month) return $a;
    }
    return null;
}

// El client accepta o demana canvis des del Hub.
function decideCalendarApproval($client_id, $month, $decision, $comment = '') {
    if (!in_array($decision, ['acceptat', 'canvis_sollicitats'])) return null;
    $approval = getCalendarApprovalRaw($client_id, $month);
    if (!$approval) return null;
    $approval['status']         = $decision;
    $approval['decided_at']     = date('Y-m-d H:i:s');
    $approval['client_comment'] = $comment;
    saveCalendarApproval($approval);
    notifyAgencyOfCalendarDecision($approval);
    return $approval;
}

// Avisa l'agència per email quan el client accepta o demana canvis.
function notifyAgencyOfCalendarDecision($approval) {
    $cfg = getAdminConfig();
    $to = $cfg['email'] ?? '';
    if (!$to) return;
    $client = getClient($approval['client_id']);
    $st = getCalendarApprovalStatusOptions();
    $subject = ($approval['status'] === 'acceptat' ? '✅ Calendari acceptat: ' : '✏️ Canvis sol·licitats al calendari: ') .
        ($client['name'] ?? '') . ' — ' . monthLabelCa(strtotime($approval['month'] . '-01'));
    $body = "Client: " . ($client['name'] ?? '') . "\n" .
        "Mes: " . monthLabelCa(strtotime($approval['month'] . '-01')) . "\n" .
        "Estat: " . ($st[$approval['status']]['label'] ?? $approval['status']) . "\n" .
        (!empty($approval['client_comment']) ? "\nComentari del client:\n" . $approval['client_comment'] . "\n" : '') .
        "\nRevisa-ho a: " . ($cfg['site_url'] ?? 'https://akratechstudio.es') . "/admin/social-calendar.php?client=" . $approval['client_id'];
    $from_name = $cfg['site_name'] ?? 'AKRA Tech Studio';
    $headers  = "From: $from_name <$to>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    @mail($to, $subject, $body, $headers);
}

// ══════════════════════════════════════════════════════════════════════════
// ─── COMPTABILITAT: PROVEÏDORS I DESPESES ───────────────────────────────────
// ══════════════════════════════════════════════════════════════════════════

function getSuppliers() {
    $s = readActiveData('suppliers');
    usort($s, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
    return $s;
}

function getSupplier($id) {
    foreach (readData('suppliers') as $s) if ($s['id'] === $id) return $s;
    return null;
}

function saveSupplier($supplier) {
    $suppliers = readData('suppliers');
    $idx = array_search($supplier['id'], array_column($suppliers, 'id'));
    if ($idx !== false) $suppliers[$idx] = array_merge($suppliers[$idx], $supplier);
    else $suppliers[] = $supplier;
    writeData('suppliers', $suppliers);
    return $supplier;
}

function deleteSupplier($id) {
    softDeleteRecord('suppliers', $id);
}

function getExpenseCategoryOptions() {
    return [
        'subministraments' => 'Subministraments (llum, aigua, internet)',
        'lloguer'          => 'Lloguer / espai de treball',
        'software'         => 'Software i eines',
        'publicitat'       => 'Publicitat i marketing',
        'assessoria'       => 'Assessoria / gestoria',
        'material'         => 'Material i equipament',
        'formacio'         => 'Formació',
        'desplacament'     => 'Desplaçament i dietes',
        'subcontractacio'  => 'Subcontractació / col·laboradors',
        'altres'           => 'Altres',
    ];
}

function getVatRateOptions() {
    return [0 => '0% (exempt)', 4 => '4%', 10 => '10%', 21 => '21%'];
}

// ─── DESPESES ────────────────────────────────────────────────────────────
function getExpenses($filters = []) {
    $expenses = readActiveData('expenses');
    if (!empty($filters['supplier_id'])) $expenses = array_filter($expenses, fn($e) => ($e['supplier_id'] ?? '') === $filters['supplier_id']);
    if (!empty($filters['category']))    $expenses = array_filter($expenses, fn($e) => ($e['category'] ?? '') === $filters['category']);
    if (!empty($filters['from']))        $expenses = array_filter($expenses, fn($e) => ($e['date'] ?? '') >= $filters['from']);
    if (!empty($filters['to']))          $expenses = array_filter($expenses, fn($e) => ($e['date'] ?? '') <= $filters['to']);
    if (isset($filters['deductible']))   $expenses = array_filter($expenses, fn($e) => (bool)($e['deductible'] ?? true) === (bool)$filters['deductible']);
    usort($expenses, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return array_values($expenses);
}

function getExpense($id) {
    foreach (readData('expenses') as $e) if ($e['id'] === $id) return $e;
    return null;
}

// Calcula base/IVA/total a partir de la base imposable i el % d'IVA, igual
// que ja es fa amb les factures.
function calcExpenseAmounts($base, $vat_pct) {
    $base = round((float)$base, 2);
    $tax  = round($base * $vat_pct / 100, 2);
    return ['base' => $base, 'tax' => $tax, 'total' => round($base + $tax, 2)];
}

function saveExpense($expense) {
    $amounts = calcExpenseAmounts($expense['base'] ?? 0, $expense['vat_pct'] ?? 21);
    $expense['base']  = $amounts['base'];
    $expense['tax']   = $amounts['tax'];
    $expense['total'] = $amounts['total'];

    $expenses = readData('expenses');
    $idx = array_search($expense['id'], array_column($expenses, 'id'));
    if ($idx !== false) $expenses[$idx] = array_merge($expenses[$idx], $expense);
    else $expenses[] = $expense;
    writeData('expenses', $expenses);
    return $expense;
}

function deleteExpense($id) {
    $e = getExpense($id);
    if ($e && !empty($e['receipt_file'])) {
        $full = AKRA_ROOT . '/' . $e['receipt_file'];
        if (file_exists($full)) @unlink($full);
    }
    softDeleteRecord('expenses', $id);
}

// ══════════════════════════════════════════════════════════════════════════
// ─── INFORMES: BENEFICI, IVA TRIMESTRAL I ESTIMACIÓ D'IRPF ─────────────────
// ══════════════════════════════════════════════════════════════════════════
// AVÍS IMPORTANT: estos informes són una AJUDA per decidir i per passar-los
// a la teua gestoria — no són una eina de presentació d'impostos ni
// substitueixen l'assessorament fiscal professional. Els càlculs d'IVA i
// IRPF segueixen les regles generals del règim d'estimació directa per a
// autònoms, però no cobreixen casos especials (mòduls, prorrata, béns
// d'inversió, etc.).

// Ingressos (factures) i despeses reals d'un rang de dates, per calcular
// benefici net. Els ingressos es compten per DATA D'EMISSIÓ de la factura
// (no per data de cobrament), que és el criteri fiscal habitual (meritació).
function getProfitAndLoss($from, $to) {
    $invoices = array_filter(readActiveData('invoices'), fn($i) => ($i['status'] ?? '') !== 'cancelled' && ($i['date'] ?? '') >= $from && ($i['date'] ?? '') <= $to);
    $expenses = getExpenses(['from' => $from, 'to' => $to]);

    $income_base = array_sum(array_column($invoices, 'subtotal'));
    $income_tax  = array_sum(array_column($invoices, 'tax'));
    $expense_base = array_sum(array_column($expenses, 'base'));
    $expense_tax  = array_sum(array_column($expenses, 'tax'));

    $by_category = [];
    foreach ($expenses as $e) {
        $cat = $e['category'] ?? 'altres';
        $by_category[$cat] = ($by_category[$cat] ?? 0) + $e['base'];
    }
    arsort($by_category);

    return [
        'income_base'   => round($income_base, 2),
        'income_tax'    => round($income_tax, 2),
        'expense_base'  => round($expense_base, 2),
        'expense_tax'   => round($expense_tax, 2),
        'net_profit'    => round($income_base - $expense_base, 2),
        'invoice_count' => count($invoices),
        'expense_count' => count($expenses),
        'by_category'   => $by_category,
    ];
}

// Rang de dates d'un trimestre natural (T1: gen-mar, T2: abr-jun, T3: jul-set, T4: oct-des)
function quarterDateRange($year, $quarter) {
    $start_month = ($quarter - 1) * 3 + 1;
    $from = sprintf('%04d-%02d-01', $year, $start_month);
    $to   = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $start_month + 2)));
    return [$from, $to];
}

// IVA repercutit (factures) - IVA suportat (despeses) = resultat del trimestre (Model 303)
function getQuarterlyVatSummary($year, $quarter) {
    [$from, $to] = quarterDateRange($year, $quarter);
    $pl = getProfitAndLoss($from, $to);
    $result = round($pl['income_tax'] - $pl['expense_tax'], 2);
    return [
        'from' => $from, 'to' => $to,
        'iva_repercutit' => $pl['income_tax'],
        'iva_suportat'   => $pl['expense_tax'],
        'resultat'       => $result,
        'a_pagar'        => $result > 0 ? $result : 0,
        'a_compensar'    => $result < 0 ? abs($result) : 0,
    ];
}

// Estimació del pagament fraccionat d'IRPF (Model 130) — rendiment net
// acumulat de l'any x 20%, menys retencions ja practicades a les factures
// emeses i menys els pagaments fraccionats d'trimestres anteriors del mateix any.
function getQuarterlyIrpfEstimate($year, $quarter) {
    $cumulative = function($upto_quarter) use ($year) {
        [, $to] = quarterDateRange($year, $upto_quarter);
        $from = sprintf('%04d-01-01', $year);
        $pl = getProfitAndLoss($from, $to);
        $invoices = array_filter(readActiveData('invoices'), fn($i) => ($i['status'] ?? '') !== 'cancelled' && ($i['date'] ?? '') >= $from && ($i['date'] ?? '') <= $to);
        $retention = array_sum(array_column($invoices, 'irpf'));
        return ['net' => $pl['net_profit'], 'retention' => round($retention, 2)];
    };

    $this_q = $cumulative($quarter);
    $gross_payment = max(0, round($this_q['net'] * 0.20, 2));

    $prior_payments = 0;
    for ($q = 1; $q < $quarter; $q++) {
        $prior = $cumulative($q);
        $prior_gross = max(0, round($prior['net'] * 0.20, 2));
        $prior_payments += max(0, round($prior_gross - $prior['retention'], 2));
    }

    $due = round($gross_payment - $this_q['retention'] - $prior_payments, 2);

    return [
        'net_profit_cumulative' => $this_q['net'],
        'retention_cumulative'  => $this_q['retention'],
        'gross_payment'         => $gross_payment,
        'prior_payments'        => round($prior_payments, 2),
        'due'                   => max(0, $due),
    ];
}

// ─── EXPORTACIÓ CSV PER A LA GESTORIA ───────────────────────────────────────
function exportAccountingCsv($from, $to) {
    $invoices = array_filter(readActiveData('invoices'), fn($i) => ($i['status'] ?? '') !== 'cancelled' && ($i['date'] ?? '') >= $from && ($i['date'] ?? '') <= $to);
    usort($invoices, fn($a, $b) => strcmp($a['date'], $b['date']));
    $expenses = getExpenses(['from' => $from, 'to' => $to]);

    $rows = [['Tipus', 'Data', 'Núm/Concepte', 'Client/Proveïdor', 'Categoria', 'Base', '% IVA', 'IVA', '% IRPF', 'IRPF', 'Total']];
    foreach ($invoices as $inv) {
        $client = getClient($inv['client_id'] ?? '');
        $rows[] = ['Ingrés', $inv['date'], $inv['number'], $client['name'] ?? '', '', number_format($inv['subtotal'], 2, ',', ''), $inv['tax_pct'] . '%', number_format($inv['tax'], 2, ',', ''), ($inv['irpf_pct'] ?? 0) . '%', number_format($inv['irpf'] ?? 0, 2, ',', ''), number_format($inv['total'], 2, ',', '')];
    }
    $cats = getExpenseCategoryOptions();
    foreach ($expenses as $e) {
        $supplier = !empty($e['supplier_id']) ? getSupplier($e['supplier_id']) : null;
        $rows[] = ['Despesa', $e['date'], $e['concept'] ?? '', $supplier['name'] ?? ($e['supplier_name'] ?? ''), $cats[$e['category'] ?? ''] ?? '', number_format($e['base'], 2, ',', ''), $e['vat_pct'] . '%', number_format($e['tax'], 2, ',', ''), '', '', number_format($e['total'], 2, ',', '')];
    }

    $fh = fopen('php://temp', 'w+');
    foreach ($rows as $r) fputcsv($fh, $r, ';');
    rewind($fh);
    $csv = stream_get_contents($fh);
    fclose($fh);
    return "\xEF\xBB\xBF" . $csv;
}

// ══════════════════════════════════════════════════════════════════════════
// ─── TICKETS (incidències i problemes que informen els clients) ────────────
// ══════════════════════════════════════════════════════════════════════════
// Cada tiquet és una fitxa (client, assumpte, categoria, prioritat, estat)
// amb un fil de missatges propi (com un xicotet xat entre agència i client).

function getTicketCategoryOptions() {
    return [
        'incidencia' => 'Incidència / error',
        'dubte'      => 'Dubte',
        'peticio'    => 'Petició de canvi',
        'facturacio' => 'Facturació',
        'altres'     => 'Altres',
    ];
}

function getTicketPriorityOptions() {
    return [
        'baixa'  => ['label' => 'Baixa',   'class' => 'badge-gray'],
        'mitjana'=> ['label' => 'Mitjana', 'class' => 'badge-blue'],
        'alta'   => ['label' => 'Alta',    'class' => 'badge-gold'],
        'urgent' => ['label' => 'Urgent',  'class' => 'badge-red'],
    ];
}

function getTicketStatusOptions() {
    return [
        'obert'     => ['label' => 'Obert',      'class' => 'badge-red'],
        'en_proces' => ['label' => 'En procés',  'class' => 'badge-gold'],
        'resolt'    => ['label' => 'Resolt',     'class' => 'badge-green'],
        'tancat'    => ['label' => 'Tancat',     'class' => 'badge-gray'],
    ];
}

function getTickets($client_id = null, $status = null) {
    $tickets = readData('tickets');
    if ($client_id) $tickets = array_filter($tickets, fn($t) => ($t['client_id'] ?? '') === $client_id);
    if ($status)    $tickets = array_filter($tickets, fn($t) => ($t['status'] ?? '') === $status);
    usort($tickets, fn($a, $b) => strcmp($b['updated_at'] ?? $b['created_at'] ?? '', $a['updated_at'] ?? $a['created_at'] ?? ''));
    return array_values($tickets);
}

function getTicket($id) {
    foreach (readData('tickets') as $t) if ($t['id'] === $id) return $t;
    return null;
}

function saveTicket($ticket) {
    $tickets = readData('tickets');
    $now = date('Y-m-d H:i:s');
    $idx = array_search($ticket['id'], array_column($tickets, 'id'));
    if ($idx !== false) {
        $ticket['created_at'] = $tickets[$idx]['created_at'] ?? $now;
        $ticket['updated_at'] = $now;
        $tickets[$idx] = $ticket;
    } else {
        $ticket['created_at'] = $now;
        $ticket['updated_at'] = $now;
        $tickets[] = $ticket;
    }
    writeData('tickets', $tickets);
    return $ticket;
}

function deleteTicket($id) {
    writeData('tickets', array_values(array_filter(readData('tickets'), fn($t) => $t['id'] !== $id)));
    writeData('ticket_messages', array_values(array_filter(readData('ticket_messages'), fn($m) => $m['ticket_id'] !== $id)));
}

function countOpenTickets($client_id = null) {
    $tickets = getTickets($client_id);
    return count(array_filter($tickets, fn($t) => !in_array($t['status'] ?? '', ['resolt', 'tancat'])));
}

// ─── MISSATGES D'UN TIQUET (fil de conversa) ────────────────────────────────
function getTicketMessages($ticket_id) {
    $msgs = array_values(array_filter(readData('ticket_messages'), fn($m) => $m['ticket_id'] === $ticket_id));
    usort($msgs, fn($a, $b) => strcmp($a['created_at'] ?? '', $b['created_at'] ?? ''));
    return $msgs;
}

function saveTicketMessage($msg) {
    $msgs = readData('ticket_messages');
    $msg['id'] = $msg['id'] ?: generateId();
    $msg['created_at'] = $msg['created_at'] ?? date('Y-m-d H:i:s');
    $msgs[] = $msg;
    writeData('ticket_messages', $msgs);
    // Toca el tiquet perquè "actualitzat" reflectisca l'últim missatge
    $ticket = getTicket($msg['ticket_id']);
    if ($ticket) { $ticket['updated_at'] = date('Y-m-d H:i:s'); saveTicket($ticket); }
    return $msg;
}

// Marca com a llegits (per l'agència) tots els missatges que el client ha
// enviat en un tiquet. S'invoca en obrir la fitxa del tiquet des de l'admin.
function markTicketMessagesReadByAgency($ticket_id) {
    $msgs = readData('ticket_messages');
    $changed = false;
    foreach ($msgs as &$m) {
        if ($m['ticket_id'] === $ticket_id && ($m['sender'] ?? '') === 'client' && empty($m['read_by_agency'])) {
            $m['read_by_agency'] = true;
            $changed = true;
        }
    }
    unset($m);
    if ($changed) writeData('ticket_messages', $msgs);
}

// Marca com a llegits (pel client, al Hub) tots els missatges que l'agència
// ha enviat en un tiquet.
function markTicketMessagesReadByClient($ticket_id) {
    $msgs = readData('ticket_messages');
    $changed = false;
    foreach ($msgs as &$m) {
        if ($m['ticket_id'] === $ticket_id && ($m['sender'] ?? '') === 'agency' && empty($m['read_by_client'])) {
            $m['read_by_client'] = true;
            $changed = true;
        }
    }
    unset($m);
    if ($changed) writeData('ticket_messages', $msgs);
}

function countUnreadTicketMessagesForAgency() {
    $msgs = readData('ticket_messages');
    return count(array_filter($msgs, fn($m) => ($m['sender'] ?? '') === 'client' && empty($m['read_by_agency'])));
}

// Avisa l'agència (per email, a l'adreça de Configuració) quan un client obri
// un tiquet nou o hi afig un missatge nou.
function notifyAgencyOfTicket($ticket, $is_new) {
    $cfg = getAdminConfig();
    $to = $cfg['email'] ?? '';
    if (!$to) return;
    $client = getClient($ticket['client_id']);
    $subject = ($is_new ? '🎫 Tiquet nou: ' : '🎫 Nou missatge al tiquet: ') . $ticket['subject'];
    $body = ($is_new ? "S'ha obert un tiquet nou" : "Hi ha un missatge nou en un tiquet") .
        " de " . ($client['name'] ?? 'un client') . ".\n\n" .
        "Assumpte: " . $ticket['subject'] . "\n" .
        "Prioritat: " . (getTicketPriorityOptions()[$ticket['priority'] ?? 'mitjana']['label'] ?? '') . "\n\n" .
        "Revisa'l a l'admin: " . ($cfg['site_url'] ?? 'https://akratechstudio.es') . "/admin/ticket-view.php?id=" . $ticket['id'];
    $from_name  = $cfg['site_name'] ?? 'AKRA Tech Studio';
    $headers  = "From: $from_name <$to>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    @mail($to, $subject, $body, $headers);
}




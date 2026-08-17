<?php
// hub/set-lang.php — Canvia l'idioma del portal.
// Si el client ja ha iniciat sessió, es guarda a la seua fitxa (persisteix per
// a futures visites, i és visible/editable des de l'admin). Si encara no ha
// entrat (pantalla de login), es guarda nomes en una cookie temporal.
require_once 'includes/hub-core.php';

$lang = $_GET['lang'] ?? $_POST['lang'] ?? 'ca';
if (!array_key_exists($lang, getHubLangOptions())) $lang = 'ca';

if (hubIsLoggedIn()) {
    setClientHubLang($_SESSION['akra_hub_client_id'], $lang);
} else {
    setcookie('akra_hub_lang', $lang, time() + 60 * 60 * 24 * 180, '/');
}

$return_to = $_GET['return_to'] ?? $_POST['return_to'] ?? 'index.php';
// Evita redireccions obertes: només permet fitxers locals del propi Hub
if (!preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $return_to)) $return_to = 'index.php';

header('Location: ' . $return_to);
exit;

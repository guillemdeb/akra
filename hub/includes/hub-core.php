<?php
// hub/includes/hub-core.php — Arrencada del portal de clients (akratechstudio.es/hub)
// Reutilitza el mateix motor de dades que l'admin (mateixos fitxers JSON a
// /admin/data/), però amb sessió i autenticació totalment independents.
require_once dirname(__DIR__, 2) . '/admin/includes/core.php';
require_once __DIR__ . '/hub-i18n.php';

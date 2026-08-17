<?php
// Inicialització de la base de dades SQLite
$db_file = 'countdowns.db';
$db = new SQLite3($db_file);

// Crear taula si no existeix
$db->exec('CREATE TABLE IF NOT EXISTS countdowns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    start_date TEXT NOT NULL,
    end_date TEXT NOT NULL,
    color TEXT DEFAULT "#C08A2E",
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)');

// API Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'add':
            $stmt = $db->prepare('INSERT INTO countdowns (title, start_date, end_date, color) VALUES (:title, :start_date, :end_date, :color)');
            $stmt->bindValue(':title', $input['title'], SQLITE3_TEXT);
            $stmt->bindValue(':start_date', $input['start_date'], SQLITE3_TEXT);
            $stmt->bindValue(':end_date', $input['end_date'], SQLITE3_TEXT);
            $stmt->bindValue(':color', $input['color'] ?? '#C08A2E', SQLITE3_TEXT);
            $stmt->execute();
            echo json_encode(['success' => true, 'id' => $db->lastInsertRowID()]);
            break;

        case 'delete':
            $stmt = $db->prepare('DELETE FROM countdowns WHERE id = :id');
            $stmt->bindValue(':id', $input['id'], SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'update':
            $stmt = $db->prepare('UPDATE countdowns SET title = :title, start_date = :start_date, end_date = :end_date, color = :color WHERE id = :id');
            $stmt->bindValue(':id', $input['id'], SQLITE3_INTEGER);
            $stmt->bindValue(':title', $input['title'], SQLITE3_TEXT);
            $stmt->bindValue(':start_date', $input['start_date'], SQLITE3_TEXT);
            $stmt->bindValue(':end_date', $input['end_date'], SQLITE3_TEXT);
            $stmt->bindValue(':color', $input['color'], SQLITE3_TEXT);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'getAll':
            $results = $db->query('SELECT * FROM countdowns ORDER BY end_date ASC');
            $countdowns = [];
            while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
                $countdowns[] = $row;
            }
            echo json_encode($countdowns);
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Compte Enrere · Gestor de temporitzadors</title>
  <link rel="icon" type="image/png" href="logo.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..700&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --cream: #FBF7EF;
      --cream-deep: #F1E8D6;
      --panel: #FFFDF8;
      --ink: #12303F;
      --ink-soft: #5B7382;
      --ink-faint: #8A9AA4;
      --line: rgba(18, 48, 63, 0.13);
      --gold: #C08A2E;
      --gold-soft: rgba(192, 138, 46, 0.14);
      --coral: #C24B3F;
      --good: #3E7C5A;
      --radius: 16px;
      --shadow: 0 1px 2px rgba(18, 48, 63, 0.04), 0 8px 24px rgba(18, 48, 63, 0.06);
      --shadow-hover: 0 4px 10px rgba(18, 48, 63, 0.06), 0 16px 36px rgba(18, 48, 63, 0.10);
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation-duration: 0.001ms !important; transition-duration: 0.001ms !important; }
    }

    html { overflow-x: hidden; }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--cream);
      background-image: radial-gradient(circle at 12% 8%, rgba(192,138,46,0.07), transparent 45%),
                         radial-gradient(circle at 90% 90%, rgba(18,48,63,0.05), transparent 40%);
      color: var(--ink);
      min-height: 100vh;
      overflow-x: hidden;
      width: 100%;
    }

    .topbar {
      display: flex;
      align-items: center;
      gap: 1rem;
      max-width: 1280px;
      margin: 0 auto;
      padding: 2.5rem 2rem 1.5rem;
    }

    .brand-mark {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      box-shadow: var(--shadow);
      flex-shrink: 0;
    }

    .brand-text h1 {
      font-family: 'Fraunces', serif;
      font-optical-sizing: auto;
      font-weight: 600;
      font-size: clamp(1.6rem, 3vw, 2.1rem);
      letter-spacing: -0.01em;
    }

    .brand-text .tagline {
      color: var(--ink-soft);
      font-size: 0.95rem;
      margin-top: 0.15rem;
    }

    .container {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 2rem 4rem;
    }

    .layout {
      display: grid;
      grid-template-columns: minmax(300px, 380px) 1fr;
      gap: 1.75rem;
      align-items: start;
      margin-bottom: 1.75rem;
    }

    .panel {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      padding: 1.75rem;
      box-shadow: var(--shadow);
    }

    .panel h2 {
      font-family: 'Fraunces', serif;
      font-weight: 600;
      font-size: 1.25rem;
      margin-bottom: 1.25rem;
      color: var(--ink);
    }

    .input-group { margin-bottom: 1.1rem; }

    label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.4rem;
      color: var(--ink-soft);
      text-transform: uppercase;
      font-size: 0.72rem;
      letter-spacing: 0.07em;
    }

    input[type="text"],
    input[type="datetime-local"],
    input[type="search"],
    select {
      width: 100%;
      padding: 0.75rem 0.9rem;
      font-size: 0.95rem;
      font-family: 'Inter', sans-serif;
      background: var(--cream);
      border: 1.5px solid var(--line);
      border-radius: 10px;
      color: var(--ink);
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
      outline: none;
    }

    input[type="color"] {
      width: 100%;
      height: 44px;
      padding: 4px;
      border: 1.5px solid var(--line);
      border-radius: 10px;
      background: var(--cream);
      cursor: pointer;
    }

    input:focus, select:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 4px var(--gold-soft);
    }

    .form-actions { display: flex; gap: 0.6rem; margin-top: 1.4rem; }

    button {
      padding: 0.85rem 1.4rem;
      font-size: 0.92rem;
      font-weight: 600;
      font-family: 'Inter', sans-serif;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.2s ease;
      background: var(--ink);
      color: var(--cream);
    }

    button:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(18, 48, 63, 0.18); }
    button:active { transform: translateY(0); }

    #save-btn { flex: 1; background: var(--gold); color: #2A1D06; }

    .btn-ghost {
      background: transparent;
      color: var(--ink-soft);
      border: 1.5px solid var(--line);
      box-shadow: none;
    }
    .btn-ghost:hover { color: var(--ink); box-shadow: none; border-color: var(--ink-soft); }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0.9rem;
      margin-bottom: 1.5rem;
    }

    .stat-card {
      background: var(--cream);
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 1.1rem;
      text-align: center;
    }

    .stat-value {
      font-family: 'Fraunces', serif;
      font-size: 2rem;
      font-weight: 600;
      color: var(--ink);
      display: block;
      line-height: 1;
    }

    .stat-label {
      font-size: 0.72rem;
      color: var(--ink-soft);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-top: 0.35rem;
      display: block;
    }

    .chart-container {
      position: relative;
      height: 260px;
      background: var(--cream);
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 1.1rem;
      overflow: hidden;
    }

    .chart-container canvas {
      max-width: 100% !important;
      width: 100% !important;
    }

    .list-panel { padding: 1.75rem; }

    .list-header {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .list-header h2 { margin-bottom: 0; }

    .list-controls { display: flex; gap: 0.6rem; }
    .list-controls input, .list-controls select { min-width: 160px; }

    .countdown-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1.25rem;
    }

    .countdown-card {
      background: var(--cream);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      padding: 1.4rem;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      animation: cardIn 0.4s ease backwards;
    }

    @keyframes cardIn {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .countdown-card { cursor: pointer; }
    .countdown-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); }
    .countdown-card:focus-visible { outline: 2px solid var(--gold); outline-offset: 2px; }

    .card-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 0.5rem;
    }

    .card-title {
      font-family: 'Fraunces', serif;
      font-weight: 600;
      font-size: 1.1rem;
      color: var(--ink);
      line-height: 1.3;
      word-break: break-word;
    }

    .card-menu { display: flex; gap: 0.3rem; flex-shrink: 0; }

    .icon-btn {
      width: 28px;
      height: 28px;
      padding: 0;
      border-radius: 8px;
      background: transparent;
      color: var(--ink-soft);
      font-size: 0.85rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: none;
    }
    .icon-btn:hover { background: var(--gold-soft); color: var(--ink); box-shadow: none; }
    .icon-btn-danger:hover { background: rgba(194, 75, 63, 0.12); color: var(--coral); }

    .dial-wrap {
      position: relative;
      width: 132px;
      height: 132px;
      margin: 1.2rem auto 0.9rem;
    }

    .dial-ticks {
      position: absolute;
      inset: -9px;
      border-radius: 50%;
      background: repeating-conic-gradient(from 0deg, var(--ink) 0deg 1.3deg, transparent 1.3deg 9deg);
      opacity: 0.13;
      -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 9px), #000 calc(100% - 8px));
      mask: radial-gradient(farthest-side, transparent calc(100% - 9px), #000 calc(100% - 8px));
    }

    .dial {
      position: relative;
      width: 100%;
      height: 100%;
      border-radius: 50%;
      padding: 7px;
      background: conic-gradient(var(--card-color) calc(var(--p) * 1%), rgba(18, 48, 63, 0.09) 0);
      transition: background 1s linear;
    }

    .dial-face {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background: var(--panel);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-shadow: inset 0 0 0 1px var(--line);
    }

    .dial-num {
      font-family: 'Fraunces', serif;
      font-size: 2rem;
      font-weight: 600;
      color: var(--ink);
      line-height: 1;
    }

    .dial-sub {
      font-family: 'Inter', sans-serif;
      font-size: 0.66rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--ink-soft);
      margin-top: 0.3rem;
    }

    .dial-check { font-size: 2rem; color: var(--card-color); line-height: 1; }

    .is-completed .dial-face { background: var(--cream-deep); }

    .card-time {
      font-family: 'Space Mono', monospace;
      font-size: 1.05rem;
      font-weight: 700;
      text-align: center;
      letter-spacing: 0.04em;
      color: var(--card-color);
    }
    .is-completed .card-time { color: var(--good); }

    .card-dates {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      font-size: 0.78rem;
      color: var(--ink-faint);
      margin-top: 0.7rem;
    }
    .dates-arrow { color: var(--ink-faint); }

    .empty-state {
      text-align: center;
      padding: 3.5rem 1.5rem;
      color: var(--ink-soft);
    }
    .empty-state strong { display: block; font-family: 'Fraunces', serif; font-size: 1.2rem; color: var(--ink); margin-bottom: 0.4rem; }

    .toast-container {
      position: fixed;
      bottom: 1.5rem;
      right: 1.5rem;
      display: flex;
      flex-direction: column;
      gap: 0.6rem;
      z-index: 100;
    }

    .toast {
      background: var(--ink);
      color: var(--cream);
      padding: 0.85rem 1.2rem;
      border-radius: 10px;
      font-size: 0.88rem;
      box-shadow: var(--shadow-hover);
      animation: toastIn 0.25s ease;
    }
    .toast.error { background: var(--coral); }
    @keyframes toastIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

    .expand-overlay[hidden] {
      display: none;
    }

    .expand-overlay {
      position: fixed;
      inset: 0;
      background: rgba(18, 48, 63, 0.35);
      backdrop-filter: blur(3px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      z-index: 200;
      animation: overlayIn 0.2s ease;
    }
    @keyframes overlayIn { from { opacity: 0; } to { opacity: 1; } }

    .expand-card {
      position: relative;
      width: 100%;
      max-width: 420px;
      max-height: 90vh;
      overflow-y: auto;
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(18, 48, 63, 0.28);
      padding: 2.2rem 1.8rem 1.8rem;
      text-align: center;
      animation: expandIn 0.22s cubic-bezier(.2,.9,.3,1.2);
    }
    @keyframes expandIn { from { opacity: 0; transform: scale(0.92); } to { opacity: 1; transform: scale(1); } }

    .expand-close {
      position: absolute;
      top: 1rem;
      right: 1rem;
      width: 32px;
      height: 32px;
      padding: 0;
      border-radius: 50%;
      background: var(--cream);
      color: var(--ink-soft);
      box-shadow: none;
      font-size: 0.9rem;
    }
    .expand-close:hover { color: var(--ink); background: var(--gold-soft); box-shadow: none; }

    .expand-title {
      font-family: 'Fraunces', serif;
      font-weight: 600;
      font-size: 1.5rem;
      color: var(--ink);
      padding-right: 1.5rem;
      word-break: break-word;
    }

    .expand-dial-wrap { width: 220px; height: 220px; margin: 1.8rem auto 1.2rem; }
    .expand-dial-wrap .dial-num { font-size: 3.4rem; }
    .expand-dial-wrap .dial-sub { font-size: 0.8rem; }
    .expand-dial-wrap .dial-check { font-size: 3.4rem; }

    .expand-breakdown {
      display: flex;
      justify-content: center;
      gap: 0.6rem;
      margin: 0.5rem 0 1.2rem;
      flex-wrap: wrap;
    }

    .breakdown-unit {
      background: var(--cream);
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 0.6rem 0.9rem;
      min-width: 64px;
    }
    .breakdown-unit .val {
      display: block;
      font-family: 'Space Mono', monospace;
      font-weight: 700;
      font-size: 1.25rem;
      color: var(--card-color, var(--ink));
    }
    .breakdown-unit .lbl {
      display: block;
      font-size: 0.62rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--ink-soft);
      margin-top: 0.15rem;
    }

    .expand-dates { margin-top: 0.4rem; font-size: 0.85rem; }

    .expand-actions {
      display: flex;
      gap: 0.5rem;
      margin-top: 1.6rem;
      flex-wrap: wrap;
      justify-content: center;
    }
    .expand-actions button { flex: 1; min-width: 110px; }

    @media (max-width: 900px) {
      .layout { grid-template-columns: 1fr; }
      .stats-grid { grid-template-columns: repeat(3, 1fr); }
      .list-controls { width: 100%; }
      .list-controls input, .list-controls select { flex: 1; min-width: 0; }
    }
  </style>
</head>
<body>
  <header class="topbar">
    <img src="logo.png" class="brand-mark" alt="Countdown logo">
    <div class="brand-text">
      <h1>Compte Enrere</h1>
      <p class="tagline">El temps que queda, d'un cop d'ull.</p>
    </div>
  </header>

  <div class="container">
    <div class="layout">
      <!-- Formulari d'entrada / edició -->
      <div class="panel form-panel">
        <h2 id="form-title">Nou compte enrere</h2>
        <div class="input-group">
          <label for="countdown-title">Títol</label>
          <input type="text" id="countdown-title" placeholder="Ex: Vacances d'estiu" />
        </div>

        <div class="input-group">
          <label for="start-date">Data d'inici</label>
          <input type="datetime-local" id="start-date" />
        </div>

        <div class="input-group">
          <label for="end-date">Data de finalització</label>
          <input type="datetime-local" id="end-date" />
        </div>

        <div class="input-group">
          <label for="color-picker">Color</label>
          <input type="color" id="color-picker" value="#C08A2E" />
        </div>

        <div class="form-actions">
          <button id="save-btn" onclick="saveCountdown()">Guardar</button>
          <button id="cancel-btn" class="btn-ghost" onclick="cancelEdit()" hidden>Cancel·lar</button>
        </div>
      </div>

      <!-- Estadístiques -->
      <div class="panel">
        <h2>Estadístiques</h2>
        <div class="stats-grid">
          <div class="stat-card">
            <span class="stat-value" id="total-count">0</span>
            <span class="stat-label">Total</span>
          </div>
          <div class="stat-card">
            <span class="stat-value" id="active-count">0</span>
            <span class="stat-label">Actius</span>
          </div>
          <div class="stat-card">
            <span class="stat-value" id="completed-count">0</span>
            <span class="stat-label">Completats</span>
          </div>
        </div>

        <div class="chart-container">
          <canvas id="timelineChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Llista de comptes enrere -->
    <div class="panel list-panel">
      <div class="list-header">
        <h2>Comptes enrere</h2>
        <div class="list-controls">
          <input type="search" id="search-input" placeholder="Cercar per títol…" oninput="renderCountdowns()" />
          <select id="sort-select" onchange="renderCountdowns()">
            <option value="end_asc">Properes primer</option>
            <option value="end_desc">Llunyanes primer</option>
            <option value="title">Nom (A-Z)</option>
            <option value="created_desc">Afegits recentment</option>
          </select>
        </div>
      </div>
      <div id="countdown-list" class="countdown-grid"></div>
    </div>
  </div>

  <div id="toast-container" class="toast-container"></div>

  <!-- Vista ampliada d'una fitxa -->
  <div id="expand-overlay" class="expand-overlay" hidden onclick="if(event.target===this) closeExpandedCard()">
    <div class="expand-card" style="--card-color: #C08A2E">
      <button class="expand-close" onclick="closeExpandedCard()" title="Tancar">✕</button>

      <h3 class="expand-title" id="expand-title"></h3>

      <div class="dial-wrap expand-dial-wrap">
        <div class="dial-ticks"></div>
        <div class="dial" id="expand-dial">
          <div class="dial-face">
            <span class="dial-check" id="expand-check" hidden>✓</span>
            <span class="dial-num" id="expand-num"></span>
            <span class="dial-sub" id="expand-sub"></span>
          </div>
        </div>
      </div>

      <div class="expand-breakdown" id="expand-breakdown"></div>

      <div class="card-dates expand-dates" id="expand-dates"></div>

      <div class="expand-actions">
        <button class="btn-ghost" onclick="editCountdown(currentExpandedId); closeExpandedCard();">✎ Editar</button>
        <button class="btn-ghost" onclick="duplicateCountdown(currentExpandedId)">⧉ Duplicar</button>
        <button class="btn-ghost icon-btn-danger" onclick="deleteCountdown(currentExpandedId); closeExpandedCard();">✕ Esborrar</button>
      </div>
    </div>
  </div>

  <script>
    let countdowns = [];
    let editingId = null;
    let updateInterval;
    let chart;
    let currentExpandedId = null;

    window.addEventListener('load', () => {
      loadCountdowns();
      startAutoUpdate();
    });

    async function loadCountdowns() {
      try {
        const response = await fetch('', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'getAll' })
        });
        countdowns = await response.json();
        renderCountdowns();
        updateStats();
        updateChart();
      } catch (error) {
        console.error('Error carregant comptes enrere:', error);
        showToast('No s\'han pogut carregar els comptes enrere', 'error');
      }
    }

    async function saveCountdown() {
      const title = document.getElementById('countdown-title').value.trim();
      const startDate = document.getElementById('start-date').value;
      const endDate = document.getElementById('end-date').value;
      const color = document.getElementById('color-picker').value;

      if (!title || !startDate || !endDate) {
        showToast('Has d\'omplir tots els camps', 'error');
        return;
      }

      if (new Date(endDate) <= new Date(startDate)) {
        showToast('La data final ha de ser posterior a la inicial', 'error');
        return;
      }

      const payload = {
        action: editingId ? 'update' : 'add',
        title, start_date: startDate, end_date: endDate, color
      };
      if (editingId) payload.id = editingId;

      try {
        const response = await fetch('', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (result.success) {
          showToast(editingId ? 'Compte enrere actualitzat' : 'Compte enrere afegit');
          cancelEdit();
          loadCountdowns();
        }
      } catch (error) {
        console.error('Error guardant compte enrere:', error);
        showToast('No s\'ha pogut guardar', 'error');
      }
    }

    function editCountdown(id) {
      const c = countdowns.find(c => c.id === id);
      if (!c) return;
      editingId = id;
      document.getElementById('countdown-title').value = c.title;
      document.getElementById('start-date').value = toLocalInput(c.start_date);
      document.getElementById('end-date').value = toLocalInput(c.end_date);
      document.getElementById('color-picker').value = c.color;
      document.getElementById('form-title').textContent = 'Editar compte enrere';
      document.getElementById('save-btn').textContent = 'Actualitzar';
      document.getElementById('cancel-btn').hidden = false;
      document.querySelector('.form-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
      document.getElementById('countdown-title').focus();
    }

    function cancelEdit() {
      editingId = null;
      document.getElementById('countdown-title').value = '';
      document.getElementById('start-date').value = '';
      document.getElementById('end-date').value = '';
      document.getElementById('color-picker').value = '#C08A2E';
      document.getElementById('form-title').textContent = 'Nou compte enrere';
      document.getElementById('save-btn').textContent = 'Guardar';
      document.getElementById('cancel-btn').hidden = true;
    }

    async function duplicateCountdown(id) {
      const c = countdowns.find(c => c.id === id);
      if (!c) return;
      try {
        const response = await fetch('', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'add',
            title: c.title + ' (còpia)',
            start_date: c.start_date,
            end_date: c.end_date,
            color: c.color
          })
        });
        const result = await response.json();
        if (result.success) {
          showToast('Compte enrere duplicat');
          loadCountdowns();
        }
      } catch (error) {
        console.error('Error duplicant compte enrere:', error);
        showToast('No s\'ha pogut duplicar', 'error');
      }
    }

    async function deleteCountdown(id) {
      if (!confirm('Estàs segur que vols esborrar aquest compte enrere?')) return;

      try {
        await fetch('', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'delete', id: id })
        });
        if (editingId === id) cancelEdit();
        showToast('Compte enrere esborrat');
        loadCountdowns();
      } catch (error) {
        console.error('Error esborrant compte enrere:', error);
        showToast('No s\'ha pogut esborrar', 'error');
      }
    }

    function toLocalInput(dateStr) {
      // Converteix una data guardada a l'input datetime-local (respecta el valor original)
      const d = new Date(dateStr);
      const pad = n => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function getVisibleCountdowns() {
      const query = document.getElementById('search-input').value.trim().toLowerCase();
      const sortBy = document.getElementById('sort-select').value;

      let list = countdowns.filter(c => c.title.toLowerCase().includes(query));

      list.sort((a, b) => {
        switch (sortBy) {
          case 'end_desc': return new Date(b.end_date) - new Date(a.end_date);
          case 'title': return a.title.localeCompare(b.title);
          case 'created_desc': return new Date(b.created_at) - new Date(a.created_at);
          default: return new Date(a.end_date) - new Date(b.end_date);
        }
      });

      return list;
    }

    // Reconstrueix les targetes (només cal quan canvien les dades, la cerca o l'ordre).
    // Els números s'actualitzen a part cada segon amb updateCountdownTimes(), sense
    // tornar a crear el DOM, perquè la llista no "tremoli" ni repeteixi l'animació.
    function renderCountdowns() {
      const container = document.getElementById('countdown-list');
      const list = getVisibleCountdowns();

      if (countdowns.length === 0) {
        container.innerHTML = `<div class="empty-state"><strong>Encara no hi ha res en marxa</strong>Afegeix el teu primer compte enrere amb el formulari.</div>`;
        return;
      }
      if (list.length === 0) {
        container.innerHTML = `<div class="empty-state"><strong>Cap resultat</strong>Prova amb una altra cerca.</div>`;
        return;
      }

      container.innerHTML = list.map(countdown => `
        <article class="countdown-card" data-id="${countdown.id}" style="--card-color: ${countdown.color}"
                 onclick="openExpandedCard(${countdown.id})" tabindex="0"
                 onkeydown="if(event.key==='Enter') openExpandedCard(${countdown.id})">
          <div class="card-top">
            <h3 class="card-title">${escapeHtml(countdown.title)}</h3>
            <div class="card-menu">
              <button class="icon-btn" title="Editar" onclick="event.stopPropagation(); editCountdown(${countdown.id})">✎</button>
              <button class="icon-btn" title="Duplicar" onclick="event.stopPropagation(); duplicateCountdown(${countdown.id})">⧉</button>
              <button class="icon-btn icon-btn-danger" title="Esborrar" onclick="event.stopPropagation(); deleteCountdown(${countdown.id})">✕</button>
            </div>
          </div>

          <div class="dial-wrap">
            <div class="dial-ticks"></div>
            <div class="dial">
              <div class="dial-face">
                <span class="dial-check" hidden>✓</span>
                <span class="dial-num"></span>
                <span class="dial-sub"></span>
              </div>
            </div>
          </div>

          <div class="card-time"></div>

          <div class="card-dates">
            <span>${new Date(countdown.start_date).toLocaleDateString('ca-ES')}</span>
            <span class="dates-arrow">→</span>
            <span>${new Date(countdown.end_date).toLocaleDateString('ca-ES')}</span>
          </div>
        </article>
      `).join('');

      updateCountdownTimes();
    }

    // Actualitza només els números/anelles de les targetes ja pintades (sense tocar el DOM
    // de cada targeta), per a que el "tick" de cada segon sigui suau.
    function updateCountdownTimes() {
      const pad = n => String(n).padStart(2, '0');

      document.querySelectorAll('.countdown-card').forEach(card => {
        const countdown = countdowns.find(c => String(c.id) === card.dataset.id);
        if (!countdown) return;

        const { percent, days, hours, minutes, seconds, isCompleted } = calculateTime(countdown);

        card.classList.toggle('is-completed', isCompleted);
        card.querySelector('.dial').style.setProperty('--p', percent);

        const checkEl = card.querySelector('.dial-check');
        const numEl = card.querySelector('.dial-num');
        const subEl = card.querySelector('.dial-sub');

        checkEl.hidden = !isCompleted;
        numEl.hidden = isCompleted;
        if (isCompleted) {
          subEl.textContent = 'Fet';
        } else {
          numEl.textContent = days;
          subEl.textContent = days === 1 ? 'dia' : 'dies';
        }

        card.querySelector('.card-time').textContent = isCompleted
          ? 'Completat'
          : `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
      });

      if (currentExpandedId !== null) updateExpandedCard();
    }

    function openExpandedCard(id) {
      currentExpandedId = id;
      updateExpandedCard();
      document.getElementById('expand-overlay').hidden = false;
      document.body.style.overflow = 'hidden';
    }

    function closeExpandedCard() {
      document.getElementById('expand-overlay').hidden = true;
      currentExpandedId = null;
      document.body.style.overflow = '';
    }

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && currentExpandedId !== null) closeExpandedCard();
    });

    function updateExpandedCard() {
      const countdown = countdowns.find(c => c.id === currentExpandedId);
      if (!countdown) { closeExpandedCard(); return; }

      const { percent, days, hours, minutes, seconds, isCompleted } = calculateTime(countdown);
      const pad = n => String(n).padStart(2, '0');

      document.querySelector('.expand-card').style.setProperty('--card-color', countdown.color);
      document.getElementById('expand-title').textContent = countdown.title;
      document.getElementById('expand-dial').style.setProperty('--p', percent);

      document.getElementById('expand-check').hidden = !isCompleted;
      document.getElementById('expand-num').hidden = isCompleted;
      document.getElementById('expand-sub').textContent = isCompleted ? 'Fet' : (days === 1 ? 'dia' : 'dies');
      if (!isCompleted) document.getElementById('expand-num').textContent = days;

      document.getElementById('expand-breakdown').innerHTML = isCompleted ? '' : `
        <div class="breakdown-unit"><span class="val">${pad(hours)}</span><span class="lbl">Hores</span></div>
        <div class="breakdown-unit"><span class="val">${pad(minutes)}</span><span class="lbl">Minuts</span></div>
        <div class="breakdown-unit"><span class="val">${pad(seconds)}</span><span class="lbl">Segons</span></div>
      `;

      document.getElementById('expand-dates').innerHTML = `
        <span>${new Date(countdown.start_date).toLocaleString('ca-ES')}</span>
        <span class="dates-arrow">→</span>
        <span>${new Date(countdown.end_date).toLocaleString('ca-ES')}</span>
      `;
    }

    function escapeHtml(str) {
      const div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    function calculateTime(countdown) {
      const now = new Date();
      const start = new Date(countdown.start_date);
      const end = new Date(countdown.end_date);

      const totalDuration = end - start;
      const elapsed = now - start;
      const remaining = end - now;

      const percent = Math.min(100, Math.max(0, (elapsed / totalDuration) * 100));
      const isCompleted = remaining <= 0;

      const days = Math.max(0, Math.floor(remaining / (1000 * 60 * 60 * 24)));
      const hours = Math.max(0, Math.floor((remaining / (1000 * 60 * 60)) % 24));
      const minutes = Math.max(0, Math.floor((remaining / (1000 * 60)) % 60));
      const seconds = Math.max(0, Math.floor((remaining / 1000) % 60));

      return { remaining, percent, days, hours, minutes, seconds, isCompleted };
    }

    function updateStats() {
      const total = countdowns.length;
      const active = countdowns.filter(c => new Date(c.end_date) > new Date()).length;
      const completed = total - active;

      document.getElementById('total-count').textContent = total;
      document.getElementById('active-count').textContent = active;
      document.getElementById('completed-count').textContent = completed;
    }

    function updateChart() {
      if (typeof Chart === 'undefined') {
        console.warn('Chart.js no s\'ha pogut carregar; s\'omet el gràfic.');
        return;
      }

      const ctx = document.getElementById('timelineChart');
      if (chart) chart.destroy();

      const sorted = [...countdowns].sort((a, b) => new Date(a.end_date) - new Date(b.end_date));

      chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: sorted.map(c => c.title),
          datasets: [{
            label: 'Dies restants',
            data: sorted.map(c => {
              const remaining = new Date(c.end_date) - new Date();
              return Math.max(0, Math.floor(remaining / (1000 * 60 * 60 * 24)));
            }),
            backgroundColor: sorted.map(c => c.color + 'aa'),
            borderColor: sorted.map(c => c.color),
            borderWidth: 1.5,
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              labels: { color: '#12303F', font: { family: 'Inter', size: 11 } }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { color: '#5B7382', font: { family: 'Inter', size: 10 } },
              grid: { color: 'rgba(18, 48, 63, 0.08)' }
            },
            x: {
              ticks: { color: '#5B7382', font: { family: 'Inter', size: 10 } },
              grid: { display: false }
            }
          }
        }
      });
    }

    function startAutoUpdate() {
      updateInterval = setInterval(() => {
        updateCountdownTimes();
      }, 1000);
    }

    function showToast(message, type = 'success') {
      const container = document.getElementById('toast-container');
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      toast.textContent = message;
      container.appendChild(toast);
      setTimeout(() => toast.remove(), 3200);
    }
  </script>
</body>
</html>

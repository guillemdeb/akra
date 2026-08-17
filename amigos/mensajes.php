<?php
$page_title = 'Missatges';
require_once "includes/pwa_head.php";
?>
<html lang="ca">
<body>
<?php ra_splash_body(); ?>
<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit(); }
require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];
$usuario_seleccionat = (int)($_GET['usuario'] ?? 0);

// Processar enviament de missatge (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dest_id  = (int)($_POST['destinatario_id'] ?? 0);
    $missatge = trim($_POST['mensaje'] ?? '');
    
    if ($dest_id > 0 && !empty($missatge) && mb_strlen($missatge) <= 2000) {
        // Verificar amistat
        $s = $pdo->prepare("SELECT COUNT(*) FROM amistades 
            WHERE ((usuario_id=:jo AND amigo_id=:ell) OR (amigo_id=:jo AND usuario_id=:ell))
            AND estado='aceptada'");
        $s->execute(['jo' => $usuario_id, 'ell' => $dest_id]);
        if ($s->fetchColumn() > 0) {
            $pdo->prepare("INSERT INTO mensajes (remitente_id, destinatario_id, mensaje) VALUES (:r,:d,:m)")
                ->execute(['r' => $usuario_id, 'd' => $dest_id, 'm' => $missatge]);
            // Notificació
            try {
                $nom = $_SESSION['usuario_nombre'] ?? 'Algú';
                $pdo->prepare("INSERT INTO notificaciones (usuario_id, tipo, contenido, enlace) VALUES (:u,'mensaje',:c,:l)")
                    ->execute(['u' => $dest_id, 'c' => "Nou missatge de {$nom}", 'l' => "mensajes.php?usuario={$usuario_id}"]);
            } catch (Exception $e) {}
        }
    }
    header("Location: mensajes.php?usuario={$dest_id}");
    exit();
}

// ── Dades principals ──
// Llista de converses (tots els amics, ordenats per últim missatge)
$sql = "SELECT DISTINCT u.id, u.nombre, u.foto,
    (SELECT COUNT(*) FROM mensajes m 
     WHERE m.remitente_id = u.id AND m.destinatario_id = :uid AND m.leido = 0) as no_llegits,
    (SELECT m.mensaje FROM mensajes m 
     WHERE (m.remitente_id=u.id AND m.destinatario_id=:uid2) 
        OR (m.remitente_id=:uid3 AND m.destinatario_id=u.id)
     ORDER BY m.fecha_envio DESC LIMIT 1) as ultim_missatge,
    (SELECT m.fecha_envio FROM mensajes m 
     WHERE (m.remitente_id=u.id AND m.destinatario_id=:uid4) 
        OR (m.remitente_id=:uid5 AND m.destinatario_id=u.id)
     ORDER BY m.fecha_envio DESC LIMIT 1) as data_ultim
FROM usuarios u
INNER JOIN amistades a ON ((a.usuario_id=:uid6 AND a.amigo_id=u.id) OR (a.amigo_id=:uid7 AND a.usuario_id=u.id))
WHERE a.estado='aceptada'
ORDER BY CASE WHEN data_ultim IS NULL THEN 1 ELSE 0 END, data_ultim DESC, u.nombre ASC";
$s = $pdo->prepare($sql);
$s->execute(['uid'=>$usuario_id,'uid2'=>$usuario_id,'uid3'=>$usuario_id,'uid4'=>$usuario_id,'uid5'=>$usuario_id,'uid6'=>$usuario_id,'uid7'=>$usuario_id]);
$converses = $s->fetchAll(PDO::FETCH_ASSOC);

// Dades de l'usuari del xat seleccionat
$usuari_xat   = null;
$missatges    = [];
$ultima_id    = 0;

if ($usuario_seleccionat > 0) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM amistades 
        WHERE ((usuario_id=:jo AND amigo_id=:ell) OR (amigo_id=:jo AND usuario_id=:ell)) AND estado='aceptada'");
    $s->execute(['jo' => $usuario_id, 'ell' => $usuario_seleccionat]);
    if ($s->fetchColumn() > 0) {
        $s = $pdo->prepare("SELECT id, nombre, foto FROM usuarios WHERE id = :id");
        $s->execute(['id' => $usuario_seleccionat]);
        $usuari_xat = $s->fetch(PDO::FETCH_ASSOC);
        
        // Marcar llegits
        $pdo->prepare("UPDATE mensajes SET leido=1 WHERE remitente_id=:r AND destinatario_id=:d AND leido=0")
            ->execute(['r' => $usuario_seleccionat, 'd' => $usuario_id]);
        
        // Carregar missatges inicials
        $s = $pdo->prepare("SELECT m.id, m.remitente_id, m.mensaje,
            DATE_FORMAT(m.fecha_envio,'%H:%i') as hora
            FROM mensajes m
            WHERE (m.remitente_id=:jo AND m.destinatario_id=:ell)
               OR (m.remitente_id=:ell2 AND m.destinatario_id=:jo2)
            ORDER BY m.fecha_envio ASC");
        $s->execute(['jo'=>$usuario_id,'ell'=>$usuario_seleccionat,'ell2'=>$usuario_seleccionat,'jo2'=>$usuario_id]);
        $missatges = $s->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($missatges)) {
            $ultima_id = (int)end($missatges)['id'];
        }
    }
}
?>
<?php $active_page = 'mensajes'; require_once "includes/navbar.php"; ?>

<div class="page-container" style="padding-top:16px; padding-bottom:8px;">
    <div class="chat-layout">

        <!-- ═══ SIDEBAR: llista de converses ═══ -->
        <div class="chat-sidebar <?= $usuari_xat ? 'hidden-mobile' : '' ?>" id="chat-sidebar">
            <div class="chat-sidebar-header">
                <i class="fas fa-comment-dots" style="color:var(--primary);"></i>
                Missatges
            </div>
            <div class="chat-list">
                <?php if (empty($converses)): ?>
                    <div class="empty-state" style="padding:40px 16px;">
                        <div class="empty-icon"><i class="fas fa-user-friends"></i></div>
                        <h3>Cap conversa</h3>
                        <p>Quan tinguis amics, aquí apareixeran les converses.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($converses as $c): ?>
                        <a href="mensajes.php?usuario=<?= $c['id'] ?>"
                           class="chat-item <?= $usuario_seleccionat == $c['id'] ? 'active' : '' ?>">
                            <img src="uploads/<?= htmlspecialchars($c['foto'] ?: 'default.png') ?>"
                                 class="user-avatar-sm"
                                 onerror="this.src='uploads/default.png'">
                            <div class="chat-item-info">
                                <div class="chat-item-name"><?= htmlspecialchars($c['nombre']) ?></div>
                                <?php if ($c['ultim_missatge']): ?>
                                    <div class="chat-item-preview">
                                        <?= htmlspecialchars(mb_substr($c['ultim_missatge'], 0, 40)) ?>
                                        <?= mb_strlen($c['ultim_missatge']) > 40 ? '…' : '' ?>
                                    </div>
                                <?php else: ?>
                                    <div class="chat-item-preview" style="font-style:italic;">Cap missatge</div>
                                <?php endif; ?>
                            </div>
                            <?php if ($c['no_llegits'] > 0): ?>
                                <span class="chat-unread"><?= $c['no_llegits'] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ═══ ZONA PRINCIPAL DEL XAT ═══ -->
        <?php if ($usuari_xat): ?>
        <div class="chat-main" id="chat-main">
            <!-- Capçalera del xat -->
            <div class="chat-main-header">
                <!-- Botó enrere (mòbil) -->
                <a href="mensajes.php" class="btn btn-ghost btn-sm show-mobile" id="btn-back"
                   style="display:none; margin-right:4px;">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <img src="uploads/<?= htmlspecialchars($usuari_xat['foto'] ?: 'default.png') ?>"
                     class="user-avatar-sm"
                     onerror="this.src='uploads/default.png'">
                <div>
                    <div><?= htmlspecialchars($usuari_xat['nombre']) ?></div>
                    <div class="typing-indicator" id="typing-ind">Escrivint...</div>
                </div>
                <a href="perfil_usuario.php?id=<?= $usuari_xat['id'] ?>"
                   class="btn btn-ghost btn-sm" style="margin-left:auto;">
                    <i class="fas fa-user"></i> Perfil
                </a>
            </div>

            <!-- Missatges -->
            <div class="chat-messages" id="chat-messages">
                <?php foreach ($missatges as $m): ?>
                    <?php $es_meu = ($m['remitente_id'] == $usuario_id); ?>
                    <div class="chat-message <?= $es_meu ? 'mine' : 'other' ?>" id="msg-<?= $m['id'] ?>">
                        <?php if (!$es_meu): ?>
                            <img src="uploads/<?= htmlspecialchars($usuari_xat['foto'] ?: 'default.png') ?>"
                                 class="user-avatar-sm"
                                 onerror="this.src='uploads/default.png'"
                                 style="width:32px;height:32px;">
                        <?php endif; ?>
                        <div class="chat-bubble"><?= nl2br(htmlspecialchars($m['mensaje'])) ?></div>
                        <span class="chat-time"><?= $m['hora'] ?></span>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($missatges)): ?>
                    <div class="empty-state" style="margin:auto;">
                        <div class="empty-icon"><i class="fas fa-comments"></i></div>
                        <h3>Inici de la conversa</h3>
                        <p>Envia el primer missatge a <?= htmlspecialchars($usuari_xat['nombre']) ?>!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Formulari d'enviament -->
            <div class="chat-input-area">
                <form method="POST" id="msg-form" style="display:flex;flex:1;gap:10px;align-items:center;">
                    <input type="hidden" name="destinatario_id" value="<?= $usuari_xat['id'] ?>">
                    <input type="text"
                           name="mensaje"
                           id="msg-input"
                           placeholder="Escriu un missatge..."
                           autocomplete="off"
                           maxlength="2000"
                           required>
                    <button type="submit" class="btn btn-primary" style="border-radius:50%;width:44px;height:44px;padding:0;flex-shrink:0;">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- Estat: cap conversa seleccionada -->
        <div class="chat-main" style="align-items:center;justify-content:center;">
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-comment-alt"></i></div>
                <h3>Selecciona una conversa</h3>
                <p>Tria un amic de la llista per enviar-li un missatge.</p>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.chat-layout -->
</div>

<script>
(function() {
    // ── Scroll al final ──
    const msgs = document.getElementById('chat-messages');
    if (msgs) msgs.scrollTop = msgs.scrollHeight;

    // ── Mòbil: mostrar/amagar sidebar ──
    const sidebar = document.getElementById('chat-sidebar');
    const btnBack = document.getElementById('btn-back');
    
    function checkMobile() {
        if (window.innerWidth <= 768) {
            if (btnBack) btnBack.style.display = 'flex';
            <?php if ($usuari_xat): ?>
            if (sidebar) sidebar.classList.add('hidden');
            <?php endif; ?>
        } else {
            if (btnBack) btnBack.style.display = 'none';
            if (sidebar) { sidebar.classList.remove('hidden'); sidebar.style.display = ''; }
        }
    }
    checkMobile();
    window.addEventListener('resize', checkMobile);

    <?php if ($usuari_xat): ?>
    // ══════════════════════════════════════
    //  AJAX POLLING - Missatges en temps real
    // ══════════════════════════════════════
    const AMB_USER  = <?= $usuari_xat['id'] ?>;
    const JO_USER   = <?= $usuario_id ?>;
    let ULTIMA_ID   = <?= $ultima_id ?>;
    const INTERVAL  = 5000; // 5 segons
    let pollTimer   = null;
    let pausat      = false;

    function afegirMissatge(m) {
        // Evitar duplicats
        if (document.getElementById('msg-' + m.id)) return;

        // Eliminar estat buit si n'hi havia
        const buit = msgs.querySelector('.empty-state');
        if (buit) buit.remove();

        const esMeu = (parseInt(m.remitente_id) === JO_USER);
        const div   = document.createElement('div');
        div.id      = 'msg-' + m.id;
        div.className = 'chat-message ' + (esMeu ? 'mine' : 'other') + ' fade-in';

        let avatarHtml = '';
        if (!esMeu) {
            avatarHtml = `<img src="uploads/<?= htmlspecialchars($usuari_xat['foto'] ?: 'default.png') ?>"
                              class="user-avatar-sm"
                              onerror="this.src='uploads/default.png'"
                              style="width:32px;height:32px;">`;
        }

        const text = m.mensaje.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
        div.innerHTML = `${avatarHtml}
            <div class="chat-bubble">${text}</div>
            <span class="chat-time">${m.hora}</span>`;

        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
    }

    async function poll() {
        if (pausat) return;
        try {
            const r = await fetch(`api_mensajes_poll.php?con=${AMB_USER}&ultima_id=${ULTIMA_ID}`);
            if (!r.ok) return;
            const data = await r.json();
            if (data.missatges && data.missatges.length > 0) {
                data.missatges.forEach(m => afegirMissatge(m));
                ULTIMA_ID = data.ultima_id;
            }
        } catch(e) {
            // Connexió fallida temporalment - continuem
        }
    }

    // Iniciar polling
    pollTimer = setInterval(poll, INTERVAL);

    // Enviar per AJAX (sense recarregar pàgina)
    const form  = document.getElementById('msg-form');
    const input = document.getElementById('msg-input');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;

        input.value = '';
        pausat = true; // Pausar polling mentre enviem

        try {
            const fd = new FormData();
            fd.append('destinatario_id', AMB_USER);
            fd.append('mensaje', text);
            const r = await fetch('mensajes.php?usuario=' + AMB_USER, { method: 'POST', body: fd });
            // Fer poll immediatament per veure el missatge enviat
            await poll();
        } catch(e) {
            // Fallback: recàrrega normal
            form.submit();
            return;
        }
        pausat = false;
    });

    // Parar polling quan la pàgina no és visible
    document.addEventListener('visibilitychange', () => {
        pausat = document.hidden;
        if (!document.hidden) poll(); // Poll immediat en tornar
    });
    <?php endif; ?>
})();
</script>

</body>
</html>

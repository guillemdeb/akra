<?php
/**
 * HELPER CODIS PWA PERSONALS — RedAmigos
 * Cada usuari aprovat té el seu codi únic per instal·lar l'app.
 *
 * Format: 2 lletres + 4 números + 2 lletres  →  RA-2847-XQ
 * Llegible, curt i fàcil de dictar per telèfon.
 */

/**
 * Genera un codi personal PWA únic (format RA-NNNN-LL)
 */
function pwa_generar_codi(): string {
    $lletres = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // sense I, O per evitar confusió
    $n       = strlen($lletres);

    do {
        $prefix = $lletres[random_int(0, $n-1)] . $lletres[random_int(0, $n-1)];
        $nums   = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $sufix  = $lletres[random_int(0, $n-1)] . $lletres[random_int(0, $n-1)];
        $codi   = $prefix . '-' . $nums . '-' . $sufix;  // p.ex. "RA-2847-XQ"
    } while (pwa_codi_existeix($codi));

    return $codi;
}

/**
 * Comprova si el codi ja existeix a la BD
 */
function pwa_codi_existeix(string $codi): bool {
    global $pdo;
    try {
        $s = $pdo->prepare("SELECT 1 FROM usuarios WHERE codigo_pwa = :c LIMIT 1");
        $s->execute(['c' => $codi]);
        return (bool) $s->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Assigna un codi PWA a un usuari (si no en té ja)
 * Retorna el codi assignat
 */
function pwa_assignar_codi(int $usuario_id): string {
    global $pdo;

    // Comprovar si ja té codi
    $s = $pdo->prepare("SELECT codigo_pwa FROM usuarios WHERE id = :id");
    $s->execute(['id' => $usuario_id]);
    $existent = $s->fetchColumn();
    if ($existent) return $existent;

    // Generar i assignar
    $codi = pwa_generar_codi();
    $pdo->prepare("UPDATE usuarios SET codigo_pwa = :c WHERE id = :id")
        ->execute(['c' => $codi, 'id' => $usuario_id]);

    return $codi;
}

/**
 * Valida un codi PWA introduït per l'usuari
 * Retorna: ['valid'=>true, 'usuario_id'=>N, 'nombre'=>'...'] 
 *       o  ['valid'=>false, 'error'=>'...']
 */
function pwa_validar_codi(string $codi): array {
    global $pdo;

    $codi = strtoupper(trim($codi));

    if (empty($codi)) {
        return ['valid' => false, 'error' => 'Introdueix el teu codi personal.'];
    }

    // Format mínim: 10 chars
    if (strlen($codi) < 9) {
        return ['valid' => false, 'error' => 'El codi no té el format correcte.'];
    }

    try {
        $s = $pdo->prepare("
            SELECT id, nombre, email, aprobado, activo
            FROM usuarios
            WHERE codigo_pwa = :c
            LIMIT 1
        ");
        $s->execute(['c' => $codi]);
        $u = $s->fetch(PDO::FETCH_ASSOC);

        if (!$u) {
            return ['valid' => false, 'error' => 'Codi no reconegut. Comprova que ho has escrit bé.'];
        }
        if (!$u['activo']) {
            return ['valid' => false, 'error' => 'Aquest compte no està actiu.'];
        }
        if (!$u['aprobado']) {
            return ['valid' => false, 'error' => 'El teu compte encara no ha estat aprovat per l\'administrador.'];
        }

        return [
            'valid'      => true,
            'usuario_id' => (int) $u['id'],
            'nombre'     => $u['nombre'],
            'email'      => $u['email'],
        ];
    } catch (Exception $e) {
        return ['valid' => false, 'error' => 'Error intern. Torna-ho a intentar.'];
    }
}

/**
 * Regenera el codi PWA d'un usuari (per si l'ha perdut o volen canviar-lo)
 */
function pwa_regenerar_codi(int $usuario_id): string {
    global $pdo;
    $codi = pwa_generar_codi();
    $pdo->prepare("UPDATE usuarios SET codigo_pwa = :c WHERE id = :id")
        ->execute(['c' => $codi, 'id' => $usuario_id]);
    return $codi;
}

/**
 * Registra que un usuari ha instal·lat l'app (log)
 */
function pwa_registrar_instalacio(int $usuario_id, string $codi): void {
    global $pdo;
    try {
        $pdo->prepare("
            INSERT INTO pwa_instalaciones (usuario_id, codigo_usat, ip, user_agent)
            VALUES (:uid, :c, :ip, :ua)
        ")->execute([
            'uid' => $usuario_id,
            'c'   => $codi,
            'ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
            'ua'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    } catch (Exception $e) { /* silent */ }
}

/**
 * Obté la URL de la pàgina d'instal·lació per a un codi
 */
function pwa_url_instalacio(string $codi): string {
    return 'https://akratechstudio.es/amigos/instalar.php?c=' . urlencode($codi);
}

/**
 * Email amb el codi d'instal·lació
 */
function pwa_email_codi(string $to_email, string $nom, string $codi): bool {
    $url = pwa_url_instalacio($codi);

    $body = ra_email_template("
        <h2 style='color:#4A90E2;'>El teu accés a RedAmigos 📱</h2>
        <p>Hola <strong>" . htmlspecialchars($nom) . "</strong>,</p>
        <p>El teu compte ha estat <strong>aprovat</strong>! Aquí tens el teu codi personal
        per instal·lar <strong>RedAmigos</strong> al teu dispositiu:</p>

        <div style='text-align:center; margin:28px 0;'>
            <div style='
                display:inline-block;
                background:#f0f7ff;
                border:2px dashed #4A90E2;
                border-radius:14px;
                padding:18px 36px;
                font-size:1.8rem;
                font-weight:800;
                letter-spacing:6px;
                color:#4A90E2;
                font-family:\"Courier New\",monospace;
            '>{$codi}</div>
            <p style='color:#888; font-size:0.85rem; margin-top:10px;'>
                ⚠️ Aquest codi és personal. No el comparteixis.
            </p>
        </div>

        <p><strong>Com instal·lar l'app:</strong></p>
        <ol style='margin:12px 0 20px; padding-left:20px; line-height:2.2; color:#555;'>
            <li>Obre l'enllaç de sota al teu mòbil o tauleta</li>
            <li>Introdueix el teu codi personal</li>
            <li>Prem «Instal·lar» quan aparegui el botó</li>
            <li>L'app apareixerà a la pantalla d'inici com qualsevol altra app</li>
        </ol>

        <div style='text-align:center; margin:24px 0;'>
            <a href='{$url}'
               style='background:#4A90E2;color:white;padding:15px 36px;
                      border-radius:10px;text-decoration:none;font-weight:800;
                      font-size:1rem;display:inline-block;'>
                📲 Instal·lar RedAmigos
            </a>
        </div>

        <p style='font-size:0.82rem;color:#aaa;word-break:break-all;'>
            O copia: <a href='{$url}' style='color:#4A90E2;'>{$url}</a>
        </p>
        <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>
        <p style='font-size:0.85rem;color:#888;'>
            Si tens qualsevol problema, truca'ns al <strong>900 123 456</strong>.
        </p>
    ");

    return ra_enviar_email($to_email, '📱 El teu codi per instal·lar RedAmigos', $body);
}

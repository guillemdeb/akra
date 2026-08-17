<?php
/**
 * HELPER CODIS D'INVITACIÓ - RedAmigos
 * Funcions per generar, validar i gestionar codis
 */

/**
 * Genera un codi únic llegible: 4-4-4 (ex: AMIC-X7K2-9PQR)
 */
function ra_generar_codigo(): string {
    $chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sense 0,O,I,1 per evitar confusió
    $longitud = strlen($chars);
    
    do {
        $part1 = '';
        $part2 = '';
        $part3 = '';
        for ($i = 0; $i < 4; $i++) $part1 .= $chars[random_int(0, $longitud - 1)];
        for ($i = 0; $i < 4; $i++) $part2 .= $chars[random_int(0, $longitud - 1)];
        for ($i = 0; $i < 4; $i++) $part3 .= $chars[random_int(0, $longitud - 1)];
        $codi = $part1 . '-' . $part2 . '-' . $part3; // 12 chars + 2 guions = 14
    } while (ra_codigo_existeix($codi));
    
    return $codi;
}

/**
 * Comprova si un codi ja existeix a la BD
 */
function ra_codigo_existeix(string $codi): bool {
    global $pdo;
    try {
        $s = $pdo->prepare("SELECT COUNT(*) FROM codigos_invitacion WHERE codigo = :c");
        $s->execute(['c' => $codi]);
        return $s->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Valida un codi: retorna ['valid'=>true, 'id'=>N] o ['valid'=>false, 'error'=>'...']
 */
function ra_validar_codigo(string $codi): array {
    global $pdo;
    
    // Normalitzar (majúscules, sense espais)
    $codi = strtoupper(trim($codi));
    
    if (empty($codi)) {
        return ['valid' => false, 'error' => 'Introdueix el codi d\'invitació.'];
    }
    
    try {
        $s = $pdo->prepare("
            SELECT id, usado_por, activo 
            FROM codigos_invitacion 
            WHERE codigo = :c
        ");
        $s->execute(['c' => $codi]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return ['valid' => false, 'error' => 'El codi d\'invitació no és vàlid.'];
        }
        if (!$row['activo']) {
            return ['valid' => false, 'error' => 'Aquest codi ha estat desactivat.'];
        }
        if ($row['usado_por'] !== null) {
            return ['valid' => false, 'error' => 'Aquest codi ja ha estat utilitzat.'];
        }
        
        return ['valid' => true, 'id' => (int)$row['id'], 'codigo' => $codi];
        
    } catch (Exception $e) {
        return ['valid' => false, 'error' => 'Error en validar el codi.'];
    }
}

/**
 * Marca un codi com a usat per un usuari
 */
function ra_usar_codigo(string $codi, int $usuario_id): bool {
    global $pdo;
    try {
        $s = $pdo->prepare("
            UPDATE codigos_invitacion 
            SET usado_por = :uid, fecha_uso = NOW(), activo = TRUE
            WHERE codigo = :c AND usado_por IS NULL AND activo = TRUE
        ");
        $s->execute(['uid' => $usuario_id, 'c' => $codi]);
        return $s->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Crea N codis nous a la BD
 */
function ra_crear_codigos(int $admin_id, int $quantitat = 1, string $nota = ''): array {
    global $pdo;
    $generats = [];
    
    for ($i = 0; $i < $quantitat; $i++) {
        $codi = ra_generar_codigo();
        try {
            $s = $pdo->prepare("
                INSERT INTO codigos_invitacion (codigo, creado_por, nota)
                VALUES (:c, :admin, :nota)
            ");
            $s->execute(['c' => $codi, 'admin' => $admin_id, 'nota' => $nota]);
            $generats[] = $codi;
        } catch (Exception $e) {
            // Continuar si un falla
        }
    }
    
    return $generats;
}

/**
 * Obté l'URL d'invitació completa per a un codi
 */
function ra_url_invitacio(string $codi): string {
    $base = 'https://akratechstudio.es/amigos';
    return $base . '/register.php?codi=' . urlencode($codi);
}

/**
 * Email enviant el codi a una persona
 */
function ra_email_invitacio(string $to_email, string $nom_dest, string $codi, string $nom_admin): bool {
    $url   = ra_url_invitacio($codi);
    $subject = "🎉 T'han convidat a RedAmigos!";
    $body = ra_email_template("
        <h2 style='color:#4A90E2;'>Tens una invitació! 🎉</h2>
        <p>Hola <strong>" . htmlspecialchars($nom_dest) . "</strong>,</p>
        <p><strong>" . htmlspecialchars($nom_admin) . "</strong> t'ha convidat a unir-te a <strong>RedAmigos</strong>, la nostra xarxa social privada per connectar persones amb interessos comuns.</p>
        <p>El teu codi d'invitació personal és:</p>
        <div style='text-align:center;margin:28px 0;'>
            <div style='
                display:inline-block;
                background:#f0f7ff;border:2px dashed #4A90E2;
                border-radius:12px;padding:16px 32px;
                font-size:1.6rem;font-weight:800;letter-spacing:4px;
                color:#4A90E2;font-family:monospace;'>
                {$codi}
            </div>
        </div>
        <p style='color:#888;font-size:0.9rem;'>⚠️ Aquest codi és personal i d'un sol ús. No el comparteixis.</p>
        <div style='text-align:center;margin:28px 0;'>
            <a href='{$url}'
               style='background:#7ED321;color:white;padding:16px 40px;
                      border-radius:10px;text-decoration:none;font-weight:800;
                      font-size:1.05rem;display:inline-block;'>
                Crear el meu compte →
            </a>
        </div>
        <p style='font-size:0.85rem;color:#aaa;'>
            O copia aquest enllaç al navegador:<br>
            <a href='{$url}' style='color:#4A90E2;word-break:break-all;'>{$url}</a>
        </p>
    ");
    return ra_enviar_email($to_email, $subject, $body);
}

<?php
/**
 * HELPER D'EMAILS - RedAmigos
 * Gestiona l'enviament de correus del sistema
 * 
 * Utilitza la funció mail() nativa de PHP.
 * Per a producció, recomanem PHPMailer o SendGrid.
 */

/**
 * Envia un email amb format HTML bàsic
 */
function ra_enviar_email(string $to, string $subject, string $html_body): bool {
    $from_name  = 'RedAmigos';
    $from_email = 'noreply@redamigos.com'; // Canvia aquest email!
    
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: {$from_name} <{$from_email}>\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $html_body, $headers);
}

/**
 * Email de benvinguda (compte aprovat)
 */
function ra_email_aprovacio(string $to_email, string $nom): bool {
    $subject = "✅ El teu compte a RedAmigos ha estat aprovat!";
    $body = ra_email_template("
        <h2 style='color:#4A90E2;'>Benvingut/da, {$nom}! 🎉</h2>
        <p>Estem encantats de confirmar-te que el teu compte a <strong>RedAmigos</strong>
        ha estat aprovat per l'equip d'administració.</p>
        <p>Ja pots accedir a la plataforma i:</p>
        <ul style='margin:12px 0; padding-left:20px; line-height:2;'>
            <li>🤝 Descobrir persones amb interessos comuns</li>
            <li>💬 Enviar missatges i fer nous amics</li>
            <li>📅 Participar en esdeveniments</li>
            <li>📰 Compartir publicacions amb la comunitat</li>
        </ul>
        <div style='text-align:center; margin:28px 0;'>
            <a href='https://redamigos.com/login.php'
               style='background:#4A90E2; color:white; padding:14px 36px;
                      border-radius:8px; text-decoration:none; font-weight:700;
                      font-size:1.05rem; display:inline-block;'>
                Entrar a RedAmigos →
            </a>
        </div>
        <p style='color:#888; font-size:0.9rem;'>
            Si tens alguna pregunta, contacta'ns al 900 123 456.
        </p>
    ");
    return ra_enviar_email($to_email, $subject, $body);
}

/**
 * Email de rebuig (compte no aprovat)
 */
function ra_email_rebuig(string $to_email, string $nom, string $motiu = ''): bool {
    $subject = "Informació sobre la teva sol·licitud a RedAmigos";
    $motiu_html = $motiu ? "<p><strong>Motiu:</strong> " . htmlspecialchars($motiu) . "</p>" : '';
    $body = ra_email_template("
        <h2 style='color:#E74C3C;'>Sol·licitud revisada</h2>
        <p>Hola {$nom},</p>
        <p>Hem revisat la teva sol·licitud d'accés a <strong>RedAmigos</strong> i,
        per ara, no ha pogut ser aprovada.</p>
        {$motiu_html}
        <p>Si creus que hi ha hagut un error o vols aclarir algun aspecte,
        posa't en contacte amb nosaltres:</p>
        <p><strong>📞 900 123 456</strong><br>
           <strong>✉️ info@redamigos.com</strong></p>
    ");
    return ra_enviar_email($to_email, $subject, $body);
}

/**
 * Email de notificació a l'admin (nou usuari pendent)
 */
function ra_email_admin_nou_usuari(string $admin_email, string $nom_nou, string $email_nou, int $id_nou): bool {
    $subject = "🔔 Nou usuari pendent d'aprovació: {$nom_nou}";
    $body = ra_email_template("
        <h2 style='color:#F39C12;'>Nou usuari pendent</h2>
        <p>Hi ha un nou usuari que necessita la teva aprovació:</p>
        <table style='width:100%; border-collapse:collapse; margin:16px 0;'>
            <tr style='background:#f8f9fa;'>
                <td style='padding:10px; font-weight:bold; width:40%;'>Nom</td>
                <td style='padding:10px;'>" . htmlspecialchars($nom_nou) . "</td>
            </tr>
            <tr>
                <td style='padding:10px; font-weight:bold;'>Email</td>
                <td style='padding:10px;'>" . htmlspecialchars($email_nou) . "</td>
            </tr>
        </table>
        <div style='text-align:center; margin:24px 0;'>
            <a href='https://redamigos.com/admin/aprobar_usuarios.php'
               style='background:#F39C12; color:white; padding:12px 30px;
                      border-radius:8px; text-decoration:none; font-weight:700;
                      display:inline-block;'>
                Revisar sol·licitud →
            </a>
        </div>
    ");
    return ra_enviar_email($admin_email, $subject, $body);
}

/**
 * Plantilla HTML base per emails
 */
function ra_email_template(string $content): string {
    return <<<HTML
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="margin:0; padding:0; background:#f0f2f5; font-family:Arial,sans-serif;">
        <div style="max-width:520px; margin:30px auto; background:white; 
                    border-radius:12px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.1);">
            <!-- Capçalera -->
            <div style="background:#4A90E2; padding:24px; text-align:center;">
                <h1 style="color:white; margin:0; font-size:1.6rem; font-weight:800;">
                    Red<span style="color:#7ED321;">Amigos</span>
                </h1>
                <p style="color:rgba(255,255,255,0.85); margin:4px 0 0; font-size:0.9rem;">
                    Connectem persones, creem somriures
                </p>
            </div>
            <!-- Contingut -->
            <div style="padding:32px 36px; line-height:1.6; color:#333;">
                {$content}
            </div>
            <!-- Peu -->
            <div style="background:#f8f9fa; padding:18px; text-align:center; 
                        color:#888; font-size:0.8rem; border-top:1px solid #eee;">
                © RedAmigos · Has rebut aquest email perquè ets membre de la comunitat.<br>
                Si no l'esperaves, ignora'l.
            </div>
        </div>
    </body>
    </html>
    HTML;
}

# 🚀 INSTAL·LACIÓ RÀPIDA - RedAmigos v2.0 Millorat

## ✅ QUÈ INCLOU AQUESTA VERSIÓ

### Millores Implementades:
1. ✅ **Login segur** amb password_verify i rate limiting
2. ✅ **Edició completa de perfil** amb pujada d'imatges
3. ✅ **Recuperació de contrasenya** amb tokens
4. ✅ **Registre millorat** amb validació avançada
5. ✅ **Sessions segures** (httponly, secure, samesite)
6. ✅ **Protecció contra brute force** (5 intents, bloqueig 5 min)

---

## 📦 INSTAL·LACIÓ EN 5 PASSOS

### PASO 1: Descomprimir
```bash
unzip redamigos_v2_mejorado.zip -d redamigos
cd redamigos
```

### PASO 2: Crear Base de Dades
```bash
mysql -u root -p
```
```sql
CREATE DATABASE app_social CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE app_social;
SOURCE database.sql;
```

### PASO 3: Configurar Connexió
Edita `config.php` amb les teves credencials:
```php
$host = "localhost";
$dbname = "app_social";
$username = "root";  // El teu usuari
$password = "";      // La teva contrasenya
```

### PASO 4: Crear Carpeta Uploads
```bash
mkdir -p uploads
chmod 755 uploads
cp uploads/default.png uploads/
```

### PASO 5: Actualitzar Contrasenyes
**IMPORTANT:** Les contrasenyes ara utilitzen bcrypt. Cal actualitzar-les:

```sql
-- Contrasenya "password123" per a tots els usuaris de prova
UPDATE usuarios SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
```

---

## 🔑 USUARIS DE PROVA

Després d'actualitzar les contrasenyes, pots entrar amb:

| Email | Contrasenya |
|-------|-------------|
| maria@ejemplo.com | password123 |
| juan@ejemplo.com | password123 |
| carmen@ejemplo.com | password123 |
| antonio@ejemplo.com | password123 |
| rosa@ejemplo.com | password123 |

---

## 🧪 PROVAR LES MILLORES

### 1. Login Segur
- ❌ Prova 5 intents fallits → Veuràs bloqueig de 5 minuts
- ✅ Login correcte → Funciona amb password_verify

### 2. Recuperar Contrasenya
- Vés a "¿Olvidaste tu contraseña?"
- Introdueix un email
- Veuràs el link (en producció s'enviaria per email)
- Clica el link i canvia la contrasenya

### 3. Editar Perfil Complet
- Login → Dashboard → "Editar mi perfil"
- Canvia nom, foto, ubicació, descripció
- Puja una imatge (JPG, PNG, GIF, WebP, màx 5MB)
- Veuràs previsualització abans de guardar

### 4. Registrar Nou Usuari
- Vés a "¿No tienes cuenta? Regístrate"
- Contrasenya OBLIGATORI:
  - Mínim 8 caràcters
  - 1 majúscula
  - 1 número
- Veuràs indicadors visuals en temps real

---

## 🗂️ ESTRUCTURA DE FITXERS

```
redamigos/
├── config.php                      # Configuració BD
├── database.sql                    # BD amb noves taules
├── index.php                       # Pàgina inicial
├── login.php                       # ✨ MILLORAT - Login segur
├── register.php                    # ✨ MILLORAT - Registre amb validació
├── recuperar_password.php          # ✨ NOU - Recuperació contrasenya
├── dashboard.php                   # ✨ ACTUALITZAT - Nou botó
├── editar_perfil.php               # Editar interessos
├── editar_perfil_completo.php      # ✨ NOU - Editar tot el perfil
├── feed.php                        # Feed social
├── mensajes.php                    # Missatgeria
├── solicitudes.php                 # Sol·licituds d'amistat
├── perfil_usuario.php              # Veure perfil d'altres
├── enviar_solicitud.php            # Backend enviar sol·licitud
├── update_intereses.php            # Backend actualitzar interessos
├── logout.php                      # Tancar sessió
├── uploads/                        # ⚠️ Cal crear amb chmod 755
│   └── default.png                 # Imatge per defecte
├── includes/
│   ├── head.php                    # CSS comú
│   ├── header.php                  # Capçalera
│   └── footer.php                  # Peu de pàgina
├── assets/
│   ├── css/
│   │   └── styles.css
│   └── img/
└── docs/
    ├── README.md                   # Documentació completa
    ├── MEJORAS_IMPLEMENTADAS.md    # ✨ Aquest document
    ├── MEJORAS_SUGERIDAS.md        # Roadmap futur
    ├── GUIA_DESARROLLADORES.md     # Referència tècnica
    └── RESUMEN_PROYECTO.md         # Visió general
```

---

## 🔐 NOVES TAULES EN LA BASE DE DADES

### `intentos_login` (rate limiting)
```sql
CREATE TABLE intentos_login (
    ip VARCHAR(45) PRIMARY KEY,
    intentos_fallidos INT DEFAULT 0,
    ultimo_intento TIMESTAMP
);
```

### `password_resets` (recuperació contrasenya)
```sql
CREATE TABLE password_resets (
    email VARCHAR(150) PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    expiracion DATETIME NOT NULL
);
```

---

## ⚠️ IMPORTANT PER PRODUCCIÓ

### 1. HTTPS Obligatori
Activa HTTPS al teu servidor. Sense HTTPS:
- Les sessions no seran 100% segures
- Les cookies secure no funcionaran

### 2. Email Real per Recuperació
El fitxer `recuperar_password.php` actualment **mostra el link directament** (només per desenvolupament).

Per producció, cal configurar SMTP:

**Opció 1: PHPMailer (recomanat)**
```bash
composer require phpmailer/phpmailer
```

Després edita `recuperar_password.php` línia 49:
```php
// Reemplaçar:
$success .= "<br><br><strong>SOLO DESARROLLO:</strong><br><a href='$reset_link'>$reset_link</a>";

// Amb:
require 'vendor/autoload.php';
$mail = new PHPMailer\PHPMailer\PHPMailer(true);

// Configurar SMTP (Gmail, SendGrid, etc.)
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'tu_email@gmail.com';
$mail->Password = 'tu_app_password';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->setFrom('noreply@redamigos.com', 'RedAmigos');
$mail->addAddress($email);
$mail->Subject = 'Recuperar contraseña - RedAmigos';
$mail->Body = "Hola,\n\nHaz clic para restablecer tu contraseña:\n\n$reset_link\n\nExpira en 30 minutos.";
$mail->send();
```

**Opció 2: SendGrid API**
Més fiable que SMTP directe.

### 3. Permisos de Fitxers
```bash
# Carpeta uploads
chmod 755 uploads

# Fitxers PHP
chmod 644 *.php

# Config (més restrictiu)
chmod 600 config.php
```

### 4. Logs d'Errors
Afegir a `config.php`:
```php
// En desenvolupament
ini_set('display_errors', 1);
error_reporting(E_ALL);

// En producció (canviar a):
ini_set('display_errors', 0);
error_log('log_file', 'path/to/errors.log');
```

### 5. Backups Automàtics
```bash
# Afegir a crontab (cada dia a les 2am)
0 2 * * * mysqldump -u root -p app_social > /backups/app_social_$(date +\%Y\%m\%d).sql
```

---

## 🐛 TROUBLESHOOTING

### Error: "Call to undefined function password_verify()"
- PHP < 5.5 no té password_verify()
- Solució: Actualitza a PHP 7.4+ o 8.0+

### Error: "Table 'intentos_login' doesn't exist"
- No has executat el database.sql actualitzat
- Solució: Executa les noves taules manualment

### Error: "Permission denied" al pujar imatges
- Carpeta uploads sense permisos d'escriptura
- Solució: `chmod 755 uploads`

### Login no funciona amb usuaris antics
- Les contrasenyes antigues eren SHA256
- Solució: Executa l'UPDATE de contrasenyes

### Link de recuperació dona error
- Token expirat (>30 min) o ja utilitzat
- Solució: Sol·licita un nou link

---

## 📈 ESTADÍSTIQUES DE MILLORES

- **Fitxers nous:** 3 (editar_perfil_completo.php, recuperar_password.php, MEJORAS_IMPLEMENTADAS.md)
- **Fitxers reescrits:** 2 (login.php, register.php)
- **Fitxers actualitzats:** 2 (database.sql, dashboard.php)
- **Línies de codi afegides:** ~1,300
- **Vulnerabilitats corregides:** 5 crítiques
- **Funcionalitats noves:** 3 majors

---

## ✅ CHECKLIST ABANS DE PRODUCCIÓ

- [ ] Executa `database.sql` amb noves taules
- [ ] Actualitza contrasenyes a bcrypt
- [ ] Crea carpeta `uploads` amb permisos
- [ ] Configura SMTP per emails
- [ ] Activa HTTPS
- [ ] Desactiva display_errors
- [ ] Configura backups automàtics
- [ ] Prova tots els fluxos:
  - [ ] Login / Logout
  - [ ] Registre
  - [ ] Recuperar contrasenya
  - [ ] Editar perfil complet
  - [ ] Pujar imatge
  - [ ] 5 intents fallits (bloqueig)
- [ ] Revisa logs d'errors
- [ ] Fes backup de BD actual

---

## 🎯 SEGÜENTS PASSOS RECOMANATS

Ara que tens la seguretat i funcionalitats bàsiques, et recomano:

1. **Curt termini (1 setmana):**
   - Afegir notificacions en temps real (AJAX)
   - Sistema de cerca d'usuaris
   - Millor gestió d'errors amb try-catch

2. **Mitjà termini (2-4 setmanes):**
   - Sistema d'esdeveniments/quedades
   - Grups temàtics
   - Videoxat amb WebRTC
   - Dashboard d'administració

3. **Llarg termini (2-6 mesos):**
   - App mòbil nativa
   - API REST
   - Notificacions push
   - Analytics i estadístiques

---

## 📞 SUPORT

Si tens problemes:
1. Consulta GUIA_DESARROLLADORES.md per referència tècnica
2. Revisa logs d'errors PHP i MySQL
3. Comprova permisos de fitxers
4. Verifica configuració a config.php

---

**RedAmigos v2.0** - Ara més segur i funcional! 🚀

**Data:** 31 de gener de 2026
**Versió:** 2.0 - Millores Crítiques Implementades
**Estat:** ✅ Llest per proves finals

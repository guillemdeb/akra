# 🔧 ERRORS COMUNS I SOLUCIONS - RedAmigos

## 📋 Índex d'Errors

1. [Error: Taula 'intentos_login' no existeix](#error-1)
2. [Error: No puc fer login amb cap usuari](#error-2)
3. [Error: "Pendiente de aprobación"](#error-3)
4. [Error: Password incorrecte](#error-4)
5. [Error: Pàgina en blanc](#error-5)
6. [Error: "Call to undefined function"](#error-6)
7. [Error: No es poden pujar imatges](#error-7)
8. [Error: Sessions no funcionen](#error-8)

---

## <a id="error-1"></a>❌ ERROR 1: Taula 'intentos_login' no existeix

### Missatge d'Error:
```
SQLSTATE[42S02]: Base table or view not found: 1146 
Table 'app_social.intentos_login' doesn't exist
```

### Causa:
Has executat el `database.sql` antic en lloc del `database_v3.sql` actualitzat.

### ✅ Solució 1 (Recomanada - Crear només la taula):
```sql
USE app_social;

CREATE TABLE IF NOT EXISTS intentos_login (
    ip VARCHAR(45) PRIMARY KEY,
    intentos_fallidos INT DEFAULT 0,
    ultimo_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ultimo_intento (ultimo_intento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### ✅ Solució 2 (Executar BD completa):
```bash
# ⚠️ ATENCIÓ: Això esborrarà totes les dades!
mysql -u root -p < database_v3.sql
```

---

## <a id="error-2"></a>❌ ERROR 2: No puc fer login amb cap usuari

### Símptomes:
- Introdueixo email i password correctes
- Torno a la pàgina de login sense missatge
- O missatge "Email o password incorrectes"

### Causes Possibles:
1. Usuaris NO aprovats (`aprobado = FALSE`)
2. Passwords no són bcrypt hash
3. Usuaris desactivats (`activo = FALSE`)
4. No hi ha usuaris a la BD

### ✅ Diagnòstic:
Executa aquest script: **`check_errors.php`** (inclòs al ZIP)

### ✅ Solucions:

#### Solució A: Aprovar tots els usuaris
```sql
USE app_social;
UPDATE usuarios SET aprobado = TRUE, fecha_aprobacion = NOW();
```

#### Solució B: Actualitzar passwords a bcrypt
```sql
USE app_social;
UPDATE usuarios 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email IN ('maria@ejemplo.com', 'juan@ejemplo.com', 'carmen@ejemplo.com');

-- Aquest hash correspon a: password123
```

#### Solució C: Activar usuaris
```sql
USE app_social;
UPDATE usuarios SET activo = TRUE;
```

#### Solució D: Usar login temporal
Usa el fitxer `login_temporal.php` que accepta qualsevol tipus de password.

---

## <a id="error-3"></a>❌ ERROR 3: "Pendiente de aprobación"

### Missatge:
```
Tu cuenta está pendiente de aprobación. 
Te avisaremos por email cuando sea aprobada.
```

### Causa:
El camp `aprobado = FALSE` a la taula usuarios.

### ✅ Solució Ràpida:
```sql
USE app_social;

-- Aprovar TOTS els usuaris
UPDATE usuarios SET aprobado = TRUE, fecha_aprobacion = NOW();
```

### ✅ Solució per Usuari Específic:
```sql
UPDATE usuarios 
SET aprobado = TRUE, fecha_aprobacion = NOW() 
WHERE email = 'maria@ejemplo.com';
```

---

## <a id="error-4"></a>❌ ERROR 4: Password incorrecte

### Símptomes:
- Estic segur que la password és correcta
- Però diu "Password incorrecte"

### Causes:
1. Password NO està en format bcrypt
2. Hash corrupte
3. Utilitzes SHA256 antic

### ✅ Diagnòstic:
```sql
-- Veure el hash actual
SELECT email, password FROM usuarios WHERE email = 'maria@ejemplo.com';

-- Si el hash NO comença per $2y$, està malament
```

### ✅ Solució:
```sql
-- Actualitzar a bcrypt correcte
UPDATE usuarios 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'maria@ejemplo.com';

-- Password: password123
```

### ✅ Generar nou hash (PHP):
```php
<?php
$password = "la_teva_password";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;
?>
```

---

## <a id="error-5"></a>❌ ERROR 5: Pàgina en blanc

### Símptomes:
- La pàgina carrega però està completament en blanc
- No hi ha missatges d'error

### Causes:
1. Error PHP fatal
2. `display_errors = Off` al php.ini
3. Error de sintaxi

### ✅ Solució:
Activar errors temporalment al fitxer:

```php
<?php
// Afegir SEMPRE al començament del fitxer
error_reporting(E_ALL);
ini_set('display_errors', 1);

// El teu codi aquí...
?>
```

### ✅ Comprovar logs d'error:
```bash
# WAMP
C:\wamp64\logs\php_error.log

# XAMPP
C:\xampp\apache\logs\error.log
```

---

## <a id="error-6"></a>❌ ERROR 6: "Call to undefined function"

### Missatge Típic:
```
Fatal error: Call to undefined function password_verify()
```

### Causa:
Versió de PHP antiga (<5.5)

### ✅ Solució:
1. Actualitza PHP a 7.4 o 8.x
2. Al WAMP: Clic esquerre icona → PHP → Version → Tria 7.4+

---

## <a id="error-7"></a>❌ ERROR 7: No es poden pujar imatges

### Símptomes:
- Error en pujar foto de perfil
- "Permission denied"

### Causa:
Carpeta `uploads/` sense permisos d'escriptura

### ✅ Solució Windows (WAMP):
1. Botó dret a carpeta `uploads/`
2. Propietats → Seguretat
3. Editar → Afegir "Tots" amb control total

### ✅ Solució Linux:
```bash
chmod 755 uploads/
chown www-data:www-data uploads/
```

### ✅ Comprovar permisos (PHP):
```php
<?php
$dir = 'uploads';
echo is_writable($dir) ? "✅ Escrivible" : "❌ NO escrivible";
?>
```

---

## <a id="error-8"></a>❌ ERROR 8: Sessions no funcionen

### Símptomes:
- Login sembla funcionar però torna a index.php
- `$_SESSION` està buida
- "Headers already sent"

### Causa:
1. `session_start()` després d'output
2. Espais abans de `<?php`
3. Carpeta sessions no escrivible

### ✅ Solució 1: Comprovar session_start()
```php
<?php
// ✅ CORRECTE: session_start() PRIMER
session_start();
require "config.php";
?>

<?php
// ❌ INCORRECTE: HTML abans
echo "Hola";
session_start(); // ERROR!
?>
```

### ✅ Solució 2: Eliminar espais
```php
<?php // ← Res abans d'això!
session_start();
?>
```

### ✅ Solució 3: Permisos carpeta sessions
```bash
# Windows WAMP
C:\wamp64\tmp

# Dona permisos d'escriptura
```

---

## 🔧 SCRIPT DE DIAGNÒSTIC AUTOMÀTIC

He creat **3 scripts** per diagnosticar problemes:

### 1. **check_errors.php** (Diagnòstic complet)
- Comprova BD, taules, camps
- Verifica fitxers i carpetes
- Test de passwords
- Accions ràpides d'arreglament

**Ús:**
```
http://localhost/caminantes2/check_errors.php
```

### 2. **test_login.php** (Test específic de login)
- Llista usuaris
- Comprova aprovació
- Test manual de login
- Botó per aprovar usuaris

**Ús:**
```
http://localhost/caminantes2/test_login.php
```

### 3. **login_temporal.php** (Login d'emergència)
- Accepta passwords amb/sense hash
- Bypass temporal de seguretat
- Només per diagnòstic

**Ús:**
```
http://localhost/caminantes2/login_temporal.php
```

---

## 📊 CHECKLIST DE VERIFICACIÓ

Abans de demanar ajuda, comprova:

- [ ] He executat `database_v3.sql` (NO database.sql)
- [ ] La taula `intentos_login` existeix
- [ ] Els usuaris tenen `aprobado = TRUE`
- [ ] Les passwords són bcrypt (comencen per `$2y$`)
- [ ] La carpeta `uploads/` té permisos d'escriptura
- [ ] PHP versió 7.4 o superior
- [ ] He executat `check_errors.php` per diagnòstic
- [ ] Els errors de PHP estan activats
- [ ] `config.php` té les credencials correctes

---

## 🚀 SOLUCIÓ RÀPIDA TOTAL

Si vols començar de zero amb tot correcte:

```sql
-- 1. Esborrar i recrear BD
DROP DATABASE IF EXISTS app_social;
CREATE DATABASE app_social CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Executar database_v3.sql complet
SOURCE /ruta/a/database_v3.sql;

-- O amb command line:
-- mysql -u root -p < database_v3.sql

-- 3. Verificar que funciona
USE app_social;
SELECT email, aprobado FROM usuarios;

-- Tots haurien de tenir aprobado = 1 (TRUE)
```

**Després:**
- Login: `maria@ejemplo.com` / `password123`
- Admin: `admin@redamigos.com` / `password123`

---

## 📞 ENCARA TENS PROBLEMES?

1. **Executa:** `check_errors.php`
2. **Fes captura** de pantalla dels resultats
3. **Copia** els missatges d'error exactes
4. **Comprova** els logs de PHP

---

## 🎓 MILLORS PRÀCTIQUES

### ✅ Desenvolupament:
```php
// Activar errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### ✅ Producció:
```php
// Desactivar errors
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
```

### ✅ Sessions:
```php
// SEMPRE al començament
session_start();
```

### ✅ BD:
```php
// SEMPRE amb PDO i prepared statements
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
$stmt->execute(['email' => $email]);
```

---

**Data:** 1 febrer 2026
**Versió:** RedAmigos v4.0

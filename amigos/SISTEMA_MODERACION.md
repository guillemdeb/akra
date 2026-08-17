# 🛡️ SISTEMA DE MODERACIÓ I APROVACIÓ PRÈVIA

## 📋 Visió General

Sistema complet de **moderació amb aprovació prèvia** per garantir que només usuaris verificats accedeixin a la plataforma.

**Concepte clau:** "Censura prèvia" - Cap usuari nou pot accedir fins que un administrador ho aprovi.

---

## ✨ COM FUNCIONA

### 🔄 Flux Complet

```
1. Usuari nou → Registre
   ↓
2. Compte creada amb aprobado = FALSE
   ↓
3. Missatge: "Pendiente de aprobación"
   ↓
4. Admin rep notificació
   ↓
5. Admin revisa dades al panel
   ↓
6. Admin decideix:
   a) APROBAR → aprobado = TRUE
   b) RECHAZAR → activo = FALSE
   ↓
7. Usuari rep notificació
   ↓
8. Si aprovat → Pot fer login
   Si rebutjat → No pot entrar
```

---

## 🎯 ESTATS D'USUARI

### Taula `usuarios` - Nous Camps:

```sql
aprobado BOOLEAN DEFAULT FALSE
  → TRUE: Usuari aprovat, pot accedir
  → FALSE: Pendent d'aprovació, NO pot accedir

fecha_aprobacion TIMESTAMP NULL
  → Quan va ser aprovat

aprobado_por INT NULL
  → ID de l'admin que va aprovar

motivo_rechazo TEXT NULL
  → Motiu si va ser rebutjat

notas_admin TEXT NULL
  → Notes internes per admins
```

### Combinacions d'Estats:

| aprobado | activo | Resultat |
|----------|--------|----------|
| FALSE | TRUE | **Pendent aprovació** (nou registre) |
| TRUE | TRUE | **Usuari actiu** (normal) |
| FALSE | FALSE | **Rebutjat** (no pot entrar) |
| TRUE | FALSE | **Desactivat** (suspès) |

---

## 🔐 SISTEMA DE LOGIN ACTUALITZAT

### Validació en 3 Passos:

```php
// 1. Verificar email i password
if (password_verify($password, $usuario['password'])) {
    
    // 2. Verificar aprovació
    if (!$usuario['aprobado']) {
        $error = "Tu cuenta está pendiente de aprobación";
        // NO PERMET LOGIN
    }
    
    // 3. Verificar actiu
    elseif (!$usuario['activo']) {
        $error = "Tu cuenta está desactivada";
        // NO PERMET LOGIN
    }
    
    // 4. Tot OK
    else {
        // LOGIN PERMÈS
    }
}
```

---

## 📝 REGISTRE ACTUALITZAT

### Nou Flux de Registre:

**Abans:**
```php
INSERT INTO usuarios (...) VALUES (...)
→ Login immediat
```

**Ara:**
```php
INSERT INTO usuarios (..., aprobado) VALUES (..., FALSE)
→ Notificar admins
→ Missatge: "Pendiente de aprobación"
→ NO login fins aprovació
```

### Pantalla Post-Registre:

```
✅ ¡Registro completado!

Tu cuenta ha sido creada exitosamente.
Está pendiente de aprobación por nuestro equipo.

📋 Próximos pasos:
• Revisaremos tu solicitud en 24-48 horas
• Te enviaremos un email cuando sea aprobada
• Podrás iniciar sesión una vez aprobada

Si tienes preguntas: soporte@redamigos.com
```

---

## 👨‍💼 PANEL D'ADMINISTRACIÓ

### Accés al Panel:

**URL:** `/admin/aprobar_usuarios.php`

**Qui pot accedir:**
- Lista blanca d'emails administradors
- Per defecte: `admin@redamigos.com` i `maria@ejemplo.com`
- **IMPORTANT:** Canviar això en producció!

```php
$admins_emails = [
    'admin@redamigos.com',
    'tu@email.com'  // Afegir aquí
];
```

### Característiques del Panel:

#### 📊 Estadístiques en Temps Real:
```
┌─────────────────────────────┐
│ Pendientes: 5               │
│ Aprobados: 127              │
│ Rechazados: 8               │
└─────────────────────────────┘
```

#### 📋 Llista de Pendents:

Per a cada usuari pendent es mostra:
- ✅ **Nom complet**
- ✅ **Email**
- ✅ **Edat i gènere**
- ✅ **Ubicació**
- ✅ **Telèfon** (si el va proporcionar)
- ✅ **Descripció personal**
- ✅ **Data de registre** + dies pendents
- ✅ **Badge "PENDIENTE"**

#### ⚡ Accions Disponibles:

**1. APROBAR:**
```
Botó verd: "Aprobar Usuario"
→ Confirmació
→ UPDATE aprobado = TRUE
→ Notificació a l'usuari
→ Usuari ja pot entrar
```

**2. RECHAZAR:**
```
Botó vermell: "Rechazar"
→ Modal amb motiu
→ UPDATE activo = FALSE
→ Guardar motiu_rechazo
→ Notificació a l'usuari amb motiu
→ Usuari NO podrà entrar
```

---

## 🔔 SISTEMA DE NOTIFICACIONS

### Per a Admins:

Quan un nou usuari es registra:
```sql
INSERT INTO notificaciones (usuario_id, tipo, contenido, enlace) 
VALUES (
    admin_id,
    'sistema',
    'Nuevo usuario pendiente de aprobación: María García',
    'admin/aprobar_usuarios.php?id=123'
)
```

### Per a Usuaris Aprovats:

```sql
INSERT INTO notificaciones (usuario_id, tipo, contenido, enlace) 
VALUES (
    usuario_id,
    'sistema',
    '¡Bienvenido! Tu cuenta ha sido aprobada. Ya puedes empezar a usar RedAmigos.',
    'dashboard.php'
)
```

### Per a Usuaris Rebutjats:

```sql
INSERT INTO notificaciones (usuario_id, tipo, contenido) 
VALUES (
    usuario_id,
    'sistema',
    'Tu solicitud de registro ha sido rechazada. Motivo: [motivo especificat]'
)
```

---

## 🎨 INTERFÍCIE DEL PANEL

### Disseny Professional:

```
┌─────────────────────────────────────────────┐
│  🛡️ Panel de Administración                │
│  Gestión y aprobación de nuevos usuarios   │
│                                             │
│  [Volver a RedAmigos] [Cerrar sesión]      │
└─────────────────────────────────────────────┘

┌─────────────┬─────────────┬─────────────┐
│ Pendientes  │ Aprobados   │ Rechazados  │
│     5       │    127      │      8      │
└─────────────┴─────────────┴─────────────┘

┌─────────────────────────────────────────────┐
│ María García                    [PENDIENTE] │
│ Registrado hace 2 días · 29/01/2026 14:30  │
│                                             │
│ Email: maria@test.com                       │
│ Edad: 65 años | Género: Mujer              │
│ Ubicación: Alicante                         │
│ Teléfono: 666 123 456                       │
│                                             │
│ Descripción:                                │
│ Me encanta caminar y conocer gente...      │
│                                             │
│ [✓ Aprobar Usuario] [✗ Rechazar]          │
└─────────────────────────────────────────────┘
```

---

## 💡 CASOS D'ÚS

### Cas 1: Registre Normal

**Usuari:**
1. Completa formulari de registre
2. Veu missatge: "Pendiente de aprobación"
3. Espera email

**Admin:**
1. Rep notificació
2. Revisa dades al panel
3. Tot correcte → Clica "Aprobar"
4. Usuari rep email

**Usuari:**
5. Fa login correctament
6. Comença a usar l'app

### Cas 2: Perfil Sospitós

**Usuari:**
1. Es registra amb dades estranyes
2. Descripció: "Vendo productos..."

**Admin:**
1. Revisa al panel
2. Veu que és spam
3. Clica "Rechazar"
4. Motiu: "Perfil comercial no permitido"

**Usuari:**
5. Rep notificació de rebuig
6. NO pot fer login mai

### Cas 3: Informació Incompleta

**Usuari:**
1. Es registra sense descripció
2. Edat: 18 anys (mínim)

**Admin:**
1. Veu que falta info
2. Clica "Rechazar"
3. Motiu: "Por favor completa tu perfil con más información"

**Usuari:**
4. Rep notificació
5. Pot registrar-se de nou amb més detalls

---

## 🔒 SEGURETAT

### Protecció del Panel Admin:

```php
// 1. Verificar sessió
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// 2. Verificar que és admin
$admins_emails = ['admin@redamigos.com'];
if (!in_array($usuario['email'], $admins_emails)) {
    header("Location: ../dashboard.php");
    exit();
}
```

### Millores de Seguretat Recomanades:

1. **Autenticació 2FA per admins**
2. **Logs d'accions** (qui va aprovar què i quan)
3. **Límit de reintents** de registre per IP
4. **Verificació d'email** abans d'aprovar
5. **Captcha** en el registre

---

## 📊 MÈTRIQUES A SEGUIR

### KPIs Importants:

1. **Taxa d'aprovació:**
   ```
   Aprovats / Total registres = X%
   Objectiu: >80%
   ```

2. **Temps mitjà d'aprovació:**
   ```
   Mitjana (data_aprovació - data_registre)
   Objectiu: <24 hores
   ```

3. **Taxa de rebuig:**
   ```
   Rebutjats / Total registres = X%
   Objectiu: <20%
   ```

4. **Motius de rebuig més comuns:**
   ```
   Top 3 motius
   Per millorar procés de registre
   ```

---

## 🎓 GUIA PER A ADMINISTRADORS

### Criteris d'Aprovació:

**✅ APROVAR si:**
- Nom i cognom reals
- Edat adequada (18+)
- Descripció coherent i real
- Email vàlid
- Ubicació real
- No hi ha sospites de spam/bot

**❌ REBUTJAR si:**
- Perfil incomplet
- Informació falsa o sospitosa
- Edat inferior a 18
- Descripció comercial/spam
- Email temporal/fals
- Duplicat (mateix usuari 2 cops)

### Bones Pràctiques:

1. **Revisar TOTS els camps**
2. **Comprovar coherència** (edat vs descripció)
3. **Buscar a Google** si hi ha sospites
4. **Ser just però estricte**
5. **Donar sempre un motiu clar** de rebuig
6. **Revisar cada 24h màxim**
7. **Logs** de totes les accions

---

## 🚀 MILLORES FUTURES

### Fase 1 (Recomanat):
- [ ] **Email de verificació** abans d'aprovar
- [ ] **Logs d'auditoria** de totes les aprovacions
- [ ] **Dashboard amb gràfics** de stats
- [ ] **Filtres i cerca** al panel

### Fase 2:
- [ ] **Aprovació automàtica** amb IA (ML)
- [ ] **Verificació d'identitat** amb DNI
- [ ] **Sistema de puntuació** (score de confiança)
- [ ] **Review posterior** aleatori d'aprovats

### Fase 3:
- [ ] **Multi-admin** amb rols
- [ ] **Aprovació en 2 passos** (2 admins)
- [ ] **API** per gestió externa
- [ ] **Mobile app** per admins

---

## 📞 CONFIGURACIÓ PER A PRODUCCIÓ

### 1. Canviar Admins:

**Fitxer:** `admin/aprobar_usuarios.php`

```php
// Línia ~18 - CANVIAR AIXÒ!
$admins_emails = [
    'admin@redamigos.com',     // Email principal
    'moderador@redamigos.com', // Email secundari
    'tu@email.com'             // El teu email
];
```

### 2. Configurar Emails:

Integrar PHPMailer per enviar emails automàtics:

```php
// Quan s'aprova
$mail->Subject = '¡Tu cuenta ha sido aprobada!';
$mail->Body = 'Bienvenido a RedAmigos...';
$mail->send();

// Quan es rebutja
$mail->Subject = 'Solicitud de registro';
$mail->Body = 'Tu solicitud ha sido rechazada. Motivo: ' . $motivo;
$mail->send();
```

### 3. Configurar Notificacions:

**Mètodes recomanats:**
- **Email** (essencial)
- **SMS** (opcional, per urgents)
- **Slack/Discord** (per equip admin)
- **Panel notificacions** (ja implementat)

---

## ✅ CHECKLIST D'IMPLEMENTACIÓ

- [x] Camp `aprobado` a taula usuarios
- [x] Validació al login
- [x] Missatge post-registre
- [x] Panel d'administració
- [x] Estadístiques en temps real
- [x] Aprovar usuaris
- [x] Rebutjar amb motiu
- [x] Notificacions automàtiques
- [x] Usuaris de prova aprovats
- [x] Documentació completa

---

## 🎉 CONCLUSIÓ

El **Sistema de Moderació** garanteix que:

✅ **Només usuaris reals** accedeixen a la plataforma
✅ **Control total** sobre qui entra
✅ **Filtre de spam** i bots
✅ **Comunitat segura** i de confiança
✅ **Transparència** amb motius de rebuig
✅ **Procés ràpid** (<24h)

Aquest sistema és **essencial** per a una xarxa social de gent gran, on la **confiança i seguretat** són prioritàries! 🛡️

---

**Versió:** 4.0 - Sistema de Moderació
**Data:** 1 de febrer de 2026
**Estat:** ✅ Implementat i funcional

**IMPORTANT:** Recorda canviar els emails d'admin abans de posar en producció!

# 🚀 GUIA RÀPIDA PER A DESENVOLUPADORS

## ⚡ Referència Ràpida de l'Aplicació RedAmigos

---

## 📋 Fitxers Principals i la seva Funció

### **Autenticació i Usuari**
| Fitxer | Funció |
|--------|--------|
| `index.php` | Pàgina d'inici amb formulari de login |
| `login.php` | Processa l'autenticació |
| `register.php` | Registre de nous usuaris |
| `logout.php` | Tancament de sessió |
| `dashboard.php` | Perfil personal de l'usuari |

### **Feed i Xarxa Social**
| Fitxer | Funció |
|--------|--------|
| `feed.php` | Feed principal amb pestanyes Amigos/Coincidencias |
| `perfil_usuario.php` | Veure perfil d'un altre usuari |
| `solicitudes.php` | Gestió de sol·licituds d'amistat |
| `enviar_solicitud.php` | Endpoint per enviar sol·licitud |

### **Missatgeria**
| Fitxer | Funció |
|--------|--------|
| `mensajes.php` | Sistema de xat entre amics |

### **Configuració**
| Fitxer | Funció |
|--------|--------|
| `config.php` | Connexió a la base de dades |
| `editar_perfil.php` | Edició d'interessos |
| `update_intereses.php` | Processa actualització d'interessos |

### **Base de Dades**
| Fitxer | Funció |
|--------|--------|
| `database.sql` | Script SQL complet amb estructura i dades de prova |

---

## 🗄️ Estructura de la Base de Dades

### **Taules Principals**

#### `usuarios`
```sql
- id (PK)
- nombre, email, password
- telefono, edad, genero, ubicacion
- foto, descripcion
- mostrar_telefono, mostrar_email
- fecha_registro, ultima_conexion, activo
```

#### `intereses`
```sql
- id (PK)
- nombre (UNIQUE)
- icono (classe FontAwesome)
- categoria
```

#### `usuario_interes` (molts a molts)
```sql
- usuario_id (FK → usuarios)
- interes_id (FK → intereses)
- fecha_agregado
```

#### `amistades`
```sql
- id (PK)
- usuario_id (FK → usuarios, qui envia)
- amigo_id (FK → usuarios, qui rep)
- estado ('pendiente', 'aceptada', 'rechazada', 'bloqueada')
- fecha_solicitud, fecha_respuesta
```

#### `mensajes`
```sql
- id (PK)
- remitente_id (FK → usuarios)
- destinatario_id (FK → usuarios)
- mensaje, leido
- fecha_envio
```

#### `notificaciones`
```sql
- id (PK)
- usuario_id (FK → usuarios)
- tipo ('amistad', 'mensaje', 'sistema')
- contenido, leida, enlace
- fecha_creacion
```

---

## 🔐 Sessions PHP

### Variables de Sessió Utilitzades:
```php
$_SESSION['usuario_id']     // ID de l'usuari autenticat
$_SESSION['usuario_nombre']  // Nom de l'usuari
```

### Verificació de Sessió (codi estàndard):
```php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}
```

---

## 🎨 Colors i Estil

### Variables CSS Principals:
```css
:root {
    --color-principal: #4A90E2;   /* Blau principal */
    --color-accion: #7ED321;      /* Verd acció */
    --color-fondo: #F5F5F5;       /* Fons */
    --color-blanco: #FFFFFF;      /* Blanc */
    --color-texto: #333333;       /* Text */
    --color-gris: #888888;        /* Gris secundari */
    --sombra: 0 2px 8px rgba(0,0,0,0.1);
}
```

---

## 📝 Consultes SQL Comunes

### 1. Obtenir Amics Acceptats
```php
$sql = "SELECT u.* 
        FROM usuarios u
        INNER JOIN amistades a ON 
            (a.usuario_id = :id AND a.amigo_id = u.id) OR 
            (a.amigo_id = :id AND a.usuario_id = u.id)
        WHERE a.estado = 'aceptada' AND u.id != :id";
```

### 2. Usuaris amb Interessos Comuns
```php
$sql = "SELECT u.*, COUNT(DISTINCT ui.interes_id) AS intereses_comunes
        FROM usuarios u
        INNER JOIN usuario_interes ui ON ui.usuario_id = u.id
        INNER JOIN usuario_interes ui2 ON 
            ui2.interes_id = ui.interes_id AND 
            ui2.usuario_id = :usuario_id
        WHERE u.id != :usuario_id
        GROUP BY u.id
        HAVING intereses_comunes > 0
        ORDER BY intereses_comunes DESC";
```

### 3. Sol·licituds Pendents
```php
$sql = "SELECT a.*, u.nombre, u.foto 
        FROM amistades a
        INNER JOIN usuarios u ON u.id = a.usuario_id
        WHERE a.amigo_id = :id AND a.estado = 'pendiente'";
```

### 4. Missatges entre Dos Usuaris
```php
$sql = "SELECT m.*, u.nombre as remitente_nombre
        FROM mensajes m
        INNER JOIN usuarios u ON u.id = m.remitente_id
        WHERE (m.remitente_id = :yo AND m.destinatario_id = :el) 
           OR (m.remitente_id = :el AND m.destinatario_id = :yo)
        ORDER BY m.fecha_envio ASC";
```

---

## 🛠️ Funcions d'Utilitat Comunes

### Protecció XSS:
```php
htmlspecialchars($dada, ENT_QUOTES, 'UTF-8');
```

### Neteja d'Input:
```php
trim($_POST['camp'] ?? '');
```

### Hash de Contrasenya:
```php
// Al registre:
$hash = password_hash($contrasenya, PASSWORD_DEFAULT);

// Al login:
if (password_verify($contrasenya_input, $hash_bd)) {
    // Autenticat!
}
```

### Validació Email:
```php
filter_var($email, FILTER_VALIDATE_EMAIL);
```

---

## 🚦 Estats d'Amistat

| Estat | Significat |
|-------|-----------|
| `pendiente` | Sol·licitud enviada, esperant resposta |
| `aceptada` | Amistat confirmada |
| `rechazada` | Sol·licitud rebutjada |
| `bloqueada` | Usuari bloquejat (futur) |

---

## 🎯 Fluxos Principals

### Flux de Sol·licitud d'Amistat:
```
1. Usuari A veu usuari B al feed (Coincidencias)
2. Usuari A clica "Enviar solicitud"
3. → enviar_solicitud.php crea registre a `amistades` amb estado='pendiente'
4. → Crea notificació per a usuari B
5. Usuari B veu sol·licitud a solicitudes.php
6. Usuari B accepta o rebutja
7. → solicitudes.php actualitza estado a 'aceptada' o 'rechazada'
8. Usuari A rep notificació si s'ha acceptat
9. Ara apareixen mútuament al feed (Amigos)
```

### Flux de Missatgeria:
```
1. Dos usuaris han de ser amics (estado='aceptada')
2. Usuari A accedeix a mensajes.php
3. Selecciona conversa amb usuari B
4. Escriu missatge i envia
5. → missatge es guarda a taula `mensajes`
6. → Es crea notificació per a usuari B
7. Usuari B veu badge de missatge nou
8. Usuari B accedeix a la conversa
9. → Missatges es marquen com llegits automàticament
```

---

## 📱 Responsive Design

### Breakpoints:
```css
/* Mòbil petit */
@media (max-width: 480px) { }

/* Mòbil gran / Tablet petit */
@media (max-width: 768px) { }

/* Tablet / Desktop petit */
@media (min-width: 768px) { }

/* Desktop gran */
@media (min-width: 1200px) { }
```

---

## 🔧 Troubleshooting Comú

### 1. Error de Connexió a BD:
```
❌ Error de connexió: SQLSTATE[HY000] [1045]
```
**Solució:** Revisar credencials a `config.php`

### 2. Sessions no Funcionen:
**Solució:** Assegurar-se que `session_start()` és al principi del fitxer

### 3. Imatges no es Mostren:
**Solució:** Verificar que la carpeta `uploads/` existeix i té permisos 755

### 4. FontAwesome no es Carrega:
**Solució:** Verificar connexió a internet i CDN:
```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
```

---

## 📦 Checklist Abans de Deploy

- [ ] Actualitzar credencials de BD a `config.php`
- [ ] Canviar login.php per verificar contrasenyes reals
- [ ] Crear carpeta `uploads/` amb permisos adequats
- [ ] Importar `database.sql`
- [ ] Provar registre d'usuari
- [ ] Provar login i navegació
- [ ] Verificar que HTTPS està activat (producció)
- [ ] Configurar backups automàtics de BD
- [ ] Configurar logs d'errors

---

## 🎓 Convencions de Codi

### Nomenclatura:
- **Variables PHP:** `$nom_variable` (snake_case)
- **Funcions:** `nomFuncio()` (camelCase)
- **Classes:** `NomClasse` (PascalCase)
- **Taules SQL:** `nom_taula` (snake_case, plural)
- **CSS Classes:** `nom-classe` (kebab-case)

### Comentaris:
```php
// Comentari d'una línia

/*
 * Comentari de bloc
 * per a explicacions llargues
 */
 
/**
 * Comentari de documentació
 * @param type $variable Descripció
 * @return type Descripció
 */
```

---

## 🆘 Recursos Útils

- **PHP Manual:** https://www.php.net/manual/es/
- **PDO Tutorial:** https://phpdelusions.net/pdo
- **FontAwesome Icons:** https://fontawesome.com/icons
- **CSS Flexbox:** https://css-tricks.com/snippets/css/a-guide-to-flexbox/
- **SQL Joins:** https://www.w3schools.com/sql/sql_join.asp

---

## 📞 Contacte Tècnic

Per a preguntes tècniques o dubtes sobre el codi, consulta:
- README.md (documentació general)
- MEJORAS_SUGERIDAS.md (roadmap de funcionalitats)
- Codi font amb comentaris inline

---

**RedAmigos** - Guia ràpida per a desenvolupadors

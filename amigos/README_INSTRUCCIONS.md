# RedAmigos - Millores Implementades 🚀

## Funcionalitats Noves Afegides

### 1. ✅ Sistema de Notificacions
- Notificacions en temps real
- Badge amb contador de notificacions no llegides
- Dropdown amb les últimes notificacions
- Actualització automàtica cada 30 segons
- Tipus de notificacions: amistats, esdeveniments, missatges, comentaris, sistema

### 2. 👥 Gestió d'Amistats Completa
- Acceptar/rebutjar sol·licituds d'amistat
- Veure sol·licituds pendents
- Llista d'amics amb perfils
- Suggeriments intel·ligents d'amics (basats en amics en comú)
- Sistema bidireccional d'amistats

### 3. 💬 Sistema de Comentaris en Esdeveniments
- Comentar en esdeveniments (només participants i creador)
- Veure comentaris en temps real
- Eliminar els propis comentaris
- Notificacions quan reben comentaris nous
- Badge d'organizador en comentaris

---

## 📋 Instal·lació Pas a Pas

### PAS 1: Actualitzar la Base de Dades

Executa l'arxiu `database_updates.sql` a la teva base de dades MySQL:

```bash
# Opció 1: Des de phpMyAdmin
# 1. Obre phpMyAdmin
# 2. Selecciona la teva base de dades
# 3. Ves a la pestanya "SQL"
# 4. Copia i enganxa tot el contingut de database_updates.sql
# 5. Fes clic a "Continuar"

# Opció 2: Des de línia de comandes
mysql -u root -p nom_base_dades < database_updates.sql
```

Això crearà les taules:
- `notificaciones`
- `evento_comentarios`
- `evento_valoraciones`
- `configuracion_privacidad`

I afegirà camps nous a:
- `usuarios` (biografia, intereses, verificado)
- `eventos` (foto, precio, puntuacion_media)
- `amistades` (fecha_respuesta)

### PAS 2: Copiar els Fitxers Nous

Copia aquests fitxers a la carpeta del teu projecte:

**Fitxers PHP principals:**
- `amigos.php` - Pàgina de gestió d'amistats
- `notificaciones_helper.php` - Funcions helper per notificacions

**APIs:**
- `api_notificaciones.php` - API per gestionar notificacions
- `api_amistades.php` - API per gestionar amistats
- `api_comentarios.php` - API per gestionar comentaris

**Widgets/Components:**
- `widget_notificaciones.php` - Component de notificacions (per al header)
- `widget_comentarios.php` - Component de comentaris (per a esdeveniments)

**Fitxers actualitzats:**
- `eventos.php` - Versió corregida (substitueix l'anterior)
- `ver_evento.php` - Versió amb comentaris (substitueix l'anterior)

### PAS 3: Actualitzar els Headers

A **TOTS** els fitxers principals (dashboard.php, eventos.php, feed.php, mensajes.php, etc.), afegeix el widget de notificacions dins del `<header>`:

```php
<header>
    <div class="header-content">
        <div class="header-title">
            <h1><i class="fas fa-calendar-alt"></i> El teu títol aquí</h1>
        </div>
        <nav class="header-nav">
            <!-- Els teus enllaços existents -->
            <a href="dashboard.php"><i class="fas fa-home"></i> Inicio</a>
            <a href="amigos.php"><i class="fas fa-user-friends"></i> Amigos</a>
            <a href="eventos.php"><i class="fas fa-calendar"></i> Eventos</a>
            
            <!-- AFEGEIX AIXÒ: Widget de notificacions -->
            <?php include 'widget_notificaciones.php'; ?>
        </nav>
    </div>
</header>
```

### PAS 4: Actualitzar apuntar_evento.php

Quan un usuari s'apunta a un esdeveniment, afegeix notificacions. Al final de `apuntar_evento.php`:

```php
require_once "notificaciones_helper.php";

// Després d'apuntar-se correctament:
// Notificar al creador
notificarCreadorEvento(
    $pdo,
    $evento_id,
    'Nuevo participante',
    "{$mi_nombre} se ha apuntado a tu evento '{$evento_titulo}'"
);

$_SESSION['success'] = "Te has apuntado correctamente al evento";
header("Location: ver_evento.php?id=" . $evento_id);
```

### PAS 5: Actualitzar crear_evento.php

Quan es crea un esdeveniment, notificar als amics:

```php
require_once "notificaciones_helper.php";

// Després de crear l'esdeveniment:
// Obtenir amics
$sql = "SELECT DISTINCT CASE 
            WHEN a.usuario_id = :usuario_id THEN a.amigo_id 
            ELSE a.usuario_id 
        END as amigo_id
        FROM amistades a
        WHERE (a.usuario_id = :usuario_id2 OR a.amigo_id = :usuario_id3)
        AND a.estado = 'aceptada'";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'usuario_id' => $usuario_id,
    'usuario_id2' => $usuario_id,
    'usuario_id3' => $usuario_id
]);
$amigos = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Notificar a cada amic
foreach ($amigos as $amigo_id) {
    crearNotificacion(
        $pdo,
        $amigo_id,
        'evento',
        'Nuevo evento',
        "{$mi_nombre} ha creado un nuevo evento: {$evento_titulo}",
        "ver_evento.php?id={$evento_id}"
    );
}
```

### PAS 6: Crear una Pàgina de Notificacions (Opcional)

Pots crear `notificaciones.php` per veure totes les notificacions:

```php
<?php
session_start();
require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];

$sql = "SELECT * FROM notificaciones 
        WHERE usuario_id = :usuario_id 
        ORDER BY fecha_creacion DESC 
        LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario_id' => $usuario_id]);
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Aquí mostra totes les notificacions -->
```

---

## 🎯 Noves Rutes

Afegeix aquests enllaços al menú de navegació:

```html
<a href="amigos.php"><i class="fas fa-user-friends"></i> Amigos</a>
<a href="notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a>
```

---

## 🔧 Configuració Addicional

### Netejar notificacions antigues (Opcional)

Pots crear un cron job que netegi notificacions llegides de fa més de 30 dies:

```php
// cron_limpiar_notificaciones.php
<?php
require_once "config.php";
require_once "notificaciones_helper.php";

limpiarNotificacionesAntiguas($pdo);
echo "Notificaciones antiguas eliminadas\n";
```

I executar-lo cada dia:
```bash
0 2 * * * php /ruta/a/tu/proyecto/cron_limpiar_notificaciones.php
```

---

## 📱 Funcionalitats Futures Recomanades

Les següents funcionalitats estan preparades per implementar-se fàcilment:

1. **Valoracions d'esdeveniments** (taula ja creada: `evento_valoraciones`)
2. **Configuració de privacitat** (taula ja creada: `configuracion_privacidad`)
3. **Esdeveniments de pagament** (camp `precio` ja afegit)
4. **Fotos d'esdeveniments** (camp `foto` ja afegit)
5. **Sistema de badges/gamificació**
6. **Cerca avançada d'esdeveniments i usuaris**
7. **Mapa interactiu d'esdeveniments**
8. **Xat grupal per esdeveniments**

---

## ⚠️ Notes Importants

1. **PHP 8.1+**: Tots els fitxers utilitzen `IntlDateFormatter` en lloc de `strftime()` deprecated
2. **Seguretat**: Tots els inputs estan sanititzats amb `htmlspecialchars()` i prepared statements
3. **Performance**: Les consultes estan optimitzades amb índexs
4. **Responsive**: Tot el CSS és responsive i funciona en mòbils

---

## 🐛 Resolució de Problemes

### Error: "Table 'notificaciones' doesn't exist"
→ Executa `database_updates.sql`

### Les notificacions no apareixen
→ Verifica que `widget_notificaciones.php` està inclòs al header

### Error: "Function formatearFechaEspanol not found"
→ Assegura't que els fitxers actualitzats tenen la funció al principi

### Els comentaris no es mostren
→ Verifica que `widget_comentarios.php` està inclòs a `ver_evento.php`

---

## 📞 Suport

Si tens algun problema, revisa:
1. Els logs d'errors de PHP (`error_log`)
2. La consola del navegador (F12)
3. Que tots els fitxers s'han copiat correctament

---

**Gaudeix de la teva nova RedAmigos millorada! 🎉**

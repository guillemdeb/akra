# Alacant Barris · Recursos Urbans
**Sistema complet PHP + MySQL per gestionar recursos i mancances per barri**

---

## 📁 Estructura del projecte

```
alacant-barris/
├── index.php              ← Frontend SPA + SSR
├── database.sql           ← Esquema BD + dades inicials
├── includes/
│   └── config.php         ← Configuració BD
└── api/
    ├── barris.php         ← API REST barris i estadístiques
    └── peticions.php      ← API REST sol·licituds ciutadanes
```

---

## 🚀 Instal·lació ràpida

### 1. Requisits
- PHP 8.0+ amb extensions `pdo`, `pdo_mysql`
- MySQL 5.7+ o MariaDB 10.3+
- Servidor web: Apache + mod_rewrite, o Nginx

### 2. Base de dades
```bash
mysql -u root -p < database.sql
```
Això crearà la BD `alacant_barris` amb totes les taules i dades d'exemple.

### 3. Configura les credencials
Edita `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'alacant_barris');
define('DB_USER', 'el_teu_usuari');
define('DB_PASS', 'la_teva_contrasenya');
```

### 4. Apache (opcional .htaccess)
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /alacant-barris/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^api/(.*)$ api/$1 [L]
</IfModule>
```

### 5. Servidor local de prova
```bash
# Amb PHP built-in
cd alacant-barris
php -S localhost:8080

# Amb Docker
docker run -d -p 8080:80 \
  -v $(pwd):/var/www/html \
  -e MYSQL_HOST=host.docker.internal \
  php:8.2-apache
```

---

## 🗄️ Estructura de la BD

| Taula            | Descripció                                    |
|------------------|-----------------------------------------------|
| `districtes`     | 9 districtes de la ciutat                     |
| `barris`         | 19 barris amb coordenades i color             |
| `categories`     | 10 tipologies de recursos urbans              |
| `recursos_barri` | Estat (ok/partial/missing) per barri+categoria|
| `peticions`      | Sol·licituds ciutadanes                       |
| `auditoria`      | Log de canvis d'estat                         |

---

## 🔌 API REST

### Barris
```
GET  api/barris.php?action=list           → llista amb puntuació
GET  api/barris.php?action=get&id=X      → detall d'un barri
GET  api/barris.php?action=stats         → estadístiques globals
GET  api/barris.php?action=categories    → categories
POST api/barris.php?action=update_estat  → canviar estat recurs
```

### Peticions
```
GET  api/peticions.php?action=list       → llista (filtrable)
POST api/peticions.php?action=create     → nova sol·licitud
POST api/peticions.php?action=update     → canviar estat
POST api/peticions.php?action=vot        → votar
```

---

## 🛠️ Personalització

### Afegir barri nou
```sql
INSERT INTO barris (districte_id, nom, slug, color, lat, lng, poblacio)
VALUES (1, 'Nou Barri', 'nou_barri', '#ff6b35', 38.350000, -0.490000, 5000);

-- Afegir registres per cada categoria
INSERT INTO recursos_barri (barri_id, categoria_id, estat)
SELECT LAST_INSERT_ID(), id, 'missing' FROM categories WHERE activa=1;
```

### Canviar estat d'un recurs
```sql
UPDATE recursos_barri rb
JOIN barris b ON b.id = rb.barri_id AND b.slug = 'pla'
JOIN categories c ON c.id = rb.categoria_id AND c.slug = 'salut'
SET rb.estat = 'ok', rb.notes = 'Nou CAP inaugurat febrer 2026';
```

---

## ⚡ Funcionalitats

- 🗺️ **Mapa interactiu** Leaflet amb marcadors de color per barri, cercles de radi, badges de mancances
- 📊 **Dashboard** estadístiques en temps real des de BD
- 🏘️ **Barris** llistat expandible amb puntuació circular animada, filtre per districte
- 📋 **Recursos** cobertura per categoria amb barres de progrés
- ➕ **Sol·licitar** formulari validat que guarda a BD via AJAX
- 📝 **Peticions** gestió d'estat, paginació, votació, filtre per estat
- 📱 **Mobile-first** 100% adaptat a mòbil
- ⚡ **SSR** dades inicials pre-renderitzades per velocitat

---

## 🔒 Seguretat recomanada

Per a producció:
1. Canvia `APP_DEBUG` a `false` a `config.php`
2. Afegeix autenticació a les operacions d'edició (update, delete)
3. Implementa rate limiting a l'API de peticions
4. Usa HTTPS
5. Crea usuari MySQL amb mínims privilegis:
   ```sql
   CREATE USER 'alacant_user'@'localhost' IDENTIFIED BY 'contrasenya_segura';
   GRANT SELECT, INSERT, UPDATE ON alacant_barris.* TO 'alacant_user'@'localhost';
   ```

# 🌐 RedAmigos - App Social para Personas Adultas

## 📋 Descripció del Projecte

**RedAmigos** és una plataforma social dissenyada específicament per a persones adultes i gent gran activa, amb l'objectiu de:

- ✅ Combatre la soledat no desitjada
- ✅ Facilitar amistats basades en afinitats i interessos comuns
- ✅ Proporcionar un entorn segur, accessible i fàcil d'utilitzar

**Eslogan:** *Conectamos personas, creamos sonrisas* 😊

---

## 🎨 Característiques Principals

### ✅ Funcionalitats Implementades:

1. **Sistema de Registre i Login**
   - Autenticació amb sessions PHP
   - Validació d'usuaris

2. **Perfil d'Usuari Complet**
   - Foto de perfil
   - Informació personal (nom, edat, gènere, ubicació)
   - Descripció personal
   - Gestió d'interessos amb icones FontAwesome
   - Control de privacitat per telèfon i email

3. **Feed Social Intel·ligent**
   - Pestanya "Amigos": mostra només amistats acceptades
   - Pestanya "Coincidencias": troba persones amb interessos comuns
   - Algorisme de recomanació basat en interessos compartits
   - Targetes d'usuari amb informació rellevant

4. **Sistema d'Amistats**
   - Enviament de sol·licituds d'amistat
   - Gestió de sol·licituds (acceptar/rebutjar)
   - Visualització de perfils d'altres usuaris
   - Estats: pendent, acceptada, rebutjada, bloquejada

5. **Missatgeria Interna**
   - Xat privat entre amics
   - Missatges en temps real
   - Indicadors de missatges no llegits
   - Historial de converses

6. **Sistema de Notificacions**
   - Notificacions de sol·licituds d'amistat
   - Notificacions de nous missatges
   - Badges visuals per a esdeveniments pendents

---

## 🛠️ Stack Tecnològic

- **Backend:** PHP 7.4+ (procedimental + PDO)
- **Base de Dades:** MySQL 5.7+
- **Frontend:** HTML5 + CSS3 (Mobile-First)
- **Icones:** FontAwesome 6.5
- **Autenticació:** Sessions PHP

---

## 📦 Instal·lació

### Requisits Previs:

- XAMPP, WAMP, LAMP o servidor web amb:
  - PHP 7.4 o superior
  - MySQL 5.7 o superior
  - Apache amb mod_rewrite activat

### Passos d'Instal·lació:

1. **Clonar o descarregar el projecte:**
   ```bash
   git clone [url-del-repositori]
   cd redamigos
   ```

2. **Configurar la base de dades:**
   - Accedir a phpMyAdmin o MySQL
   - Crear una base de dades anomenada `app_social`
   - Importar el fitxer `database.sql`
   
   ```sql
   mysql -u root -p < database.sql
   ```

3. **Configurar la connexió:**
   - Editar `config.php` amb les teves credencials:
   
   ```php
   $host = "localhost";
   $db   = "app_social";
   $user = "root";
   $pass = "";  // La teva contrasenya MySQL
   ```

4. **Crear carpeta d'uploads:**
   ```bash
   mkdir uploads
   chmod 755 uploads
   ```

5. **Accedir a l'aplicació:**
   - Obrir el navegador i anar a: `http://localhost/redamigos`

---

## 👥 Usuaris de Prova

El fitxer `database.sql` inclou 5 usuaris de prova:

| Email | Contrasenya | Nom |
|-------|-------------|-----|
| maria@ejemplo.com | password123 | María García |
| juan@ejemplo.com | password123 | Juan Pérez |
| carmen@ejemplo.com | password123 | Carmen López |
| antonio@ejemplo.com | password123 | Antonio Martínez |
| rosa@ejemplo.com | password123 | Rosa Sánchez |

---

## 📂 Estructura del Projecte

```
redamigos/
│
├── config.php              # Configuració BD
├── database.sql            # Script SQL complet
├── index.php               # Pàgina d'inici (login)
├── login.php               # Procés d'autenticació
├── register.php            # Registre d'usuaris
├── logout.php              # Tancament de sessió
├── dashboard.php           # Perfil de l'usuari
├── editar_perfil.php       # Edició d'interessos
├── update_intereses.php    # Actualització d'interessos
│
├── feed.php                # Feed principal (Amigos/Coincidencias)
├── solicitudes.php         # Gestió de sol·licituds
├── enviar_solicitud.php    # Enviar sol·licitud d'amistat
├── perfil_usuario.php      # Veure perfil d'altres usuaris
├── mensajes.php            # Sistema de missatgeria
│
├── includes/
│   ├── head.php            # <head> comú amb CSS
│   ├── header.php          # Capçalera de l'app
│   └── footer.php          # Peu de pàgina
│
├── assets/
│   ├── css/
│   └── img/
│
└── uploads/                # Imatges de perfil
    └── default.png
```

---

## 🎨 Colors Corporatius

```css
--color-principal: #4A90E2  /* Blau principal */
--color-accion: #7ED321     /* Verd acció positiva */
--color-fondo: #F5F5F5      /* Gris clar fons */
--color-blanco: #FFFFFF     /* Blanc targetes */
--color-texto: #333333      /* Text fosc */
```

---

## 📱 Disseny Responsive

L'aplicació està dissenyada amb un enfocament **Mobile-First**:

- ✅ Disseny adaptatiu de 320px a 1920px
- ✅ Tipografia gran i llegible
- ✅ Botons grans i fàcils de prémer
- ✅ Contrast alt per a millor accessibilitat
- ✅ Navegació senzilla i intuïtiva

---

## 🔒 Seguretat

- ✅ Contrasenyes hashejades amb `password_hash()` (bcrypt)
- ✅ Protecció contra SQL Injection amb PDO prepared statements
- ✅ Validació de sessions en totes les pàgines
- ✅ Escapament HTML amb `htmlspecialchars()`
- ✅ Control d'accés basat en sessions

---

## 🚀 Millores Futures Suggerides

Vegeu el fitxer `MEJORAS_SUGERIDAS.md` per a una llista detallada de millores possibles.

### Prioritat Alta:
- [ ] Sistema complet d'edició de perfil
- [ ] Pujada d'imatges de perfil
- [ ] Recuperació de contrasenya
- [ ] Validació avançada de formularis

### Prioritat Mitjana:
- [ ] Notificacions push/email
- [ ] Cerca avançada d'usuaris
- [ ] Filtres per ubicació i edat
- [ ] Esdeveniments i quedades

### Prioritat Baixa:
- [ ] Migració a framework modern (Laravel)
- [ ] API REST per a app mòbil nativa
- [ ] Sistema de verificació d'usuaris
- [ ] Analytics i estadístiques

---

## 🤝 Contribucions

Aquest projecte està obert a millores. Si vols contribuir:

1. Fork del projecte
2. Crea una branca (`git checkout -b feature/millora`)
3. Commit dels canvis (`git commit -m 'Afegir millora'`)
4. Push a la branca (`git push origin feature/millora`)
5. Obre un Pull Request

---

## 📄 Llicència

Aquest projecte és de codi obert i està disponible sota la llicència MIT.

---

## 👨‍💻 Autor

Desenvolupat amb ❤️ per al projecte RedAmigos

---

## 📞 Contacte i Suport

Per a preguntes, suggeriments o problemes:
- Obre un issue al repositori
- Email: [contacte@redamigos.com]

---

**RedAmigos** - *Conectamos personas, creamos sonrisas* 😊🌐

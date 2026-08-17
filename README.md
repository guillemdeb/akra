# AKRA Tech Studio v2 — README & Guia de Configuració

## Estructura de fitxers

```
akra_v2/
├── index.php                  ← Pàgina d'inici (hero, serveis, local, procés, portfolio, CTA)
├── includes/
│   ├── config.php             ← ⭐ BASE DE DADES PRINCIPAL — Edita ací tot
│   ├── header.php             ← Capçalera, nav, schema markup
│   └── footer.php             ← Peu, cookie banner
├── pages/
│   ├── serveis.php            ← Pàgina de serveis amb contingut SEO ric
│   ├── projectes.php          ← Portfolio (placeholder fins que hi hagen projectes)
│   ├── contacte.php           ← Formulari de contacte complet
│   ├── nosaltres.php          ← [Pendent crear]
│   ├── bloc.php               ← [Pendent crear — molt important per SEO]
│   ├── proces.php             ← [Pendent crear]
│   ├── privacitat.php         ← [Pendent crear]
│   ├── cookies.php            ← [Pendent crear]
│   └── avis-legal.php         ← [Pendent crear]
├── assets/
│   ├── css/styles.css         ← Disseny premium (Syne + DM Sans)
│   ├── js/main.js             ← Interaccions, animacions, cookie banner
│   └── img/                   ← [Afegir imatges: og-image.jpg, favicon.svg, hero-bg.jpg]
└── lang/
    ├── ca.php                 ← Català (principal)
    ├── es.php                 ← Castellà
    ├── en.php                 ← Anglès
    ├── fr.php                 ← [Pendent completar]
    └── it.php                 ← [Pendent completar]
```

---

## ⭐ Com afegir/editar contingut des de la BD (config.php)

### Afegir un projecte al portfolio
Obre `includes/config.php` i afegeix a `$projects_db`:
```php
[
    'id'          => 1,
    'slug'        => 'nom-del-projecte',
    'category'    => 'web',       // web | ecommerce | marketing | design
    'status'      => 'active',    // active | demo | concept | closed
    'featured'    => true,        // true = apareix a l'inici
    'title'       => ['ca' => 'Nom del projecte', 'es' => 'Nombre del proyecto'],
    'description' => ['ca' => 'Descripció...', 'es' => 'Descripción...'],
    'results'     => ['ca' => '+200% de reserves', 'es' => '+200% de reservas'],
    'thumbnail'   => 'assets/img/projects/nom-projecte.webp',
    'url'         => 'https://clienteweb.es',
    'video'       => null,  // o 'https://www.youtube.com/embed/ID'
    'tech'        => ['WordPress', 'SEO', 'WooCommerce'],
    'date'        => '2025-03',
    'client'      => ['ca' => 'Sector Hostaleria', 'es' => 'Sector Hostelería'],
],
```

### Afegir un testimoni
```php
[
    'id'      => 4,
    'name'    => ['ca' => 'Nom Cognom'],
    'company' => ['ca' => 'Empresa · Alacant'],
    'text'    => ['ca' => 'Text del testimoni...', 'es' => 'Texto del testimonio...'],
    'rating'  => 5,
    'active'  => true,
],
```

### Editar informació de contacte
A la part superior de `config.php`:
```php
define('CONTACT_PHONE', '+34 600 000 000');  // ← Canvia pel teu telèfon real
define('CONTACT_EMAIL', 'hola@akratechstudio.es');
define('CONTACT_ADDRESS', 'Alacant, Comunitat Valenciana, Espanya');
define('SITE_URL', 'https://akratechstudio.es');  // ← Canvia pel teu domini real
```

---

## 🔍 Checklist SEO Local Alacant

### Coses a fer ABANS del llançament:
- [ ] Actualitzar `SITE_URL` amb el domini real
- [ ] Actualitzar `CONTACT_PHONE` i `CONTACT_EMAIL`
- [ ] Crear i pujar `/assets/img/og-image.jpg` (1200×630px, branding Akra)
- [ ] Crear i pujar `/assets/img/favicon.svg`
- [ ] Afegir foto real de l'equip a `/pages/nosaltres.php`
- [ ] Activar Google Analytics i afegir ID a `header.php`
- [ ] Crear compte a Google Search Console i verificar domini
- [ ] Crear / optimitzar fitxa Google My Business (GMB)

### Coses a fer DESPRÉS del llançament:
- [ ] Enviar sitemap.xml a Google Search Console
- [ ] Crear pàgina del blog (`pages/bloc.php`) amb contingut local:
  - "Agencia web Alicante: cómo elegir la mejor"
  - "SEO local Alicante para restaurantes"
  - "Diseño web Costa Blanca: guía completa 2025"
- [ ] Aconseguir backlinks locals (directoris, cambra de comerç, col·legis professionals)
- [ ] Afegir ressenyes a Google My Business
- [ ] Crear pàgines de servei específiques per zona (Benidorm, Elx, Torrevella)

### Keywords principals per posicionar (ordre de prioritat):
1. `agencia web Alicante` — Alta competència, alt valor
2. `diseño web Alicante` — Alta competència
3. `SEO local Alicante` — Mitja competència, molt qualificada
4. `agencia marketing digital Alicante` — Mitja-alta
5. `diseño web Costa Blanca` — Baixa competència, bona intent
6. `agencia web Benidorm`, `diseño web Elche` — Baixa competència, oportunitat
7. `desarrollo web Alicante precio` — Intent comercial alt

---

## 🎨 Personalització de disseny

### Canviar colors
A `assets/css/styles.css`, modifica les variables CSS:
```css
:root {
    --c-gold:  #c9a84c;  /* Color d'accent principal — canvia si tens brand diferent */
    --c-navy:  #0b1628;  /* Fons fosc */
}
```

### Canviar fonts
Substitueix `Syne` (display) i `DM Sans` (body) al `header.php` i `styles.css`:
```html
<!-- Al header.php, canvia el link de Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=NOVA_FONT:wght@...&display=swap">
```

---

## 📧 Formulari de contacte — Activar enviament real

Al fitxer `pages/contacte.php`, cerca el comentari `// TODO: Implementar envio real` 
i implementa amb PHPMailer o un servei SMTP:

```php
// Exemple amb mail() bàsic (canviar per PHPMailer per producció)
$to = CONTACT_EMAIL;
$subject = "Nou contacte Akra: $name";
$body = "Nom: $name\nEmail: $email\nTel: $phone\nServei: $service\n\n$message";
$headers = "From: noreply@akratechstudio.es\r\nReply-To: $email";
mail($to, $subject, $body, $headers);
```

---

## 📊 Analítica recomanada

Afegeix a `includes/header.php`, just abans del `</head>`:
```html
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');  // ← Substitueix pel teu ID real
</script>
```

---

*AKRA Tech Studio v2 — Generat amb anàlisi de mercat local Alacant (Febrer 2026)*

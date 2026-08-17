<?php
// admin/content.php — Edició de contingut del web
require_once 'includes/core.php';
requireLogin();

// Seccions editables
$sections = [
    'hero' => ['icon' => '🦸', 'name' => 'Hero (Inici)', 'desc' => 'Títol principal i subtítol de la pàgina d\'inici'],
    'stats' => ['icon' => '📊', 'name' => 'Estadístiques', 'desc' => 'Números destacats (projectes, anys, dies...)'],
    'process' => ['icon' => '⚙️', 'name' => 'Procés (45 dies)', 'desc' => 'Les 3 fases de 15 dies cadascuna'],
    'timeline' => ['icon' => '⏱️', 'name' => 'Timeline detallat', 'desc' => 'Explicació dels 45 dies'],
    'why_us' => ['icon' => '⭐', 'name' => 'Per què nosaltres', 'desc' => 'Diferenciadors i valor afegit'],
    'cta' => ['icon' => '📢', 'name' => 'CTA Final', 'desc' => 'Crida a l\'acció del footer'],
];

// Llegir contingut actual
$content = readData('content');
if (empty($content)) {
    $content = [
        'hero' => [
            'title_1' => ['ca' => 'Webs que', 'es' => 'Webs que', 'en' => 'Webs that'],
            'title_2' => ['ca' => 'venen.', 'es' => 'venden.', 'en' => 'sell.'],
            'title_3' => ['ca' => 'Marques que', 'es' => 'Marcas que', 'en' => 'Brands that'],
            'title_4' => ['ca' => 'es recorden.', 'es' => 'se recuerdan.', 'en' => 'are remembered.'],
            'subtitle' => [
                'ca' => 'Disseny web professional a Alacant en 45 dies: 15 dies de disseny UX/UI, 15 de desenvolupament robust, 15 de testatge i SEO. No fem webs ràpides. Fem webs que funcionen durant anys.',
                'es' => 'Diseño web profesional en Alicante en 45 días: 15 días de diseño UX/UI, 15 de desarrollo robusto, 15 de testing y SEO. No hacemos webs rápidas. Hacemos webs que funcionan durante años.',
                'en' => 'Professional web design in Alicante in 45 days: 15 days UX/UI design, 15 robust development, 15 testing and SEO.',
            ],
        ],
        'stats' => [
            'projects' => '50',
            'days' => '45',
            'rating' => '5★',
            'years' => '5',
            'label_projects' => ['ca' => 'Projectes lliurats', 'es' => 'Proyectos entregados', 'en' => 'Projects delivered'],
            'label_days' => ['ca' => 'Dies de procés', 'es' => 'Días de proceso', 'en' => 'Process days'],
            'label_rating' => ['ca' => 'Valoració Google', 'es' => 'Valoración Google', 'en' => 'Google rating'],
            'label_years' => ['ca' => 'Anys d\'experiència', 'es' => 'Años de experiencia', 'en' => 'Years experience'],
        ],
        'process' => [
            'subtitle' => [
                'ca' => 'Un procés clar en 45 dies. 15 de disseny, 15 de desenvolupament, 15 de perfeccionament. Sense sorpreses.',
                'es' => 'Un proceso claro en 45 días. 15 de diseño, 15 de desarrollo, 15 de perfeccionamiento. Sin sorpresas.',
                'en' => 'A clear process in 45 days. 15 design, 15 development, 15 refinement. No surprises.',
            ],
        ],
        'timeline' => [
            'title' => ['ca' => 'Per què 45 dies?', 'es' => '¿Por qué 45 días?', 'en' => 'Why 45 days?'],
            'phase_1_title' => ['ca' => 'Disseny UX/UI', 'es' => 'Diseño UX/UI', 'en' => 'UX/UI Design'],
            'phase_1_desc' => [
                'ca' => 'Wireframes, prototips i validació visual amb el teu equip',
                'es' => 'Wireframes, prototipos y validación visual con tu equipo',
                'en' => 'Wireframes, prototypes and visual validation with your team',
            ],
            'phase_2_title' => ['ca' => 'Desenvolupament', 'es' => 'Desarrollo', 'en' => 'Development'],
            'phase_2_desc' => [
                'ca' => 'Codi net, responsive, optimitzat per a velocitat i SEO',
                'es' => 'Código limpio, responsive, optimizado para velocidad y SEO',
                'en' => 'Clean code, responsive, optimized for speed and SEO',
            ],
            'phase_3_title' => ['ca' => 'Testatge i llançament', 'es' => 'Testing y lanzamiento', 'en' => 'Testing & Launch'],
            'phase_3_desc' => [
                'ca' => 'Revisions, SEO tècnic, velocitat, formació i suport',
                'es' => 'Revisiones, SEO técnico, velocidad, formación y soporte',
                'en' => 'Reviews, technical SEO, speed, training and support',
            ],
            'value_prop' => [
                'ca' => 'No fem webs en 48 hores. Fem webs que funcionen durant anys.',
                'es' => 'No hacemos webs en 48 horas. Hacemos webs que funcionan durante años.',
                'en' => 'We don\'t make websites in 48 hours. We make websites that work for years.',
            ],
        ],
        'why_us' => [
            'diff_3_title' => ['ca' => '45 dies ben invertits', 'es' => '45 días bien invertidos', 'en' => '45 well-invested days'],
            'diff_3_desc' => [
                'ca' => 'El mercat està ple de webs barates en 48 hores que ningú troba a Google. Nosaltres invertim 45 dies: 15 en disseny UX, 15 en programació robusta, 15 en testatge i SEO. Resultat: una web que et fa vendre durant anys.',
                'es' => 'El mercado está lleno de webs baratas en 48 horas que nadie encuentra en Google. Nosotros invertimos 45 días: 15 en diseño UX, 15 en programación robusta, 15 en testing y SEO. Resultado: una web que te hace vender durante años.',
                'en' => 'The market is full of cheap 48-hour websites that nobody finds on Google. We invest 45 days: 15 in UX design, 15 in robust programming, 15 in testing and SEO.',
            ],
        ],
        'cta' => [
            'subtitle' => [
                'ca' => 'Primera consulta gratuïta. En 45 dies tindràs una web professional, optimitzada i preparada per a vendre. 15 dies de disseny, 15 de desenvolupament, 15 de perfeccionament. Sense compromís.',
                'es' => 'Primera consulta gratuita. En 45 días tendrás una web profesional, optimizada y preparada para vender. 15 días de diseño, 15 de desarrollo, 15 de perfeccionamiento. Sin compromiso.',
                'en' => 'First consultation free. In 45 days you will have a professional, optimized website ready to sell.',
            ],
        ],
    ];
}

// Guardar canvis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';
    if (isset($sections[$section])) {
        foreach ($_POST as $key => $value) {
            if ($key !== 'section' && is_array($value)) {
                // Netejar idiomes
                $content[$section][$key] = [
                    'ca' => sanitize($value['ca'] ?? ''),
                    'es' => sanitize($value['es'] ?? ''),
                    'en' => sanitize($value['en'] ?? ''),
                ];
            } elseif ($key !== 'section') {
                $content[$section][$key] = sanitize($value);
            }
        }
        writeData('content', $content);
        syncContentToConfig($content);
        $success = 'Contingut actualitzat: ' . $sections[$section]['name'];
    }
}

$current_section = $_GET['section'] ?? 'hero';
if (!isset($sections[$current_section])) $current_section = 'hero';

$page_title = 'Contingut del web';
$page_subtitle = 'Edita textos, títols i missatges';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contingut · AKRA Admin</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .content-nav { display: flex; flex-direction: column; gap: 8px; }
        .content-nav-item { 
            display: flex; align-items: center; gap: 12px; 
            padding: 14px 16px; border-radius: 10px; 
            background: white; border: 1.5px solid var(--a-border);
            transition: all 0.15s; cursor: pointer;
        }
        .content-nav-item:hover { border-color: var(--a-navy); }
        .content-nav-item.active { 
            background: var(--a-navy); border-color: var(--a-navy); color: white;
        }
        .content-nav-item.active .content-nav-desc { color: rgba(255,255,255,0.6); }
        .content-nav-icon { font-size: 1.5rem; }
        .content-nav-text { flex: 1; }
        .content-nav-name { font-weight: 700; font-size: 0.95rem; }
        .content-nav-desc { font-size: 0.8rem; color: var(--a-muted); margin-top: 2px; }
        
        .highlight-box { 
            background: linear-gradient(135deg, rgba(37,99,235,0.05) 0%, rgba(37,99,235,0.02) 100%);
            border: 1px solid rgba(37,99,235,0.15);
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .highlight-box strong { color: var(--a-navy); }
    </style>
</head>
<body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($success)): ?>
<div class="alert alert-success">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <?= $success ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 300px 1fr; gap: 20px; align-items: start;">
    <!-- Navegació de seccions -->
    <div class="content-nav">
        <?php foreach ($sections as $key => $info): ?>
        <a href="?section=<?= $key ?>" class="content-nav-item <?= $current_section === $key ? 'active' : '' ?>">
            <span class="content-nav-icon"><?= $info['icon'] ?></span>
            <div class="content-nav-text">
                <div class="content-nav-name"><?= $info['name'] ?></div>
                <div class="content-nav-desc"><?= $info['desc'] ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Formulari -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= $sections[$current_section]['icon'] ?> <?= $sections[$current_section]['name'] ?></div>
        </div>
        <div class="card-body">
            <form method="POST" class="form-grid">
                <input type="hidden" name="section" value="<?= $current_section ?>">
                
                <?php if ($current_section === 'hero'): ?>
                <div class="highlight-box">
                    <strong>💡 Consell:</strong> El títol es divideix en 4 parts per crear l'efecte visual. Les parts 2 i 4 apareixen en <strong>blau accentuat</strong>.
                </div>
                
                <div class="form-row-2">
                    <div class="form-group">
                        <label>Títol part 1 (CA)</label>
                        <input type="text" name="title_1[ca]" value="<?= htmlspecialchars($content['hero']['title_1']['ca'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Títol part 2 - Accentuat (CA)</label>
                        <input type="text" name="title_2[ca]" value="<?= htmlspecialchars($content['hero']['title_2']['ca'] ?? '') ?>" style="border-color: var(--a-gold);">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label>Títol part 3 (CA)</label>
                        <input type="text" name="title_3[ca]" value="<?= htmlspecialchars($content['hero']['title_3']['ca'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Títol part 4 - Accentuat (CA)</label>
                        <input type="text" name="title_4[ca]" value="<?= htmlspecialchars($content['hero']['title_4']['ca'] ?? '') ?>" style="border-color: var(--a-gold);">
                    </div>
                </div>
                
                <hr style="border: none; border-top: 1px solid var(--a-border); margin: 20px 0;">
                
                <div class="form-row-2">
                    <div class="form-group">
                        <label>Títol part 1 (ES)</label>
                        <input type="text" name="title_1[es]" value="<?= htmlspecialchars($content['hero']['title_1']['es'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Títol part 2 - Accentuat (ES)</label>
                        <input type="text" name="title_2[es]" value="<?= htmlspecialchars($content['hero']['title_2']['es'] ?? '') ?>" style="border-color: var(--a-gold);">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label>Títol part 3 (ES)</label>
                        <input type="text" name="title_3[es]" value="<?= htmlspecialchars($content['hero']['title_3']['es'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Títol part 4 - Accentuat (ES)</label>
                        <input type="text" name="title_4[es]" value="<?= htmlspecialchars($content['hero']['title_4']['es'] ?? '') ?>" style="border-color: var(--a-gold);">
                    </div>
                </div>
                
                <hr style="border: none; border-top: 1px solid var(--a-border); margin: 20px 0;">
                
                <div class="form-group">
                    <label>Subtítol (CA) — <strong>Aquí es venen els 45 dies!</strong></label>
                    <textarea name="subtitle[ca]" rows="4"><?= htmlspecialchars($content['hero']['subtitle']['ca'] ?? '') ?></textarea>
                    <p class="hint">Aquest és el text més important. Ha d'incloure "45 dies" i el breakdown de 15+15+15.</p>
                </div>
                <div class="form-group">
                    <label>Subtítol (ES)</label>
                    <textarea name="subtitle[es]" rows="4"><?= htmlspecialchars($content['hero']['subtitle']['es'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Subtítol (EN)</label>
                    <textarea name="subtitle[en]" rows="4"><?= htmlspecialchars($content['hero']['subtitle']['en'] ?? '') ?></textarea>
                </div>
                
                <?php elseif ($current_section === 'stats'): ?>
                <div class="highlight-box">
                    <strong>📊 Estadístiques del Hero</strong><br>
                    Aquests números apareixen a la pàgina d'inici, sota el títol principal.
                </div>
                
                <div class="form-row-2">
                    <div class="form-group">
                        <label>Número de projectes</label>
                        <input type="text" name="projects" value="<?= htmlspecialchars($content['stats']['projects'] ?? '50') ?>">
                    </div>
                    <div class="form-group">
                        <label>Dies de procés (45!)</label>
                        <input type="text" name="days" value="<?= htmlspecialchars($content['stats']['days'] ?? '45') ?>" style="border-color: var(--a-gold); font-weight: 700;">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label>Valoració</label>
                        <input type="text" name="rating" value="<?= htmlspecialchars($content['stats']['rating'] ?? '5★') ?>">
                    </div>
                    <div class="form-group">
                        <label>Anys d'experiència</label>
                        <input type="text" name="years" value="<?= htmlspecialchars($content['stats']['years'] ?? '5') ?>">
                    </div>
                </div>
                
                <hr style="border: none; border-top: 1px solid var(--a-border); margin: 20px 0;">
                
                <div class="form-row-2">
                    <div class="form-group">
                        <label>Etiqueta "Projectes" (CA)</label>
                        <input type="text" name="label_projects[ca]" value="<?= htmlspecialchars($content['stats']['label_projects']['ca'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Etiqueta "Dies" (CA)</label>
                        <input type="text" name="label_days[ca]" value="<?= htmlspecialchars($content['stats']['label_days']['ca'] ?? '') ?>">
                    </div>
                </div>
                
                <?php elseif ($current_section === 'timeline'): ?>
                <div class="highlight-box" style="background: linear-gradient(135deg, rgba(201,168,76,0.1) 0%, rgba(201,168,76,0.02) 100%); border-color: rgba(201,168,76,0.3);">
                    <strong>⏱️ Aquesta és la secció clau per vendre els 45 dies!</strong><br>
                    Es mostra després del procés de 4 passos i explica el breakdown 15+15+15.
                </div>
                
                <div class="form-group">
                    <label>Títol de la secció (CA)</label>
                    <input type="text" name="title[ca]" value="<?= htmlspecialchars($content['timeline']['title']['ca'] ?? '') ?>">
                </div>
                
                <hr style="border: none; border-top: 2px solid var(--a-gold); margin: 24px 0;">
                
                <div style="background: var(--a-bg); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="font-weight: 700; color: var(--a-navy); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <span style="background: var(--a-gold); color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">1</span>
                        FASE 1: 15 dies de Disseny
                    </div>
                    <div class="form-group">
                        <label>Nom de la fase (CA)</label>
                        <input type="text" name="phase_1_title[ca]" value="<?= htmlspecialchars($content['timeline']['phase_1_title']['ca'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Descripció (CA)</label>
                        <textarea name="phase_1_desc[ca]" rows="2"><?= htmlspecialchars($content['timeline']['phase_1_desc']['ca'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div style="background: var(--a-bg); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="font-weight: 700; color: var(--a-navy); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <span style="background: var(--a-gold); color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">2</span>
                        FASE 2: 15 dies de Desenvolupament
                    </div>
                    <div class="form-group">
                        <label>Nom de la fase (CA)</label>
                        <input type="text" name="phase_2_title[ca]" value="<?= htmlspecialchars($content['timeline']['phase_2_title']['ca'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Descripció (CA)</label>
                        <textarea name="phase_2_desc[ca]" rows="2"><?= htmlspecialchars($content['timeline']['phase_2_desc']['ca'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div style="background: var(--a-bg); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="font-weight: 700; color: var(--a-navy); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <span style="background: var(--a-gold); color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">3</span>
                        FASE 3: 15 dies de Testatge
                    </div>
                    <div class="form-group">
                        <label>Nom de la fase (CA)</label>
                        <input type="text" name="phase_3_title[ca]" value="<?= htmlspecialchars($content['timeline']['phase_3_title']['ca'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Descripció (CA)</label>
                        <textarea name="phase_3_desc[ca]" rows="2"><?= htmlspecialchars($content['timeline']['phase_3_desc']['ca'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <hr style="border: none; border-top: 2px solid var(--a-gold); margin: 24px 0;">
                
                <div class="form-group">
                    <label>Frase de valor final (CA) — <strong>Aquesta ven!</strong></label>
                    <textarea name="value_prop[ca]" rows="3" style="border-color: var(--a-gold); font-weight: 500;"><?= htmlspecialchars($content['timeline']['value_prop']['ca'] ?? '') ?></textarea>
                    <p class="hint">Exemple: "No fem webs en 48 hores. Fem webs que funcionen durant anys."</p>
                </div>
                
                <?php elseif ($current_section === 'why_us'): ?>
                <div class="highlight-box">
                    <strong>⭐ Diferenciador #3: Els 45 dies</strong><br>
                    Aquest és el text que apareix a la secció fosca "Per què nosaltres".
                </div>
                
                <div class="form-group">
                    <label>Títol del diferenciador (CA)</label>
                    <input type="text" name="diff_3_title[ca]" value="<?= htmlspecialchars($content['why_us']['diff_3_title']['ca'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Descripció detallada (CA) — <strong>Venda els 45 dies aquí!</strong></label>
                    <textarea name="diff_3_desc[ca]" rows="6"><?= htmlspecialchars($content['why_us']['diff_3_desc']['ca'] ?? '') ?></textarea>
                    <p class="hint">Explica per què 45 dies és millor que 48 hores. Menciona el breakdown 15+15+15.</p>
                </div>
                
                <?php elseif ($current_section === 'cta'): ?>
                <div class="highlight-box">
                    <strong>📢 CTA Final</strong><br>
                    Text que apareix a la secció fosca abans del footer.
                </div>
                
                <div class="form-group">
                    <label>Subtítol CTA (CA) — <strong>Inclou els 45 dies!</strong></label>
                    <textarea name="subtitle[ca]" rows="4"><?= htmlspecialchars($content['cta']['subtitle']['ca'] ?? '') ?></textarea>
                </div>
                
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Guardar canvis
                </button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>
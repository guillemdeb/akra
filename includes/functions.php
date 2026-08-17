<?php
// includes/functions.php — Helpers per al frontend

require_once __DIR__ . '/config.php';

/**
 * Obtenir SEO per a una pàgina específica
 */
function getPageSeo($page_key, $override = []) {
    $default = [
        'title' => SITE_NAME,
        'description' => SITE_SLOGAN,
        'keywords' => '',
        'canonical' => SITE_URL . '/' . ($page_key !== 'index' ? $page_key : ''),
        'og_image' => SITE_URL . '/assets/img/og-image.jpg',
    ];
    
    // Llegir de la configuració SEO si existeix
    $seo_file = __DIR__ . '/seo-config.php';
    if (file_exists($seo_file)) {
        $seo_config = require $seo_file;
        if (isset($seo_config[$page_key])) {
            $page_seo = $seo_config[$page_key];
            $lang = getCurrentLang();
            
            $default['title'] = $page_seo['title'][$lang] ?? $page_seo['title']['ca'] ?? $default['title'];
            $default['description'] = $page_seo['description'][$lang] ?? $page_seo['description']['ca'] ?? $default['description'];
            $default['keywords'] = $page_seo['keywords'][$lang] ?? $page_seo['keywords']['ca'] ?? $default['keywords'];
            
            if (!empty($page_seo['canonical'])) {
                $default['canonical'] = $page_seo['canonical'];
            }
            if (!empty($page_seo['og_image'])) {
                $default['og_image'] = SITE_URL . '/' . ltrim($page_seo['og_image'], '/');
            }
        }
    }
    
    return array_merge($default, $override);
}

/**
 * Obtenir contingut d'una secció
 */
function getContent($section, $field = null, $lang = null) {
    if ($lang === null) $lang = getCurrentLang();
    
    $content_file = __DIR__ . '/content-config.php';
    if (!file_exists($content_file)) return null;
    
    $content = require $content_file;
    
    if (!isset($content[$section])) return null;
    
    $section_data = $content[$section];
    
    if ($field === null) return $section_data;
    
    if (!isset($section_data[$field])) return null;
    
    $value = $section_data[$field];
    
    if (is_array($value)) {
        return $value[$lang] ?? $value['ca'] ?? $value['es'] ?? '';
    }
    
    return $value;
}

/**
 * Helper per mostrar text segur
 */
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
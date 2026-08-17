<?php
/**
 * DIAGNÒSTIC BD - Esborra aquest fitxer després de provar!
 * Accedeix a: http://localhost/apps/barris/test_db.php
 */
header('Content-Type: text/html; charset=utf-8');
echo "<pre style='font-family:monospace;font-size:13px;padding:20px;background:#111;color:#0f0'>\n";
echo "=== DIAGNÒSTIC ALACANT BARRIS ===\n\n";

// 1. PHP i extensions
echo "PHP: " . PHP_VERSION . "\n";
echo "PDO: " . (extension_loaded('pdo') ? 'OK' : 'NO CARREGAT') . "\n";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'OK' : 'NO CARREGAT') . "\n\n";

// 2. Connexió
$host = 'localhost'; $dbname = 'alacant_barris'; $user = 'root'; $pass = '';
echo "Connectant a $host / $dbname com $user...\n";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "CONNEXIÓ: OK\n\n";

    // 3. Taules
    $taules = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "TAULES TROBADES: " . implode(', ', $taules) . "\n\n";

    // 4. Comptar registres
    foreach (['districtes','barris','categories','recursos_barri','peticions'] as $t) {
        if (in_array($t, $taules)) {
            $n = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            echo "  $t: $n registres\n";
        } else {
            echo "  $t: TAULA NO EXISTEIX\n";
        }
    }

    // 5. Prova la query del frontend
    echo "\nQUERY BARRIS (primeres 3 files):\n";
    $rows = $pdo->query("
        SELECT b.id, b.nom, b.lat, b.lng, d.nom AS districte
        FROM barris b JOIN districtes d ON d.id=b.districte_id
        WHERE b.actiu=1 ORDER BY b.id LIMIT 3
    ")->fetchAll();
    foreach($rows as $r) echo "  [{$r['id']}] {$r['nom']} ({$r['districte']}) lat={$r['lat']}\n";

} catch (PDOException $e) {
    echo "ERROR CONNEXIÓ: " . $e->getMessage() . "\n";
    echo "\nPossibles causes:\n";
    echo "  - La BD 'alacant_barris' no existeix → executa database.sql al phpMyAdmin\n";
    echo "  - Credencials incorrectes → edita includes/config.php\n";
    echo "  - MySQL no arrancant → comprova WAMP\n";
}
echo "</pre>";

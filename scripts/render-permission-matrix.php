<?php
/**
 * Genera la matriz de permisos de un módulo en Markdown a partir de su instantánea
 * (tests/Functional/Permissions/snapshots/<módulo>.json), lista para pegar en el capítulo del manual.
 *
 *   php scripts/render-permission-matrix.php fct
 *
 * La instantánea la verifica PermissionMatrixTest contra el código, así que la tabla que sale de aquí
 * coincide con lo que hace la aplicación. Los matices que no son sí/no (por ejemplo, «solo de sus propios
 * acuerdos») se escriben a mano bajo la tabla.
 */

$module = $argv[1] ?? null;
$file = __DIR__ . "/../tests/Functional/Permissions/snapshots/$module.json";
if ($module === null || !is_file($file)) {
    fwrite(STDERR, "Uso: php scripts/render-permission-matrix.php <módulo>\nMódulos: " . implode(', ', array_map(static fn(string $f): string => basename($f, '.json'), glob(__DIR__ . '/../tests/Functional/Permissions/snapshots/*.json') ?: [])) . "\n");
    exit(1);
}

$snapshot = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
$profiles = $snapshot['profiles'];

$cells = static fn(string $first, array $values): string => '| ' . implode(' | ', [$first, ...$values]) . ' |';

echo $cells('Puede...', array_column($profiles, 'label')), "\n";
echo '| --- | ' . implode(' | ', array_fill(0, count($profiles), ':-:')) . " |\n";

foreach ($snapshot['sections'] as $section) {
    // fila de sección: título en negrita y celdas vacías
    echo $cells('**' . $section['title'] . '**', array_fill(0, count($profiles), '')), "\n";
    foreach ($section['actions'] as $action) {
        $values = array_map(static fn(string $profile): string => $action['allowed'][$profile] ? '✅' : '—', array_keys($profiles));
        echo $cells($action['label'], $values), "\n";
    }
}

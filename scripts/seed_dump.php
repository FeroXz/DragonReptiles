<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';

final class RawValue
{
    public function __construct(public string $expression)
    {
    }
}

function raw(string $expression): RawValue
{
    return new RawValue($expression);
}

function ensure_directory(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException(sprintf('Directory "%s" could not be created.', $path));
    }
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT name FROM sqlite_master WHERE type = "table" AND name = :table LIMIT 1');
    $stmt->execute(['table' => $table]);

    return (bool) $stmt->fetchColumn();
}

function table_is_empty(PDO $pdo, string $table): bool
{
    $count = $pdo->query(sprintf('SELECT COUNT(*) FROM %s', $table))->fetchColumn();

    return ((int) $count) === 0;
}

function fetch_table_columns(PDO $pdo, string $table): array
{
    $stmt = $pdo->query(sprintf('PRAGMA table_info(%s)', $table));
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $map = [];
    foreach ($columns as $column) {
        $name = $column['name'] ?? null;
        if (!is_string($name) || $name === '') {
            continue;
        }

        $map[strtolower($name)] = $name;
    }

    return $map;
}

function resolve_column_map(array $availableColumns, array $synonyms): ?array
{
    $resolved = [];
    foreach ($synonyms as $field => $candidates) {
        $matched = null;
        foreach ($candidates as $candidate) {
            $lower = strtolower($candidate);
            if (isset($availableColumns[$lower])) {
                $matched = $availableColumns[$lower];
                break;
            }
        }

        if ($matched === null) {
            return null;
        }

        $resolved[$field] = $matched;
    }

    return $resolved;
}

function format_value(PDO $pdo, mixed $value): string
{
    if ($value instanceof RawValue) {
        return $value->expression;
    }

    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    return $pdo->quote((string) $value);
}

function build_seed_sql(PDO $pdo, string $table, array $columnMap, array $rows): string
{
    $columns = array_values($columnMap);
    $columnList = implode(', ', $columns);
    $valueRows = [];

    foreach ($rows as $row) {
        $values = [];
        foreach ($columnMap as $field => $column) {
            $values[] = format_value($pdo, $row[$field] ?? null);
        }
        $valueRows[] = '(' . implode(', ', $values) . ')';
    }

    $insert = sprintf("INSERT INTO %s (%s) VALUES\n%s;", $table, $columnList, implode(",\n", $valueRows));

    return sprintf("-- seed: %s\n%s\n-- end\n", $table, $insert);
}

function write_seed_file(string $path, string $contents): void
{
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException(sprintf('Seed-Datei %s konnte nicht geschrieben werden.', $path));
    }
}

function detect_seed_table(string $filePath): ?string
{
    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        return null;
    }

    try {
        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (stripos($trimmed, '-- seed:') === 0) {
                return trim(substr($trimmed, 8));
            }
        }
    } finally {
        fclose($handle);
    }

    return null;
}

function update_manifest(string $seedDirectory, array $priorityTables): void
{
    $files = [];
    $iterator = new DirectoryIterator($seedDirectory);
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isDot() || !$fileInfo->isFile()) {
            continue;
        }

        $extension = strtolower($fileInfo->getExtension());
        if ($extension !== 'sql') {
            continue;
        }

        $files[] = $fileInfo->getFilename();
    }

    sort($files, SORT_STRING);

    $tableToFiles = [];
    foreach ($files as $file) {
        $table = detect_seed_table($seedDirectory . '/' . $file);
        if ($table === null) {
            continue;
        }

        $tableToFiles[strtolower($table)][] = $file;
    }

    $order = [];
    foreach ($priorityTables as $table) {
        $lower = strtolower($table);
        if (!isset($tableToFiles[$lower])) {
            continue;
        }

        foreach ($tableToFiles[$lower] as $file) {
            if (!in_array($file, $order, true)) {
                $order[] = $file;
            }
        }
    }

    foreach ($files as $file) {
        if (!in_array($file, $order, true)) {
            $order[] = $file;
        }
    }

    $manifestPath = $seedDirectory . '/manifest.json';
    $manifest = [
        'required' => $files,
        'order' => $order,
    ];

    $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    if (file_put_contents($manifestPath, $json) === false) {
        throw new RuntimeException('Manifest konnte nicht geschrieben werden.');
    }
}

$pdo = get_database_connection();
$seedDirectory = __DIR__ . '/../storage/seeds';
ensure_directory($seedDirectory);

$definitions = [
    'menu_items' => [
        'file' => '01_menu_items.sql',
        'rows' => [
            ['label' => 'Start', 'path' => '/', 'visible' => 1, 'position' => 0],
            ['label' => 'Tiere', 'path' => '/tiere', 'visible' => 1, 'position' => 1],
            ['label' => 'Genetik', 'path' => '/genetik', 'visible' => 1, 'position' => 2],
            ['label' => 'Wiki', 'path' => '/wiki', 'visible' => 1, 'position' => 3],
            ['label' => 'News', 'path' => '/news', 'visible' => 1, 'position' => 4],
            ['label' => 'Kontakt', 'path' => '/kontakt', 'visible' => 1, 'position' => 5],
        ],
        'synonyms' => [
            'label' => ['label', 'title', 'name', 'text'],
            'path' => ['path', 'url', 'href', 'link'],
            'visible' => ['visible', 'is_visible', 'enabled', 'is_enabled', 'is_active'],
            'position' => ['position', 'menu_order', 'sort_order', 'ordering', 'order_index'],
        ],
    ],
    'pages' => [
        'file' => '02_pages.sql',
        'rows' => [
            ['slug' => 'start', 'title' => 'Willkommen', 'content' => '<h1>DragonReptiles</h1><p>Beispielseite.</p>', 'published' => 1],
            ['slug' => 'kontakt', 'title' => 'Kontakt', 'content' => '<p>E-Mail: info@example.org</p>', 'published' => 1],
        ],
        'synonyms' => [
            'slug' => ['slug'],
            'title' => ['title', 'name'],
            'content' => ['content', 'body', 'html'],
            'published' => ['published', 'is_published', 'published_flag', 'visible', 'is_visible'],
        ],
    ],
    'news' => [
        'file' => '03_news.sql',
        'rows' => [
            ['title' => 'Launch', 'slug' => 'launch', 'body' => '<p>CMS installiert. Dies ist ein Beispielbeitrag.</p>', 'published_at' => raw('CURRENT_TIMESTAMP')],
        ],
        'synonyms' => [
            'title' => ['title', 'name'],
            'slug' => ['slug'],
            'body' => ['body', 'content', 'html', 'text'],
            'published_at' => ['published_at', 'published_on', 'publish_at', 'created_at'],
        ],
    ],
    'wiki' => [
        'file' => '04_wiki.sql',
        'rows' => [
            ['slug' => 'bartagame-grundlagen', 'title' => 'Bartagame – Grundlagen', 'body' => '<h2>Haltung</h2><p>Beispieltext.</p>'],
        ],
        'synonyms' => [
            'slug' => ['slug'],
            'title' => ['title', 'name'],
            'body' => ['body', 'content', 'html', 'text'],
        ],
    ],
    'articles' => [
        'file' => '04_wiki.sql',
        'rows' => [
            ['slug' => 'bartagame-grundlagen', 'title' => 'Bartagame – Grundlagen', 'body' => '<h2>Haltung</h2><p>Beispieltext.</p>'],
        ],
        'synonyms' => [
            'slug' => ['slug'],
            'title' => ['title', 'name'],
            'body' => ['body', 'content', 'html', 'text'],
        ],
    ],
    'species' => [
        'file' => '05_species.sql',
        'rows' => [
            ['slug' => 'pogona-vitticeps', 'name_scientific' => 'Pogona vitticeps', 'name_common' => 'Bartagame'],
            ['slug' => 'heterodon-nasicus', 'name_scientific' => 'Heterodon nasicus', 'name_common' => 'Hakennasennatter'],
        ],
        'synonyms' => [
            'slug' => ['slug'],
            'name_scientific' => ['name_scientific', 'scientific_name', 'latin_name'],
            'name_common' => ['name_common', 'common_name', 'display_name'],
        ],
    ],
    'morphs' => [
        'file' => '06_morphs.sql',
        'rows' => [
            ['species_slug' => 'pogona-vitticeps', 'name' => 'Hypo', 'type' => 'recessive'],
            ['species_slug' => 'pogona-vitticeps', 'name' => 'Zero', 'type' => 'recessive'],
            ['species_slug' => 'heterodon-nasicus', 'name' => 'Albino', 'type' => 'recessive'],
            ['species_slug' => 'heterodon-nasicus', 'name' => 'Arctic', 'type' => 'incomplete_dominant'],
        ],
        'synonyms' => [
            'species_slug' => ['species_slug', 'species', 'species_id'],
            'name' => ['name', 'title'],
            'type' => ['type', 'inheritance', 'inheritance_mode'],
        ],
    ],
    'animals' => [
        'file' => '07_animals.sql',
        'rows' => [
            ['species_slug' => 'pogona-vitticeps', 'name' => 'Beispiel-Bartagame', 'sex' => 'F', 'hatch_date' => '2024-06-01', 'traits' => 'Hypo, possible het Zero'],
            ['species_slug' => 'heterodon-nasicus', 'name' => 'Beispiel-Hognose', 'sex' => 'M', 'hatch_date' => '2024-07-15', 'traits' => 'Albino 100% het Arctic'],
        ],
        'synonyms' => [
            'species_slug' => ['species_slug', 'species'],
            'name' => ['name', 'title'],
            'sex' => ['sex', 'gender'],
            'hatch_date' => ['hatch_date', 'birth_date', 'born_at'],
            'traits' => ['traits', 'genetics', 'notes'],
        ],
    ],
];

$created = [];
foreach ($definitions as $table => $definition) {
    $filePath = $seedDirectory . '/' . $definition['file'];

    if (is_file($filePath)) {
        continue;
    }

    if (!table_exists($pdo, $table)) {
        continue;
    }

    if (!table_is_empty($pdo, $table)) {
        continue;
    }

    $columns = fetch_table_columns($pdo, $table);
    $columnMap = resolve_column_map($columns, $definition['synonyms']);
    if ($columnMap === null) {
        fwrite(STDERR, sprintf("⚠️  Tabelle %s besitzt nicht alle benötigten Spalten – Seed wird übersprungen.\n", $table));
        continue;
    }

    $sql = build_seed_sql($pdo, $table, $columnMap, $definition['rows']);
    write_seed_file($filePath, $sql);
    $created[] = $definition['file'];
    printf("✅ Seed-Datei %s wurde erzeugt.\n", $definition['file']);
}

update_manifest($seedDirectory, ['menu_items', 'pages', 'wiki', 'news', 'species', 'morphs', 'animals']);

if ($created === []) {
    echo "Keine neuen Seed-Dateien erforderlich.\n";
}

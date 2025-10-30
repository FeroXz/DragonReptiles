<?php declare(strict_types=1);

/**
 * Gets or creates a database connection.
 *
 * @return PDO Database connection
 */
function get_database_connection(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $needsDirectory = !is_dir(dirname(DATA_PATH));
    if ($needsDirectory) {
        mkdir(dirname(DATA_PATH), 0775, true);
    }

    $pdo = new PDO('sqlite:' . DATA_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

function initialize_database(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT "admin",
        can_manage_animals INTEGER NOT NULL DEFAULT 1,
        can_manage_settings INTEGER NOT NULL DEFAULT 1,
        can_manage_adoptions INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS home_sections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        subtitle TEXT,
        eyebrow TEXT,
        body TEXT,
        cta_label TEXT,
        cta_url TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS animals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        species TEXT NOT NULL,
        species_slug TEXT,
        age TEXT,
        genetics TEXT,
        genetics_profile TEXT,
        origin TEXT,
        special_notes TEXT,
        description TEXT,
        image_path TEXT,
        owner_id INTEGER,
        is_private INTEGER NOT NULL DEFAULT 0,
        is_showcased INTEGER NOT NULL DEFAULT 0,
        is_piebald INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(owner_id) REFERENCES users(id)
    )');

    $animalColumns = $pdo->query('PRAGMA table_info(animals)')->fetchAll();
    $animalColumnNames = array_column($animalColumns, 'name');
    if (!in_array('is_piebald', $animalColumnNames, true)) {
        $pdo->exec('ALTER TABLE animals ADD COLUMN is_piebald INTEGER NOT NULL DEFAULT 0');
    }
    if (!in_array('species_slug', $animalColumnNames, true)) {
        $pdo->exec('ALTER TABLE animals ADD COLUMN species_slug TEXT');
    }
    if (!in_array('genetics_profile', $animalColumnNames, true)) {
        $pdo->exec('ALTER TABLE animals ADD COLUMN genetics_profile TEXT');
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS adoption_listings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        animal_id INTEGER,
        title TEXT NOT NULL,
        species TEXT,
        species_slug TEXT,
        genetics TEXT,
        genetics_profile TEXT,
        price TEXT,
        description TEXT,
        image_path TEXT,
        status TEXT NOT NULL DEFAULT "available",
        contact_email TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(animal_id) REFERENCES animals(id)
    )');

    $adoptionColumns = $pdo->query('PRAGMA table_info(adoption_listings)')->fetchAll();
    $adoptionColumnNames = array_column($adoptionColumns, 'name');
    if (!in_array('species_slug', $adoptionColumnNames, true)) {
        $pdo->exec('ALTER TABLE adoption_listings ADD COLUMN species_slug TEXT');
    }
    if (!in_array('genetics_profile', $adoptionColumnNames, true)) {
        $pdo->exec('ALTER TABLE adoption_listings ADD COLUMN genetics_profile TEXT');
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS adoption_inquiries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        listing_id INTEGER NOT NULL,
        interested_in TEXT,
        sender_name TEXT NOT NULL,
        sender_email TEXT NOT NULL,
        message TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(listing_id) REFERENCES adoption_listings(id)
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        content TEXT NOT NULL,
        is_published INTEGER NOT NULL DEFAULT 0,
        show_in_menu INTEGER NOT NULL DEFAULT 0,
        parent_id INTEGER,
        menu_order INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $pageColumns = $pdo->query('PRAGMA table_info(pages)')->fetchAll();
    $pageColumnNames = array_column($pageColumns, 'name');
    if (!in_array('show_in_menu', $pageColumnNames, true)) {
        $pdo->exec('ALTER TABLE pages ADD COLUMN show_in_menu INTEGER NOT NULL DEFAULT 0');
    }
    if (!in_array('parent_id', $pageColumnNames, true)) {
        $pdo->exec('ALTER TABLE pages ADD COLUMN parent_id INTEGER');
    }
    if (!in_array('menu_order', $pageColumnNames, true)) {
        $pdo->exec('ALTER TABLE pages ADD COLUMN menu_order INTEGER NOT NULL DEFAULT 0');
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS news_posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        excerpt TEXT,
        content TEXT NOT NULL,
        is_published INTEGER NOT NULL DEFAULT 0,
        published_at TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS breeding_plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        season TEXT,
        notes TEXT,
        expected_genetics TEXT,
        incubation_notes TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS breeding_plan_parents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        plan_id INTEGER NOT NULL,
        parent_type TEXT NOT NULL,
        animal_id INTEGER,
        name TEXT,
        sex TEXT,
        species TEXT,
        genetics TEXT,
        notes TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(plan_id) REFERENCES breeding_plans(id) ON DELETE CASCADE,
        FOREIGN KEY(animal_id) REFERENCES animals(id)
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS care_articles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        summary TEXT,
        content TEXT NOT NULL,
        is_published INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS care_topics (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        description TEXT,
        parent_id INTEGER,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(parent_id) REFERENCES care_topics(id) ON DELETE SET NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS care_article_topic (
        article_id INTEGER NOT NULL,
        topic_id INTEGER NOT NULL,
        PRIMARY KEY(article_id, topic_id),
        FOREIGN KEY(article_id) REFERENCES care_articles(id) ON DELETE CASCADE,
        FOREIGN KEY(topic_id) REFERENCES care_topics(id) ON DELETE CASCADE
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS genetic_species (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        scientific_name TEXT,
        description TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS genetic_genes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        species_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        slug TEXT NOT NULL,
        shorthand TEXT,
        inheritance_mode TEXT NOT NULL,
        description TEXT,
        image_path TEXT,
        normal_label TEXT,
        heterozygous_label TEXT,
        homozygous_label TEXT,
        is_reference INTEGER NOT NULL DEFAULT 0,
        display_order INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(species_id) REFERENCES genetic_species(id) ON DELETE CASCADE,
        UNIQUE(species_id, slug)
    )');

    $geneColumns = $pdo->query('PRAGMA table_info(genetic_genes)')->fetchAll();
    $geneColumnNames = array_column($geneColumns, 'name');
    if (!in_array('is_reference', $geneColumnNames, true)) {
        $pdo->exec('ALTER TABLE genetic_genes ADD COLUMN is_reference INTEGER NOT NULL DEFAULT 0');
    }
    if (!in_array('image_path', $geneColumnNames, true)) {
        $pdo->exec('ALTER TABLE genetic_genes ADD COLUMN image_path TEXT');
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS gallery_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        image_path TEXT NOT NULL,
        tags TEXT,
        is_featured INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS media_assets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT,
        file_path TEXT NOT NULL,
        original_name TEXT,
        mime_type TEXT,
        file_size INTEGER,
        width INTEGER,
        height INTEGER,
        alt_text TEXT,
        tags TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    // Create database indexes for performance
    create_database_indexes($pdo);
}

/**
 * Creates database indexes for improved query performance.
 *
 * @param PDO $pdo Database connection
 * @return void
 */
function create_database_indexes(PDO $pdo): void
{
    // Check if indexes exist before creating them
    $indexes = [
        // Animals table indexes
        'CREATE INDEX IF NOT EXISTS idx_animals_species_slug ON animals(species_slug)',
        'CREATE INDEX IF NOT EXISTS idx_animals_owner_id ON animals(owner_id)',
        'CREATE INDEX IF NOT EXISTS idx_animals_showcased ON animals(is_showcased)',
        'CREATE INDEX IF NOT EXISTS idx_animals_private ON animals(is_private)',

        // Adoption listings indexes
        'CREATE INDEX IF NOT EXISTS idx_adoption_status ON adoption_listings(status)',
        'CREATE INDEX IF NOT EXISTS idx_adoption_animal_id ON adoption_listings(animal_id)',

        // Adoption inquiries indexes
        'CREATE INDEX IF NOT EXISTS idx_inquiries_listing_id ON adoption_inquiries(listing_id)',

        // Pages indexes
        'CREATE INDEX IF NOT EXISTS idx_pages_slug ON pages(slug)',
        'CREATE INDEX IF NOT EXISTS idx_pages_published ON pages(is_published)',
        'CREATE INDEX IF NOT EXISTS idx_pages_parent_id ON pages(parent_id)',

        // News posts indexes
        'CREATE INDEX IF NOT EXISTS idx_news_slug ON news_posts(slug)',
        'CREATE INDEX IF NOT EXISTS idx_news_published ON news_posts(is_published)',
        'CREATE INDEX IF NOT EXISTS idx_news_published_at ON news_posts(published_at)',

        // Care articles indexes
        'CREATE INDEX IF NOT EXISTS idx_care_slug ON care_articles(slug)',
        'CREATE INDEX IF NOT EXISTS idx_care_published ON care_articles(is_published)',

        // Care topics indexes
        'CREATE INDEX IF NOT EXISTS idx_care_topics_slug ON care_topics(slug)',
        'CREATE INDEX IF NOT EXISTS idx_care_topics_parent_id ON care_topics(parent_id)',

        // Genetic species indexes
        'CREATE INDEX IF NOT EXISTS idx_genetic_species_slug ON genetic_species(slug)',

        // Genetic genes indexes
        'CREATE INDEX IF NOT EXISTS idx_genetic_genes_species_id ON genetic_genes(species_id)',
        'CREATE INDEX IF NOT EXISTS idx_genetic_genes_slug ON genetic_genes(slug)',

        // Breeding plan parents indexes
        'CREATE INDEX IF NOT EXISTS idx_breeding_parents_plan_id ON breeding_plan_parents(plan_id)',
        'CREATE INDEX IF NOT EXISTS idx_breeding_parents_animal_id ON breeding_plan_parents(animal_id)',

        // Media assets indexes
        'CREATE INDEX IF NOT EXISTS idx_media_file_path ON media_assets(file_path)',

        // Menu items indexes
        'CREATE INDEX IF NOT EXISTS idx_menu_location ON menu_items(location)',
        'CREATE INDEX IF NOT EXISTS idx_menu_visible ON menu_items(visible)',
        'CREATE INDEX IF NOT EXISTS idx_menu_position ON menu_items(position)'
    ];

    foreach ($indexes as $indexSql) {
        try {
            $pdo->exec($indexSql);
        } catch (PDOException $e) {
            // Log error but continue with other indexes
            error_log("Failed to create index: " . $e->getMessage());
        }
    }
}

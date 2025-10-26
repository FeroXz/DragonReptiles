<?php
session_start();
require_once __DIR__ . '/../app/bootstrap.php';

$route = $_GET['route'] ?? 'home';
$GLOBALS['currentRoute'] = $route;

switch ($route) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf_token('login');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            if (authenticate($pdo, $username, $password)) {
                flash('success', 'Willkommen zurück!');
                redirect('admin/dashboard');
            }
            flash('error', 'Ungültige Zugangsdaten.');
        }
        view('auth/login', ['settings' => get_all_settings($pdo)]);
        break;

    case 'logout':
        logout();
        redirect('home');
        break;

    case 'home':
        $settings = get_all_settings($pdo);
        $animals = get_showcased_animals($pdo);
        $listings = get_public_listings($pdo);
        $latestNews = get_latest_published_news($pdo, 3);
        $careHighlights = array_slice(get_published_care_articles($pdo), 0, 3);
        $galleryItems = get_featured_gallery_items($pdo, 6);
        $homeSections = get_home_sections_layout($settings);
        view('home', compact('settings', 'animals', 'listings', 'latestNews', 'careHighlights', 'galleryItems', 'homeSections'));
        break;

    case 'animals':
        $settings = get_all_settings($pdo);
        $animals = get_public_animals($pdo);
        view('animals/index', compact('settings', 'animals'));
        break;

    case 'my-animals':
        require_login();
        $settings = get_all_settings($pdo);
        $animals = get_user_animals($pdo, current_user()['id']);
        view('animals/my_animals', compact('settings', 'animals'));
        break;

    case 'breeding':
        require_login();
        $settings = get_all_settings($pdo);
        $breedingPlans = get_breeding_plans($pdo);
        view('breeding/index', compact('settings', 'breedingPlans'));
        break;

    case 'adoption':
        $settings = get_all_settings($pdo);
        $listings = get_public_listings($pdo);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $listingId = (int)($_POST['listing_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $message = trim($_POST['message'] ?? '');
            if ($listingId && $name && $email && $message) {
                try {
                    create_inquiry($pdo, [
                        'listing_id' => $listingId,
                        'interested_in' => $_POST['interested_in'] ?? null,
                        'sender_name' => $name,
                        'sender_email' => $email,
                        'message' => $message,
                    ]);
                    flash('success', 'Anfrage wurde gesendet.');
                    redirect('adoption');
                } catch (InvalidArgumentException $exception) {
                    flash('error', $exception->getMessage());
                }
            } else {
                flash('error', 'Bitte füllen Sie alle Pflichtfelder aus und ergänzen Sie eine aussagekräftige Nachricht.');
            }
        }
        $flashSuccess = flash('success');
        $flashError = flash('error');
        view('adoption/index', compact('settings', 'listings', 'flashSuccess', 'flashError'));
        break;

    case 'page':
        $slug = $_GET['slug'] ?? '';
        $page = $slug ? get_page_by_slug($pdo, $slug) : null;
        if (!$page || (!$page['is_published'] && (!current_user() || !is_authorized('can_manage_settings')))) {
            http_response_code(404);
            view('errors/404', ['settings' => get_all_settings($pdo)]);
            break;
        }
        $settings = get_all_settings($pdo);
        view('pages/show', [
            'settings' => $settings,
            'page' => $page,
            'activePageSlug' => $page['slug'],
        ]);
        break;

    case 'news':
        $settings = get_all_settings($pdo);
        $slug = $_GET['slug'] ?? null;
        if ($slug) {
            $post = get_news_by_slug($pdo, $slug);
            if (!$post || (!$post['is_published'] && (!current_user() || !is_authorized('can_manage_settings')))) {
                http_response_code(404);
                view('errors/404', ['settings' => $settings]);
                break;
            }
            view('news/show', [
                'settings' => $settings,
                'post' => $post,
            ]);
        } else {
            $newsPosts = get_published_news($pdo);
            view('news/index', compact('settings', 'newsPosts'));
        }
        break;

    case 'gallery':
        $settings = get_all_settings($pdo);
        $items = get_gallery_items($pdo);
        view('gallery/index', compact('settings', 'items'));
        break;

    case 'care-guide':
        $settings = get_all_settings($pdo);
        $allArticles = get_published_care_articles($pdo);
        $topicsTree = get_care_topics_hierarchy($pdo, true);
        $searchQuery = trim($_GET['q'] ?? '');
        $topicSlug = $_GET['topic'] ?? null;
        $activeTopic = $topicSlug ? get_care_topic_by_slug($pdo, $topicSlug) : null;
        $topicId = $activeTopic ? (int)$activeTopic['id'] : null;
        if ($searchQuery !== '' || $topicId) {
            $careArticles = search_care_articles($pdo, $searchQuery, $topicId, true);
        } else {
            $careArticles = $allArticles;
        }
        view('care/index', [
            'settings' => $settings,
            'careArticles' => $careArticles,
            'topicsTree' => $topicsTree,
            'searchQuery' => $searchQuery,
            'activeTopic' => $activeTopic,
            'allArticles' => $allArticles,
        ]);
        break;

    case 'care-article':
        $slug = $_GET['slug'] ?? '';
        $article = $slug ? get_care_article_by_slug($pdo, $slug) : null;
        if (!$article || (!$article['is_published'] && (!current_user() || !is_authorized('can_manage_settings')))) {
            http_response_code(404);
            view('errors/404', ['settings' => get_all_settings($pdo)]);
            break;
        }
        $settings = get_all_settings($pdo);
        $publishedArticles = get_published_care_articles($pdo);
        $articleIndex = build_care_article_index($publishedArticles);
        $rendered = render_care_article_markup($article['content'], $articleIndex);
        $relatedArticles = get_related_care_articles($pdo, (int)$article['id']);
        view('care/show', [
            'settings' => $settings,
            'article' => $article,
            'activeCareSlug' => $article['slug'],
            'articleContent' => $rendered['html'],
            'articleHeadings' => $rendered['headings'],
            'relatedArticles' => $relatedArticles,
        ]);
        break;

    case 'genetics':
        $settings = get_all_settings($pdo);
        $speciesList = get_genetic_species($pdo);
        $selectedSlug = $_POST['species_slug'] ?? $_GET['species'] ?? ($speciesList[0]['slug'] ?? null);
        $selectedSpecies = $selectedSlug ? get_genetic_species_by_slug($pdo, $selectedSlug) : null;
        if (!$selectedSpecies && !empty($speciesList)) {
            $selectedSpecies = get_genetic_species_by_id($pdo, (int)$speciesList[0]['id']);
            $selectedSlug = $speciesList[0]['slug'];
        }
        $genes = $selectedSpecies ? get_genetic_genes($pdo, (int)$selectedSpecies['id']) : [];
        $activeGenes = array_values(array_filter($genes, static fn($gene) => empty($gene['is_reference'])));
        $referenceGenes = array_values(array_filter($genes, static fn($gene) => !empty($gene['is_reference'])));
        $parentSelections = [
            'parent1' => $_POST['parent1'] ?? [],
            'parent2' => $_POST['parent2'] ?? [],
        ];
        $results = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedSpecies && !empty($activeGenes)) {
            $results = calculate_genetic_outcomes($activeGenes, $parentSelections['parent1'], $parentSelections['parent2']);
        }
        view('genetics/index', [
            'settings' => $settings,
            'speciesList' => $speciesList,
            'selectedSpecies' => $selectedSpecies,
            'selectedSpeciesSlug' => $selectedSlug,
            'genes' => $activeGenes,
            'referenceGenes' => $referenceGenes,
            'parentSelections' => $parentSelections,
            'results' => $results,
        ]);
        break;

    case 'admin/dashboard':
        require_login();
        $settings = get_all_settings($pdo);
        $animals = get_animals($pdo);
        $listings = get_listings($pdo);
        $inquiries = get_inquiries($pdo);
        $pages = get_pages($pdo);
        $newsPosts = get_news($pdo);
        $breedingPlans = get_breeding_plans($pdo);
        $careArticles = get_care_articles($pdo);
        $geneticSpecies = get_genetic_species($pdo);
        $geneticGenes = get_all_genetic_genes($pdo);
        view('admin/dashboard', compact('settings', 'animals', 'listings', 'inquiries', 'pages', 'newsPosts', 'breedingPlans', 'careArticles', 'geneticSpecies', 'geneticGenes'));
        break;

    case 'admin/gallery':
        require_login();
        if (!is_authorized('can_manage_settings')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf_token('admin/gallery');
            $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
            $title = trim($_POST['title'] ?? '');
            $description = $_POST['description'] ?? '';
            $tags = $_POST['tags'] ?? '';
            $isFeatured = !empty($_POST['is_featured']);
            $selectedAssetId = normalize_nullable_id($_POST['media_asset_id'] ?? null);
            $persistedPath = normalize_media_path($_POST['image_path'] ?? null);
            $newImagePath = $persistedPath;
            $redirectParams = $id ? ['edit' => $id] : [];

            if (!empty($_FILES['image']['name'])) {
                $assetId = create_media_asset_from_upload($pdo, $_FILES['image'], [
                    'title' => $title !== '' ? $title : 'Galeriebild',
                    'tags' => 'gallery,upload',
                ]);
                if ($assetId) {
                    $asset = get_media_asset($pdo, $assetId);
                    if ($asset) {
                        $newImagePath = $asset['file_path'] ?? $newImagePath;
                        $selectedAssetId = $assetId;
                    }
                } else {
                    flash('error', 'Upload fehlgeschlagen. Bitte Bildformat prüfen.');
                    redirect('admin/gallery', $redirectParams);
                }
            } elseif ($selectedAssetId) {
                $asset = get_media_asset($pdo, $selectedAssetId);
                if ($asset) {
                    $newImagePath = $asset['file_path'];
                }
            }

            if (!$selectedAssetId && $newImagePath) {
                $asset = ensure_media_asset_for_path($pdo, $newImagePath, [
                    'title' => $title !== '' ? $title : null,
                    'tags' => $tags !== '' ? $tags : 'gallery,backfill',
                ]);
                if ($asset) {
                    $newImagePath = $asset['file_path'];
                }
            }

            if ($title === '') {
                flash('error', 'Bitte einen Titel angeben.');
                redirect('admin/gallery', $redirectParams);
            }

            if ($id) {
                $item = find_gallery_item($pdo, $id);
                if (!$item) {
                    flash('error', 'Der Eintrag wurde nicht gefunden.');
                    redirect('admin/gallery');
                }

                update_gallery_item($pdo, $id, [
                    'title' => $title,
                    'description' => $description,
                    'tags' => $tags,
                    'is_featured' => $isFeatured,
                    'image_path' => $newImagePath,
                ]);
                flash('success', 'Galerie-Eintrag aktualisiert.');
            } else {
                if (!$newImagePath) {
                    flash('error', 'Bitte ein Bild für den neuen Eintrag wählen oder hochladen.');
                    redirect('admin/gallery');
                }

                create_gallery_item($pdo, [
                    'title' => $title,
                    'description' => $description,
                    'tags' => $tags,
                    'is_featured' => $isFeatured,
                    'image_path' => $newImagePath,
                ]);
                flash('success', 'Galerie-Eintrag angelegt.');
            }

            redirect('admin/gallery');
        }

        $settings = get_all_settings($pdo);
        $galleryItems = get_gallery_items($pdo);
        $editingItem = null;
        if (isset($_GET['edit'])) {
            $editingItem = find_gallery_item($pdo, (int)$_GET['edit']);
            if ($editingItem && !empty($editingItem['image_path'])) {
                $media = find_media_asset_by_path($pdo, $editingItem['image_path']);
                if ($media) {
                    $editingItem['media_asset_id'] = (int)$media['id'];
                }
            }
        }
        $flashSuccess = flash('success');
        $flashError = flash('error');
        $mediaAssets = get_media_assets($pdo);
        view('admin/gallery', compact('settings', 'galleryItems', 'editingItem', 'flashSuccess', 'flashError', 'mediaAssets'));
        break;

    case 'admin/gallery/delete':
        require_login();
        if (!is_authorized('can_manage_settings')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf_token('admin/gallery/delete');
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                delete_gallery_item($pdo, $id);
                flash('success', 'Galerie-Eintrag entfernt.');
            }
        }
        redirect('admin/gallery');
        break;

    case 'admin/media':
        require_login();
        if (!is_authorized('can_manage_settings')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'upload_asset';
            if ($action === 'upload_asset') {
                require_csrf_token('admin/media');
                $title = trim($_POST['title'] ?? '');
                $altText = trim($_POST['alt_text'] ?? '');
                $tags = trim($_POST['tags'] ?? '');
                $file = $_FILES['media_file'] ?? [];
                if (empty($file['name'])) {
                    flash('error', 'Bitte eine Bilddatei auswählen.');
                    redirect('admin/media');
                }
                $mediaId = create_media_asset_from_upload($pdo, $file, [
                    'title' => $title !== '' ? $title : null,
                    'alt_text' => $altText !== '' ? $altText : null,
                    'tags' => $tags !== '' ? $tags : null,
                ]);
                if ($mediaId) {
                    flash('success', 'Medienobjekt gespeichert.');
                } else {
                    flash('error', 'Upload fehlgeschlagen. Bitte gültiges Bild prüfen.');
                }
                redirect('admin/media');
            } elseif ($action === 'update_asset') {
                $assetId = (int)($_POST['id'] ?? 0);
                $redirectParams = $assetId ? ['edit' => $assetId] : [];
                require_csrf_token('admin/media', $redirectParams);
                if (!$assetId) {
                    flash('error', 'Medienobjekt nicht gefunden.');
                    redirect('admin/media');
                }
                $asset = get_media_asset($pdo, $assetId);
                if (!$asset) {
                    flash('error', 'Medienobjekt nicht gefunden.');
                    redirect('admin/media');
                }
                $updateData = [
                    'title' => ($t = trim($_POST['title'] ?? '')) !== '' ? $t : null,
                    'alt_text' => ($a = trim($_POST['alt_text'] ?? '')) !== '' ? $a : null,
                    'tags' => ($tg = trim($_POST['tags'] ?? '')) !== '' ? $tg : null,
                ];
                $replacement = $_FILES['replacement_file'] ?? [];
                if (!empty($replacement['name'])) {
                    $upload = handle_upload($replacement, true);
                    if (!$upload || empty($upload['path'])) {
                        flash('error', 'Upload fehlgeschlagen. Bitte gültiges Bild prüfen.');
                        redirect('admin/media', ['edit' => $assetId]);
                    }
                    remove_media_file($asset['file_path'] ?? null);
                    $updateData['file_path'] = $upload['path'];
                    $updateData['original_name'] = $upload['original_name'] ?? null;
                    $updateData['mime_type'] = $upload['mime_type'] ?? null;
                    $updateData['file_size'] = $upload['file_size'] ?? null;
                    $updateData['width'] = $upload['width'] ?? null;
                    $updateData['height'] = $upload['height'] ?? null;
                }
                update_media_asset($pdo, $assetId, $updateData);
                flash('success', 'Medienobjekt aktualisiert.');
                redirect('admin/media', ['edit' => $assetId]);
            } elseif ($action === 'delete_asset') {
                require_csrf_token('admin/media');
                $assetId = (int)($_POST['id'] ?? 0);
                if ($assetId) {
                    delete_media_asset($pdo, $assetId);
                    flash('success', 'Medienobjekt gelöscht.');
                }
                redirect('admin/media');
            }
        }

        $settings = get_all_settings($pdo);
        $searchQuery = trim($_GET['q'] ?? '');
        $assets = $searchQuery !== '' ? search_media_assets($pdo, $searchQuery) : get_media_assets($pdo);
        $editingAsset = null;
        if (isset($_GET['edit'])) {
            $editingAsset = get_media_asset($pdo, (int)$_GET['edit']);
        }
        $flashSuccess = flash('success');
        $flashError = flash('error');
        view('admin/media', compact('settings', 'assets', 'editingAsset', 'flashSuccess', 'flashError', 'searchQuery'));
        break;

    case 'admin/home-layout':
        require_login();
        if (!is_authorized('can_manage_settings')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }

        $settings = get_all_settings($pdo);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf_token('admin/home-layout');
            $layoutJson = $_POST['layout'] ?? '[]';
            $decoded = json_decode($layoutJson, true);
            $layout = is_array($decoded) ? sanitize_home_sections_layout($decoded) : default_home_sections_layout();
            set_setting($pdo, 'home_sections_layout', serialize_home_sections_layout($layout));
            flash('success', 'Startseiten-Layout wurde aktualisiert.');
            redirect('admin/home-layout');
        }

        $layout = get_home_sections_layout($settings);
        $definitions = get_home_section_definitions();
        $flashSuccess = flash('success');
        $flashError = flash('error');
        view('admin/home_layout', compact('settings', 'layout', 'definitions', 'flashSuccess', 'flashError'));
        break;

    case 'admin/update':
        require_login();
        if (!is_authorized('can_manage_settings')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf_token('admin/update');
            try {
                $result = apply_update_package($pdo, $_FILES['package'] ?? []);
                $message = 'Update erfolgreich angewendet. Aktualisierte Dateien: ' . ($result['files'] ?? 0);
                if (!empty($result['version'])) {
                    $message .= '. Neue Version: ' . $result['version'];
                }
                flash('success', $message);
            } catch (RuntimeException $exception) {
                flash('error', $exception->getMessage());
            }
            redirect('admin/update');
        }

        $settings = get_all_settings($pdo);
        $currentVersion = $settings['app_version'] ?? APP_VERSION;
        $availablePackages = array_values(array_filter(
            glob(__DIR__ . '/../storage/updates/*') ?: [],
            'is_dir'
        ));
        $flashSuccess = flash('success');
        $flashError = flash('error');
        view('admin/update', compact('settings', 'currentVersion', 'availablePackages', 'flashSuccess', 'flashError'));
        break;

    case 'admin/settings':
        require_login();
        if (!is_authorized('can_manage_settings')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf_token('admin/settings');
            update_settings($pdo, [
                'site_title' => $_POST['site_title'] ?? '',
                'site_tagline' => $_POST['site_tagline'] ?? '',
                'hero_intro' => $_POST['hero_intro'] ?? '',
                'adoption_intro' => $_POST['adoption_intro'] ?? '',
                'footer_text' => $_POST['footer_text'] ?? '',
                'contact_email' => $_POST['contact_email'] ?? '',
                'active_theme' => $_POST['active_theme'] ?? 'aurora',
            ]);
            flash('success', 'Einstellungen gespeichert.');
            redirect('admin/settings');
        }
        $settings = get_all_settings($pdo);
        $flashSuccess = flash('success');
        view('admin/settings', compact('settings', 'flashSuccess'));
        break;

    case 'admin/content':
        require_login();
        if (!is_authorized('can_manage_settings')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf_token('admin/content');
            $definitions = get_content_definitions();
            $blocks = $_POST['blocks'] ?? [];
            $payload = [];
            foreach ($blocks as $key => $value) {
                if (isset($definitions[$key])) {
                    $payload[$key] = is_string($value) ? $value : '';
                }
            }
            if ($payload) {
                update_settings($pdo, $payload);
            }
            flash('success', 'Texte gespeichert.');
            redirect('admin/content');
        }
        $settings = get_all_settings($pdo);
        $contentGroups = get_content_groups();
        $flashSuccess = flash('success');
        view('admin/content', compact('settings', 'contentGroups', 'flashSuccess'));
        break;

    case 'admin/pages':
        require_login();
        if (!is_authorized('can_manage_settings')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['action'] ?? '') === 'delete_page') {
                require_csrf_token('admin/pages');
                $pageId = (int)($_POST['page_id'] ?? 0);
                if ($pageId) {
                    delete_page($pdo, $pageId);
                    flash('success', 'Seite gelöscht.');
                }
                redirect('admin/pages');
            }

            $redirectParams = !empty($_POST['id']) ? ['edit' => (int)$_POST['id']] : [];
            require_csrf_token('admin/pages', $redirectParams);
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'slug' => trim($_POST['slug'] ?? ''),
                'content' => $_POST['content'] ?? '',
                'is_published' => isset($_POST['is_published']),
                'show_in_menu' => isset($_POST['show_in_menu']),
                'parent_id' => $_POST['parent_id'] ?? null,
            ];
            if ($data['title'] && $data['content']) {
                if (!empty($_POST['id'])) {
                    update_page($pdo, (int)$_POST['id'], $data);
                    flash('success', 'Seite aktualisiert.');
                } else {
                    create_page($pdo, $data);
                    flash('success', 'Neue Seite angelegt.');
                }
                redirect('admin/pages');
            } else {
                flash('error', 'Bitte geben Sie Titel und Inhalt ein, um die Seite zu speichern.');
            }
        }
        $settings = get_all_settings($pdo);
        $pages = get_pages($pdo);
        $editPage = null;
        if (isset($_GET['edit'])) {
            $editPage = get_page($pdo, (int)$_GET['edit']);
        }
        $flashSuccess = flash('success');
        $flashError = flash('error');
        view('admin/pages', compact('settings', 'pages', 'editPage', 'flashSuccess', 'flashError'));
        break;

    case 'admin/news':
        require_login();
        if (!is_authorized('can_manage_settings')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['action'] ?? '') === 'delete_news') {
                require_csrf_token('admin/news');
                $postId = (int)($_POST['post_id'] ?? 0);
                if ($postId) {
                    delete_news($pdo, $postId);
                    flash('success', 'Neuigkeit gelöscht.');
                }
                redirect('admin/news');
            }

            $redirectParams = !empty($_POST['id']) ? ['edit' => (int)$_POST['id']] : [];
            require_csrf_token('admin/news', $redirectParams);
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'slug' => trim($_POST['slug'] ?? ''),
                'excerpt' => $_POST['excerpt'] ?? null,
                'content' => $_POST['content'] ?? '',
                'is_published' => isset($_POST['is_published']),
                'published_at' => trim($_POST['published_at'] ?? ''),
            ];
            if ($data['title'] && $data['content']) {
                if (!empty($_POST['id'])) {
                    update_news($pdo, (int)$_POST['id'], $data);
                    flash('success', 'Neuigkeit aktualisiert.');
                } else {
                    create_news($pdo, $data);
                    flash('success', 'Neuigkeit veröffentlicht.');
                }
                redirect('admin/news');
            } else {
                flash('error', 'Bitte tragen Sie einen Titel und den vollständigen Textbeitrag ein.');
            }
        }
        $settings = get_all_settings($pdo);
        $newsPosts = get_news($pdo);
        $editPost = null;
        if (isset($_GET['edit'])) {
            $editPost = get_news_post($pdo, (int)$_GET['edit']);
        }
        $flashSuccess = flash('success');
        $flashError = flash('error');
        view('admin/news', compact('settings', 'newsPosts', 'editPost', 'flashSuccess', 'flashError'));
        break;

    case 'admin/animals':
        require_login();
        if (!is_authorized('can_manage_animals')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }
        $prefillAnimal = null;
        $speciesList = get_genetic_species($pdo);
        $speciesBySlug = [];
        $speciesGenes = [];
        foreach ($speciesList as $speciesEntry) {
            $speciesBySlug[$speciesEntry['slug']] = $speciesEntry;
            $speciesGeneList = get_genetic_genes($pdo, (int)$speciesEntry['id']);
            $speciesGenes[$speciesEntry['slug']] = array_values(array_filter($speciesGeneList, static fn($gene) => empty($gene['is_reference'])));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['action'] ?? '') === 'delete_animal') {
                require_csrf_token('admin/animals');
                $animalId = (int)($_POST['animal_id'] ?? 0);
                if ($animalId) {
                    delete_animal($pdo, $animalId);
                    flash('success', 'Tier gelöscht.');
                }
                redirect('admin/animals');
            }

            $redirectParams = !empty($_POST['id']) ? ['edit' => (int)$_POST['id']] : [];
            require_csrf_token('admin/animals', $redirectParams);

            $ageParts = [
                'year' => $_POST['age_year'] ?? '',
                'month' => $_POST['age_month'] ?? '',
                'day' => $_POST['age_day'] ?? '',
            ];
            [$normalizedAge, $ageError] = normalize_partial_date_input($ageParts);

            $speciesSlug = trim((string)($_POST['species_slug'] ?? ''));
            $selectedSpecies = $speciesSlug !== '' ? ($speciesBySlug[$speciesSlug] ?? null) : null;

            $geneStates = [];
            if (isset($_POST['gene_states']) && is_array($_POST['gene_states'])) {
                foreach ($_POST['gene_states'] as $slug => $state) {
                    if (!is_string($slug) || !is_string($state)) {
                        continue;
                    }
                    $geneStates[$slug] = trim($state);
                }
            }

            $selectedAssetId = normalize_nullable_id($_POST['media_asset_id'] ?? null);

            $data = [
                'id' => $_POST['id'] ?? null,
                'name' => trim((string)($_POST['name'] ?? '')),
                'origin' => trim((string)($_POST['origin'] ?? '')),
                'special_notes' => $_POST['special_notes'] ?? null,
                'description' => $_POST['description'] ?? null,
                'owner_id' => $_POST['owner_id'] ?? null,
                'image_path' => normalize_media_path($_POST['image_path'] ?? null),
                'is_private' => isset($_POST['is_private']),
                'is_showcased' => isset($_POST['is_showcased']),
                'is_piebald' => isset($_POST['is_piebald']),
            ];

            $errorMessage = null;
            if ($data['name'] === '') {
                $errorMessage = 'Bitte vergeben Sie einen Namen für das Tier.';
            } elseif (!$selectedSpecies) {
                $errorMessage = 'Bitte wählen Sie eine gültige Art aus der Liste.';
            } elseif ($ageError) {
                $errorMessage = $ageError;
            }

            $data['species'] = $selectedSpecies['name'] ?? '';
            $data['species_slug'] = $selectedSpecies['slug'] ?? null;
            $data['age'] = $normalizedAge;

            $geneSummary = [];
            $geneticsProfile = [];
            if ($selectedSpecies) {
                $availableGenes = $speciesGenes[$selectedSpecies['slug']] ?? [];
                $geneLookup = [];
                foreach ($availableGenes as $gene) {
                    $geneLookup[$gene['slug']] = $gene;
                }
                foreach ($geneStates as $slug => $state) {
                    if (!isset($geneLookup[$slug])) {
                        continue;
                    }
                    $label = build_gene_state_label($geneLookup[$slug], $state);
                    if ($label) {
                        $geneSummary[] = $label;
                        $geneticsProfile[$slug] = $state;
                    }
                }
            }
            $data['genetics'] = $geneSummary ? implode(', ', $geneSummary) : null;
            $data['genetics_profile'] = $geneticsProfile ? json_encode($geneticsProfile, JSON_UNESCAPED_UNICODE) : null;

            $uploadError = null;
            if ($errorMessage === null) {
                if (!empty($_FILES['image']['name'])) {
                    $assetId = create_media_asset_from_upload($pdo, $_FILES['image'], [
                        'title' => $data['name'] !== '' ? $data['name'] : ($selectedSpecies['name'] ?? 'Tierbild'),
                        'alt_text' => $data['name'] !== '' ? $data['name'] . ' Portrait' : null,
                        'tags' => 'animals,profile',
                    ]);
                    if ($assetId) {
                        $asset = get_media_asset($pdo, $assetId);
                        if ($asset) {
                            $data['image_path'] = $asset['file_path'];
                            $selectedAssetId = $assetId;
                        }
                    } else {
                        $uploadError = 'Upload fehlgeschlagen. Bitte Bildformat prüfen.';
                    }
                } elseif ($selectedAssetId) {
                    $asset = get_media_asset($pdo, $selectedAssetId);
                    if ($asset) {
                        $data['image_path'] = $asset['file_path'];
                    }
                }

                if (!$selectedAssetId && !empty($data['image_path'])) {
                    $asset = ensure_media_asset_for_path($pdo, $data['image_path'], [
                        'title' => $data['name'] !== '' ? $data['name'] : null,
                        'tags' => 'animals,backfill',
                    ]);
                    if ($asset) {
                        $data['image_path'] = $asset['file_path'];
                    }
                }
            }

            if ($errorMessage !== null || $uploadError !== null) {
                flash('error', $errorMessage ?? $uploadError);
                $prefillAnimal = array_merge($data, [
                    'species_slug' => $speciesSlug,
                    'age_parts' => $ageParts,
                    'gene_states' => $geneticsProfile ? $geneticsProfile : $geneStates,
                    'media_asset_id' => $selectedAssetId,
                ]);
            } else {
                if (!empty($data['id'])) {
                    update_animal($pdo, (int)$data['id'], $data);
                    flash('success', 'Tier aktualisiert.');
                } else {
                    create_animal($pdo, $data);
                    flash('success', 'Tier angelegt.');
                }
                redirect('admin/animals');
            }
        }
        $animals = get_animals($pdo);
        $users = get_users($pdo);
        $settings = get_all_settings($pdo);
        $editAnimal = $prefillAnimal;
        if (!$editAnimal && isset($_GET['edit'])) {
            $editAnimal = get_animal($pdo, (int)$_GET['edit']);
        }
        if ($editAnimal) {
            if (empty($editAnimal['species_slug']) && !empty($editAnimal['species'])) {
                foreach ($speciesList as $speciesEntry) {
                    if (strcasecmp($speciesEntry['name'], $editAnimal['species']) === 0) {
                        $editAnimal['species_slug'] = $speciesEntry['slug'];
                        break;
                    }
                }
            }
            $editAnimal['age_parts'] = parse_partial_date($editAnimal['age'] ?? null);
            if (!empty($editAnimal['genetics_profile'])) {
                $decodedProfile = json_decode($editAnimal['genetics_profile'], true);
                if (is_array($decodedProfile)) {
                    $editAnimal['gene_states'] = $decodedProfile;
                }
            }
            if (!empty($editAnimal['image_path']) && empty($editAnimal['media_asset_id'])) {
                $asset = find_media_asset_by_path($pdo, $editAnimal['image_path']);
                if ($asset) {
                    $editAnimal['media_asset_id'] = (int)$asset['id'];
                }
            }
        }
        $flashSuccess = flash('success');
        $flashError = flash('error');
        $mediaAssets = get_media_assets($pdo);
        view('admin/animals', compact('animals', 'users', 'editAnimal', 'flashSuccess', 'flashError', 'settings', 'speciesList', 'speciesGenes', 'mediaAssets'));
        break;

    case 'admin/breeding':
        require_login();
        if (!is_authorized('can_manage_animals')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formType = $_POST['form'] ?? 'plan';
            if ($formType === 'parent') {
                $planId = (int)($_POST['plan_id'] ?? 0);
                require_csrf_token('admin/breeding', $planId ? ['edit_plan' => $planId] : []);
                if ($planId) {
                    $parentType = $_POST['parent_type'] ?? 'animal';
                    $data = [
                        'plan_id' => $planId,
                        'parent_type' => $parentType === 'virtual' ? 'virtual' : 'animal',
                        'animal_id' => $_POST['animal_id'] ?? null,
                        'name' => trim($_POST['name'] ?? ''),
                        'sex' => trim($_POST['sex'] ?? ''),
                        'species' => trim($_POST['species'] ?? ''),
                        'genetics' => trim($_POST['genetics'] ?? ''),
                        'notes' => $_POST['notes'] ?? null,
                    ];
                    if ($data['parent_type'] === 'animal' && empty($data['animal_id'])) {
                        flash('error', 'Bitte wählen Sie ein Tier aus dem Bestand oder aktivieren Sie die Option für ein virtuelles Tier.');
                    } else {
                        if ($data['parent_type'] === 'virtual' && !$data['name']) {
                            $data['name'] = 'Virtueller Elternteil';
                        }
                        add_breeding_parent($pdo, $data);
                        flash('success', 'Elternteil hinzugefügt.');
                    }
                }
                redirect('admin/breeding', ['edit_plan' => $planId]);
            } elseif ($formType === 'pair') {
                $planId = (int)($_POST['pair_plan_id'] ?? 0);
                require_csrf_token('admin/breeding', $planId ? ['edit_plan' => $planId] : []);
                if ($planId) {
                    $parents = [];
                    $labels = ['parent_a' => 'erstes Elternteil', 'parent_b' => 'zweites Elternteil'];
                    foreach ($labels as $prefix => $label) {
                        $type = $_POST[$prefix . '_type'] ?? 'animal';
                        $entry = [
                            'plan_id' => $planId,
                            'parent_type' => $type === 'virtual' ? 'virtual' : 'animal',
                            'animal_id' => $_POST[$prefix . '_animal_id'] ?? null,
                            'name' => trim($_POST[$prefix . '_name'] ?? ''),
                            'sex' => trim($_POST[$prefix . '_sex'] ?? ''),
                            'species' => trim($_POST[$prefix . '_species'] ?? ''),
                            'genetics' => trim($_POST[$prefix . '_genetics'] ?? ''),
                            'notes' => $_POST[$prefix . '_notes'] ?? null,
                        ];
                        if ($entry['parent_type'] === 'animal') {
                            if (empty($entry['animal_id'])) {
                                flash('error', "Bitte wählen Sie für das {$label} ein Tier aus dem Bestand aus oder wechseln Sie zur virtuellen Eingabe.");
                                redirect('admin/breeding', ['edit_plan' => $planId]);
                            }
                        } else {
                            if ($entry['name'] === '') {
                                $entry['name'] = 'Virtuelles Elternteil';
                            }
                        }
                        $parents[] = $entry;
                    }
                    foreach ($parents as $entry) {
                        add_breeding_parent($pdo, $entry);
                    }
                    flash('success', 'Verpaarung gespeichert.');
                } else {
                    flash('error', 'Bitte wählen Sie einen Zuchtplan aus.');
                }
                redirect('admin/breeding', ['edit_plan' => $planId]);
            } elseif ($formType === 'delete_plan') {
                $planId = (int)($_POST['plan_id'] ?? 0);
                require_csrf_token('admin/breeding');
                if ($planId) {
                    delete_breeding_plan($pdo, $planId);
                    flash('success', 'Zuchtplan gelöscht.');
                }
                redirect('admin/breeding');
            } elseif ($formType === 'delete_parent') {
                $planId = (int)($_POST['plan_id'] ?? 0);
                $redirectParams = $planId ? ['edit_plan' => $planId] : [];
                require_csrf_token('admin/breeding', $redirectParams);
                $parentId = (int)($_POST['parent_id'] ?? 0);
                if ($parentId) {
                    delete_breeding_parent($pdo, $parentId);
                    flash('success', 'Elternteil entfernt.');
                }
                redirect('admin/breeding', $redirectParams);
            } else {
                $redirectParams = !empty($_POST['id']) ? ['edit_plan' => (int)$_POST['id']] : [];
                require_csrf_token('admin/breeding', $redirectParams);
                $data = [
                    'title' => trim($_POST['title'] ?? ''),
                    'season' => trim($_POST['season'] ?? ''),
                    'notes' => $_POST['notes'] ?? null,
                    'expected_genetics' => $_POST['expected_genetics'] ?? null,
                    'incubation_notes' => $_POST['incubation_notes'] ?? null,
                ];
                if ($data['title']) {
                    if (!empty($_POST['id'])) {
                        update_breeding_plan($pdo, (int)$_POST['id'], $data);
                        flash('success', 'Zuchtplan aktualisiert.');
                        redirect('admin/breeding', ['edit_plan' => (int)$_POST['id']]);
                    } else {
                        $planId = create_breeding_plan($pdo, $data);
                        flash('success', 'Zuchtplan erstellt.');
                        redirect('admin/breeding', ['edit_plan' => $planId]);
                    }
                } else {
                    flash('error', 'Bitte vergeben Sie einen aussagekräftigen Titel für den Zuchtplan.');
                }
            }
        }
        $settings = get_all_settings($pdo);
        $animals = get_animals($pdo);
        $breedingPlans = get_breeding_plans($pdo);
        $editPlan = null;
        if (isset($_GET['edit_plan'])) {
            $editPlan = get_breeding_plan($pdo, (int)$_GET['edit_plan']);
        }
        $flashSuccess = flash('success');
        $flashError = flash('error');
        view('admin/breeding', compact('settings', 'animals', 'breedingPlans', 'editPlan', 'flashSuccess', 'flashError'));
        break;

    case 'admin/adoption':
        require_login();
        if (!is_authorized('can_manage_adoptions')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }
        $speciesList = get_genetic_species($pdo);
        $speciesBySlug = [];
        $speciesGenes = [];
        foreach ($speciesList as $speciesEntry) {
            $speciesBySlug[$speciesEntry['slug']] = $speciesEntry;
            $speciesGeneList = get_genetic_genes($pdo, (int)$speciesEntry['id']);
            $speciesGenes[$speciesEntry['slug']] = array_values(array_filter($speciesGeneList, static fn($gene) => empty($gene['is_reference'])));
        }
        $prefillListing = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['action'] ?? '') === 'delete_listing') {
                require_csrf_token('admin/adoption');
                $listingId = (int)($_POST['listing_id'] ?? 0);
                if ($listingId) {
                    delete_listing($pdo, $listingId);
                    flash('success', 'Eintrag gelöscht.');
                }
                redirect('admin/adoption');
            }

            $redirectParams = !empty($_POST['id']) ? ['edit' => (int)$_POST['id']] : [];
            require_csrf_token('admin/adoption', $redirectParams);
            $speciesSlug = trim((string)($_POST['species_slug'] ?? ''));
            $selectedSpecies = $speciesSlug !== '' ? ($speciesBySlug[$speciesSlug] ?? null) : null;

            $geneStates = [];
            if (isset($_POST['gene_states']) && is_array($_POST['gene_states'])) {
                foreach ($_POST['gene_states'] as $slug => $state) {
                    if (!is_string($slug) || !is_string($state)) {
                        continue;
                    }
                    $geneStates[$slug] = trim($state);
                }
            }

            $selectedAssetId = normalize_nullable_id($_POST['media_asset_id'] ?? null);

            $data = [
                'id' => $_POST['id'] ?? null,
                'title' => trim((string)($_POST['title'] ?? '')),
                'animal_id' => $_POST['animal_id'] ?? null,
                'price' => $_POST['price'] ?? null,
                'description' => $_POST['description'] ?? null,
                'status' => $_POST['status'] ?? 'available',
                'contact_email' => $_POST['contact_email'] ?? null,
                'image_path' => normalize_media_path($_POST['image_path'] ?? null),
            ];

            $errorMessage = null;
            if ($data['title'] === '') {
                $errorMessage = 'Bitte gib dem Inserat einen aussagekräftigen Titel.';
            } elseif (!empty($speciesList) && !$selectedSpecies) {
                $errorMessage = 'Bitte wähle eine Art aus der Liste.';
            }

            $data['species'] = $selectedSpecies['name'] ?? null;
            $data['species_slug'] = $selectedSpecies['slug'] ?? null;

            $geneSummary = [];
            $geneticsProfile = [];
            if ($selectedSpecies) {
                $availableGenes = $speciesGenes[$selectedSpecies['slug']] ?? [];
                $geneLookup = [];
                foreach ($availableGenes as $gene) {
                    $geneLookup[$gene['slug']] = $gene;
                }
                foreach ($geneStates as $slug => $state) {
                    if (!isset($geneLookup[$slug])) {
                        continue;
                    }
                    $label = build_gene_state_label($geneLookup[$slug], $state);
                    if ($label) {
                        $geneSummary[] = $label;
                        $geneticsProfile[$slug] = $state;
                    }
                }
            }
            $data['genetics'] = $geneSummary ? implode(', ', $geneSummary) : null;
            $data['genetics_profile'] = $geneticsProfile ? json_encode($geneticsProfile, JSON_UNESCAPED_UNICODE) : null;

            $uploadError = null;
            if ($errorMessage === null) {
                if (!empty($_FILES['image']['name'])) {
                    $assetId = create_media_asset_from_upload($pdo, $_FILES['image'], [
                        'title' => $data['title'] !== '' ? $data['title'] : 'Adoptionsbild',
                        'tags' => 'adoption,upload',
                    ]);
                    if ($assetId) {
                        $asset = get_media_asset($pdo, $assetId);
                        if ($asset) {
                            $data['image_path'] = $asset['file_path'];
                            $selectedAssetId = $assetId;
                        }
                    } else {
                        $uploadError = 'Upload fehlgeschlagen. Bitte Bildformat prüfen.';
                    }
                } elseif ($selectedAssetId) {
                    $asset = get_media_asset($pdo, $selectedAssetId);
                    if ($asset) {
                        $data['image_path'] = $asset['file_path'];
                    }
                }

                if (!$selectedAssetId && !empty($data['image_path'])) {
                    $asset = ensure_media_asset_for_path($pdo, $data['image_path'], [
                        'title' => $data['title'] !== '' ? $data['title'] : null,
                        'tags' => 'adoption,backfill',
                    ]);
                    if ($asset) {
                        $data['image_path'] = $asset['file_path'];
                    }
                }
            }

            if ($errorMessage !== null || $uploadError !== null) {
                flash('error', $errorMessage ?? $uploadError);
                $prefillListing = array_merge($data, [
                    'species_slug' => $speciesSlug,
                    'gene_states' => $geneticsProfile ? $geneticsProfile : $geneStates,
                    'media_asset_id' => $selectedAssetId,
                ]);
            } else {
                if (!empty($data['id'])) {
                    update_listing($pdo, (int)$data['id'], $data);
                    flash('success', 'Abgabeintrag aktualisiert.');
                } else {
                    create_listing($pdo, $data);
                    flash('success', 'Abgabeintrag erstellt.');
                }
                redirect('admin/adoption');
            }
        }
        $listings = get_listings($pdo);
        $animals = get_animals($pdo);
        $settings = get_all_settings($pdo);
        $editListing = $prefillListing;
        if (!$editListing && isset($_GET['edit'])) {
            $editListing = get_listing($pdo, (int)$_GET['edit']);
        }
        if ($editListing) {
            if (empty($editListing['species_slug']) && !empty($editListing['species'])) {
                foreach ($speciesList as $speciesEntry) {
                    if (strcasecmp($speciesEntry['name'], $editListing['species']) === 0) {
                        $editListing['species_slug'] = $speciesEntry['slug'];
                        break;
                    }
                }
            }
            if (!empty($editListing['genetics_profile'])) {
                $decodedProfile = json_decode($editListing['genetics_profile'], true);
                if (is_array($decodedProfile)) {
                    $editListing['gene_states'] = $decodedProfile;
                }
            }
            if (!empty($editListing['image_path']) && empty($editListing['media_asset_id'])) {
                $asset = find_media_asset_by_path($pdo, $editListing['image_path']);
                if ($asset) {
                    $editListing['media_asset_id'] = (int)$asset['id'];
                }
            }
        }
        $flashSuccess = flash('success');
        $flashError = flash('error');
        $mediaAssets = get_media_assets($pdo);
        view('admin/adoption', compact('listings', 'animals', 'editListing', 'flashSuccess', 'flashError', 'settings', 'speciesList', 'speciesGenes', 'mediaAssets'));
        break;

    case 'admin/care':
        require_login();
        if (!is_authorized('can_manage_settings')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'save_care_article';
            if ($action === 'delete_care_article') {
                require_csrf_token('admin/care');
                $articleId = (int)($_POST['article_id'] ?? 0);
                if ($articleId) {
                    delete_care_article($pdo, $articleId);
                    flash('success', 'Artikel gelöscht.');
                }
                redirect('admin/care');
            } elseif ($action === 'save_care_article') {
                $redirectParams = !empty($_POST['id']) ? ['edit' => (int)$_POST['id']] : [];
                require_csrf_token('admin/care', $redirectParams);
                $topicIds = isset($_POST['topic_ids']) && is_array($_POST['topic_ids']) ? $_POST['topic_ids'] : [];
                $data = [
                    'title' => trim($_POST['title'] ?? ''),
                    'slug' => trim($_POST['slug'] ?? ''),
                    'summary' => $_POST['summary'] ?? null,
                    'content' => $_POST['content'] ?? '',
                    'is_published' => isset($_POST['is_published']),
                ];
                if ($data['title'] && $data['content']) {
                    if (!empty($_POST['id'])) {
                        update_care_article($pdo, (int)$_POST['id'], $data, $topicIds);
                        flash('success', 'Artikel aktualisiert.');
                    } else {
                        create_care_article($pdo, $data, $topicIds);
                        flash('success', 'Artikel erstellt.');
                    }
                    redirect('admin/care');
                }
                flash('error', 'Bitte formulieren Sie einen Titel und den vollständigen Artikelinhalt.');
            } elseif ($action === 'save_care_topic') {
                $redirectParams = !empty($_POST['id']) ? ['edit_topic' => (int)$_POST['id']] : [];
                require_csrf_token('admin/care', $redirectParams);
                $topicData = [
                    'title' => trim($_POST['topic_title'] ?? ''),
                    'slug' => trim($_POST['topic_slug'] ?? ''),
                    'description' => $_POST['topic_description'] ?? null,
                    'parent_id' => $_POST['topic_parent_id'] ?? null,
                ];
                if ($topicData['title'] === '') {
                    flash('error', 'Bitte vergeben Sie einen Themen-Titel.');
                } else {
                    if (!empty($_POST['id'])) {
                        update_care_topic($pdo, (int)$_POST['id'], $topicData);
                        flash('success', 'Thema aktualisiert.');
                        redirect('admin/care', ['edit_topic' => (int)$_POST['id']]);
                    } else {
                        create_care_topic($pdo, $topicData);
                        flash('success', 'Thema angelegt.');
                        redirect('admin/care');
                    }
                }
            } elseif ($action === 'delete_care_topic') {
                require_csrf_token('admin/care');
                $topicId = (int)($_POST['topic_id'] ?? 0);
                if ($topicId) {
                    delete_care_topic($pdo, $topicId);
                    flash('success', 'Thema gelöscht.');
                }
                redirect('admin/care');
            }
        }
        $settings = get_all_settings($pdo);
        $careArticles = get_care_articles($pdo);
        $editArticle = null;
        if (isset($_GET['edit'])) {
            $editArticle = get_care_article($pdo, (int)$_GET['edit']);
        }
        $topicsTree = get_care_topics_hierarchy($pdo);
        $flatTopics = get_flat_care_topics($pdo);
        $topicToEdit = null;
        if (isset($_GET['edit_topic'])) {
            $topicToEdit = get_care_topic($pdo, (int)$_GET['edit_topic']);
        }
        $flashSuccess = flash('success');
        $flashError = flash('error');
        view('admin/care', compact('settings', 'careArticles', 'editArticle', 'topicsTree', 'flatTopics', 'topicToEdit', 'flashSuccess', 'flashError'));
        break;

    case 'admin/genetics':
        require_login();
        if (!is_authorized('can_manage_settings')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formType = $_POST['form_type'] ?? '';
            if ($formType === 'species') {
                $redirectParams = !empty($_POST['id']) ? ['edit_species' => (int)$_POST['id']] : [];
                require_csrf_token('admin/genetics', $redirectParams);
                $data = [
                    'name' => trim($_POST['name'] ?? ''),
                    'slug' => trim($_POST['slug'] ?? ''),
                    'scientific_name' => trim($_POST['scientific_name'] ?? ''),
                    'description' => $_POST['description'] ?? '',
                ];
                if ($data['name'] === '') {
                    flash('error', 'Bitte benennen Sie die Art, bevor Sie speichern.');
                } else {
                    try {
                        if (!empty($_POST['id'])) {
                            update_genetic_species($pdo, (int)$_POST['id'], $data);
                            $species = get_genetic_species_by_id($pdo, (int)$_POST['id']);
                            flash('success', 'Art aktualisiert.');
                        } else {
                            $newId = create_genetic_species($pdo, $data);
                            $species = get_genetic_species_by_id($pdo, $newId);
                            flash('success', 'Art angelegt.');
                        }
                        $slug = $species['slug'] ?? null;
                        redirect('admin/genetics', $slug ? ['species' => $slug] : []);
                    } catch (Throwable $e) {
                        flash('error', 'Art konnte nicht gespeichert werden.');
                    }
                }
            } elseif ($formType === 'gene') {
                $speciesId = (int)($_POST['species_id'] ?? 0);
                $speciesSlugParam = $_POST['species_slug'] ?? null;
                $redirectParams = $speciesSlugParam ? ['species' => $speciesSlugParam] : [];
                if (!empty($_POST['id'])) {
                    $redirectParams['edit_gene'] = (int)$_POST['id'];
                }
                require_csrf_token('admin/genetics', $redirectParams);
                $data = [
                    'species_id' => $speciesId,
                    'name' => trim($_POST['name'] ?? ''),
                    'slug' => trim($_POST['slug'] ?? ''),
                    'shorthand' => trim($_POST['shorthand'] ?? ''),
                    'inheritance_mode' => $_POST['inheritance_mode'] ?? 'recessive',
                    'description' => $_POST['description'] ?? '',
                    'normal_label' => $_POST['normal_label'] ?? '',
                    'heterozygous_label' => $_POST['heterozygous_label'] ?? '',
                    'homozygous_label' => $_POST['homozygous_label'] ?? '',
                    'display_order' => (int)($_POST['display_order'] ?? 0),
                ];
                if ($data['name'] === '' || $speciesId <= 0) {
                    flash('error', 'Bitte wählen Sie eine Art aus und vergeben Sie einen Namen für das Gen.');
                } else {
                    try {
                        if (!empty($_POST['id'])) {
                            update_genetic_gene($pdo, (int)$_POST['id'], $data);
                            flash('success', 'Gen aktualisiert.');
                            $gene = get_genetic_gene($pdo, (int)$_POST['id']);
                        } else {
                            $newId = create_genetic_gene($pdo, $data);
                            flash('success', 'Gen angelegt.');
                            $gene = get_genetic_gene($pdo, $newId);
                        }
                        $species = $gene ? get_genetic_species_by_id($pdo, (int)$gene['species_id']) : null;
                        $slug = $species['slug'] ?? null;
                        redirect('admin/genetics', $slug ? ['species' => $slug] : []);
                    } catch (Throwable $e) {
                        flash('error', 'Gen konnte nicht gespeichert werden.');
                    }
                }
            } elseif ($formType === 'delete_species') {
                require_csrf_token('admin/genetics');
                $speciesId = (int)($_POST['species_id'] ?? 0);
                if ($speciesId) {
                    $species = get_genetic_species_by_id($pdo, $speciesId);
                    if ($species) {
                        delete_genetic_species($pdo, $speciesId);
                        flash('success', 'Art entfernt.');
                    }
                }
                redirect('admin/genetics');
            } elseif ($formType === 'delete_gene') {
                $speciesSlugParam = $_POST['species_slug'] ?? null;
                $redirectParams = $speciesSlugParam ? ['species' => $speciesSlugParam] : [];
                require_csrf_token('admin/genetics', $redirectParams);
                $geneId = (int)($_POST['gene_id'] ?? 0);
                if ($geneId) {
                    $gene = get_genetic_gene($pdo, $geneId);
                    if ($gene) {
                        delete_genetic_gene($pdo, $geneId);
                        $species = get_genetic_species_by_id($pdo, (int)$gene['species_id']);
                        $slug = $species['slug'] ?? $speciesSlugParam;
                        flash('success', 'Gen entfernt.');
                        redirect('admin/genetics', $slug ? ['species' => $slug] : []);
                    }
                }
                redirect('admin/genetics', $redirectParams);
            }
        }
        $settings = get_all_settings($pdo);
        $speciesList = get_genetic_species($pdo);
        $selectedSlug = $_GET['species'] ?? $_POST['species_slug'] ?? ($speciesList[0]['slug'] ?? null);
        $selectedSpecies = $selectedSlug ? get_genetic_species_by_slug($pdo, $selectedSlug) : null;
        if (!$selectedSpecies && !empty($speciesList)) {
            $selectedSpecies = get_genetic_species_by_id($pdo, (int)$speciesList[0]['id']);
            $selectedSlug = $speciesList[0]['slug'];
        }
        $editSpecies = null;
        if (isset($_GET['edit_species'])) {
            $editSpecies = get_genetic_species_by_id($pdo, (int)$_GET['edit_species']);
            if ($editSpecies) {
                $selectedSpecies = $editSpecies;
                $selectedSlug = $editSpecies['slug'];
            }
        }
        $genes = $selectedSpecies ? get_genetic_genes($pdo, (int)$selectedSpecies['id']) : [];
        $editGene = null;
        if (isset($_GET['edit_gene'])) {
            $editGene = get_genetic_gene($pdo, (int)$_GET['edit_gene']);
            if ($editGene && (!$selectedSpecies || (int)$selectedSpecies['id'] !== (int)$editGene['species_id'])) {
                $selectedSpecies = get_genetic_species_by_id($pdo, (int)$editGene['species_id']);
                $selectedSlug = $selectedSpecies['slug'] ?? $selectedSlug;
                $genes = $selectedSpecies ? get_genetic_genes($pdo, (int)$selectedSpecies['id']) : [];
            }
        }

        $flashSuccess = flash('success');
        $flashError = flash('error');
        view('admin/genetics', [
            'settings' => $settings,
            'speciesList' => $speciesList,
            'selectedSpecies' => $selectedSpecies,
            'selectedSpeciesSlug' => $selectedSlug,
            'genes' => $genes,
            'editSpecies' => $editSpecies,
            'editGene' => $editGene,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
        ]);
        break;

    case 'admin/inquiries':
        require_login();
        if (!is_authorized('can_manage_adoptions')) {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }
        $inquiries = get_inquiries($pdo);
        $settings = get_all_settings($pdo);
        view('admin/inquiries', compact('inquiries', 'settings'));
        break;

    case 'admin/users':
        require_login();
        if (current_user()['role'] !== 'admin') {
            flash('error', 'Keine Berechtigung.');
            redirect('admin/dashboard');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['action'] ?? '') === 'delete_user') {
                require_csrf_token('admin/users');
                $userId = (int)($_POST['user_id'] ?? 0);
                if ($userId && $userId !== (int)current_user()['id']) {
                    delete_user($pdo, $userId);
                    flash('success', 'Benutzer gelöscht.');
                }
                redirect('admin/users');
            }

            $redirectParams = !empty($_POST['id']) ? ['edit' => (int)$_POST['id']] : [];
            require_csrf_token('admin/users', $redirectParams);
            $data = $_POST;
            if (!empty($data['id'])) {
                update_user($pdo, (int)$data['id'], $data);
                flash('success', 'Benutzer aktualisiert.');
            } else {
                create_user($pdo, $data);
                flash('success', 'Benutzer erstellt.');
            }
            redirect('admin/users');
        }
        $users = get_users($pdo);
        $settings = get_all_settings($pdo);
        $editUser = null;
        if (isset($_GET['edit'])) {
            $editUser = get_user($pdo, (int)$_GET['edit']);
        }
        $flashSuccess = flash('success');
        view('admin/users', compact('users', 'editUser', 'flashSuccess', 'settings'));
        break;

    default:
        http_response_code(404);
        view('errors/404', ['settings' => get_all_settings($pdo)]);
}

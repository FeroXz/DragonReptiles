<?php
declare(strict_types=1);

session_start();

$projectRoot = dirname(__DIR__, 3);
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', $projectRoot);
}

require_once PROJECT_ROOT . '/app/config.php';
require_once PROJECT_ROOT . '/app/database.php';
require_once PROJECT_ROOT . '/app/helpers.php';
require_once PROJECT_ROOT . '/app/menu.php';

header('Cache-Control: no-store, no-cache, must-revalidate');

$pdo = get_database_connection();
ensure_menu_schema($pdo);

function respond(array $payload, int $status = 200): void
{
    if (!isset($payload['csrf'])) {
        $payload['csrf'] = csrf_token();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function require_settings_access(): void
{
    $user = current_user();
    if (!$user) {
        respond(['error' => 'Nicht angemeldet.'], 401);
    }
    if (!is_authorized('can_manage_settings')) {
        respond(['error' => 'Keine Berechtigung.'], 403);
    }
}

function require_csrf_header(): void
{
    $header = $_SERVER['HTTP_X_CSRF'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!verify_csrf_token($header)) {
        respond(['error' => 'CSRF-Überprüfung fehlgeschlagen.'], 419);
    }
}

$action = $_GET['action'] ?? '';
require_settings_access();

switch ($action) {
    case 'index':
        $items = get_all_menu_items($pdo);
        respond(['ok' => true, 'data' => $items]);
        break;

    case 'create':
        require_csrf_header();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(['error' => 'Methode nicht erlaubt.'], 405);
        }
        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            respond(['error' => 'Ungültige Daten.'], 422);
        }
        try {
            $item = create_menu_item($pdo, $payload);
            respond(['ok' => true, 'data' => $item]);
        } catch (InvalidArgumentException $exception) {
            $errors = json_decode($exception->getMessage(), true);
            respond(['error' => 'Validierung fehlgeschlagen.', 'details' => $errors], 422);
        } catch (Throwable $exception) {
            respond(['error' => 'Eintrag konnte nicht erstellt werden.'], 500);
        }
        break;

    case 'update':
        require_csrf_header();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(['error' => 'Methode nicht erlaubt.'], 405);
        }
        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload) || empty($payload['id'])) {
            respond(['error' => 'Ungültige Daten.'], 422);
        }
        $id = (int)$payload['id'];
        try {
            $item = update_menu_item($pdo, $id, $payload);
            respond(['ok' => true, 'data' => $item]);
        } catch (InvalidArgumentException $exception) {
            $errors = json_decode($exception->getMessage(), true);
            respond(['error' => 'Validierung fehlgeschlagen.', 'details' => $errors], 422);
        } catch (RuntimeException $exception) {
            respond(['error' => 'Eintrag nicht gefunden.'], 404);
        } catch (Throwable $exception) {
            respond(['error' => 'Eintrag konnte nicht aktualisiert werden.'], 500);
        }
        break;

    case 'delete':
        require_csrf_header();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(['error' => 'Methode nicht erlaubt.'], 405);
        }
        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload) || empty($payload['id'])) {
            respond(['error' => 'Ungültige Daten.'], 422);
        }
        $id = (int)$payload['id'];
        delete_menu_item($pdo, $id);
        respond(['ok' => true]);
        break;

    case 'toggle':
        require_csrf_header();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(['error' => 'Methode nicht erlaubt.'], 405);
        }
        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload) || empty($payload['id'])) {
            respond(['error' => 'Ungültige Daten.'], 422);
        }
        $id = (int)$payload['id'];
        $item = toggle_menu_visibility($pdo, $id);
        if (!$item) {
            respond(['error' => 'Eintrag nicht gefunden.'], 404);
        }
        respond(['ok' => true, 'data' => $item]);
        break;

    case 'reorder':
        require_csrf_header();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(['error' => 'Methode nicht erlaubt.'], 405);
        }
        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            respond(['error' => 'Ungültige Daten.'], 422);
        }
        $items = $payload;
        if (isset($payload['items']) && is_array($payload['items'])) {
            $items = $payload['items'];
        }
        if (empty($items)) {
            respond(['error' => 'Keine Einträge übermittelt.'], 422);
        }
        $first = reset($items);
        if (!is_array($first) || !isset($first['id'])) {
            respond(['error' => 'Ungültige Sortierdaten.'], 422);
        }
        $locations = [];
        $orderedByLocation = [];
        foreach ($items as $entry) {
            if (!is_array($entry) || empty($entry['id'])) {
                respond(['error' => 'Ungültige Sortierdaten.'], 422);
            }
            $id = (int)$entry['id'];
            $menuItem = menu_item_by_id($pdo, $id);
            if (!$menuItem) {
                respond(['error' => 'Eintrag nicht gefunden.'], 404);
            }
            $location = $menuItem['location'] ?? 'frontend';
            $locations[$location] = true;
            $orderedByLocation[$location][] = [
                'id' => $id,
                'position' => isset($entry['position']) ? (int)$entry['position'] : null,
            ];
        }
        if (count($locations) !== 1) {
            respond(['error' => 'Sortierung darf nur innerhalb eines Bereichs erfolgen.'], 422);
        }
        $location = array_key_first($orderedByLocation);
        usort($orderedByLocation[$location], static function ($a, $b) {
            return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
        });
        $ids = array_column($orderedByLocation[$location], 'id');
        reorder_menu_items($pdo, $location, $ids);
        respond(['ok' => true]);
        break;

    default:
        respond(['error' => 'Unbekannte Aktion.'], 400);
}

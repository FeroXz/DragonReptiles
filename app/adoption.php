<?php

function normalize_listing_status(?string $status): string
{
    $allowed = ['available', 'reserved', 'adopted'];
    $normalized = strtolower(trim((string)$status));
    return in_array($normalized, $allowed, true) ? $normalized : 'available';
}

function create_listing(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare('INSERT INTO adoption_listings(animal_id, title, species, species_slug, genetics, genetics_profile, price, description, image_path, status, contact_email) VALUES (:animal_id, :title, :species, :species_slug, :genetics, :genetics_profile, :price, :description, :image_path, :status, :contact_email)');
    $stmt->execute([
        'animal_id' => normalize_nullable_id($data['animal_id'] ?? null),
        'title' => trim($data['title'] ?? ''),
        'species' => trim($data['species'] ?? '') ?: null,
        'species_slug' => $data['species_slug'] ?? null,
        'genetics' => trim($data['genetics'] ?? '') ?: null,
        'genetics_profile' => $data['genetics_profile'] ?? null,
        'price' => ($price = trim((string)($data['price'] ?? ''))) === '' ? null : $price,
        'description' => $data['description'] ?? null,
        'image_path' => $data['image_path'] ?? null,
        'status' => normalize_listing_status($data['status'] ?? 'available'),
        'contact_email' => ($email = trim($data['contact_email'] ?? '')) === '' ? null : $email,
    ]);
}

function update_listing(PDO $pdo, int $id, array $data): void
{
    $stmt = $pdo->prepare('UPDATE adoption_listings SET animal_id = :animal_id, title = :title, species = :species, species_slug = :species_slug, genetics = :genetics, genetics_profile = :genetics_profile, price = :price, description = :description, image_path = :image_path, status = :status, contact_email = :contact_email WHERE id = :id');
    $stmt->execute([
        'animal_id' => normalize_nullable_id($data['animal_id'] ?? null),
        'title' => trim($data['title'] ?? ''),
        'species' => trim($data['species'] ?? '') ?: null,
        'species_slug' => $data['species_slug'] ?? null,
        'genetics' => trim($data['genetics'] ?? '') ?: null,
        'genetics_profile' => $data['genetics_profile'] ?? null,
        'price' => ($price = trim((string)($data['price'] ?? ''))) === '' ? null : $price,
        'description' => $data['description'] ?? null,
        'image_path' => $data['image_path'] ?? null,
        'status' => normalize_listing_status($data['status'] ?? 'available'),
        'contact_email' => ($email = trim($data['contact_email'] ?? '')) === '' ? null : $email,
        'id' => $id,
    ]);
}

function delete_listing(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM adoption_listings WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function get_listing(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM adoption_listings WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $listing = $stmt->fetch();
    return $listing ?: null;
}

function get_listings(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM adoption_listings ORDER BY created_at DESC')->fetchAll();
}

function get_public_listings(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM adoption_listings WHERE status != "adopted" ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function create_inquiry(PDO $pdo, array $data): void
{
    $listingId = normalize_nullable_id($data['listing_id'] ?? null);
    if (!$listingId) {
        throw new InvalidArgumentException('Bitte wählen Sie einen gültigen Abgabeeintrag aus.');
    }

    if (!get_listing($pdo, $listingId)) {
        throw new InvalidArgumentException('Der ausgewählte Abgabeeintrag ist nicht mehr verfügbar.');
    }

    $interestedIn = isset($data['interested_in']) ? trim((string)$data['interested_in']) : null;
    if ($interestedIn === '') {
        $interestedIn = null;
    }

    $name = trim($data['sender_name'] ?? '');
    $email = trim($data['sender_email'] ?? '');
    $message = trim($data['message'] ?? '');

    $stmt = $pdo->prepare('INSERT INTO adoption_inquiries(listing_id, interested_in, sender_name, sender_email, message) VALUES (:listing_id, :interested_in, :sender_name, :sender_email, :message)');
    $stmt->execute([
        'listing_id' => $listingId,
        'interested_in' => $interestedIn,
        'sender_name' => $name,
        'sender_email' => $email,
        'message' => $message,
    ]);
}

function get_inquiries(PDO $pdo): array
{
    $sql = 'SELECT adoption_inquiries.*, adoption_listings.title as listing_title FROM adoption_inquiries JOIN adoption_listings ON adoption_listings.id = adoption_inquiries.listing_id ORDER BY adoption_inquiries.created_at DESC';
    return $pdo->query($sql)->fetchAll();
}

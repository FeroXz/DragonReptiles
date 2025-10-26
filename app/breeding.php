<?php
function create_breeding_plan(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO breeding_plans(title, season, notes, expected_genetics, incubation_notes) VALUES (:title, :season, :notes, :expected_genetics, :incubation_notes)');
    $stmt->execute([
        'title' => $data['title'],
        'season' => $data['season'] ?? null,
        'notes' => $data['notes'] ?? null,
        'expected_genetics' => $data['expected_genetics'] ?? null,
        'incubation_notes' => $data['incubation_notes'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
}

function update_breeding_plan(PDO $pdo, int $id, array $data): void
{
    $stmt = $pdo->prepare('UPDATE breeding_plans SET title = :title, season = :season, notes = :notes, expected_genetics = :expected_genetics, incubation_notes = :incubation_notes WHERE id = :id');
    $stmt->execute([
        'title' => $data['title'],
        'season' => $data['season'] ?? null,
        'notes' => $data['notes'] ?? null,
        'expected_genetics' => $data['expected_genetics'] ?? null,
        'incubation_notes' => $data['incubation_notes'] ?? null,
        'id' => $id,
    ]);
}

function delete_breeding_plan(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM breeding_plans WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function get_breeding_plan(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM breeding_plans WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $plan = $stmt->fetch();
    if (!$plan) {
        return null;
    }
    $plan['parents'] = get_breeding_parents($pdo, $id);
    return $plan;
}

function get_breeding_plans(PDO $pdo): array
{
    $plans = $pdo->query('SELECT * FROM breeding_plans ORDER BY created_at DESC')->fetchAll();
    foreach ($plans as &$plan) {
        $plan['parents'] = get_breeding_parents($pdo, (int)$plan['id']);
    }
    return $plans;
}

function add_breeding_parent(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare('INSERT INTO breeding_plan_parents(plan_id, parent_type, animal_id, name, sex, species, genetics, notes) VALUES (:plan_id, :parent_type, :animal_id, :name, :sex, :species, :genetics, :notes)');
    $stmt->execute([
        'plan_id' => (int)($data['plan_id'] ?? 0),
        'parent_type' => $data['parent_type'],
        'animal_id' => $data['parent_type'] === 'animal' ? normalize_nullable_id($data['animal_id'] ?? null) : null,
        'name' => $data['name'] ?? null,
        'sex' => $data['sex'] ?? null,
        'species' => $data['species'] ?? null,
        'genetics' => $data['genetics'] ?? null,
        'notes' => $data['notes'] ?? null,
    ]);
}

function delete_breeding_parent(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM breeding_plan_parents WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function get_breeding_parents(PDO $pdo, int $planId): array
{
    $stmt = $pdo->prepare('SELECT breeding_plan_parents.*, animals.name AS animal_name, animals.species AS animal_species, animals.genetics AS animal_genetics FROM breeding_plan_parents LEFT JOIN animals ON animals.id = breeding_plan_parents.animal_id WHERE plan_id = :plan ORDER BY breeding_plan_parents.id ASC');
    $stmt->execute(['plan' => $planId]);
    return $stmt->fetchAll();
}


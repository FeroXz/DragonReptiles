#!/usr/bin/env node
import { promises as fs } from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const filePath = fileURLToPath(import.meta.url);
const scriptDir = path.dirname(filePath);
const projectRoot = path.resolve(scriptDir, '..');
const seedsDirectory = path.join(projectRoot, 'storage', 'seeds');
const manifestPath = path.join(seedsDirectory, 'manifest.json');

const seedsBaseLabel = toPosix(path.relative(projectRoot, seedsDirectory)) || 'seeds';

function toPosix(value) {
    return value.split(path.sep).join('/');
}

async function pathExists(targetPath) {
    try {
        await fs.access(targetPath);
        return true;
    } catch {
        return false;
    }
}

async function loadManifest() {
    if (!(await pathExists(manifestPath))) {
        return null;
    }

    const raw = await fs.readFile(manifestPath, 'utf8');
    let data;
    try {
        data = JSON.parse(raw);
    } catch (error) {
        throw new Error(
            `Die Manifest-Datei ${toPosix(path.relative(projectRoot, manifestPath))} enthält ungültiges JSON: ${error.message}`,
        );
    }

    const entries = Array.isArray(data) ? data : data?.required;
    if (!Array.isArray(entries)) {
        throw new Error(
            `Die Manifest-Datei ${toPosix(path.relative(projectRoot, manifestPath))} muss entweder ein Array oder ein Objekt mit dem Feld "required" enthalten.`,
        );
    }

    return entries.map((entry) => normalizeManifestEntry(entry));
}

function normalizeManifestEntry(entry) {
    if (typeof entry !== 'string' || entry.trim() === '') {
        throw new Error('Ein Eintrag im Seed-Manifest ist leer oder kein String.');
    }

    const cleaned = entry.replace(/\\/g, '/').replace(/^\.\/+/, '').trim();
    const normalized = toPosix(path.posix.normalize(cleaned));

    if (normalized.startsWith('..')) {
        throw new Error(
            `Ungültiger Manifest-Eintrag "${entry}": Pfade außerhalb des Seed-Verzeichnisses sind nicht erlaubt.`,
        );
    }

    return normalized;
}

async function collectSeedFiles(currentDirectory = seedsDirectory) {
    const entries = await fs.readdir(currentDirectory, { withFileTypes: true });
    const collected = [];

    for (const entry of entries) {
        if (entry.name.startsWith('.')) {
            continue;
        }

        const fullPath = path.join(currentDirectory, entry.name);
        const relative = toPosix(path.relative(seedsDirectory, fullPath));

        if (entry.isDirectory()) {
            const nested = await collectSeedFiles(fullPath);
            for (const nestedEntry of nested) {
                collected.push(nestedEntry);
            }
        } else if (entry.isFile()) {
            collected.push(relative);
        }
    }

    return collected;
}

function printList(items, heading) {
    if (!items.length) {
        return;
    }

    console.log(`\n${heading}`);
    for (const item of items) {
        console.log(`  • ${seedsBaseLabel}/${item}`);
    }
}

async function main() {
    console.log(`📂 Seed-Verzeichnis: ${toPosix(path.relative(projectRoot, seedsDirectory)) || '.'}`);

    if (!(await pathExists(seedsDirectory))) {
        console.error('✖️  Das Seed-Verzeichnis existiert nicht. Lege die benötigten Dateien unter storage/seeds/ ab.');
        process.exitCode = 1;
        return;
    }

    let manifestEntries = null;
    try {
        manifestEntries = await loadManifest();
    } catch (error) {
        console.error(`✖️  ${error.message}`);
        process.exitCode = 1;
        return;
    }

    const availableFiles = await collectSeedFiles();
    const availableSet = new Set(availableFiles);

    if (manifestEntries) {
        const requiredSet = new Set(manifestEntries);
        const missing = manifestEntries.filter((entry) => !availableSet.has(entry));
        const present = manifestEntries.filter((entry) => availableSet.has(entry));
        const unexpected = availableFiles.filter((entry) => !requiredSet.has(entry));

        printList(present, '✅ Gefundene Seeds:');
        printList(missing, '❌ Fehlende Seeds:');
        printList(unexpected, '⚠️ Zusätzliche Dateien (nicht im Manifest):');

        if (missing.length > 0) {
            console.error('\nErgänze die fehlenden Seed-Dateien, damit der initiale Datenbestand vollständig importiert werden kann.');
            process.exitCode = 1;
        } else {
            console.log('\nAlle Seed-Dateien aus dem Manifest sind vorhanden.');
        }
    } else {
        if (availableFiles.length === 0) {
            console.error('❌ Es wurden keine Seed-Dateien gefunden. Lege die Seeds unter storage/seeds/ ab oder definiere ein Manifest.');
            process.exitCode = 1;
        } else {
            printList(availableFiles, '✅ Gefundene Seed-Dateien:');
            console.log('\nHinweis: Ohne Manifest kann nicht geprüft werden, ob weitere Seeds fehlen.');
        }
    }
}

main().catch((error) => {
    console.error('✖️  Unerwarteter Fehler beim Prüfen der Seed-Dateien:', error);
    process.exitCode = 1;
});

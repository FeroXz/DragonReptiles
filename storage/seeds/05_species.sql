-- seed: genetic_species
DELETE FROM genetic_genes;
DELETE FROM genetic_species;
DELETE FROM sqlite_sequence WHERE name IN ('genetic_species','genetic_genes');

INSERT INTO genetic_species (id, name, slug, scientific_name, description, created_at, updated_at) VALUES
    (1, 'Bartagame', 'pogona-vitticeps', 'Pogona vitticeps', 'Australische Wüstenbewohner mit ausgeprägten Sozialstrukturen und vielfältigen Farbformen.', '2024-01-03 08:00:00', '2024-01-03 08:00:00'),
    (2, 'Hakennasennatter', 'heterodon-nasicus', 'Heterodon nasicus', 'Robuste nordamerikanische Kolubriden mit spannenden Farbprojekten.', '2024-01-03 08:05:00', '2024-01-03 08:05:00'),
    (3, 'Königspython', 'python-regius', 'Python regius', 'Beliebte Art mit hunderten dokumentierten Morphen und einer aktiven Community.', '2024-01-03 08:10:00', '2024-01-03 08:10:00'),
    (4, 'Leopardgecko', 'eublepharis-macularius', 'Eublepharis macularius', 'Einer der Klassiker in der Terraristik mit vielen etablierten Linien.', '2024-01-03 08:15:00', '2024-01-03 08:15:00'),
    (5, 'Grüner Baumpython', 'morelia-viridis', 'Morelia viridis', 'Spektakuläre Färbungen und locale-spezifische Linien mit genetischem Potenzial.', '2024-01-03 08:20:00', '2024-01-03 08:20:00'),
    (6, 'Kornnatter', 'pantherophis-guttatus', 'Pantherophis guttatus', 'Extrem variantenreiche Art mit intensiver Hybridzuchtgeschichte.', '2024-01-03 08:25:00', '2024-01-03 08:25:00'),
    (7, 'Blauzungenskink', 'tiliqua-scincoides', 'Tiliqua scincoides', 'Australischer Skink mit markanten Mustern und lokal angepassten Farbformen.', '2024-01-03 08:30:00', '2024-01-03 08:30:00'),
    (8, 'Gila-Krustenechse', 'heloderma-suspectum', 'Heloderma suspectum', 'Seltene, giftige Echse mit dokumentierten Farbmutationen in Privathaltung.', '2024-01-03 08:35:00', '2024-01-03 08:35:00'),
    (9, 'Stachelschwanzwaran', 'varanus-acanthurus', 'Varanus acanthurus', 'Aktive Warane, bei denen Farbunterschiede oft lokalitätsgebunden sind.', '2024-01-03 08:40:00', '2024-01-03 08:40:00'),
    (10, 'Königsnatter', 'lampropeltis-triangulum', 'Lampropeltis triangulum', 'Kontrastreiche Muster in zahllosen Morphen, beliebt für Kombinationsprojekte.', '2024-01-03 08:45:00', '2024-01-03 08:45:00');
-- end

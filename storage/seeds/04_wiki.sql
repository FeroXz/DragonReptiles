-- seed: care
DELETE FROM care_article_topic;
DELETE FROM care_articles;
DELETE FROM care_topics;
DELETE FROM sqlite_sequence WHERE name IN ('care_topics','care_articles');

INSERT INTO care_topics (id, title, slug, description, parent_id, created_at, updated_at) VALUES
    (1, 'Grundlagen', 'grundlagen', 'Basiswissen für alle Halter*innen – von der Vorbereitung bis zur Routinepflege.', NULL, '2024-01-01 09:00:00', '2024-01-01 09:00:00'),
    (2, 'Ernährung', 'ernaehrung', 'Fütterungsstrategien, Supplementierung und saisonale Anpassungen.', 1, '2024-01-01 09:05:00', '2024-01-01 09:05:00'),
    (3, 'Terrarium-Technik', 'technik', 'Licht, Klimaautomation und Sicherheitskonzepte für Terrarien.', 1, '2024-01-01 09:10:00', '2024-01-01 09:10:00'),
    (4, 'Gesundheit', 'gesundheit', 'Früherkennung, Diagnostik und Erste-Hilfe-Maßnahmen.', 1, '2024-01-01 09:15:00', '2024-01-01 09:15:00'),
    (5, 'Zucht', 'zucht', 'Planung, Brutpflege und Inkubationsstrategien für unterschiedliche Arten.', 1, '2024-01-01 09:20:00', '2024-01-01 09:20:00'),
    (6, 'Quarantäne', 'quarantaene', 'Protokolle zur sicheren Eingewöhnung neuer Tiere.', 4, '2024-01-01 09:25:00', '2024-01-01 09:25:00'),
    (7, 'Verhalten', 'verhalten', 'Signale richtig deuten und Stress vermeiden.', 1, '2024-01-01 09:30:00', '2024-01-01 09:30:00'),
    (8, 'Jungtierpflege', 'jungtiere', 'Optimierte Pflege für die ersten Lebensmonate.', 5, '2024-01-01 09:35:00', '2024-01-01 09:35:00'),
    (9, 'Hygiene & Reinigung', 'hygiene', 'Reinigungszyklen, Desinfektion und Monitoring.', 3, '2024-01-01 09:40:00', '2024-01-01 09:40:00');

INSERT INTO care_articles (id, title, slug, summary, content, is_published, created_at, updated_at) VALUES
    (1, 'Setup-Checkliste vor dem Einzug', 'setup-checkliste', 'Alles, was vor dem Einzug eines neuen Tieres vorbereitet sein muss.', '<p>Von der Quarantänebox über Temperaturgradienten bis zur Dokumentation – diese Checkliste sorgt für einen stressfreien Start.</p>', 1, '2024-01-02 08:00:00', '2024-01-02 08:00:00'),
    (2, 'UVB-Licht richtig einsetzen', 'uvb-licht', 'So wählst du UVB-Lampen, misst die Intensität und planst Austauschzyklen.', '<p>Wir zeigen Messprotokolle, Vergleichskurven und eine Wartungstabelle für unterschiedliche Lampentypen.</p>', 1, '2024-01-02 08:30:00', '2024-01-02 08:30:00'),
    (3, 'Futterplan für Bartagamen', 'futterplan-bartagamen', 'Optimierter Wochenplan mit Greens, Insekten und Supplementen.', '<p>Der Plan berücksichtigt Wachstumsphasen, saisonale Schwankungen und liefert Einkaufsempfehlungen.</p>', 1, '2024-01-02 09:00:00', '2024-01-02 09:00:00'),
    (4, 'Hakennasennatter: Ratten vs. Mäuse', 'hakennasennatter-futter', 'Welche Futtertiere wann sinnvoll sind und wie man Übergänge begleitet.', '<p>Detaillierte Beobachtungsprotokolle helfen dabei, das Gewicht im Blick zu behalten und Stress zu minimieren.</p>', 1, '2024-01-02 09:30:00', '2024-01-02 09:30:00'),
    (5, 'Inkubation bei 31°C', 'inkubation-31-grad', 'Standardisierte Brutparameter für gängige Kornnatter-Linien.', '<p>Wir dokumentieren Luftfeuchte, Substratvarianten und unser Alarm-Setup für Temperaturabweichungen.</p>', 1, '2024-01-02 10:00:00', '2024-01-02 10:00:00'),
    (6, 'Quarantäne in zehn Schritten', 'quarantaene-zehn-schritte', 'Vom Gesundheitscheck bis zum abschließenden Freigabetest.', '<p>Der Artikel enthält Checklisten, Laborprofile und Beispiele für digitale Monitoring-Formulare.</p>', 1, '2024-01-02 10:30:00', '2024-01-02 10:30:00'),
    (7, 'Stresssignale erkennen', 'stresssignale', 'Körpersprache, Färbung und Aktivität richtig einordnen.', '<p>Wir kombinieren Videoanalysen mit kommentierten Bildern und schlagen Gegenmaßnahmen vor.</p>', 1, '2024-01-02 11:00:00', '2024-01-02 11:00:00'),
    (8, 'Feuchtigkeitsmanagement im Terrarium', 'feuchtigkeit-terrarium', 'Sensorik, Nebelanlage und Luftzirkulation feinjustieren.', '<p>Dieser Beitrag liefert Diagramme aus unseren Datenloggern und konkrete Presets für verschiedene Arten.</p>', 1, '2024-01-02 11:30:00', '2024-01-02 11:30:00'),
    (9, 'Jungtiere sicher wiegen', 'jungtiere-wiegen', 'Wie oft messen, welche Behälter und wie man Messfehler reduziert.', '<p>Wir zeigen Best-Practices und eine automatisierte Auswertung in Tabellenform.</p>', 1, '2024-01-02 12:00:00', '2024-01-02 12:00:00'),
    (10, 'Supplementierung planen', 'supplement-plan', 'Kalzium, Vitamine und Spurenelemente abgestimmt auf Art und Alter.', '<p>Der Plan enthält Dosierungsbeispiele, Herstellervergleiche und Warnhinweise bei Überversorgung.</p>', 1, '2024-01-02 12:30:00', '2024-01-02 12:30:00'),
    (11, 'Notfallkarte für Tierärzt*innen', 'notfallkarte', 'Kompakter Überblick für den Ernstfall im Terrarium.', '<p>Die Karte fasst Stammdaten, bisherige Diagnosen und aktuelle Medikation pro Tier zusammen.</p>', 1, '2024-01-02 13:00:00', '2024-01-02 13:00:00'),
    (12, 'Reinigungskonzept für Großanlagen', 'reinigung-grossanlage', 'Workflow mit Zonen, Checklisten und Audit-Logbuch.', '<p>Wir erläutern, wie wir RFID-Tags, Reinigungsmittel und Temperaturüberwachung kombinieren.</p>', 1, '2024-01-02 13:30:00', '2024-01-02 13:30:00');

INSERT INTO care_article_topic (article_id, topic_id) VALUES
    (1, 1), (1, 3),
    (2, 3),
    (3, 2),
    (4, 2),
    (5, 5), (5, 8),
    (6, 6),
    (7, 7),
    (8, 3), (8, 9),
    (9, 8),
    (10, 2),
    (11, 4), (11, 6),
    (12, 9);
-- end

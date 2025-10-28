-- seed: pages
DELETE FROM pages;
DELETE FROM sqlite_sequence WHERE name = 'pages';

INSERT INTO pages (id, slug, title, content, is_published, show_in_menu, parent_id, menu_order, created_at, updated_at) VALUES
    (1, 'start', 'Willkommen bei Dragon Reptiles', '<h1>Willkommen</h1><p>Erlebe verantwortungsvolle Reptilienhaltung mit ausführlichen Leitfäden, Projekteinblicken und vielen Erfolgsgeschichten unserer Schützlinge.</p>', 1, 1, NULL, 0, '2024-01-02 08:00:00', '2024-01-02 08:00:00'),
    (2, 'ueber-uns', 'Über uns', '<h2>Unser Anspruch</h2><p>Wir kombinieren Fachwissen, moderne Technik und jahrelange Erfahrung, um Reptilienhaltung nachhaltig und transparent zu gestalten.</p>', 1, 1, NULL, 1, '2024-01-05 09:30:00', '2024-01-05 09:30:00'),
    (3, 'team', 'Unser Team', '<p>Lerne unser multidisziplinäres Team aus Biologinnen, Tierärzten und Pflegeprofis kennen. Jede Geschichte zeigt, wie vielfältig Reptilienleidenschaft sein kann.</p>', 1, 0, 2, 0, '2024-01-06 11:15:00', '2024-01-06 11:15:00'),
    (4, 'mission', 'Mission & Werte', '<p>Transparenz, Tierwohl und Community-Engagement stehen an erster Stelle. Wir dokumentieren jeden Schritt von der Aufzucht bis zur Abgabe.</p>', 1, 0, 2, 1, '2024-01-07 10:45:00', '2024-01-07 10:45:00'),
    (5, 'partner', 'Partnernetzwerk', '<p>Gemeinsam mit Tierärzt*innen, Laboren und Futterproduzenten entwickeln wir smarte Lösungen für eine gesunde Haltung.</p>', 1, 1, NULL, 2, '2024-01-08 14:10:00', '2024-01-08 14:10:00'),
    (6, 'events', 'Events & Workshops', '<ul><li>Monatliche Pflegeschulungen</li><li>Live-Demos zur Inkubation</li><li>Community-Treffen mit Q&A</li></ul>', 1, 1, NULL, 3, '2024-01-09 16:00:00', '2024-01-09 16:00:00'),
    (7, 'wissensbasis', 'Wissensbasis', '<p>Vertiefe dein Wissen mit strukturierten Artikeln, die von unserem Redaktionsteam gepflegt werden.</p>', 1, 1, NULL, 4, '2024-01-10 13:20:00', '2024-01-10 13:20:00'),
    (8, 'shop', 'Equipment-Shop', '<p>Vom Terrarium-Zubehör bis zur smarten Klimaüberwachung – hier findest du unsere getesteten Favoriten.</p>', 1, 1, NULL, 5, '2024-01-11 09:05:00', '2024-01-11 09:05:00'),
    (9, 'kontakt', 'Kontakt & Beratung', '<p>Schreibe uns eine Nachricht oder vereinbare einen Videocall für eine individuelle Haltungsberatung.</p>', 1, 1, NULL, 6, '2024-01-12 08:40:00', '2024-01-12 08:40:00'),
    (10, 'faq', 'FAQ', '<h2>Häufige Fragen</h2><p>Wie oft wird gefüttert? Welche Lampen nutzen wir? Die Antworten findest du hier mit praktischen Beispielen.</p>', 1, 1, NULL, 7, '2024-01-13 17:25:00', '2024-01-13 17:25:00'),
    (11, 'tagebuch', 'Zucht-Tagebuch', '<p>Wöchentliche Updates zu laufenden Projekten mit Brutdaten, Fütterungsprotokollen und Live-Bildern.</p>', 1, 0, NULL, 8, '2024-01-14 18:00:00', '2024-01-14 18:00:00'),
    (12, 'spenden', 'Spenden & Patenschaften', '<p>Unterstütze besondere Projekte oder übernimm eine Patenschaft für Langzeitpfleglinge.</p>', 0, 0, NULL, 9, '2024-01-15 09:55:00', '2024-01-15 09:55:00');
-- end

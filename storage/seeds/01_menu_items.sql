-- seed: menu_items
DELETE FROM menu_items;
DELETE FROM sqlite_sequence WHERE name = 'menu_items';

INSERT INTO menu_items (id, label, path, icon, visible, position, target, location) VALUES
    (1, 'Startseite', '/index.php', 'home', 1, 0, '_self', 'frontend'),
    (2, 'Tierbestand', '/index.php?route=animals', 'animals', 1, 1, '_self', 'frontend'),
    (3, 'Zuchtplanung', '/index.php?route=breeding', 'breeding', 1, 2, '_self', 'frontend'),
    (4, 'Adoption', '/index.php?route=adoption', 'adoption', 1, 3, '_self', 'frontend'),
    (5, 'Galerie', '/index.php?route=gallery', 'gallery', 1, 4, '_self', 'frontend'),
    (6, 'Pflegeguide', '/index.php?route=care-guide', 'care', 1, 5, '_self', 'frontend'),
    (7, 'Wissensbasis', '/index.php?route=page&slug=wissensbasis', 'book', 1, 6, '_self', 'frontend'),
    (8, 'Events', '/index.php?route=page&slug=events', 'calendar', 1, 7, '_self', 'frontend'),
    (9, 'Partner', '/index.php?route=page&slug=partner', 'link', 1, 8, '_self', 'frontend'),
    (10, 'Kontakt', '/index.php?route=page&slug=kontakt', 'mail', 1, 9, '_self', 'frontend'),
    (11, 'Shop', '/index.php?route=page&slug=shop', 'shopping-bag', 1, 10, '_self', 'frontend'),
    (12, 'FAQ', '/index.php?route=page&slug=faq', 'help-circle', 1, 11, '_self', 'frontend'),
    (101, 'Admin-Dashboard', '/index.php?route=admin/dashboard', 'dashboard', 1, 0, '_self', 'admin'),
    (102, 'Tierverwaltung', '/index.php?route=admin/animals', 'animals', 1, 1, '_self', 'admin'),
    (103, 'Adoptionsliste', '/index.php?route=admin/adoption', 'adoption', 1, 2, '_self', 'admin'),
    (104, 'Medienbibliothek', '/index.php?route=admin/media', 'media', 1, 3, '_self', 'admin'),
    (105, 'Galerieverwaltung', '/index.php?route=admin/gallery', 'gallery', 1, 4, '_self', 'admin'),
    (106, 'Pflegeartikel', '/index.php?route=admin/care', 'care', 1, 5, '_self', 'admin'),
    (107, 'Seiten & Inhalte', '/index.php?route=admin/pages', 'pages', 1, 6, '_self', 'admin'),
    (108, 'Navigation', '/index.php?route=admin/menu', 'navigation', 1, 7, '_self', 'admin'),
    (109, 'Genetik', '/index.php?route=admin/genetics', 'genetics', 1, 8, '_self', 'admin'),
    (110, 'Zuchtpläne', '/index.php?route=admin/breeding', 'breeding', 1, 9, '_self', 'admin'),
    (111, 'Benutzerverwaltung', '/index.php?route=admin/users', 'users', 1, 10, '_self', 'admin'),
    (112, 'Systemeinstellungen', '/index.php?route=admin/settings', 'settings', 1, 11, '_self', 'admin'),
    (113, 'Deployments', '/index.php?route=admin/update', 'update', 1, 12, '_self', 'admin'),
    (114, 'Textbausteine', '/index.php?route=admin/content', 'content', 1, 13, '_self', 'admin');
-- end

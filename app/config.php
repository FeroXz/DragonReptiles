<?php declare(strict_types=1);

const APP_NAME = 'Dragon Reptiles CMS';
const APP_VERSION = '5.6.3';
const DATA_PATH = __DIR__ . '/../storage/database.sqlite';
const UPLOAD_PATH = __DIR__ . '/../public/uploads';
const BASE_URL = '';

const APP_REPOSITORY_ZIP = 'https://codeload.github.com/feroxz/DragonReptiles/zip/refs/heads/main';

const PASSWORD_ALGO = PASSWORD_DEFAULT;

// Security configuration
const CSRF_TOKEN_LIFETIME = 1800; // 30 minutes
const CSRF_TOKEN_LIMIT = 50;
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_TIME = 900; // 15 minutes
const SESSION_LIFETIME = 3600; // 1 hour

// File upload configuration
const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const ALLOWED_FILE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const MAX_UPLOAD_SIZE = 10485760; // 10 MB

// Date validation
const MIN_YEAR = 1900;
const MAX_YEAR_OFFSET = 1;

// Update configuration
const UPDATE_TIMEOUT = 60;
const UPDATE_LOG_CHUNK_SIZE = 4096;

// Allowed tables for slug generation (SQL injection prevention)
const ALLOWED_SLUG_TABLES = [
    'pages',
    'news_posts',
    'care_articles',
    'care_topics',
    'genetic_species',
    'home_sections',
    'gallery_items'
];

// Allowed HTML tags for rich text (XSS prevention)
const ALLOWED_HTML_TAGS = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre><img><table><thead><tbody><tr><th><td>';
const ALLOWED_HTML_ATTRIBUTES = [
    'a' => ['href', 'title', 'target'],
    'img' => ['src', 'alt', 'title', 'width', 'height'],
    'table' => ['class'],
    'td' => ['colspan', 'rowspan'],
    'th' => ['colspan', 'rowspan']
];

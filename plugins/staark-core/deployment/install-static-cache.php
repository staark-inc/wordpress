<?php
/**
 * Install conservative browser-cache rules for public static WordPress assets.
 *
 * This modifies only a marked block in ABSPATH/.htaccess and leaves the
 * normal WordPress rewrite block untouched.
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . 'wp-admin/includes/misc.php';

$htaccess = ABSPATH . '.htaccess';

if (! file_exists($htaccess)) {
    if (@touch($htaccess) === false) {
        WP_CLI::error('Could not create ' . $htaccess);
    }
}

if (! is_writable($htaccess)) {
    WP_CLI::error(
        '.htaccess is not writable: ' . $htaccess
    );
}

$current = file_get_contents($htaccess);

if (! is_string($current)) {
    WP_CLI::error('Could not read ' . $htaccess);
}

/*
 * Keep one backup before Staark begins managing this block.
 */
if (
    strpos($current, '# BEGIN Staark Hub Static Cache') === false
    && trim($current) !== ''
) {
    $backup = ABSPATH . '.htaccess.staark-backup';

    if (! file_exists($backup)) {
        if (! @copy($htaccess, $backup)) {
            WP_CLI::warning(
                'Could not create .htaccess backup, continuing.'
            );
        } else {
            WP_CLI::log(
                'Backup: ' . $backup
            );
        }
    }
}

$rules = [
    '<IfModule mod_headers.c>',
    '    <FilesMatch "\.(?:css|js|mjs|webp|avif|jpe?g|png|gif|svg|ico|woff2?|ttf|otf)$">',
    '        Header set Cache-Control "public, max-age=31536000"',
    '    </FilesMatch>',
    '</IfModule>',
    '',
    '<IfModule mod_expires.c>',
    '    ExpiresActive On',
    '',
    '    ExpiresByType text/css "access plus 1 year"',
    '    ExpiresByType application/javascript "access plus 1 year"',
    '    ExpiresByType text/javascript "access plus 1 year"',
    '',
    '    ExpiresByType image/webp "access plus 1 year"',
    '    ExpiresByType image/avif "access plus 1 year"',
    '    ExpiresByType image/jpeg "access plus 1 year"',
    '    ExpiresByType image/png "access plus 1 year"',
    '    ExpiresByType image/gif "access plus 1 year"',
    '    ExpiresByType image/svg+xml "access plus 1 year"',
    '    ExpiresByType image/x-icon "access plus 1 year"',
    '',
    '    ExpiresByType font/woff "access plus 1 year"',
    '    ExpiresByType font/woff2 "access plus 1 year"',
    '    ExpiresByType font/ttf "access plus 1 year"',
    '    ExpiresByType font/otf "access plus 1 year"',
    '</IfModule>',
];

$result = insert_with_markers(
    $htaccess,
    'Staark Hub Static Cache',
    $rules
);

if (! $result) {
    WP_CLI::error(
        'Could not install Staark static-cache rules.'
    );
}

WP_CLI::success(
    'Staark static-cache rules installed in ' . $htaccess
);

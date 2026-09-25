<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . 'wp-admin/includes/image.php';

$directory = get_theme_file_path('assets/images/showcase');
$quality   = 82;
$widths    = [480, 768];

if (! is_dir($directory)) {
    WP_CLI::error('Showcase image directory not found: ' . $directory);
}

$processed = 0;
$created   = 0;
$updated   = 0;
$skipped   = 0;
$failed    = 0;

$iterator = new DirectoryIterator($directory);

foreach ($iterator as $entry) {
    if ($entry->isDot() || ! $entry->isFile()) {
        continue;
    }

    $extension = strtolower($entry->getExtension());

    if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
        continue;
    }

    $source = $entry->getPathname();
    $name   = pathinfo($source, PATHINFO_FILENAME);

    $source_size = @getimagesize($source);

    if (! is_array($source_size) || empty($source_size[0]) || empty($source_size[1])) {
        WP_CLI::warning('Cannot read image dimensions: ' . basename($source));
        ++$failed;
        continue;
    }

    $source_width  = (int) $source_size[0];
    $source_height = (int) $source_size[1];

    ++$processed;

    /*
     * Full-size WebP.
     */
    $full_target = dirname($source) . DIRECTORY_SEPARATOR . $name . '.webp';

    $needs_full = ! is_file($full_target)
        || filemtime($source) > filemtime($full_target);

    if ($needs_full) {
        $editor = wp_get_image_editor($source);

        if (is_wp_error($editor)) {
            WP_CLI::warning(
                basename($source) . ': ' . $editor->get_error_message()
            );
            ++$failed;
            continue;
        }

        $editor->set_quality($quality);

        $saved = $editor->save($full_target, 'image/webp');

        if (is_wp_error($saved)) {
            WP_CLI::warning(
                basename($source) . ': ' . $saved->get_error_message()
            );
            ++$failed;
            continue;
        }

        echo 'WEBP  '
            . basename($source)
            . ' -> '
            . basename($full_target)
            . PHP_EOL;

        ++$created;
    } else {
        ++$skipped;
    }

    /*
     * Responsive WebP variants.
     */
    foreach ($widths as $width) {
        if ($source_width <= $width) {
            continue;
        }

        $height = (int) round(
            $source_height * ($width / $source_width)
        );

        $target = dirname($source)
            . DIRECTORY_SEPARATOR
            . $name
            . '-'
            . $width
            . '.webp';

        $needs_variant = ! is_file($target)
            || filemtime($source) > filemtime($target);

        if (! $needs_variant) {
            ++$skipped;
            continue;
        }

        $editor = wp_get_image_editor($source);

        if (is_wp_error($editor)) {
            WP_CLI::warning(
                basename($source)
                . " {$width}px: "
                . $editor->get_error_message()
            );
            ++$failed;
            continue;
        }

        $editor->set_quality($quality);

        $resized = $editor->resize(
            $width,
            $height,
            false
        );

        if (is_wp_error($resized)) {
            WP_CLI::warning(
                basename($source)
                . " {$width}px: "
                . $resized->get_error_message()
            );
            ++$failed;
            continue;
        }

        $saved = $editor->save($target, 'image/webp');

        if (is_wp_error($saved)) {
            WP_CLI::warning(
                basename($source)
                . " {$width}px: "
                . $saved->get_error_message()
            );
            ++$failed;
            continue;
        }

        echo 'WEBP  '
            . basename($source)
            . " -> {$name}-{$width}.webp"
            . PHP_EOL;

        ++$created;
        ++$updated;
    }
}

echo PHP_EOL;
echo sprintf(
    "Done: %d source(s), %d created, %d responsive, %d skipped, %d failed. Quality: %d.\n",
    $processed,
    $created,
    $updated,
    $skipped,
    $failed,
    $quality
);

if ($failed > 0) {
    WP_CLI::halt(1);
}

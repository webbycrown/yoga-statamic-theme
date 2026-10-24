<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Statamic\Facades\Stache;
use Statamic\Facades\YAML;

class YogaCatalogue
{
    public const SLUGS = ['yoga', 'aurea', 'atelier', 'savor', 'vows', 'ridge'];

    private const COLLECTIONS = [
        'blogs',
        'classes',
        'events',
        'faqs',
        'pages',
        'podcast',
        'podcast_episode',
        'products',
        'retreats',
        'teams',
        'testimonials',
    ];

    public static function apply(string $catalogue): bool
    {
        $catalogue = strtolower($catalogue);
        if (! in_array($catalogue, self::SLUGS, true)) {
            return false;
        }

        $pack = base_path('content/industries/'.$catalogue);
        $snap = $pack.'/snapshot';
        if (! is_dir($snap)) {
            return false;
        }

        $meta = YAML::file($pack.'/setting.yaml')->parse() ?? [];
        $label = (string) ($meta['catalogue_label'] ?? ucfirst($catalogue));

        foreach (self::COLLECTIONS as $collection) {
            self::replaceMarkdown($snap.'/content/collections/'.$collection, base_path('content/collections/'.$collection));
        }

        self::copyOver($snap.'/content/taxonomies', base_path('content/taxonomies'));
        self::copyOver($snap.'/content/globals', base_path('content/globals'));
        self::copyOver($snap.'/content/trees', base_path('content/trees'));
        self::copyOver($snap.'/resources/views', resource_path('views'));
        self::copyOver($snap.'/resources/blueprints', resource_path('blueprints'));
        self::copyOver($snap.'/public/assets/css', public_path('assets/css'));
        self::copyOver($snap.'/public/assets/image', public_path('assets/image'));
        self::copyOver($snap.'/app/Http/Controllers', app_path('Http/Controllers'));

        $routes = $snap.'/routes/web.php';
        if (is_file($routes)) {
            File::copy($routes, base_path('routes/web.php'));
        }

        foreach (File::allFiles(base_path('content/globals')) as $file) {
            if ($file->getFilename() === 'setting.yaml') {
                self::stampCatalogue($file->getPathname(), $catalogue, $label);
            }
        }

        foreach (['view:clear', 'cache:clear'] as $command) {
            try {
                \Illuminate\Support\Facades\Artisan::call($command);
            } catch (\Throwable $e) {
                // Clearing cache is only needed so new photos replace the asset index.
            }
        }

        if (class_exists(Stache::class)) {
            try {
                Stache::refresh();
            } catch (\Throwable $e) {
                // The catalogue files are already written. The next request can refresh.
            }
        }

        return true;
    }

    private static function replaceMarkdown(string $source, string $dest): void
    {
        if (! is_dir($source)) {
            if (is_dir($dest)) {
                foreach (File::allFiles($dest) as $file) {
                    if ($file->getExtension() === 'md') {
                        File::delete($file->getPathname());
                    }
                }
            }

            return;
        }

        File::ensureDirectoryExists($dest);
        foreach (File::allFiles($dest) as $file) {
            if ($file->getExtension() === 'md') {
                File::delete($file->getPathname());
            }
        }
        foreach (File::allFiles($source) as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }
            $target = $dest.'/'.$file->getRelativePathname();
            File::ensureDirectoryExists(dirname($target));
            File::copy($file->getPathname(), $target);
        }
    }

    private static function copyOver(string $source, string $dest): void
    {
        if (! is_dir($source)) {
            return;
        }

        foreach (File::allFiles($source) as $file) {
            $target = $dest.'/'.$file->getRelativePathname();
            File::ensureDirectoryExists(dirname($target));
            File::copy($file->getPathname(), $target);
        }
    }

    private static function stampCatalogue(string $path, string $catalogue, string $label): void
    {
        $yaml = File::get($path);
        $quoted = "'".str_replace("'", "''", $label)."'";
        $hasData = (bool) preg_match('/^data:\s*$/m', $yaml);
        $indent = $hasData ? '  ' : '';
        $yaml = preg_replace('/^'.$indent.'catalogue:.*\n/m', '', $yaml) ?? $yaml;
        $yaml = preg_replace('/^'.$indent.'catalogue_label:.*\n/m', '', $yaml) ?? $yaml;
        $block = $indent.'catalogue: '.$catalogue."\n".$indent.'catalogue_label: '.$quoted."\n";
        if ($hasData) {
            $yaml = preg_replace('/^data:\n/m', "data:\n".$block, $yaml, 1) ?? $yaml;
        } else {
            $yaml = $block.$yaml;
        }
        File::put($path, $yaml);
    }
}

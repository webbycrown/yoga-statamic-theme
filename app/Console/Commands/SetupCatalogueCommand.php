<?php

namespace App\Console\Commands;

use App\Support\YogaCatalogue;
use Illuminate\Console\Command;

class SetupCatalogueCommand extends Command
{
    protected $signature = 'yoga:setup {catalogue : yoga|aurea|atelier|savor|vows|ridge}';

    protected $description = 'Apply a Yoga catalogue demo pack.';

    public function handle(): int
    {
        $catalogue = strtolower((string) $this->argument('catalogue'));
        if (! YogaCatalogue::apply($catalogue)) {
            $this->error('Catalogue must be one of: '.implode(', ', YogaCatalogue::SLUGS));

            return self::FAILURE;
        }

        $this->info("Yoga catalogue set to {$catalogue}. Demo pages, photos, and palette updated.");

        return self::SUCCESS;
    }
}

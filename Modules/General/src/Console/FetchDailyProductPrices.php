<?php

declare(strict_types=1);

namespace Modules\General\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\General\Models\ProductStat;
use Symfony\Component\Console\Command\Command as CommandAlias;

class FetchDailyProductPrices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'product:fetch-daily-prices';

    /**
     * The console command description.
     */
    protected $description = 'Fetch daily product prices from external API and store them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Fetching daily product prices...');

        try {
            // Fetch from external API
            // Replace this URL with your actual external API endpoint
            $response = Http::timeout(30)->get('https://api.example.com/products/prices');

            if (! $response->successful()) {
                $this->error('Failed to fetch product prices from external API');

                return CommandAlias::FAILURE;
            }

            $data = $response->json();

            // Store or update today's product stats
            ProductStat::updateOrCreate(
                ['date' => now()->toDateString()],
                ['data' => $data]
            );

            $this->info('Product prices fetched and stored successfully.');

            return CommandAlias::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error fetching product prices: '.$e->getMessage());

            return CommandAlias::FAILURE;
        }
    }
}

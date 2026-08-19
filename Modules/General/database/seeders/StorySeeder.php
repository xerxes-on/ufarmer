<?php

declare(strict_types=1);

namespace Modules\General\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\General\Models\Story;

class StorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stories = [
            [
                'color' => '#FF6B6B',
                'attachment' => 'https://example.com/stories/summer-sale.jpg',
                'is_active' => true,
                'pre_attachment' => 'https://example.com/stories/summer-sale-thumb.jpg',
                'button' => 'link',
                'button_text' => 'Shop Now',
                'button_action' => 'https://example.com/summer-sale',
            ],
            [
                'color' => '#4ECDC4',
                'attachment' => 'https://example.com/stories/new-collection.mp4',
                'is_active' => true,
                'pre_attachment' => 'https://example.com/stories/new-collection-thumb.jpg',
                'button' => 'product',
                'button_text' => 'View Collection',
                'button_action' => 'product:12345',
            ],
            [
                'color' => '#45B7D1',
                'attachment' => 'https://example.com/stories/feature-highlight.jpg',
                'is_active' => true,
                'pre_attachment' => 'https://example.com/stories/feature-highlight-thumb.jpg',
                'button' => null,
                'button_text' => null,
                'button_action' => null,
            ],
            [
                'color' => '#96CEB4',
                'attachment' => 'https://example.com/stories/customer-review.mp4',
                'is_active' => false,
                'pre_attachment' => 'https://example.com/stories/customer-review-thumb.jpg',
                'button' => 'share',
                'button_text' => 'Share Story',
                'button_action' => 'share:story:4',
            ],
            [
                'color' => '#FECA57',
                'attachment' => 'https://example.com/stories/flash-deal.jpg',
                'is_active' => true,
                'pre_attachment' => 'https://example.com/stories/flash-deal-thumb.jpg',
                'button' => 'category',
                'button_text' => 'Browse Deals',
                'button_action' => 'category:electronics',
            ],
        ];

        foreach ($stories as $story) {
            Story::create($story);
        }
    }
}

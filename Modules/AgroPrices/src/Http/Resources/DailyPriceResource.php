<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Modules\AgroPrices\Models\DailyPrice;

/**
 * @property DailyPrice $resource
 */
final class DailyPriceResource extends JsonResource
{
    private string $lang;

    private string $date;

    public function __construct(DailyPrice $resource, string $lang, ?string $date = null)
    {
        parent::__construct($resource);
        $this->lang = $lang;
        $this->date = $date ?? Carbon::today()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $name = $this->resource->product->name[$this->lang]
            ?? ($this->resource->product->name['uz'] ?? null);

        return [
            'product_id' => $this->resource->product_id,
            'name' => $name,
            'slug' => $this->resource->product->slug,
            'price' => (float) $this->resource->price,
            'status' => $this->resource->status,
            'date' => $this->date,
        ];
    }
}

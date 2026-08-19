<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        //        $records = [
        //            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'is_active' => true],
        //            ['code' => 'UZS', 'name' => 'Uzbekistan Sum', 'symbol' => 'soʻm', 'is_active' => true],
        //            ['code' => 'RUB', 'name' => 'Russian Ruble', 'symbol' => '₽', 'is_active' => true],
        //        ];

        if (! Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 3)->unique();
                $table->string('name');
                $table->string('symbol', 10);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('is_active');
            });
        }

        //        $timestamp = now();
        //        $records = array_map(static fn (array $record) => [...$record, 'created_at' => $timestamp, 'updated_at' => $timestamp], $records);

        //        DB::table('currencies')->upsert($records, ['code'], ['name', 'symbol', 'is_active', 'updated_at']);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};

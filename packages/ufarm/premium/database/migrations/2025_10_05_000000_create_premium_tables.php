<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ufarm\Premium\Models\Subscription;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('premium_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedInteger('application_id')->nullable()->comment('NULL = universal plan for all apps');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('application_id');
            $table->index('is_active');
            $table->index('currency_id');
        });

        Schema::create(Subscription::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('premium_plan_id')->constrained('premium_plans')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('renewed_at')->nullable();
            $table->unsignedInteger('application_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Subscription::TABLE);
        Schema::dropIfExists('premium_plans');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_service_types', function (Blueprint $table): void {
            $table->id();
            $table->string('alias')->unique();
            $table->string('name');
            $table->string('validator_class');
            $table->string('model_class');
            $table->string('status_field')->default('status');
            $table->string('payment_status')->default('payment_pending');
            $table->string('paid_status')->default('paid');
            $table->string('user_field')->default('user_id');
            $table->string('amount_field')->default('price');
            $table->string('currency_relation')->default('currency');
            $table->string('currency_code_field')->default('code');
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index('alias');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_service_types');
    }
};

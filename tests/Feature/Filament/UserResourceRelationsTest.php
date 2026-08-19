<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Agronom\Enums\ServiceRequestType;
use Modules\Core\Filament\Resources\UserResource;
use Modules\Core\Filament\Resources\UserResource\RelationManagers\AgronomServicesRelationManager;
use Modules\Core\Filament\Resources\UserResource\RelationManagers\AreasRelationManager;
use Modules\Core\Filament\Resources\UserResource\RelationManagers\ServiceOffersRelationManager;
use Modules\Core\Filament\Resources\UserResource\RelationManagers\ServiceRequestsRelationManager;
use Modules\Core\Models\User;
use Tests\TestCase;

class UserResourceRelationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (['agronom_details', 'exporter_profiles', 'user_profiles'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id');
                $table->timestamps();
            });
        }

        Schema::create('myid_identity_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('provider')->default('myid');
            $table->string('pinfl')->nullable();
            $table->string('verification_status')->default('verified');
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agronom_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->timestamps();
        });

        Schema::create('service_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agronom_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('farmer_id');
            $table->foreignId('agronom_id');
            $table->string('type')->default('monitoring');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'agronom_requests',
            'service_offers',
            'agronom_services',
            'areas',
            'myid_identity_verifications',
            'user_profiles',
            'exporter_profiles',
            'agronom_details',
            'users',
        ] as $tableName) {
            Schema::dropIfExists($tableName);
        }

        parent::tearDown();
    }

    public function test_user_view_relations_cover_profiles_offers_areas_and_both_request_roles(): void
    {
        $user = User::query()->create(['name' => 'User']);
        $other = User::query()->create(['name' => 'Other']);

        DB::table('myid_identity_verifications')->insert([
            'user_id' => $user->id,
            'pinfl' => '12345678901234',
            'payload' => '{}',
            'metadata' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('myid_identity_verifications')->insert([
            'user_id' => $user->id,
            'provider' => 'other',
            'pinfl' => '99999999999999',
            'payload' => '{}',
            'metadata' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['agronom_details', 'exporter_profiles', 'user_profiles'] as $tableName) {
            DB::table($tableName)->insert([
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('areas')->insert([
            'user_id' => $user->id,
            'name' => 'Field',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('agronom_services')->insert([
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('service_offers')->insert([
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('agronom_requests')->insert([
            [
                'farmer_id' => $user->id,
                'agronom_id' => $other->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'farmer_id' => $other->id,
                'agronom_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertSame('12345678901234', $user->myIdIdentity()->value('pinfl'));
        $this->assertTrue($user->agronomDetail()->exists());
        $this->assertTrue($user->exporterProfile()->exists());
        $this->assertTrue($user->workerProfile()->exists());
        $this->assertSame(1, $user->areas()->count());
        $this->assertSame(1, $user->agronomServices()->count());
        $this->assertSame(1, $user->serviceOffers()->count());
        $this->assertInstanceOf(Builder::class, $user->serviceRequests()->getQuery());
        $this->assertSame(ServiceRequestType::Monitoring, $user->serviceRequests()->first()->type);
        $this->assertSame(2, $user->serviceRequests()->count());
        $this->assertSame([
            AreasRelationManager::class,
            AgronomServicesRelationManager::class,
            ServiceOffersRelationManager::class,
            ServiceRequestsRelationManager::class,
        ], UserResource::getRelations());
    }
}

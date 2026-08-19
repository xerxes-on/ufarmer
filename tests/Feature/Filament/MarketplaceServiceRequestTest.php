<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\ServiceRequestPermission;
use App\Models\User as AdminUser;
use App\Policies\MarketplaceServiceRequestPolicy;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Mockery;
use Modules\JobsServices\Filament\Resources\MarketplaceServiceRequestResource;
use Modules\JobsServices\Filament\Resources\MarketplaceServiceRequestResource\Pages\ListMarketplaceServiceRequests;
use Modules\JobsServices\Models\MarketplaceServiceRequest;
use Modules\JobsServices\Support\WorkerMetadata;
use Tests\TestCase;

final class MarketplaceServiceRequestTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = (string) config('database.default');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('auth_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('service_offers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->json('title');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('service_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('service_offer_id');
            $table->unsignedBigInteger('requester_id');
            $table->string('status');
            $table->decimal('proposed_price', 12, 2)->nullable();
            $table->decimal('worker_salary', 12, 2)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('requester_rated_at')->nullable();
            $table->timestamp('worker_rated_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        config()->set('database.default', $this->originalConnection);

        parent::tearDown();
    }

    public function test_the_operations_scope_returns_only_pending_unactivated_ai_workers(): void
    {
        $requesterId = $this->user('Requester', 100);
        $unactivatedId = $this->worker('AI Unactivated', 200, ['origin' => ['type' => 'ai_import']]);
        $activatedId = $this->worker('AI Activated', 300, [
            'origin' => ['type' => 'ai_import'],
            'activation' => ['first_service_app_seen_at' => now()->toIso8601String()],
        ]);
        $loggedInId = $this->worker('AI Logged In', 325, [
            'origin' => ['type' => 'ai_import'],
            'activation' => ['first_auth_login_at' => now()->toIso8601String()],
        ]);
        $missingAuthId = $this->worker('AI Missing Auth', null, ['origin' => ['type' => 'ai_import']]);
        $invalidActivationId = $this->worker('AI Invalid Activation', 350, [
            'origin' => ['type' => 'ai_import'],
            'activation' => ['first_service_app_seen_at' => 'not-a-date'],
        ]);
        $manualId = $this->worker('Manual', 400, null);

        $pendingUnactivated = $this->request($requesterId, $unactivatedId, 'pending');
        $this->request($requesterId, $activatedId, 'pending');
        $this->request($requesterId, $loggedInId, 'pending');
        $pendingMissingAuth = $this->request($requesterId, $missingAuthId, 'pending');
        $pendingInvalidActivation = $this->request($requesterId, $invalidActivationId, 'pending');
        $this->request($requesterId, $manualId, 'pending');
        $this->request($requesterId, $unactivatedId, 'completed');

        $requests = MarketplaceServiceRequest::query()
            ->needsManualInspection()
            ->with('offer.user.workerProfile')
            ->orderBy('id')
            ->get();

        $this->assertSame(
            [$pendingUnactivated, $pendingMissingAuth, $pendingInvalidActivation],
            $requests->pluck('id')->all(),
        );
        $this->assertTrue($requests[0]->needsManualInspection());
        $this->assertTrue($requests[1]->needsManualInspection());
        $this->assertTrue($requests[2]->needsManualInspection());
    }

    public function test_the_resource_is_unavailable_before_worker_metadata_is_migrated(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropColumn('meta');
        });

        $this->assertFalse(MarketplaceServiceRequestResource::shouldRegisterNavigation());
        $this->assertFalse(MarketplaceServiceRequestResource::canViewAny());
        $this->assertFalse(MarketplaceServiceRequestResource::canView(new MarketplaceServiceRequest));
        $this->assertNull(MarketplaceServiceRequestResource::getNavigationBadge());
        $this->assertSame([], (new ListMarketplaceServiceRequests)->getTabs());
    }

    public function test_worker_metadata_schema_availability_is_cached_for_the_request(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->assertTrue(MarketplaceServiceRequestResource::workerMetadataIsAvailable());
        $initialQueryCount = count(DB::getQueryLog());
        $this->assertTrue(MarketplaceServiceRequestResource::workerMetadataIsAvailable());
        $this->assertTrue(MarketplaceServiceRequestResource::workerMetadataIsAvailable());

        $this->assertGreaterThan(0, $initialQueryCount);
        $this->assertSame($initialQueryCount, count(DB::getQueryLog()));
    }

    public function test_navigation_badge_counts_requests_needing_manual_inspection(): void
    {
        $requesterId = $this->user('Requester', 100);
        $unactivatedId = $this->worker('AI Unactivated', 200, ['origin' => ['type' => 'ai_import']]);
        $activatedId = $this->worker('AI Activated', 300, [
            'origin' => ['type' => 'ai_import'],
            'activation' => ['first_service_app_seen_at' => now()->toIso8601String()],
        ]);

        $this->request($requesterId, $unactivatedId, 'pending');
        $this->request($requesterId, $unactivatedId, 'pending');
        $this->request($requesterId, $activatedId, 'pending');
        $this->request($requesterId, $unactivatedId, 'completed');

        app()->setLocale('en');

        $this->assertSame('2', MarketplaceServiceRequestResource::getNavigationBadge());
        $this->assertSame('warning', MarketplaceServiceRequestResource::getNavigationBadgeColor());
        $this->assertSame(
            'Requests needing manual inspection',
            MarketplaceServiceRequestResource::getNavigationBadgeTooltip(),
        );
    }

    public function test_tab_counts_are_correct_and_cached_per_page_instance(): void
    {
        $requesterId = $this->user('Requester', 100);
        $unactivatedId = $this->worker('AI Unactivated', 200, ['origin' => ['type' => 'ai_import']]);
        $activatedId = $this->worker('AI Activated', 300, [
            'origin' => ['type' => 'ai_import'],
            'activation' => ['first_service_app_seen_at' => now()->toIso8601String()],
        ]);

        $this->request($requesterId, $unactivatedId, 'pending');
        $this->request($requesterId, $unactivatedId, 'pending');
        $this->request($requesterId, $activatedId, 'pending');
        $this->request($requesterId, $activatedId, 'completed');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $page = new ListMarketplaceServiceRequests;
        $tabs = $page->getTabs();
        $page->getTabs();
        $serviceRequestQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'service_requests'))
            ->count();

        $this->assertSame(2, $tabs['needs_inspection']->getBadge());
        $this->assertSame(3, $tabs['pending']->getBadge());
        $this->assertSame(4, $tabs['all']->getBadge());
        $this->assertSame(2, $serviceRequestQueries);
    }

    public function test_all_tab_renders_requests_with_legacy_worker_metadata(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        $requesterId = $this->user('Requester', 100);
        $workerId = $this->worker('Legacy Worker', 200, 'legacy');

        $this->request($requesterId, $workerId, 'accepted');

        Filament::setCurrentPanel(Filament::getPanel('admin')->navigation(false));
        Gate::before(static fn (): bool => true);

        Livewire::actingAs(AdminUser::query()->findOrFail($requesterId))
            ->withQueryParams(['activeTab' => 'all'])
            ->test(ListMarketplaceServiceRequests::class)
            ->assertSuccessful();
    }

    public function test_all_tab_renders_requests_with_legacy_service_title(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        $requesterId = $this->user('Requester', 100);
        $workerId = $this->worker('Worker', 200, null);

        $this->request($requesterId, $workerId, 'accepted', 'Legacy service');

        Filament::setCurrentPanel(Filament::getPanel('admin')->navigation(false));
        Gate::before(static fn (): bool => true);

        Livewire::actingAs(AdminUser::query()->findOrFail($requesterId))
            ->withQueryParams(['activeTab' => 'all'])
            ->test(ListMarketplaceServiceRequests::class)
            ->assertSuccessful();
    }

    public function test_the_policy_requires_service_request_permissions(): void
    {
        $workerOnly = Mockery::mock(AdminUser::class);
        $workerOnly->shouldReceive('can')->andReturnUsing(
            static fn (string $permission): bool => in_array($permission, ['view_worker', 'view_any_worker'], true),
        );
        $genericServiceRequestViewer = Mockery::mock(AdminUser::class);
        $genericServiceRequestViewer->shouldReceive('can')->andReturnUsing(
            static fn (string $permission): bool => in_array(
                $permission,
                [ServiceRequestPermission::View->value, ServiceRequestPermission::ViewAny->value],
                true,
            ),
        );
        $marketplaceViewer = Mockery::mock(AdminUser::class);
        $marketplaceViewer->shouldReceive('can')->andReturnUsing(
            static fn (string $permission): bool => in_array(
                $permission,
                [
                    ServiceRequestPermission::ViewMarketplace->value,
                    ServiceRequestPermission::ViewAnyMarketplace->value,
                ],
                true,
            ),
        );
        $policy = new MarketplaceServiceRequestPolicy;
        $request = new MarketplaceServiceRequest;

        $this->assertFalse($policy->viewAny($workerOnly));
        $this->assertFalse($policy->view($workerOnly, $request));
        $this->assertFalse($policy->viewAny($genericServiceRequestViewer));
        $this->assertFalse($policy->view($genericServiceRequestViewer, $request));
        $this->assertTrue($policy->viewAny($marketplaceViewer));
        $this->assertTrue($policy->view($marketplaceViewer, $request));
    }

    public function test_invalid_activation_metadata_is_treated_as_untracked(): void
    {
        $this->assertNull(WorkerMetadata::firstServiceAppSeenAt([
            'activation' => ['first_service_app_seen_at' => 'not-a-date'],
        ]));
    }

    private function user(string $name, ?int $authId): int
    {
        return DB::table('users')->insertGetId([
            'name' => $name,
            'phone' => (string) random_int(998900000000, 998999999999),
            'auth_id' => $authId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function worker(string $name, ?int $authId, mixed $meta): int
    {
        $userId = $this->user($name, $authId);

        DB::table('user_profiles')->insert([
            'user_id' => $userId,
            'meta' => $meta === null ? null : json_encode($meta, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $userId;
    }

    private function request(
        int $requesterId,
        int $workerId,
        string $status,
        array|string $title = ['uz' => 'Service'],
    ): int {
        $offerId = DB::table('service_offers')->insertGetId([
            'user_id' => $workerId,
            'title' => json_encode($title, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('service_requests')->insertGetId([
            'service_offer_id' => $offerId,
            'requester_id' => $requesterId,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

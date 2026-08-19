<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Http\Middleware\AuditAdminPanelActivity;
use App\Support\AdminActivityContext;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Models\User;
use Tests\TestCase;

final class AdminActivityLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('admin_audit_records', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('password')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer');
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        app(AdminActivityContext::class)->deactivate();

        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('admin_audit_records');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_admin_model_lifecycle_records_actor_changes_and_redacts_secrets(): void
    {
        $user = User::query()->create(['name' => 'Admin']);
        $this->actingAs($user);
        app(AdminActivityContext::class)->activate();

        $record = AdminAuditRecord::query()->create([
            'name' => 'Initial',
            'password' => 'do-not-store',
            'settings' => [
                'api_key' => 'do-not-store-either',
                'visible' => 'kept',
            ],
        ]);
        $record->update(['name' => 'Changed']);
        $record->delete();
        $record->restore();
        $record->forceDelete();

        $activities = DB::table('activity_log')->orderBy('id')->get();

        $this->assertSame(['created', 'updated', 'deleted', 'restored', 'forceDeleted'], $activities->pluck('event')->all());

        $created = json_decode($activities[0]->attribute_changes, true, flags: JSON_THROW_ON_ERROR);
        $updated = json_decode($activities[1]->attribute_changes, true, flags: JSON_THROW_ON_ERROR);
        $properties = json_decode($activities[0]->properties, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('[REDACTED]', $created['attributes']['password']);
        $this->assertSame('[REDACTED]', $created['attributes']['settings']['api_key']);
        $this->assertSame('kept', $created['attributes']['settings']['visible']);
        $this->assertSame('Initial', $updated['old']['name']);
        $this->assertSame('Changed', $updated['attributes']['name']);
        $this->assertSame(AdminAuditRecord::class, $activities[0]->subject_type);
        $this->assertSame($record->id, $activities[0]->subject_id);
        $this->assertSame(User::class, $activities[0]->causer_type);
        $this->assertSame($user->id, $activities[0]->causer_id);
        $this->assertSame('admin', $properties['panel']);
    }

    public function test_admin_panel_middleware_activates_and_clears_auditing(): void
    {
        $user = User::query()->create(['name' => 'Admin']);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        app(AuditAdminPanelActivity::class)->handle(request(), function () {
            AdminAuditRecord::query()->create(['name' => 'From middleware']);

            return response()->noContent();
        });

        $this->assertDatabaseHas('activity_log', [
            'event' => 'created',
            'subject_type' => AdminAuditRecord::class,
            'causer_id' => $user->id,
        ]);
        $this->assertFalse(app(AdminActivityContext::class)->isActive());
    }

    public function test_non_admin_and_timestamp_only_changes_are_not_logged(): void
    {
        $record = AdminAuditRecord::query()->create(['name' => 'Outside panel']);

        $this->assertSame(0, DB::table('activity_log')->count());

        $user = User::query()->create(['name' => 'Admin']);
        $this->actingAs($user);
        app(AdminActivityContext::class)->activate();

        $record->updated_at = now()->addMinute();
        $record->save();

        $this->assertSame(0, DB::table('activity_log')->count());
    }
}

final class AdminAuditRecord extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'settings' => 'array',
    ];
}

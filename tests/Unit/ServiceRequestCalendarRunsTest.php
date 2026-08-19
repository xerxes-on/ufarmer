<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Agronom\Enums\RequestCalendarRunStatus;
use Modules\Agronom\Enums\ServiceRequestType;
use Modules\Agronom\Models\RequestCalendarRun;
use Modules\Agronom\Models\ServiceRequest;
use Tests\TestCase;

class ServiceRequestCalendarRunsTest extends TestCase
{
    public function test_service_request_exposes_calendar_run_assignments_for_filament(): void
    {
        $request = new ServiceRequest;
        $relation = $request->requestCalendarRuns();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(RequestCalendarRun::class, $relation->getRelated());
        $this->assertSame('service_request_id', $relation->getForeignKeyName());
    }

    public function test_monitoring_requests_are_identified_from_the_cast_type(): void
    {
        $monitoringRequest = new ServiceRequest(['type' => ServiceRequestType::Monitoring->value]);
        $chatRequest = new ServiceRequest(['type' => ServiceRequestType::Chat->value]);

        $this->assertTrue($monitoringRequest->isMonitoring());
        $this->assertFalse($chatRequest->isMonitoring());
    }

    public function test_calendar_run_assignment_status_is_cast_to_the_shared_enum(): void
    {
        $assignment = new RequestCalendarRun(['status' => RequestCalendarRunStatus::APPROVED->value]);

        $this->assertSame(RequestCalendarRunStatus::APPROVED, $assignment->status);
    }
}

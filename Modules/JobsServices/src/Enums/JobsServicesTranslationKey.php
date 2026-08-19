<?php

declare(strict_types=1);

namespace Modules\JobsServices\Enums;

enum JobsServicesTranslationKey: string
{
    // Job Validation
    case ValidationPriceFixedRequired = 'jobsservices::messages.validation.price_fixed_required';
    case ValidationPriceMinRequired = 'jobsservices::messages.validation.price_min_required';
    case ValidationDeadlineRequired = 'jobsservices::messages.validation.deadline_required';
    case ValidationFixedTimeRequired = 'jobsservices::messages.validation.fixed_time_required';
    case ValidationImageMaxSize = 'jobsservices::messages.validation.image_max_size';
}

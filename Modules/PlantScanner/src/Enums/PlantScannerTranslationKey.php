<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Enums;

enum PlantScannerTranslationKey: string
{
    case ImageAlreadyScanned = 'plantscanner::messages.image_already_scanned';
    case ScanStarted = 'plantscanner::messages.scan_started';
    case ScanFailed = 'plantscanner::messages.scan_failed';
    case ScanNotFound = 'plantscanner::messages.scan_not_found';
    case AiAnalysisFailed = 'plantscanner::messages.ai_analysis_failed';

    // Validation
    case ValidationImageRequired = 'plantscanner::messages.validation.image_required';
    case ValidationImageProvide = 'plantscanner::messages.validation.image_provide';
    case ValidationImageInvalid = 'plantscanner::messages.validation.image_invalid';
    case ValidationImageMustBe = 'plantscanner::messages.validation.image_must_be';
    case ValidationImageMaxSize = 'plantscanner::messages.validation.image_max_size';
    case ValidationImageMax100Mb = 'plantscanner::messages.validation.image_max_100mb';
    case ValidationImageInvalidFormat = 'plantscanner::messages.validation.image_invalid_format';
    case ValidationLangInvalid = 'plantscanner::messages.validation.lang_invalid';
    case ValidationAiProviderInvalid = 'plantscanner::messages.validation.ai_provider_invalid';
}

<?php

declare(strict_types=1);

namespace Modules\General\Enums;

enum GeneralTranslationKey: string
{
    case STORY_CREATED = 'general::messages.story.created';
    case STORY_UPDATED = 'general::messages.story.updated';
    case STORY_DELETED = 'general::messages.story.deleted';
    case STORY_NOT_FOUND = 'general::messages.story.not_found';

    case ARTICLE_NOT_FOUND = 'general::messages.article.not_found';

    case PRODUCT_STAT_IMPORT_STARTED = 'general::messages.product_stat.import_started';
    case PRODUCT_STAT_TODAY_MISSING = 'general::messages.product_stat.today_missing';
    case PRODUCT_STAT_CROP_FORBIDDEN = 'general::messages.product_stat.crop_forbidden';
    case PRODUCT_STAT_CROP_NOT_FOUND = 'general::messages.product_stat.crop_not_found';

    case GENERAL_SUCCESS = 'general::messages.general.success';
}

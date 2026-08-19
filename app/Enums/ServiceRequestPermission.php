<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceRequestPermission: string
{
    case Create = 'create_service::request';
    case Delete = 'delete_service::request';
    case DeleteAny = 'delete_any_service::request';
    case ForceDelete = 'force_delete_service::request';
    case ForceDeleteAny = 'force_delete_any_service::request';
    case Replicate = 'replicate_service::request';
    case Reorder = 'reorder_service::request';
    case Restore = 'restore_service::request';
    case RestoreAny = 'restore_any_service::request';
    case Update = 'update_service::request';
    case View = 'view_service::request';
    case ViewAny = 'view_any_service::request';
    case ViewMarketplace = 'view_marketplace::service::request';
    case ViewAnyMarketplace = 'view_any_marketplace::service::request';
}

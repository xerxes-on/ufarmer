<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AdminActivityContext;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuditAdminPanelActivity
{
    public function __construct(private AdminActivityContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Filament::getCurrentPanel()?->getId() !== 'admin' || ! auth()->check()) {
            return $next($request);
        }

        $this->context->activate();

        try {
            return $next($request);
        } finally {
            $this->context->deactivate();
        }
    }
}

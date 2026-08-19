<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Xerxes\AuthBridge\Exceptions\AuthBridgeException;
use Xerxes\AuthBridge\Services\AuthBridge;

final class SsoController extends Controller
{
    private const APPLICATION_ALIAS = 'admin_panel';

    public function __construct(
        private readonly AuthBridge $authBridge,
        private readonly SocialiteFactory $socialite,
    ) {}

    public function redirect(): RedirectResponse
    {
        return $this->provider()->redirect();
    }

    public function callback(Request $request): Response
    {
        try {
            /** @var \Laravel\Socialite\Two\User $socialiteUser */
            $socialiteUser = $this->provider()->user();

            $syntheticRequest = Request::create('/', 'GET');
            $syntheticRequest->headers->set('Authorization', 'Bearer '.$socialiteUser->token);
            $syntheticRequest->headers->set(
                (string) config('authbridge.application.header', 'x-application-alias'),
                self::APPLICATION_ALIAS,
            );

            $authResult = $this->authBridge->authenticate($syntheticRequest);
            $user = $authResult->user;

            abort_unless($user instanceof User, 403);

            $user->ensurePanelRole();

            $panel = Filament::getPanel('admin');
            abort_if($panel === null || ! $user->canAccessPanel($panel), 403);

            Auth::guard((string) config('authbridge.sso.guard', 'web'))->login($user);
            $request->session()->regenerate();

            return redirect()->intended($panel->getUrl() ?? url('/admin'));
        } catch (AuthBridgeException $exception) {
            return response($exception->getMessage(), 403);
        } catch (Throwable $exception) {
            report($exception);

            return response('Failed to authenticate with UFarm Auth.', 403);
        }
    }

    private function provider(): Provider
    {
        return $this->socialite->driver('ufarm-auth');
    }
}

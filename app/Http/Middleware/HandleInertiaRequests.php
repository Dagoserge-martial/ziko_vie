<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? (function ($user) {
                    $userArray = $user->load('membre', 'role', 'roles')->toArray();
                    $userArray['isAdmin'] = $user->isAdmin();
                    // Ajouter les permissions de l'utilisateur
                    $userArray['permissions'] = [
                        'dashboard.view' => $user->hasPermission('dashboard.view'),
                        'membres.view' => $user->hasPermission('membres.view'),
                        'cotisations.view' => $user->hasPermission('cotisations.view'),
                        'depenses.view' => $user->hasPermission('depenses.view'),
                        'parametres.view' => $user->hasPermission('parametres.view'),
                    ];
                    return $userArray;
                })($request->user()) : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
            ],
        ];
    }
}

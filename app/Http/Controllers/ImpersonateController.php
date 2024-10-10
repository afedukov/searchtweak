<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ImpersonateController
{
    public function impersonate(User $user): RedirectResponse
    {
        Gate::authorize('superuser', Auth::user());

        $adminId = Auth::id();

        if (Auth::loginUsingId($user->id)) {
            session()->put('impersonating', $adminId);

            session()->flash('flash.banner', 'Impersonating user');
            session()->flash('flash.bannerStyle', 'success');
        } else {
            session()->flash('flash.banner', 'Impersonate failed');
            session()->flash('flash.bannerStyle', 'danger');
        }

        return redirect()->route('dashboard');
    }
}

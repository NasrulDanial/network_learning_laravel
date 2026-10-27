{{--
    Header account menu (both roles): the avatar opens a menu with the profile, password and logout.
    Expects: $profileRoute (route name), $roleLabel (string), $accent ('cyan' | 'purple').
--}}
@php
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $onProfile = request()->routeIs($profileRoute);
@endphp

<div class="account-menu" data-account-menu>
    <button
        type="button"
        class="account-trigger"
        id="accountMenuButton"
        aria-haspopup="menu"
        aria-expanded="false"
        aria-controls="accountMenu"
    >
        <span class="account-avatar account-avatar--{{ $accent }}" aria-hidden="true">{{ $initials ?: '?' }}</span>
        <span class="account-trigger-text">
            <span class="account-name">{{ $user->name }}</span>
            <span class="account-role">{{ $roleLabel }}</span>
        </span>
        <i class="fa-solid fa-chevron-down account-caret" aria-hidden="true"></i>
        <span class="visually-hidden">Open account menu</span>
    </button>

    <div class="account-dropdown" id="accountMenu" role="menu" aria-labelledby="accountMenuButton" hidden>
        <div class="account-dropdown-header">
            <span class="account-avatar account-avatar--{{ $accent }} account-avatar--lg" aria-hidden="true">{{ $initials ?: '?' }}</span>
            <div>
                <strong>{{ $user->name }}</strong>
                <span>{{ $user->email }}</span>
                <span class="account-role-chip">{{ $roleLabel }}</span>
            </div>
        </div>

        <a href="{{ route($profileRoute) }}" class="account-item {{ $onProfile ? 'is-current' : '' }}" role="menuitem" @if ($onProfile) aria-current="page" @endif>
            <i class="fa-solid fa-id-card" aria-hidden="true"></i>
            My Profile
        </a>
        <a href="{{ route($profileRoute) }}#change-password" class="account-item" role="menuitem">
            <i class="fa-solid fa-key" aria-hidden="true"></i>
            Change Password
        </a>

        <div class="account-divider" role="separator"></div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="account-item account-item--danger" role="menuitem">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                Log out
            </button>
        </form>
    </div>
</div>

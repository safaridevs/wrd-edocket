<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('welcome') }}" class="flex items-center space-x-2">
                        <img src="{{ asset('images/ose-logo.png') }}" alt="OSE Logo" class="h-8 w-auto">
                        <span class="font-bold text-gray-800">E-Docket</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                @php
                    $canViewReports = Auth::user()->hasAnyRole(['admin', 'hu_admin', 'hu_clerk', 'alu_mgr', 'alu_clerk', 'alu_paralegal', 'alu_atty']);
                    $canViewFilingHistory = Auth::user()->hasAnyRole(['hu_admin', 'hu_clerk']);
                    $canManageUsers = Auth::user()->canManageUsers();
                    $canManageDocumentTypes = Auth::user()->hasAnyRole(['hu_admin']);
                    $hasCaseWork = Auth::user()->canCreateCase() || $canViewReports || $canViewFilingHistory;
                    $hasAdministration = $canManageUsers || $canManageDocumentTypes;
                @endphp
                <div class="hidden sm:-my-px sm:ms-8 sm:flex sm:items-center sm:gap-6">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-link :href="route('cases.index')" :active="request()->routeIs('cases.*')">
                        {{ __('Cases') }}
                    </x-nav-link>

                    <x-nav-link :href="route('documents.search')" :active="request()->routeIs('documents.search')">
                        {{ __('Document Search') }}
                    </x-nav-link>

                    @if($hasCaseWork)
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex h-16 items-center gap-1 border-b-2 px-1 text-sm font-medium transition {{ request()->routeIs('reports.*', 'audit.*', 'cases.create') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                                    Case Work
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @if(Auth::user()->canCreateCase())
                                    <x-dropdown-link :href="route('cases.create')">New Case</x-dropdown-link>
                                @endif
                                @if($canViewReports)
                                    <x-dropdown-link :href="route('reports.index')">Reports</x-dropdown-link>
                                @endif
                                @if($canViewFilingHistory)
                                    <x-dropdown-link :href="route('audit.notifications')">Filing History</x-dropdown-link>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    @endif

                    @if($hasAdministration)
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex h-16 items-center gap-1 border-b-2 px-1 text-sm font-medium transition {{ request()->routeIs('admin.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                                    Administration
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @if($canManageUsers)
                                    <x-dropdown-link :href="route('admin.users')">Users</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.notifications')">Email Issues</x-dropdown-link>
                                @endif
                                @if($canManageDocumentTypes)
                                    <x-dropdown-link :href="route('admin.document-types')">Document Types</x-dropdown-link>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    @endif
                </div>
            </div>

            <!-- Role Switcher -->
            @if(config('app.env') !== 'production')
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ session('impersonated_role') ? 'As: ' . ucfirst(str_replace('_', ' ', session('impersonated_role'))) : 'Switch Role' }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        @foreach(['admin', 'hu_admin', 'hu_clerk', 'alu_mgr', 'alu_clerk', 'alu_paralegal', 'alu_atty', 'contract_attorney', 'wrd', 'wrap_dir', 'hydrology_expert', 'party', 'external_attorney', 'interested_party'] as $role)
                            <form method="POST" action="{{ route('impersonate.switch') }}" class="block">
                                @csrf
                                <input type="hidden" name="role" value="{{ $role }}">
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    {{ ucfirst(str_replace('_', ' ', $role)) }}
                                </button>
                            </form>
                        @endforeach
                        @if(session('impersonated_role'))
                            <form method="POST" action="{{ route('impersonate.stop') }}" class="block border-t">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    Stop Impersonation
                                </button>
                            </form>
                        @endif
                    </x-slot>
                </x-dropdown>
            </div>
            @endif

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        @php
                            $hasPersonRecord = \App\Models\Person::where('email', Auth::user()->email)->exists();
                        @endphp

                        @if($hasPersonRecord)
                            <x-dropdown-link :href="route('party.contact.edit')">
                                {{ __('Contact Information') }}
                            </x-dropdown-link>
                        @endif

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('cases.index')" :active="request()->routeIs('cases.*')">
                {{ __('Cases') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('documents.search')" :active="request()->routeIs('documents.search')">
                {{ __('Document Search') }}
            </x-responsive-nav-link>

            @if($hasCaseWork)
                <div class="px-4 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-gray-400">Case Work</div>
            @endif

            @if($canViewReports)
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    {{ __('Reports') }}
                </x-responsive-nav-link>
            @endif

            @if(Auth::user()->canCreateCase())
                <x-responsive-nav-link :href="route('cases.create')" :active="request()->routeIs('cases.create')">
                    {{ __('New Case') }}
                </x-responsive-nav-link>
            @endif

            @if($canViewFilingHistory)
                <x-responsive-nav-link :href="route('audit.notifications')" :active="request()->routeIs('audit.*')">
                    {{ __('Filing History') }}
                </x-responsive-nav-link>
            @endif

            @if($hasAdministration)
                <div class="px-4 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-gray-400">Administration</div>
            @endif

                @if($canManageUsers)
                    <x-responsive-nav-link :href="route('admin.users')" :active="request()->routeIs('admin.*')">
                        {{ __('Users') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.notifications')" :active="request()->routeIs('admin.notifications')">
                        {{ __('Email Issues') }}
                    </x-responsive-nav-link>
                @endif

            @if($canManageDocumentTypes)
                <x-responsive-nav-link :href="route('admin.document-types')" :active="request()->routeIs('admin.document-types')">
                    {{ __('Document Types') }}
                </x-responsive-nav-link>
            @endif

        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @php
                    $hasPersonRecord = \App\Models\Person::where('email', Auth::user()->email)->exists();
                @endphp

                @if($hasPersonRecord)
                    <x-responsive-nav-link :href="route('party.contact.edit')">
                        {{ __('Contact Information') }}
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

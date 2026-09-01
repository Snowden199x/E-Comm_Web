<!-- Admin Sidebar Navigation -->
<aside class="w-64 bg-[#3b1735] text-white h-full flex flex-col overflow-y-auto">

    <!-- Logo -->
    <div class="px-6 py-6 flex justify-center">
        <img src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo" class="w-48">
    </div>

    <!-- Menu Items -->
    <nav class="flex-1 px-4 space-y-1">
        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-white/10' : 'hover:bg-white/5' }}">
            <img src="{{ asset('assets/icons/dashboard/dashboard-menu.svg') }}" alt="" class="w-5 h-5">
            <span class="text-sm font-medium">Dashboard</span>
        </a>

        <a href="{{ route('registrations.index') }}"
            class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('registrations.*') ? 'bg-white/10' : 'hover:bg-white/5' }}">
            <img src="{{ asset('assets/icons/dashboard/registrations-menu.svg') }}" alt="" class="w-5 h-5">
            <span class="text-sm font-medium">Registrations</span>
        </a>

        <a href="{{ route('user-management.index') }}"
            class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('user-management.*') ? 'bg-white/10' : 'hover:bg-white/5' }}">
            <img src="{{ asset('assets/icons/dashboard/user-management-menu.svg') }}" alt="" class="w-5 h-5">
            <span class="text-sm font-medium">User Management</span>
        </a>

        <a href="{{ route('seller-compliance.overview') }}"
            class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('seller-compliance.*') ? 'bg-white/10' : 'hover:bg-white/5' }}">
            <img src="{{ asset('assets/icons/dashboard/seller-compliance-menu.svg') }}" alt="" class="w-5 h-5">
            <span class="text-sm font-medium">Seller Compliance</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5">
            <img src="{{ asset('assets/icons/dashboard/complaints-disputes-menu.svg') }}" alt=""
                class="w-5 h-5">
            <span class="text-sm font-medium">Complaints and Disputes</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5">
            <img src="{{ asset('assets/icons/dashboard/commission-menu.svg') }}" alt="" class="w-5 h-5">
            <span class="text-sm font-medium">Commission</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5">
            <img src="{{ asset('assets/icons/dashboard/reports-menu.svg') }}" alt="" class="w-5 h-5">
            <span class="text-sm font-medium">Reports</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5">
            <img src="{{ asset('assets/icons/dashboard/platform-settings-menu.svg') }}" alt="" class="w-5 h-5">
            <span class="text-sm font-medium">Platform Settings</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5">
            <img src="{{ asset('assets/icons/dashboard/messages-menu.svg') }}" alt="" class="w-5 h-5">
            <span class="text-sm font-medium">Messages</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5">
            <img src="{{ asset('assets/icons/dashboard/account-management-menu.svg') }}" alt=""
                class="w-5 h-5">
            <span class="text-sm font-medium">Account Management</span>
        </a>
    </nav>

    <!-- Logout -->
    <div class="px-4 pb-6">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();"
                class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 cursor-pointer">
                <img src="{{ asset('assets/icons/dashboard/logout-menu.svg') }}" alt="" class="w-5 h-5">
                <span class="text-sm font-medium">Logout</span>
            </a>
        </form>
    </div>

</aside>

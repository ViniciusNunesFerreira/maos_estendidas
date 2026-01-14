<header class="h-16 bg-white shadow-sm border-b border-gray-200">
    <div class="h-full px-6 flex items-center justify-between">
        <!-- Breadcrumbs -->
        <div class="flex items-center space-x-2 text-sm text-gray-600">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600">
                <x-icon name="home" class="h-4 w-4" />
            </a>
            @if(isset($breadcrumbs) && $breadcrumbs)
                <x-icon name="chevron-right" class="h-4 w-4" />
                {!! $breadcrumbs !!}
            @endif
        </div>
        
        <!-- Search Bar -->
        <livewire:admin.global-search />
        
        <!-- Right Actions -->
        <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <button class="relative p-2 text-gray-400 hover:text-gray-600">
                <x-icon name="bell" class="h-6 w-6" />
                <span class="absolute top-0 right-0 h-2 w-2 bg-red-500 rounded-full"></span>
            </button>
            
            <!-- Settings -->
            <a href="{{ route('admin.settings.index') }}" class="p-2 text-gray-400 hover:text-gray-600">
                <x-icon name="cog-6-tooth" class="h-6 w-6" />
            </a>
        </div>
    </div>
</header>
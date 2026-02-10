<!DOCTYPE html>
@php
    $currentPath = request()->path();
    $currentRoute = Route::currentRouteName();
    $currentLocale = session('locale', 'en');
    $htmlDir = $currentLocale === 'en' ? 'ltr' : 'rtl';
    $permissions = [];
    if (auth()->check() && auth()->user()) {
        $userPermissions = auth()->user()->permissions ?? [];
        $permissions = is_array($userPermissions) ? $userPermissions : [];
    }
@endphp
<html class="light" dir="{{ $htmlDir }}" lang="{{ $currentLocale }}">
<head>
  <meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    @stack('title')
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link href="{{asset('css/style.css')}}" rel="stylesheet" />
    <style>
      [x-cloak] { display: none !important; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

  <!-- Tailwind Config -->
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#8B5CF6",
            "secondary": "#F1E8FF",
            "accent": "#7C3AED",
            "text-primary": "#1F2937",
            "background-light": "#F6F5F8",
            "background-dark": "#151022",
          },
          fontFamily: {
            "display": ["IBM Plex Sans Arabic", "sans-serif"]
          },
        },
      },
    }
  </script>

</head>

<body class="bg-background-light dark:bg-background-dark font-display text-text-primary dark:text-gray-200">

  <div id="overlay" class="overlay" style="display: none !important;"></div>
  <div class="flex flex-col min-h-screen">
    <div class="flex flex-1">

      <!-- ======================== SIDEBAR ======================== -->
      <aside id="sidebar" class="flex flex-col w-72 bg-white dark:bg-gray-900 text-text-primary dark:text-gray-200 shadow-sm transition-all duration-300">
        <!-- Brand -->
        <div class="flex items-center justify-center h-16 border-b border-gray-200 dark:border-gray-800 px-4">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">store</span>
            <h1 class="text-sm font-bold">{{ trans('messages.system_name', [], session('locale')) }}</h1>
          </div>
        </div>

        <!-- Nav - Only: expense, expense_category, account, user, sms, material, stock -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto text-sm">

    <a href="{{route('dashboard')}}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ ($currentRoute === 'dashboard' || $currentPath === 'dashboard') ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
        <span class="material-symbols-outlined text-xl">dashboard</span>
        <span class="font-medium text-sm">{{ trans('messages.dashboard', [], session('locale')) }}</span>
    </a>

    <!-- Stock & Material (Inventory) -->
    @php
        $inventoryMenuActive = strpos($currentPath, 'view_stock') === 0 || strpos($currentPath, 'stock') === 0 || strpos($currentPath, 'view_material') === 0 || strpos($currentPath, 'material') === 0 || strpos($currentPath, 'categories') === 0 || strpos($currentPath, 'abaya-materials') === 0 || strpos($currentPath, 'stock/comprehensive-audit') === 0 || strpos($currentPath, 'material-quantity-audit') === 0;
    @endphp
    <div>
        <button onclick="toggleSubmenu('inventoryMenu')" 
            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ $inventoryMenuActive ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">inventory_2</span>
                <span class="font-medium text-sm">{{ trans('messages.inventory_management', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined text-sm transition-transform" id="arrow-inventoryMenu">expand_more</span>
        </button>
        <div id="inventoryMenu" class="submenu mt-2 pl-8 space-y-1 {{ $inventoryMenuActive ? 'active' : '' }}">
            <a href="{{url('view_stock')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'view_stock' || (strpos(request()->path(), 'stock') === 0 && trim(request()->path(), '/') !== 'stock/audit' && trim(request()->path(), '/') !== 'stock')) ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.inventory', [], session('locale')) }}
            </a>
            <a href="{{route('stock.comprehensive_audit')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'stock/comprehensive-audit' || strpos(request()->path(), 'stock/comprehensive-audit') === 0) ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.stock_audit', [], session('locale')) }}
            </a>
            <a href="{{route('material.quantity_audit')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'material-quantity-audit' || strpos(request()->path(), 'material-quantity-audit') === 0) ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.material_quantity_audit', [], session('locale')) ?: 'Material Quantity Audit' }}
            </a>
            <a href="{{url('categories')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'categories') ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.category', [], session('locale')) }}
            </a>
         
            <a href="{{url('view_material')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'view_material' || trim(request()->path(), '/') === 'add_material' || strpos(request()->path(), 'edit_material') === 0) ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.add_material', [], session('locale')) }}
            </a>
        </div>
    </div>

    <!-- Expenses -->
    @php
        $expensesMenuActive = strpos($currentPath, 'expenses') === 0 || strpos($currentPath, 'expense-categories') === 0;
    @endphp
    <div>
        <button onclick="toggleSubmenu('expensesMenu')" 
            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ $expensesMenuActive ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">receipt_long</span>
                <span class="font-medium text-sm">{{ trans('messages.expenses', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined text-sm transition-transform" id="arrow-expensesMenu">expand_more</span>
        </button>
        <div id="expensesMenu" class="submenu mt-2 pl-8 space-y-1 {{ $expensesMenuActive ? 'active' : '' }}">
            <a href="{{url('expenses')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'expenses' || strpos(request()->path(), 'expenses/') === 0) ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.add_expense', [], session('locale')) }}
            </a>
            <a href="{{url('expense-categories')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'expense-categories' || strpos(request()->path(), 'expense-categories/') === 0) ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.expense_categories', [], session('locale')) }}
            </a>
        </div>
    </div>

    <a href="{{url('accounts')}}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'accounts' || strpos(request()->path(), 'accounts/') === 0) ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
        <span class="material-symbols-outlined text-xl">account_balance</span>
        <span class="font-medium text-sm">{{ trans('messages.accounts', [], session('locale')) }}</span>
    </a>

    <!-- Settings (User, SMS, Size, Color) -->
    @php
        $settingsMenuActive = in_array($currentPath, ['settings', 'size', 'color', 'sms', 'user', 'suppliers']) || strpos($currentPath, 'settings/') === 0 || strpos($currentPath, 'sms/') === 0 || strpos($currentPath, 'user/') === 0 || strpos($currentPath, 'users/') === 0 || strpos($currentPath, 'suppliers') === 0;
    @endphp
    <div>
        <button onclick="toggleSubmenu('settingsMenu')" 
            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg hover:bg-secondary hover:text-accent transition-colors {{ $settingsMenuActive ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">settings</span>
                <span class="font-medium text-sm">{{ trans('messages.settings', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined text-sm transition-transform" id="arrow-settingsMenu">expand_more</span>
        </button>
        <div id="settingsMenu" class="submenu mt-2 pl-8 space-y-1 {{ $settingsMenuActive ? 'active' : '' }}">
            <a href="{{url('settings')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'settings') ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.settings', [], session('locale')) }}
            </a>
            <a href="{{url('user')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'user' || strpos(request()->path(), 'user/') === 0 || strpos(request()->path(), 'users/') === 0) ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.users', [], session('locale')) }}
            </a>
            <a href="{{url('size')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'size') ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.size', [], session('locale')) }}
            </a>
         
            <a href="{{url('sms')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'sms' || strpos(request()->path(), 'sms/') === 0) ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.sms_panel', [], session('locale')) }}
            </a>
            <a href="{{url('suppliers')}}" class="flex items-center gap-2 px-3 py-1.5 text-xs rounded-lg hover:bg-secondary hover:text-accent {{ (trim(request()->path(), '/') === 'suppliers' || strpos(request()->path(), 'suppliers/') === 0) ? 'bg-cyan-100 text-cyan-600 font-semibold' : '' }}">
                <span class="material-symbols-outlined text-sm">chevron_right</span> 
                {{ trans('messages.suppliers', [], session('locale')) }}
            </a>
        </div>
    </div>

</nav>

        <!-- Sidebar Profile -->
        <div class="p-3 border-t border-gray-200 dark:border-gray-800">
          <div class="flex items-center gap-3">
            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 bg-primary/20 flex items-center justify-center"
                 style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDQi91QSkV-4K0sUKmlCpkHw7UCi1VfGmQ-c05lLV0I4_qyMmxhkJUIZXZMlB8tIrZANsYNJaOhlhyhj1iC-BN8Sgiw_uJXw8pEDNOFVFaBsJNHEADXLw46WplAJvQpldP7TQMotk4xe1F-PgoK5241wnuaPr6-Xo5FDbVryyGeGYn2WK4Sv_dGNkNYyFZqaRZPOB7fMvO7KIaex6b6Ebkg3ELbq6GQO-uaaWpQutOsk7duPdr9FdkpYZZ9dhni3iNHFIMXf2bEmgds");'>
              @if(!auth()->user() || !auth()->user()->user_name)
                <span class="material-symbols-outlined text-primary">person</span>
              @endif
            </div>
            <div class="flex-1">
              <h3 class="text-xs font-semibold">{{ auth()->user()->user_name ?? trans('messages.user_default', [], session('locale')) }}</h3>
              <p class="text-xs text-gray-500">{{ auth()->user()->user_email ?? trans('messages.admin_default', [], session('locale')) }}</p>
            </div>
            <button onclick="logout()" class="text-gray-500 hover:text-accent" title="{{ trans('messages.logout_title', [], session('locale')) }}">
              <span class="material-symbols-outlined text-sm">logout</span>
            </button>
          </div>
        </div>
      </aside>
      <!-- ======================== END SIDEBAR ======================== -->

      <!-- ======================== MAIN ======================== -->
      <div class="flex-1 flex flex-col">
        <!-- HEADER -->
        <header class="flex items-center justify-between bg-white dark:bg-gray-800/50 shadow-sm px-4 py-3 lg:px-6 lg:py-4">
  <!-- زر القائمة يظهر فقط في الموبايل -->
  <div class="flex items-center gap-2">
    <button id="menuBtn"
      class="lg:hidden h-10 w-10 flex items-center justify-center rounded-md hover:bg-secondary transition">
      <span class="material-symbols-outlined text-primary text-3xl">menu</span>
    </button>

    <span class="material-symbols-outlined text-primary text-3xl hidden lg:inline">dashboard</span>
    <h2 class="text-lg font-bold hidden lg:inline">{{ trans('messages.dashboard_title', [], session('locale')) }}</h2>
  </div>

          <!-- Actions (right side in RTL; visually on the left edge) -->
          <div class="flex items-center gap-3">
            <!-- Language Switch (click opens dropdown) -->
            <div class="relative">
              <button id="langBtn" class="h-10 w-10 rounded-full bg-secondary/60 dark:bg-primary/20 flex items-center justify-center hover:bg-secondary dark:hover:bg-primary/30 transition-colors" aria-haspopup="true" aria-expanded="false">
                <!-- Flag changes via JS (🇴🇲 or 🇬🇧) -->
                <span id="langFlag" class="text-lg leading-none" data-locale="{{ $currentLocale }}">{{ $currentLocale === 'en' ? '🇬🇧' : '🇴🇲' }}</span>
              </button>
              <!-- Language Dropdown -->
              <div id="langMenu" class="dropdown absolute top-full mt-2 {{ $currentLocale === 'en' ? 'left-0' : 'right-0' }} w-40 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-lg z-20">
                <button data-lang="ar" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-secondary dark:hover:bg-primary/20 {{ $currentLocale === 'ar' ? 'bg-secondary/50' : '' }}">
                  <span class="text-base">🇴🇲</span> {{ trans('messages.arabic', [], session('locale')) }}
                </button>
                <button data-lang="en" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-secondary dark:hover:bg-primary/20 {{ $currentLocale === 'en' ? 'bg-secondary/50' : '' }}">
                  <span class="text-base">🇬🇧</span> {{ trans('messages.english', [], session('locale')) }}
                </button>
              </div>
            </div>

            <!-- Notifications (click opens dropdown) -->
            <div class="relative">
              <button id="notifBtn" class="relative h-10 w-10 rounded-full bg-secondary/60 dark:bg-primary/20 flex items-center justify-center hover:bg-secondary dark:hover:bg-primary/30 transition-colors" aria-haspopup="true" aria-expanded="false">
                <span class="material-symbols-outlined text-primary text-2xl">notifications</span>
                <span id="notifBadge" class="absolute top-1.5 {{ $currentLocale === 'en' ? 'left-1.5' : 'right-1.5' }} flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white hidden">0</span>
              </button>
              <!-- Notifications Dropdown -->
              <div id="notifMenu" class="dropdown absolute top-full mt-2 {{ $currentLocale === 'en' ? 'left-0' : 'right-0' }} w-80 max-w-[85vw] bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-xl z-20">
                <div class="p-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary">notifications</span>
                  <h4 class="font-semibold text-sm">{{ trans('messages.notifications', [], session('locale')) }}</h4>
                </div>
                <ul id="notifList" class="max-h-80 overflow-auto">
                  <li class="px-3 py-4 text-center text-gray-500 text-sm">{{ trans('messages.loading', [], session('locale')) }}...</li>
                </ul>
                <div class="p-2 border-t border-gray-100 dark:border-gray-700 text-center">
                  <a href="#" class="text-sm text-accent hover:underline">{{ trans('messages.view_all_notifications', [], session('locale')) }}</a>
                </div>
              </div>
            </div>

            <!-- Divider -->
            <div class="h-8 w-px bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>

            <!-- Profile (avatar + dropdown) -->
            <div class="relative">
              <button id="profileBtn" class="flex items-center gap-2">
                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 bg-primary/20 flex items-center justify-center"
                     style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAMV8cxyrsD5UEUacXZKG1uOrUd6OzsEMfwBfXgQOZmbd7p6YyokoBslcg1oqcqY76c8ztgCAg9dpxT70RZAni__6Sdm-iZTfhOxMbIXEtwSl4MCoUCDqDyTBo5EJOYTIFZdvlI8Nl6JPqU_JJEyhMWzO11GpP5HTweoTGbGYt2TWdCtKmGd9WwCBzxVeqlhvMLWaP4RGBbymAtu9eOjH-lzowyMre0ADXxfcHJhhtxIqkeMjO3Hsa3iE-vyevTaGk43vmxXPjseZon");'>
                  @if(!auth()->user() || !auth()->user()->user_name)
                    <span class="material-symbols-outlined text-primary">person</span>
                  @endif
                </div>
                <span class="hidden md:inline text-sm font-medium">{{ auth()->user()->user_name ?? trans('messages.user_default', [], session('locale')) }}</span>
                <span class="material-symbols-outlined text-gray-500">expand_more</span>
              </button>
              <!-- Profile Dropdown -->
              <div id="profileMenu" class="dropdown absolute top-full mt-2 {{ $currentLocale === 'en' ? 'left-0' : 'right-0' }} w-48 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-lg z-20">
                <ul class="py-2">
                  <li>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-secondary dark:hover:bg-primary/20">
                      <span class="material-symbols-outlined text-base">person</span> {{ trans('messages.profile', [], session('locale')) }}
                    </a>
                  </li>
                  <li>
                    <a href="#" onclick="logout(); return false;" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10">
                      <span class="material-symbols-outlined text-base">logout</span> {{ trans('messages.logout_title', [], session('locale')) }}
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          
        </header>

  @yield('main')

<script>
function logout() {
    Swal.fire({
        title: '{{ trans('messages.confirm_logout', [], session('locale')) }}',
        text: '{{ trans('messages.are_you_sure_logout', [], session('locale')) }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '{{ trans('messages.logout_title', [], session('locale')) }}',
        cancelButtonText: '{{ trans('messages.cancel', [], session('locale')) }}'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a form and submit it for logout
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('logout') }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Load notifications
async function loadNotifications() {
    try {
        const response = await fetch('/dashboard/notifications', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        const notifList = document.getElementById('notifList');
        const notifBadge = document.getElementById('notifBadge');
        
        if (data.success && data.notifications && data.notifications.length > 0) {
            // Update badge
            if (notifBadge) {
                notifBadge.textContent = data.count > 99 ? '99+' : data.count;
                notifBadge.classList.remove('hidden');
            }
            
            // Render notifications
            notifList.innerHTML = data.notifications.map(notif => {
                return `
                    <li class="px-3 py-3 hover:bg-secondary/50 dark:hover:bg-primary/10 transition cursor-pointer" onclick="window.location.href='${notif.link}'">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined ${notif.iconColor}">${notif.icon}</span>
                            <div class="flex-1">
                                <p class="text-sm font-medium">${notif.title}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">${notif.message}</p>
                                ${notif.time ? `<p class="text-xs text-gray-400 dark:text-gray-500 mt-1">${notif.time}</p>` : ''}
                            </div>
                        </div>
                    </li>
                `;
            }).join('');
        } else {
            // No notifications
            if (notifBadge) {
                notifBadge.classList.add('hidden');
            }
            notifList.innerHTML = `
                <li class="px-3 py-4 text-center text-gray-500 text-sm">
                    {{ trans('messages.no_notifications', [], session('locale')) ?: 'No notifications' }}
                </li>
            `;
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        const notifList = document.getElementById('notifList');
        const notifBadge = document.getElementById('notifBadge');
        if (notifBadge) {
            notifBadge.classList.add('hidden');
        }
        if (notifList) {
            notifList.innerHTML = `
                <li class="px-3 py-4 text-center text-red-500 text-sm">
                    {{ trans('messages.error_loading_data', [], session('locale')) }}
                </li>
            `;
        }
    }
}

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    // Refresh notifications every 5 minutes
    setInterval(loadNotifications, 5 * 60 * 1000);
});
</script>
<!-- 
    Composant : Sidebar Admin
    Description : Menu latéral de navigation pour l'administration
-->

<div class="flex flex-col h-full">
    
    <!-- Logo -->
    <div class="px-6 py-6 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-primary-600">STM v2</h2>
        <p class="text-sm text-gray-500 mt-1">Administration</p>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
        
        <!-- Dashboard -->
        <a href="/admin/dashboard" 
           class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition <?= isset($active_menu) && $active_menu === 'dashboard' ? 'bg-primary-50 text-primary-600' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-medium">Dashboard</span>
        </a>
        
        <!-- Campagnes -->
        <a href="/admin/campaigns" 
           class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition <?= isset($active_menu) && $active_menu === 'campaigns' ? 'bg-primary-50 text-primary-600' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            <span class="font-medium">Campagnes</span>
        </a>
        
        <!-- Promotions -->
        <a href="/admin/products" 
           class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition <?= isset($active_menu) && $active_menu === 'products' ? 'bg-primary-50 text-primary-600' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span class="font-medium">Promotions</span>
        </a>
        
        <!-- Clients -->
        <a href="/admin/customers" 
           class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition <?= isset($active_menu) && $active_menu === 'customers' ? 'bg-primary-50 text-primary-600' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="font-medium">Clients</span>
        </a>
        
        <!-- Commandes -->
        <a href="/admin/orders" 
           class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition <?= isset($active_menu) && $active_menu === 'orders' ? 'bg-primary-50 text-primary-600' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="font-medium">Commandes</span>
        </a>
        
        <hr class="my-4 border-gray-200">
        
        <!-- Statistiques -->
        <a href="/admin/stats" 
           class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-lg transition <?= isset($active_menu) && $active_menu === 'stats' ? 'bg-primary-50 text-primary-600' : '' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span class="font-medium">Statistiques</span>
        </a>
        
    </nav>
    
    <!-- Footer Sidebar -->
    <div class="px-6 py-4 border-t border-gray-200">
        <div class="text-xs text-gray-500">
            <p class="font-medium">Version 2.0.0</p>
            <p class="mt-1">© <?= date('Y') ?> Trendy Foods</p>
        </div>
    </div>
    
</div>

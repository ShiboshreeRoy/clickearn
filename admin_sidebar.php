<?php
// Get the current page name (e.g., 'admin_dashboard.php')
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-gray-900 text-gray-300 hidden md:flex flex-col flex-shrink-0 transition-all duration-300">
    <div class="h-16 flex items-center justify-center border-b border-gray-800 bg-gray-900">
        <span class="text-xl font-bold text-white tracking-wider"><i class="fas fa-shield-alt text-blue-500 mr-2"></i> ADMIN</span>
    </div>

    <div class="p-4 border-b border-gray-800 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">A</div>
        <div>
            <p class="text-sm font-bold text-white">Super Admin</p>
            <p class="text-xs text-green-400">● Online</p>
        </div>
    </div>
    
    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1">
            <li>
                <a href="admin_dashboard.php" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-800 hover:text-white transition <?= $current_page == 'admin_dashboard.php' ? 'bg-blue-600 text-white border-r-4 border-blue-400' : '' ?>">
                    <i class="fas fa-tachometer-alt w-5"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="admin_withdrawals.php" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-800 hover:text-white transition <?= $current_page == 'admin_withdrawals.php' ? 'bg-blue-600 text-white border-r-4 border-blue-400' : '' ?>">
                    <i class="fas fa-money-bill-wave w-5"></i> Withdrawals 
                </a>
            </li>
            <li>
                <a href="admin_users.php" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-800 hover:text-white transition <?= $current_page == 'admin_users.php' ? 'bg-blue-600 text-white border-r-4 border-blue-400' : '' ?>">
                    <i class="fas fa-users w-5"></i> Users
                </a>
            </li>
            <li>
    <a href="admin_payments.php" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-800 hover:text-white transition">
        <i class="fas fa-credit-card w-5"></i> Payments
    </a>
</li><li>
    <a href="spin.php" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-800 hover:text-white transition">
        <i class="fas fa-dharmachakra w-5"></i> Lucky Spin
    </a>
</li><li>
    <a href="admin_news.php" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-800 hover:text-white transition">
        <i class="fas fa-bullhorn w-5"></i> Announcements
    </a>
</li>
            <li>
                <a href="admin_settings.php" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-800 hover:text-white transition <?= $current_page == 'admin_settings.php' ? 'bg-blue-600 text-white border-r-4 border-blue-400' : '' ?>">
                    <i class="fas fa-cogs w-5"></i> Settings
                </a>
            </li>
        </ul>
    </nav>

    <div class="p-4 border-t border-gray-800">
        <a href="logout.php" class="flex items-center gap-2 text-red-400 hover:text-red-300 transition">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>
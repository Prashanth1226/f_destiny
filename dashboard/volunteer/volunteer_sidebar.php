<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function activeMenu($page, $currentPage)
{
    return $page === $currentPage
        ? 'bg-blue-600 text-white shadow-md shadow-blue-600/10'
        : 'text-slate-500 hover:bg-white/60 hover:text-slate-900 border border-transparent hover:border-slate-100';
}
?>

<aside class="w-64 glass-sidebar hidden md:block flex-shrink-0">


<div class="p-6 border-b border-slate-200/60 text-center">
    <div class="w-12 h-12 mx-auto bg-gradient-to-tr from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center text-lg shadow-md shadow-indigo-500/20 font-black text-white">
        <?= strtoupper(substr($volunteer['name'] ?? 'V', 0, 1)) ?>
    </div>

    <h2 class="mt-3 font-extrabold text-sm text-slate-900 tracking-tight">
        <?= htmlspecialchars($volunteer['name'] ?? 'Authorized User') ?>
    </h2>

    <span class="text-[10px] font-bold text-blue-600 bg-blue-50 border border-blue-200/50 inline-block px-2 py-0.5 rounded-full mt-1">
        Volunteer
    </span>
</div>

<nav class="p-4 space-y-1">

    <a href="volunteer_index.php"
       class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all <?= activeMenu('volunteer_index.php', $currentPage); ?>">
        <span>📊</span>
        Dashboard Base
    </a>

    <a href="available_tasks.php"
       class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all <?= activeMenu('available_tasks.php', $currentPage); ?>">
        <span>📋</span>
        Available Tasks Hub
    </a>

    <a href="active_tasks.php"
       class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all <?= activeMenu('active_tasks.php', $currentPage); ?>">
        <span>⚡</span>
        Active Handshakes
    </a>

    <a href="history.php"
       class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all <?= activeMenu('history.php', $currentPage); ?>">
        <span>⏱</span>
        Distribution Ledger
    </a>

    <a href="volunteer_profile.php"
       class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all <?= activeMenu('volunteer_profile.php', $currentPage); ?>">
        <span>👤</span>
        Profile Center
    </a>

</nav>

</aside>

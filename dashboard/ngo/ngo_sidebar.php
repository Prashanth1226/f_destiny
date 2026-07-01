<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-64 min-h-screen bg-gradient-to-b from-blue-700 to-purple-700 text-white">

    <div class="p-6 text-center border-b border-blue-500">
        <h1 class="text-2xl font-bold">F-Destiny</h1>
        <p class="text-sm">NGO Panel</p>
    </div>

    <nav class="mt-5">

        <a href="ngo_index.php"
        class="block px-6 py-3 hover:bg-blue-500 <?php echo ($current_page=='ngo_index.php') ? 'bg-blue-500' : ''; ?>">
            Dashboard
        </a>

        <a href="request_food.php"
        class="block px-6 py-3 hover:bg-blue-500 <?php echo ($current_page=='request_food.php') ? 'bg-blue-500' : ''; ?>">
            Request Food
        </a>

        <a href="my_requests.php"
        class="block px-6 py-3 hover:bg-blue-500 <?php echo ($current_page=='my_requests.php') ? 'bg-blue-500' : ''; ?>">
            My Requests
        </a>

        <a href="available_donations.php"
        class="block px-6 py-3 hover:bg-blue-500 <?php echo ($current_page=='available_donations.php') ? 'bg-blue-500' : ''; ?>">
            Available Donations
        </a>

    </nav>

</aside>
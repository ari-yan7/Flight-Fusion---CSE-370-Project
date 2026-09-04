<?php
?>
<header>
    <nav>
        <div class="nav_logo">
            <h1><a href="<?php echo ff_is_admin() ? 'admin_dashboard.php' : 'home.php'; ?>">FLIGHTFUSION</a></h1>
        </div>
        <ul class="nav_link">
        <?php if (ff_is_admin()) { ?>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="show_flights.php">Flights</a></li>
            <li><a href="admin_bookings.php">Bookings</a></li>
            <li><a href="admin_passengers.php">Passengers</a></li>
            <li><a href="admin_waitlist.php">Waitlist</a></li>
            <li><a href="admin_swaps.php">Seat Swaps</a></li>
            <li><a href="admin_rewards.php">Rewards</a></li>
            <li><a href="admin_discounts.php">Discounts</a></li>
            <li><a href="admin_weather.php">Weather</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php } else if (ff_is_passenger()) { ?>
            <li><a href="home.php">Home</a></li>
            <li><a href="search_flights.php">Flights</a></li>
            <li><a href="my_bookings.php">My Bookings</a></li>
            <li><a href="my_waitlist.php">Waitlist</a></li>
            <li><a href="swap_requests.php">Seat Swap</a></li>
            <li><a href="my_rewards.php">Rewards</a></li>
            <li><a href="my_discounts.php">Discounts</a></li>
            <li><a href="my_vouchers.php">Vouchers</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php } else { ?>
            <li><a href="index.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
            <li><a href="search_flights.php">Flights</a></li>
        <?php } ?>
        </ul>
    </nav>
</header>

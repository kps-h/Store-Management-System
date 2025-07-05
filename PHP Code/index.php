<?php
// Sample PHP code to include a header or any other dynamic content if needed
include('db.php');
// Include the session management file
include('session.php'); // This file handles session validation

// Optionally, check if the user is logged in by checking a specific session variable (like 'user_id')
// For example, let's assume 'user_id' is set when the user logs in.
if (!isset($_SESSION['user_id'])) {
    // If no user is logged in, redirect to the login page
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="styleN.css"> <!-- External CSS file link -->
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Navbar Styles */
        nav {
            background-color: #333;
            color: #fff;
            padding: 10px 0;
            margin-bottom: 20px; /* Add this to your navigation CSS if it's below the form */
            position: relative;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: white;
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
			white-space: nowrap;
        }

        .logo i {
            margin-right: 10px;
            font-size: 30px;
        }

        /* Navbar Items */
        ul {
            list-style: none;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-grow: 1;
        }

        ul li {
            position: relative;
        }

        ul li a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: background 0.3s;
			white-space: nowrap;
        }

        ul li a:hover {
            background-color: #575757;
        }

        /* Dropdown Styles */
        ul li ul {
            display: none;
            position: absolute;
            top: 40px;
            left: 0;
            background-color: #333;
            padding: 10px 0;
            width: 200px;
        }

        ul li:hover > ul {
            display: block;
        }

        ul li ul li a {
            padding: 10px 20px;
        }

        /* Hamburger Styles */
        .hamburger {
            display: none;
            cursor: pointer;
            flex-direction: column;
            gap: 5px;
        }

        .hamburger div {
            width: 25px;
            height: 3px;
            background-color: white;
        }
        /* Setting Dropdown Menu */
.setting-dropdown {
    position: relative;
}

.setting-menu {
    display: none;
    position: absolute;
    top: 40px;
    right: 0;
    background-color: #333;
    border-radius: 5px;
    width: 160px;
    z-index: 10;
    padding: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.setting-menu li a {
    display: block;
    padding: 10px 20px;
    color: white;
    text-decoration: none;
}

.setting-menu li a:hover {
    background-color: #575757;
}
        /* Responsive Styling */
        @media screen and (max-width: 768px) {
            ul {
                display: none;  /* Initially hide the menu */
                flex-direction: column;
                gap: 10px;
                align-items: center;
                width: 100%;
                padding: 10px;
                background-color: #333;
                position: absolute;
                top: 60px;
                left: 0;
                z-index: 10;
				
            }

            ul li {
                width: 100%;
                text-align: center;
            }

            .container {
                width: 90%;
            }

            .logo {
                text-align: center;
                margin-bottom: 20px;
            }

            .hamburger {
                display: flex;  /* Show the hamburger icon */
            }

            ul li a {
                padding: 10px;
            }

            /* Mobile Dropdown */
            ul li ul {
                position: static;
                width: 100%;
				white-space: nowrap;
            }
        }

        /* Show menu when active */
        .nav-active {
            display: flex !important; /* Override initial hidden state */
        }

        /* Iframe container */
        .iframe-container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>

    <nav>
        <div class="container">
            <div class="logo">
                <i class="fas fa-cogs"></i> 
                SMS
            </div>
            <div class="hamburger" id="hamburger">
                <div></div>
                <div></div>
                <div></div>
            </div>
            <ul id="nav-list">
                <li><a href="zombie.php" target="content">Home</a></li>
                <li>
                    <a href="#">Item Master</a>
                    <ul>
                        <li><a href="item_group.php" target="content">Item Group</a></li>
                        <li><a href="item.php" target="content">Item</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#">Controllers</a>
                    <ul>
                        <li><a href="vendor.php" target="content">Vendor</a></li>
                        <li><a href="recipient.php" target="content">Recipient</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#">Stock</a>
                    <ul>
                        <li><a href="stock_in_copy.php" target="content">Stock In</a></li>
                        <li><a href="stock_out_copy.php" target="content">Stock Out</a></li>
						<li><a href="rejection_form.php" target="content">Rejection Form</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#">Ledger</a>
                    <ul>
                        <li><a href="stock_ledger_items.php" target="content">Item Ledger</a></li>
                        <li><a href="ledger.php" target="content">Ledger Description</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#">Stock Reports</a>
                    <ul>  
                        <li><a href="test.php" target="content">Stock In Report</a></li>
                        <li><a href="test1.php" target="content">Stock Out Report</a></li>
                    </ul>
                </li>
                <li><a href="reports.php" target="content">Reports</a></a></li>
                <li class="setting-dropdown">
    <a href="javascript:void(0);" id="settingToggle">Setting</a>
    <ul class="setting-menu" id="settingMenu">
        <li><a href="logout.php" class="btn-logout">Log Out</a></li>
    </ul>
</li>
            </ul>
        </div>
    </nav>

    <div class="iframe-container">
        <!-- The iframe that will load content -->
        <iframe name="content" src="home.php"></iframe>
    </div>

    <!-- JavaScript for hamburger menu toggle -->
    <script>
        const hamburger = document.getElementById('hamburger');
        const navList = document.getElementById('nav-list');
        const navLinks = document.querySelectorAll('#nav-list a');

        // Toggle the menu when the hamburger is clicked
        hamburger.addEventListener('click', () => {
            navList.classList.toggle('nav-active');
        });

        // Close the menu when a navigation link is clicked
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navList.classList.remove('nav-active');
            });
        });
		
		
    </script>

</body>
</html>

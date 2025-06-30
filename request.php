<?php
// request.php - City Selection Page
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'air_quality_index';
$username = 'root';
$password = '';

$error = ''; // Initialize error message

// Get favorite color from cookie, default to a standard blue if not set
$fav_color = isset($_COOKIE['fav_color']) ? htmlspecialchars($_COOKIE['fav_color']) : '#3498db';

// Function to darken a hex color (retained for custom color logic)
function darkenColor($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $r = max(0, $r - ($r * $percent / 100));
    $g = max(0, $g - ($g * $percent / 100));
    $b = max(0, $b - ($b * $percent / 100));

    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
                 . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
                 . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

$fav_color_dark = darkenColor($fav_color, 20);

// Establish MySQLi database connection
$conn = mysqli_connect($host, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// Process city selection form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['selected_cities'])) {
        $selectedCities = $_POST['selected_cities'];
        $countSelected = count($selectedCities);
        
        if ($countSelected >= 1 && $countSelected <= 10) {
            $_SESSION['selected_cities'] = $selectedCities;
            mysqli_close($conn); // Close connection before redirect
            header("Location: show.php");
            exit();
        } else {
            $error = "Please select at least 1 and at most 10 cities to proceed.";
        }
    } else {
        $error = "Please select at least 1 city to proceed.";
    }
}

// Get all cities from database using MySQLi Procedural
$allCities = [];
$result = mysqli_query($conn, "SELECT City, AQI FROM cities ORDER BY City ASC");

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $allCities[] = $row;
    }
    mysqli_free_result($result); // Free result set
} else {
    $error = "Error fetching cities from database: " . mysqli_error($conn);
}

// Prepare AQI data for display (logic unchanged as it's not DB interaction)
$aqiValues = []; 
foreach ($allCities as $city) {
    $aqi = $city['AQI'];
    $color = '';
    $status = '';
    
    if ($aqi >= 301) { $color = '#b71c1c'; $status = 'Hazardous'; } 
    elseif ($aqi >= 201) { $color = '#e57373'; $status = 'Very Unhealthy'; } 
    elseif ($aqi >= 151) { $color = '#ff8a65'; $status = 'Unhealthy'; } 
    elseif ($aqi >= 101) { $color = '#ffb74d'; $status = 'Unhealthy for Sensitive Groups'; } 
    elseif ($aqi >= 51) { $color = '#fdd835'; $status = 'Moderate'; } 
    else { $color = '#a8e05f'; $status = 'Good'; }
    
    $aqiValues[$city['City']] = [
        'value' => $aqi,
        'color' => $color,
        'status' => $status
    ];
}

mysqli_close($conn); // Close connection at the end of the script
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Air Quality Request</title>
    <style>
    /* Simplified CSS for basic styling */
    :root {
        --primary: <?php echo $fav_color; ?>;
        --primary-dark: <?php echo $fav_color_dark; ?>;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }

    body {
        background-color: #f4f4f4;
        color: #333;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
    }

    .main-container {
        background-color: #ffffff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 900px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    header {
        background-color: var(--primary);
        color: white;
        padding: 20px;
        text-align: center;
        border-radius: 8px;
        margin-bottom: 20px;
        position: relative;
    }

    .header-logo {
        width: 50px;
        height: 50px;
        vertical-align: middle;
        margin-right: 10px;
    }

    header h1 {
        font-size: 1.8rem;
        margin-bottom: 10px;
    }

    /* Profile & Logout Buttons */
    .header-buttons {
        position: absolute;
        top: 20px;
        right: 20px;
        display: flex;
        gap: 10px;
        z-index: 100; /* Ensure buttons are above other content */
    }

    .profile-btn, .logout-btn {
        background-color: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        padding: 8px 12px;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9rem;
        transition: background-color 0.3s ease;
        text-decoration: none;
    }

    .profile-btn:hover, .logout-btn:hover {
        background-color: rgba(255, 255, 255, 0.4);
    }

    .profile-dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        min-width: 250px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-radius: 8px;
        padding: 15px;
        z-index: 1;
        margin-top: 10px;
        text-align: left; /* Align text within dropdown */
    }

    .profile-dropdown-content.show {
        display: block;
    }

    .profile-header {
        text-align: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .profile-header h3 {
        margin: 0 0 5px;
        color: #333;
    }

    .profile-header p {
        color: #666;
        font-size: 0.85rem;
    }

    .profile-details {
        padding: 10px 0;
    }

    .profile-details p {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        color: #555;
        font-size: 0.9rem;
    }

    /* Main Content */
    .container {
        display: flex;
        justify-content: center;
        align-items: flex-start; /* Align items to the top for better flow */
        width: 100%; /* Ensure container takes full width */
    }

    .city-selection-box {
        background: #f9f9f9;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        width: 100%;
    }

    .city-selection-box h2 {
        text-align: center;
        color: var(--primary);
        margin-bottom: 15px;
        font-size: 1.5rem;
    }

    .subtitle {
        text-align: center;
        color: #666;
        margin-bottom: 20px;
        font-size: 1rem;
    }

    .highlight {
        color: var(--primary);
        font-weight: bold;
    }

    /* Messages */
    .message {
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
        text-align: center;
        font-size: 0.9rem;
    }

    .error-message {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* City Search */
    .city-search {
        position: relative;
        margin-bottom: 20px;
    }

    .city-search input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1rem;
    }

    .city-search input:focus {
        border-color: var(--primary);
        outline: none;
    }

    /* Selection Counter */
    .selection-counter {
        text-align: right;
        margin-bottom: 15px;
        font-size: 0.9rem;
        color: #666;
    }

    .selection-counter span {
        font-weight: bold;
        color: var(--primary);
    }

    .selection-counter span.error {
        color: #dc3545;
    }

    /* City Grid */
    .city-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 10px;
        max-height: 300px; /* Limit height for scroll */
        overflow-y: auto;
        padding-right: 5px; /* Space for scrollbar */
        margin-bottom: 20px;
        border: 1px solid #eee; /* Light border around grid */
        padding: 10px;
        border-radius: 4px;
        background-color: #fff;
    }

    .city-grid::-webkit-scrollbar {
        width: 6px;
    }

    .city-grid::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .city-grid::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }

    .city-grid::-webkit-scrollbar-thumb:hover {
        background: #aaa;
    }

    .city-item {
        display: flex;
        align-items: center;
        padding: 8px 10px;
        background: #fdfdfd;
        border-radius: 5px;
        border: 1px solid #e0e0e0;
        transition: background-color 0.2s ease;
    }

    .city-item:hover {
        background: #e9e9e9;
    }

    .city-item input[type="checkbox"] {
        margin-right: 10px;
        cursor: pointer;
        width: 16px;
        height: 16px;
    }

    .city-item label {
        display: flex;
        align-items: center;
        cursor: pointer;
        flex-grow: 1;
        font-size: 0.9rem;
        margin-bottom: 0; /* Override default label margin */
    }

    /* Save Button */
    .btn-save {
        width: 100%;
        background-color: var(--primary);
        color: white;
        border: none;
        border-radius: 5px;
        padding: 12px;
        font-size: 1rem;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-save:hover {
        background-color: var(--primary-dark);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .main-container {
            padding: 20px;
        }
        .city-grid {
            grid-template-columns: 1fr;
        }
        .city-selection-box {
            padding: 20px;
        }
        header h1 {
            font-size: 1.6rem;
        }
        .header-buttons {
            position: relative; /* Allow buttons to stack or center */
            justify-content: center;
            margin-top: 15px;
            right: auto;
            top: auto;
            flex-wrap: wrap; /* Allow buttons to wrap on smaller screens */
        }
        .profile-btn, .logout-btn {
            width: auto;
        }
    }

    @media (max-width: 480px) {
        .main-container {
            width: 95%;
            padding: 15px;
        }
        header {
            padding: 15px;
        }
        header h1 {
            font-size: 1.4rem;
        }
        .city-selection-box h2 {
            font-size: 1.4rem;
        }
        .btn-save {
            padding: 10px;
            font-size: 0.9rem;
        }
        .profile-dropdown-content {
            min-width: unset;
            width: 100%;
            left: 0;
            right: 0;
            transform: none; /* Reset transform for simpler positioning */
            border-radius: 0;
            box-shadow: none;
        }
    }
    </style>
</head>
<body>
    <div class="main-container">
        <header>
            <img src="images.png" alt="AQI Logo" class="header-logo">
            <h1>Air Quality Monitoring Dashboard</h1> <div class="header-buttons">
                <button class="profile-btn" onclick="toggleProfileDropdown()">
                    <?php echo htmlspecialchars($_SESSION['user_fname'] ?? 'Guest'); ?> </button>
                <a href="logout.php" class="logout-btn">
                    Logout </a>
                <div class="profile-dropdown-content" id="profileDropdown">
                    <div class="profile-header">
                        <h3><?php echo htmlspecialchars($_SESSION['user_fname'] ?? 'Guest'); ?></h3>
                        <p><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="profile-details">
                        <p>User ID: <?php echo htmlspecialchars($_SESSION['user_id'] ?? 'N/A'); ?></p> </div>
                </div>
            </div>
        </header>

        <div class="container">
            <div class="city-selection-box">
                <h2>City Selection</h2> <p class="subtitle">Select <span class="highlight">at least 1 and at most 10 cities</span> to monitor their air quality</p>
                
                <?php if (!empty($error)): ?>
                    <div class="message error-message">
                        <?php echo $error; ?> </div>
                <?php endif; ?>
                
                <form action="request.php" method="POST">
                    <div class="city-search">
                        <input type="text" id="city-search" placeholder="Search cities..."> </div>
                    
                    <div class="selection-counter">
                        <span id="selected-count">0</span>/10 cities selected
                    </div>
                    
                    <div class="city-grid">
                        <?php foreach ($allCities as $city): ?>
                            <div class="city-item">
                                <input type="checkbox" 
                                            id="city_<?php echo htmlspecialchars(strtolower(str_replace(' ', '_', $city['City']))); ?>" 
                                            name="selected_cities[]" 
                                            value="<?php echo htmlspecialchars($city['City']); ?>">
                                <label for="city_<?php echo htmlspecialchars(strtolower(str_replace(' ', '_', $city['City']))); ?>">
                                    <span><?php echo htmlspecialchars($city['City']); ?></span> </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" class="btn-save">
                        Save Selection </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // City selection counter and limit enforcement
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name="selected_cities[]"]');
            const counterSpan = document.getElementById('selected-count');
            const maxSelections = 10;
            
            function updateCounter() {
                const checkedCount = document.querySelectorAll('input[type="checkbox"][name="selected_cities[]"]:checked').length;
                counterSpan.textContent = checkedCount;
                
                if (checkedCount > maxSelections) {
                    counterSpan.classList.add('error');
                } else {
                    counterSpan.classList.remove('error');
                }
            }
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checkedCount = document.querySelectorAll('input[type="checkbox"][name="selected_cities[]"]:checked').length;
                    
                    if (checkedCount > maxSelections) {
                        this.checked = false;
                    }
                    updateCounter();
                });
            });
            
            // Initialize counter
            updateCounter();
            
            // City search functionality
            const searchInput = document.getElementById('city-search');
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const cityItems = document.querySelectorAll('.city-item');
                
                cityItems.forEach(item => {
                    const cityName = item.querySelector('span').textContent.toLowerCase();
                    item.style.display = cityName.includes(searchTerm) ? 'flex' : 'none';
                });
            });

            // Profile Dropdown Toggle
            function toggleProfileDropdown() {
                document.getElementById("profileDropdown").classList.toggle("show");
            }

            // Close dropdown when clicking outside
            window.onclick = function(event) {
                if (!event.target.matches('.profile-btn') && !event.target.closest('.profile-dropdown-content')) {
                    const dropdown = document.getElementById("profileDropdown");
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    }
                }
            }
            
            window.toggleProfileDropdown = toggleProfileDropdown;
        });
    </script>
</body>
</html>
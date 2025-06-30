<?php
// show.php - Display Selected Cities AQI
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Redirect to request.php if no cities are selected in session (prevents direct access or stale data)
if (!isset($_SESSION['selected_cities']) || empty($_SESSION['selected_cities'])) {
    header("Location: request.php");
    exit();
}

// Database configuration - ENSURE THIS MATCHES YOUR ACTUAL DATABASE SETUP
$host = 'localhost';
$dbname = 'air_quality_index';
$username = 'root';
$password = '';

$selectedCitiesData = []; // Initialize to an empty array

// Get favorite color from cookie, default to a standard blue if not set
$fav_color = isset($_COOKIE['fav_color']) ? htmlspecialchars($_COOKIE['fav_color']) : '#3498db';

// Function to darken a hex color (simple approach)
function darkenColor($hex, $percent) {
    // Remove # if present
    $hex = str_replace('#', '', $hex);

    // Convert hex to RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // Darken each component
    $r = max(0, $r - ($r * $percent / 100));
    $g = max(0, $g - ($g * $percent / 100));
    $b = max(0, $b - ($b * $percent / 100));

    // Convert back to hex
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
                 . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
                 . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

$fav_color_dark = darkenColor($fav_color, 20); // Darken by 20% for primary-dark

// Establish MySQLi database connection
$conn = mysqli_connect($host, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

try {
    // Get selected cities from session
    $selectedCities = $_SESSION['selected_cities'];
    $num_cities = count($selectedCities);

    // Prepare placeholders for the IN clause based on session data
    // Create string of '?' for prepared statement: e.g., ?,?,?,...
    $placeholders = implode(',', array_fill(0, $num_cities, '?'));
    
    // Create type string for mysqli_stmt_bind_param: e.g., 'ssss...' for strings
    $types = str_repeat('s', $num_cities);

    // Get selected cities data from the 'cities' table using prepared statement
    $query = "SELECT City, AQI FROM cities WHERE City IN ($placeholders) ORDER BY City ASC";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        // Dynamically bind parameters (required for mysqli prepared statements with IN clause)
        $bind_params = array($types); // First element is the type string
        foreach ($selectedCities as $key => $value) {
            $bind_params[] = &$selectedCities[$key]; // Pass by reference
        }
        call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmt), $bind_params));

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $selectedCitiesData[] = $row;
        }
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
    } else {
        die("Failed to prepare statement: " . mysqli_error($conn));
    }

    // Prepare AQI data with color coding (logic unchanged as it's not DB interaction)
    $aqiValues = [];
    foreach ($selectedCitiesData as $city) {
        $aqi = $city['AQI'];
        $color = '';
        $status = '';
        
        if ($aqi >= 301) {
            $color = '#b71c1c'; // Hazardous
            $status = 'Hazardous';
        } elseif ($aqi >= 201) {
            $color = '#e57373'; // Very Unhealthy
            $status = 'Very Unhealthy';
        } elseif ($aqi >= 151) {
            $color = '#ff8a65'; // Unhealthy
            $status = 'Unhealthy';
        } elseif ($aqi >= 101) {
            $color = '#ffb74d'; // Unhealthy for Sensitive Groups
            $status = 'Unhealthy for Sensitive Groups';
        } elseif ($aqi >= 51) {
            $color = '#fdd835'; // Moderate
            $status = 'Moderate';
        } else { // 0-50
            $color = '#a8e05f'; // Good
            $status = 'Good';
        }
        
        $aqiValues[$city['City']] = [
            'value' => $aqi,
            'color' => $color,
            'status' => $status
        ];
    }

} catch(Exception $e) { // Catch general exceptions for robustness
    die("An error occurred: " . $e->getMessage()); 
} finally {
    // Close the MySQLi connection
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selected Cities Air Quality</title>
    <style>
        /* Dynamic CSS variables based on cookie */
        :root {
            --primary-color: <?php echo $fav_color; ?>;
            --primary-dark: <?php echo $fav_color_dark; ?>;
            --text-color: #333;
            --background-light: #f0f2f5; /* Simpler, light background */
            --card-bg: #fff;
            --border-color: #ddd;
            --button-primary-bg: var(--primary-color);
            --button-primary-hover-bg: var(--primary-dark);
            --button-secondary-bg: #e9ecef; /* Lighter gray for secondary buttons */
            --button-secondary-hover-bg: #d6d8db;
            --button-secondary-border: #c8cbcf;
        }

        /* General Reset & Body */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif; /* Simpler font */
        }

        body {
            background-color: var(--background-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            color: var(--text-color);
            line-height: 1.6;
        }

        /* Main Container Layout */
        .main-container {
            width: 90%;
            max-width: 900px; /* Consistent max-width */
            display: flex;
            flex-direction: column;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 25px; /* Unified padding */
            border: 1px solid var(--border-color);
        }

        /* Header Styles */
        header {
            background-color: var(--primary-color); /* Solid background for simplicity */
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px; /* Consistent border-radius */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Simpler shadow */
            margin-bottom: 25px;
            position: relative;
        }

        .header-logo { /* The actual logo image */
            width: 60px; /* Slightly larger for prominence */
            height: auto;
            margin-bottom: 15px;
            display: block; /* Ensure it's on its own line */
            margin-left: auto;
            margin-right: auto;
        }

        header h1 {
            font-size: 2rem; /* Clearer heading */
            margin-bottom: 0; /* No extra margin */
            color: #fff;
        }

        /* Back Button & Logout Button */
        .header-buttons {
            position: absolute;
            top: 15px; /* Aligned with the top */
            width: calc(100% - 30px); /* Span across header, considering padding */
            display: flex;
            justify-content: space-between; /* Space out buttons */
            padding: 0 15px; /* Internal padding */
            box-sizing: border-box;
        }

        .back-btn, .logout-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            transition: background-color 0.2s ease;
            white-space: nowrap; /* Prevent text wrapping */
        }

        .back-btn:hover, .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.4);
        }

        /* Main Content Container (City Results Box) */
        .city-results-box {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 25px; /* Consistent padding */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); /* Lighter shadow */
            width: 100%;
            border: 1px solid var(--border-color);
        }

        .city-results-box h2 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.6rem;
        }

        .city-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); /* Slightly wider cards */
            gap: 15px;
            margin-bottom: 25px;
        }

        .city-card {
            background: #fdfdfd;
            border-radius: 6px;
            padding: 15px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05); /* Minimal shadow */
            border-left: 4px solid var(--primary-color); /* Dynamic accent line */
            text-align: left;
        }

        .city-name {
            font-weight: bold; /* Changed to bold for simpler emphasis */
            margin-bottom: 8px;
            color: var(--text-color);
            font-size: 1.1rem;
        }

        .city-aqi {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin-top: 10px;
        }

        .aqi-value {
            padding: 4px 10px; /* Smaller padding */
            border-radius: 12px; /* Softer pills */
            font-weight: bold;
            color: white;
            font-size: 0.9rem;
        }

        .aqi-status {
            font-size: 0.85rem;
            color: #555;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
            header {
                padding: 15px;
                text-align: center;
            }
            .header-logo {
                margin-bottom: 10px;
                margin-top: 50px; /* Adjust margin to give space for top buttons */
            }
            .header-buttons {
                position: static; /* Allows stacking or flex-wrap */
                width: 100%;
                justify-content: space-around; /* Space buttons evenly */
                margin-bottom: 15px; /* Space between buttons and logo/title */
                padding: 0;
            }
            .back-btn, .logout-btn {
                margin-bottom: 0; /* No extra margin for stacked buttons if they are inline-flex */
            }
            header h1 {
                font-size: 1.5rem;
            }
            .city-grid {
                grid-template-columns: 1fr;
            }
            .city-results-box {
                padding: 15px;
            }
            .city-results-box h2 {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                width: 95%;
                padding: 10px;
            }
            header {
                padding: 10px;
            }
            header h1 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <header>
            <div class="header-buttons">
                <a href="request.php" class="back-btn">Back to Selection</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
            <img src="images.png" alt="Air Quality Logo" class="header-logo">
            <h1>Selected Cities Air Quality</h1>
        </header>

        <div class="city-results-box">
            <h2>Your Selected Cities</h2>
            
            <div class="city-grid">
                <?php if (!empty($selectedCitiesData)): ?>
                    <?php foreach ($selectedCitiesData as $city): ?>
                        <div class="city-card">
                            <div class="city-name">
                                <?php echo htmlspecialchars($city['City']); ?>
                            </div>
                            <div class="city-aqi">
                                <span class="aqi-status">AQI: <?php echo $aqiValues[$city['City']]['status']; ?></span>
                                <span class="aqi-value" style="background-color: <?php echo $aqiValues[$city['City']]['color']; ?>">
                                    <?php echo $city['AQI']; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center; color: #888;">No data found for selected cities. Please go back and select cities.</p>
                <?php endif; ?>
            </div>

            </div>
    </div>
</body>
</html>
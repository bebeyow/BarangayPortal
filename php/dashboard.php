<?php 
session_start();
include 'connect.php'; // Make sure this contains a valid $conn

/*if (!isset($_SESSION['client_counter'])) {
    $_SESSION['client_counter'] = 1;
}

if (isset($_POST['proceed_services'])) {
    // Get highest existing client number
    $getClients = "SELECT client_id FROM clients WHERE client_id LIKE 'client%'";
    $result = mysqli_query($connect, $getClients);

    $maxNumber = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $number = (int) filter_var($row['client_id'], FILTER_SANITIZE_NUMBER_INT);
        if ($number > $maxNumber) {
            $maxNumber = $number;
        }
    }

    $nextNumber = $maxNumber + 1;
    $client_id = "client" . $nextNumber;

    // Save to session
    $_SESSION['session_username'] = $client_id;

    // Insert new client if not already exists (which won't happen here but good practice)
    $check_sql = "SELECT * FROM clients WHERE client_id = ?";
    $check_stmt = $connect->prepare($check_sql);
    $check_stmt->bind_param("s", $client_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        $insert_sql = "INSERT INTO clients (client_id, password) VALUES (?, ?)"; // Add default password
        $default_password = password_hash("default_password", PASSWORD_BCRYPT); // Default hashed password
        $insert_stmt = $connect->prepare($insert_sql);
        $insert_stmt->bind_param("ss", $client_id, $default_password);
        $insert_stmt->execute();
        $insert_stmt->close();
    }

    $check_stmt->close();

    header("Location: mainDashboard.php");
    exit();
}*/

// Get today's date
$isUnavailable = false;

// Check if today is unavailable
$today = date("Y-m-d");
$checkDate = $connect->prepare("SELECT * FROM unavailable_office WHERE date = ?");
$checkDate->bind_param("s", $today);
$checkDate->execute();
$result = $checkDate->get_result();
if ($result->num_rows > 0) {
    $isUnavailable = true;
}
$checkDate->close();

if (isset($_POST['proceed_services'])) {
    // If you want to keep the current session intact and just proceed to the booking page, you can redirect:
    header('Location: mainDashboard.php');  // Redirect to the booking page or whatever page you need
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baranggay Portal Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Cinzel:wght@400..900&family=Special+Gothic+Expanded+One&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Cinzel:wght@400..900&family=Special+Gothic+Expanded+One&family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    padding-top: 70px;    
    align-items: center;
}

.logo {
    width: 300px;
    height: auto;
    display: flex;
    justify-content: center;
    margin: 0 auto;
}

.user {
    display: flex;
    flex-direction: row;
    gap: 20px;
    justify-content: center;
    list-style: none;
    margin-top: 20px;
}

.button {
    padding: 12px 24px;
    font-family: "Urbanist", sans-serif;
  font-optical-sizing: auto;
  font-weight: <weight>;
  font-style: normal;
    cursor: pointer;
    border: none;
    font-size: 16px;
    background-color: #288a9f;
    color: white;
    border-radius: 5px;
    transition: background-color 0.3s;
    text-decoration: none;
    width: 220px;
    text-align: center;
    display: flex;
    justify-content: center;
}

.container ul li {
    display: flex;
    flex-direction: column;
}

.button:hover {
    background-color: #006aff;
}

header {
    position: fixed;
    background-color: #006aff;
    width: 100%;
    top: 0;
    left: 0;
}

header h1 {
    color: white;
    text-align: center;
    padding: 20px 0;
    font-family: "Cinzel", serif;
  font-optical-sizing: auto;
  font-weight: 500;
  font-style: normal;
font-size: 25px;
}

@keyframes fadeOut {
    0% { opacity: 1; }
    100% { opacity: 0; display: none; }
}

.announcement-container {
    width: 90%;
    height: 30%;
    margin: 20px auto;
    overflow: hidden;
    border: 2px solid #ccc;
    border-radius: 10px;
    position: relative;
    background-image: url('bkg-ann3.png');
    background-size: cover;
    background-position: center;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.nav-button {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background-color: rgba(0,0,0,0.5);
    border: none;
    color: white;
    font-size: 30px;
    cursor: pointer;
    padding: 10px;
    z-index: 10;
    border-radius: 5px;
}

.prev {
    left: 10px;
    background-color: rgba(0, 0, 0, 0.15);
}

.next {
    right: 10px;
    background-color: rgba(0, 0, 0, 0.15);
}

.announcement-slider {
    display: flex;
    height: 190px;
    transition: transform 0.5s ease-in-out;
    will-change: transform;
}

.slide {
    min-width: 100%;
    height: 100%;
    padding: 20px;
    box-sizing: border-box;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.slide p {
    font-family: "Urbanist", sans-serif;
    font-size: 18px;
    font-weight: 500;
    color: #333;
    margin-bottom: 10px;
    text-align: center;
    max-width: 80%;
}

.slide small {
    font-family: "Urbanist", sans-serif;
    font-size: 14px;
    color: #666;
    margin-top: 10px;
}

.slide img {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
}

@keyframes slide {
    0% { transform: translateX(0); }
    20% { transform: translateX(0); }
    25% { transform: translateX(-100%); }
    45% { transform: translateX(-100%); }
    50% { transform: translateX(-200%); }
    70% { transform: translateX(-200%); }
    75% { transform: translateX(-300%); }
    95% { transform: translateX(-300%); }
    100% { transform: translateX(0); }
}

.bottom-buttons {
    margin-top: 50px;
    display: flex;
    gap: 20px;
    justify-content: center;
}

.bottom-btn {
    padding: 12px 24px;
    background-color: teal;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
}

.bottom-btn:hover {
    background-color: #007070;
}

.modal {
    display: none;
    position: fixed;
    z-index: 999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 20px;
    border-radius: 10px;
    width: 50%;
    text-align: center;
}

.close {
    float: right;
    font-size: 24px;
    cursor: pointer;
    color: red;
}

footer {
    background-color:rgb(99, 159, 243);
    color: white;
    text-align: center;
    padding: 3px 0;
    position: fixed;
    bottom: 0;
    width: 100%;
}

.footer-container {
    font-size: 10px;
}
    </style>
</head>
<body>
    <header>
        <h1>Barangay Service Portal</h1>
    </header>
<img src="logo1.png" alt="logo" class="logo">
<?php if ($isUnavailable): ?>
    <div class="error" style="margin: 10px auto; width: 30%;">
        Office is unavailable today. Booking is temporarily disabled.
    </div>
<?php endif; ?>
<h2 style="text-align:center;">Announcements</h2>
<div class="announcement-container">
    <div class="announcement-slider" id="slider">
        <?php
        $announcements = mysqli_query($connect, "SELECT * FROM announcements ORDER BY created_at DESC");
        while($row = mysqli_fetch_assoc($announcements)) {
            echo "<div class='slide'>";
            if($row['type'] === 'text') {
                echo "<p>".$row['content']."</p>";
            } elseif($row['type'] === 'image') {
                echo "<img src='".$row['content']."' alt='Announcement Image'>";
            }
            echo "<small>Posted on: ".$row['created_at']."</small>";
            echo "</div>";
        }
        ?>
    </div>
    <!-- Navigation Buttons -->
    <button class="nav-button prev" onclick="prevSlide()">&#10094;</button>
    <button class="nav-button next" onclick="nextSlide()">&#10095;</button>
</div>
</div>
<div class="container" >
    <ul class="user">
        <li>
            <?php if ($isUnavailable): ?>
                <button class="button" type="button" style="background-color: gray; cursor: not-allowed;" disabled>
                    Booking Unavailable Today
                </button>
            <?php else: ?>
                <form action="" method="POST">
                    <button class="button" type="submit" name="proceed_services">Book a Services</button>
                </form>
            <?php endif; ?>
        </li>
        <li>
            <a href="about.php" class="button">About</a>
        </li>
    </ul>
</div>
<footer>
    <div class="footer-container">
        <p>&copy; 2025 Barangay Service Portal. All rights reserved.</p>
        <p>Developed by: Group 8</p>
    </div>
</footer>
<script>
    const slider = document.getElementById('slider');
    const slides = slider.children;
    const totalSlides = slides.length;
    let currentIndex = 0;
    let autoSlideInterval;

    function updateSlider() {
        slider.style.transform = `translateX(-${currentIndex * 100}%)`;
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateSlider();
    }

    function prevSlide() {
        currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
        updateSlider();
    }

    function startAutoSlide() {
        autoSlideInterval = setInterval(nextSlide, 5000);
    }

    function stopAutoSlide() {
        clearInterval(autoSlideInterval);
    }

    // Start sliding on load
    startAutoSlide();

    // Optional: Pause when hovering
    const container = document.querySelector('.announcement-container');
    container.addEventListener('mouseenter', stopAutoSlide);
    container.addEventListener('mouseleave', startAutoSlide);
</script>


<?php 
if(isset($_POST['btn_login'])) {
    $valid_user = 'ADMIN';
    $valid_pass = 'admin';

    $user = $_POST['user'];
    $pass = $_POST['pass'];

    if($user === $valid_user && $pass === $valid_pass) {
        $_SESSION['session_username'] = $user;
        header('location:admin.php');
        exit();
    } else {
        echo '<div id="errorMsg" class="error">Incorrect Username and Password. Try again!</div>';
    }
}
?>

</body>
</html>
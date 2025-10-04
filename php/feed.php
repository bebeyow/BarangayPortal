<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baranggay Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            /*background-image: url(background.png);
            background-repeat: repeat;
            background-position: center;*/
        }

        header {
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            background: #006aff;
            padding: 10px;
            height: 50px;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: -250px;
            width: 250px;
            height: 100vh;
            background: #006aff;
            transition: left 0.3s ease-in-out;
            padding-top: 20px;
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar ul {
            list-style: none;
            padding: 20px;
        }

        .sidebar li {
            padding: 15px;
        }

        .sidebar a {
            text-decoration: none;
            color: white;
            font-size: 18px;
            display: flex;
            align-items: center;
        }

        .sidebar i {
            margin-right: 10px;
        }

        .menu-container {
            position: fixed;
            top: 20px;
            left: 20px;
            cursor: pointer;
            z-index: 1000;
        }

        #menuIcon {
            font-size: 30px;
            color: white;
            cursor: pointer;
        }

        .hidden {
            display: none;
            align-items: center;
        }

        .container {
            text-align: center;
        }

        .click {
            cursor: pointer;
            color: blue;
            list-style: none;
        }

        .choices {
            list-style:none;
        }

        .choices li a{
            list-style: none;
            text-decoration: none;
            color: white;
        }

        .choices a {
            background-color: #288a9f;
            padding: 10px 20px;
            border-radius: 5px;
            text-align: center;
        }

        .choices a:hover {
            background-color: #006aff;
            color: white;
        }

        .allBox {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            width: 500px;
            transition: margin-left 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            text-align: center;
            align-items: center;
        }

        .allBox.shift {
            margin-left: 250px;
        }

    </style>

</head>
<body>
<div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: -1;background-image: url('background.png'); background-repeat: repeat; background-size: auto; opacity: 0.5; pointer-events: none; background-position: center;
">
</div>
    <header>
    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="mainDashboard.php"><i class="ri-home-3-line"></i>Back</a></li>
            <li><a href="docu_request.php"><i class="ri-draft-line"></i>Request Document</a></li>
            <li><a href="faci_reserve.php"><i class="ri-feedback-line"></i>Facility Reservation</a></li>
        </ul>
    </div>

    <div class="menu-container">
        <i class="ri-menu-unfold-2-line" id="menuIcon"></i>
    </div>
    </header>

    <div class="allBox">
    <div class="container" id="mainContent">
        <h1>Feedback and Complains</h1>
        <p>Make your feedback or complain through</p>
        <ul class="choices">
            <li>
                <a href="#" class="click" onclick="theForm('anonymous')" class="f_button">Anonymous</a>
            </li>
            <p>or</p>
            <li>
                <a href="#" class="click" onclick="theForm('login')" class="f_button">User with Details</a>
            </li>
        </ul>
    </div>
    <form id="anonymousForm" action="submit_anonymous.php" method="POST" class="hidden" enctype="multipart/form-data">
        <h2>Be heard, stay hidden.</h2>
        <h3>Anonymous Complain and Feedback</h3>
        <textarea placeholder="Enter text here" name="message" class="an_field"></textarea><br>
        <input type="file" name="image_path" placeholder="Upload a picture" class="an_field"><br>
        <button type="submit" name="save">Send</button>
    </form>

    <form id="loginForm" action="submit_with_details" method="POST" class="hidden">
        <h2>Transparency builds trust, so share your thoughts!</h2>
        <h3>Detailed Feedback and Complain</h3>
        <input type="text" name="c_name" placeholder="Name" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <textarea name="feedback" placeholder="Enter text here..."></textarea><br>
        <button type="submit" name="save">Send</bustton>
    </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const menuIcon = document.getElementById("menuIcon");
            const sidebar = document.getElementById("sidebar");
            const mainContent = document.getElementById('mainContent');
            const allBox = document.querySelector(".allBox");

            menuIcon.addEventListener("click", function() {
                sidebar.classList.toggle("show");
                allBox.classList.toggle("shift");
            });

            document.addEventListener("click", function(event) {
                if (!sidebar.contains(event.target) && !menuIcon.contains(event.target)) {
                    sidebar.classList.remove("show");
                    alBox.classList.remove("shift");
                }
            });
        });

        function theForm(type) {
            document.getElementById('anonymousForm').classList.add('hidden');
            document.getElementById('loginForm').classList.add('hidden');

            if(type === 'anonymous') {
                document.getElementById('anonymousForm').classList.remove('hidden');
            } else {
                document.getElementById('loginForm').classList.remove('hidden');
            }
        }

    </script>
</body>
</html>
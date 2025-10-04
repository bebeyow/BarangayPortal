<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Barangay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css" integrity="sha512-kJlvECunwXftkPwyvHbclArO8wszgBGisiLeuDFwNM8ws+wKIw0sv1os3ClWZOcrEB2eRXULYUsm8OVRGJKwGA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Cinzel:wght@400..900&family=Special+Gothic+Expanded+One&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            line-height: 1.6;
        }

        header {
            background: #006aff;
            color: white;
            padding: 10px 0;
            text-align: center;
        }

        header h1 {
            font-family: "Cinzel", serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
            font-size: 25px;
        }

        
        .back {
            position: fixed;
            top: 13px;
            left: 20px;
            cursor: pointer;
            z-index: 1000;
        }

        .back a {
            color: white;
            text-decoration: none;
            font-size: 24px;
        }

        .about-container {
            padding: 20px;
        }

        .about-bio {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 90%;
            max-width: 1200px;
            margin: 0 auto 40px auto;
        }

        .about-bio img {
            width: 70%;
            max-width: 600px;
            height: auto;
            border-radius: 8px;
        }

        .about-section-wrapper {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }

        .about1, .about2, .about3 {
            flex: 1 1 30%;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .about-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .about-row h4 {
            min-width: 120px;
            text-align: left;
            margin: 0;
        }

        .about-row p, .about-row li {
            flex: 1;
            text-align: center;
            margin: 0;
            list-style: none;
        }

        .about ul {
            padding-left: 0;
            margin: 0;
        }

        .about3 li {
            text-align: center;
        }

        @media (max-width: 768px) {
            .about-section-wrapper {
                flex-direction: column;
            }

            .about1, .about2, .about3 {
                width: 100%;
            }
        }

        h2 {
            display: flex;
            justify-content: center;
        }



        #card {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px;
            padding: 20px;
            gap: 30px;
        }

        .official1 {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 250px;
        }

        .officialsBelow {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            width: 100%;
        }

        .official2, .official3, .official4, .official5, .official6, .official7, .official8, .official9, .official10, .official11 {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            text-align: center;
            flex: 1 1 250px;
            max-width: 250px;
        }

        .container {
            padding: 16px;
            text-align: center;
        }

        .official1 img, 
        .official2 img, 
        .official3 img, 
        .official4 img, 
        .official5 img, 
        .official6 img,
        .official7 img, 
        .official8 img, 
        .official9 img, 
        .official10 img, 
        .official11 img {
            width: 120px;
            height: 150px;
            object-fit: cover;
            margin-bottom: 10px;
        }

    </style>
</head>
<body>
    <header>
        <h1>Barangay Portal</h1>
        <div class="back">
        <a href="dashboard.php"><i class="ri-arrow-left-fill"></i></a>
    </div>
    </header>
    <div class="about-container">
        <div class="about-bio">
            <h2>Barangay Mabuhay Biography</h2>
            <img src="BARANGAY HALL.png" alt="Barangay Hall" >
            <div class="about-section-wrapper">
                <div class="about1">
                    <div class="about-row"><h4>Vision:</h4></div>
                    <p>Promote inclusive development, peace, and sustainability</p>
                    <div class="about-row"><h4>Values:</h4></div>
                    <p>Unity, Bayanihan spirit, transparency in governance</p>
                    <div class="about-row"><h4>Location:</h4></div>
                    <p>Barangay Mabuhay, Cuidad Nueva, Alegria Del Sur, Philippines</p>
                    <div class="about-row"><h4>Established:</h4></div>
                    <p>1981</p>
                </div>
                <div class="about2">
                    <div class="about-row"><h4>Population:</h4></div>
                    <p>Approximately 5,000 as of 2024</p>
                    <div class="about-row"><h4>Land Area:</h4></div>
                    <p>Around 41,247 hectares (412.47 km²)</p>
                    <div class="about-row"><h4>Puroks/Sitios:</h4></div>
                    <p>10</p>
                    <div class="about-row"><h4>Livelihood:</h4></div>
                    <p>Farming, Business, Livestock</p>
                    <div class="about-row"><h4>Features:</h4></div>
                    <p>Barangay Mabuhay Fiesta every May 15</p>
                </div>
                <div class="about3">
                    <div class="about-row"><h4>Schools:</h4></div>
                    <p>Mabuhay Elementary School, Mabuhay National High School</p>
                    <div class="about-row"><h4>Programs:</h4></div>
                        <li>Clean-up drives</li>
                        <li>Livelihood training</li>
                        <li>Youth Development</li>
                        <li>Disaster Preparedness</li>
                        <li>Livestock Check-up</li>
                </div>
            </div>
        </div>
    
    
    <h2>Barangay Officials 2025</h2>
    <div id="card">
        <!-- Top Captain Alone -->
        <div class="official1">
            <img src="pic8.jpg" alt="Captain">
            <div class="container">
                <h4><b>Dennis Casuco</b></h4>
                <p>Barangay Captain</p>
            </div>
        </div>

        <div class="officialsBelow">
            <div class="official2">
                <img src="pic9.jpg" alt="">
                <div class="container">
                    <h4><b>Nicole Deloy</b></h4>
                    <p>Barangay Kagawad</p>
                </div>
            </div>
            <div class="official3">
                <img src="pic5(1).jpg" alt="">
                <div class="container">
                    <h4><b>Victor Diaz</b></h4>
                    <p>Barangay Kagawad</p>
                </div>
            </div>
            <div class="official4">
                <img src="pic11.jpg" alt="">
                <div class="container">
                    <h4><b>Maria Albiba</b></h4>
                    <p>Barangay Kagawad</p>
                </div>
            </div>
            <div class="official5">
                <img src="pic6.jpg" alt="">
                <div class="container">
                    <h4><b>Jim Amuto</b></h4>
                    <p>Barangay Kagawad</p>
                </div>
            </div>
            <div class="official6">
                <img src="pic3.jpg" alt="">
                <div class="container">
                    <h4><b>Robert Sulicar</b></h4>
                    <p>Barangay Kagawad</p>
                </div>
            </div>
            <div class="official7">
                <img src="pic1(1).jpg" alt="">
                <div class="container">
                    <h4><b>Jolly Adante</b></h4>
                    <p>Barangay Kagawad</p>
                </div>
            </div>
            <div class="official8">
                <img src="pic2.jpg" alt="">
                <div class="container">
                    <h4><b>Alex Camacho</b></h4>
                    <p>Barangay Kagawad</p>
                </div>
            </div>
            <div class="official9">
                <img src="pic4.jpg" alt="">
                <div class="container">
                    <h4><b>Lincoln Bihag</b></h4>
                    <p>Sangguniang Kabataan (SK) Chairperson</p>
                </div>
            </div>
            <div class="official10">
                <img src="pic7.jpg" alt="">
                <div class="container">
                    <h4><b>Jerry Guiral</b></h4>
                    <p>Barangay Secretary</p>
                </div>
            </div>
            <div class="official11">
                <img src="pic10.jpg" alt="">
                <div class="container">
                    <h4><b>Belle Madanglog</b></h4>
                    <p>Barangay Treasurer</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - BosEats Africa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-dark: #1e5e25;
            --text-color: #333;
            --light-bg: #f5f5f5;
            --white: #ffffff;
            --section-padding: 80px 0;
            --container-max-width: 1200px;
            --mobile-padding: 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
        }

        .about-section {
            padding: var(--section-padding);
            background-color: var(--white);
        }

        .container {
            max-width: var(--container-max-width);
            margin: 0 auto;
            padding: 0 var(--mobile-padding);
        }

        .about-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 40px;
        }

        .about-text {
            flex: 1;
            padding-right: 20px;
        }

        .about-text h1 {
            font-size: 36px;
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .about-text p {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .about-text ul {
            list-style-type: disc;
            padding-left: 20px;
            margin-bottom: 25px;
        }

        .about-text ul li {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .explore-button {
            display: inline-block;
            padding: 12px 30px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .explore-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .about-image {
            flex: 1;
        }

        .team-image {
            width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Vision Section Styling - KEEPING DESKTOP ARRANGEMENT ON MOBILE */
        #vision {
            background-color: #f9f9f9;
            padding: var(--section-padding);
        }

        .vision-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            align-items: flex-start;
        }

        .vision-images-wrapper {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .vision-left,
        .vision-right {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .vision-images {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 20px;
        }

        .vision-img {
            width: 100%;
            max-width: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .vision-img:hover {
            transform: scale(1.03);
        }

        .vision-img.center {
            max-width: 250px;
            margin: 0;
        }

        .vision-content {
            flex: 1;
            max-width: 600px;
            padding-left: 30px;
        }

        .vision-content h2 {
            font-size: 22px;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 600;
        }

        .vision-content p {
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .vision-content ul {
            list-style: none;
            padding: 0;
            margin-bottom: 20px;
        }

        .vision-content ul li {
            font-size: 13px;
            margin-bottom: 12px;
            padding-left: 25px;
            position: relative;
        }

        .vision-content ul li:before {
            content: "•";
            color: var(--primary-color);
            font-size: 20px;
            position: absolute;
            left: 0;
            top: -2px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .about-container {
                gap: 30px;
            }
            
            .about-text h1 {
                font-size: 32px;
            }
            
            .vision-img {
                max-width: 180px;
            }
            
            .vision-img.center {
                max-width: 220px;
            }
            
            .vision-container {
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .about-container {
                flex-direction: column;
                text-align: center;
            }
            
            .about-text {
                padding-right: 0;
                order: 2;
            }
            
            .about-image {
                order: 1;
                width: 100%;
                max-width: 500px;
                margin-bottom: 30px;
            }
            
            .about-text h1 {
                font-size: 28px;
            }
            
            .about-text p, .about-text ul li {
                font-size: 16px;
            }
            
            /* Keep desktop arrangement for vision images on mobile */
            .vision-container {
                flex-direction: column;
                gap: 40px;
            }
            
            .vision-images-wrapper {
                justify-content: center;
                width: 100%;
            }
            
            .vision-img {
                max-width: 120px;
            }
            
            .vision-img.center {
                max-width: 150px;
            }
            
            .vision-content {
                padding-left: 0;
                text-align: center;
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .about-text h1 {
                font-size: 24px;
            }
            
            .about-text p, .about-text ul li {
                font-size: 15px;
            }
            
            .explore-button {
                width: 100%;
                text-align: center;
            }
            
            .vision-content h2 {
                font-size: 24px;
            }
            
            .vision-content p, .vision-content ul li {
                font-size: 15px;
            }
            
            /* Adjust image sizes for very small screens but keep arrangement */
            .vision-img {
                max-width: 100px;
            }
            
            .vision-img.center {
                max-width: 130px;
            }
            
            .vision-images-wrapper {
                gap: 15px;
            }
            
            .vision-left,
            .vision-right {
                gap: 15px;
            }
        }

        @media (max-width: 360px) {
            /* For very small screens, adjust images slightly */
            .vision-img {
                max-width: 90px;
            }
            
            .vision-img.center {
                max-width: 120px;
            }
            
            .vision-images-wrapper {
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include 'includes/e_header.php'; ?>

    <!-- About Section -->
    <div class="about-section">
        <div class="container">
            <div class="about-container">
                <!-- Text Content -->
                <div class="about-text">
                    <h1>Your Gateway to Seamless African Experiences</h1>
                    <p>At BosEatsAfrica, we believe that every journey, celebration, and culinary adventure should be effortless and unforgettable. We are your trusted partner in creating exceptional African experiences, offering a comprehensive suite of services designed to meet your every need.</p>
                    <p>From the moment you envision your trip to its successful completion, BosEatsAfrica is here for you. We provide:</p>
                    <ul>
                        <li>Flight Bookings: Seamless and reliable travel arrangements</li>
                        <li>Events Management: Expert planning for memorable occasions, big or small</li>
                        <li>African Cuisine: A taste of the continent's vibrant flavors</li>
                        <li>Car Hiring: Convenient and comfortable transportation solutions</li>
                        <li>Hotel Reservations: Handpicked accommodations for every preference and budget</li>
                    </ul>
                    <a href="#" class="explore-button">Explore our companies</a>
                </div>

                <!-- Image Section -->
                <div class="about-image">
                    <img src="https://leo.it.tab.digital/s/5zcg7zaWwQfe3bc/preview" alt="Team Image" class="team-image">
                </div>
            </div>
        </div>
    </div>

    <!-- Vision Section -->
    <section id="vision" class="vision-section">
        <div class="container">
            <div class="vision-container">
                <div class="vision-images-wrapper">
                    <div class="vision-left">
                        <img src="https://leo.it.tab.digital/s/TGgWQerqg6J25pp/preview" alt="Boseatsafrica Team 1" class="vision-img">
                        <img src="https://leo.it.tab.digital/s/SC5RR3xpnyHKzsS/preview" alt="Boseatsafrica Team 2" class="vision-img">
                    </div>

                    <div class="vision-images">
                        <!-- Center Image -->
                        <img src="https://leo.it.tab.digital/s/Qdj4wni3wXHLGC5/preview" alt="Boseatsafrica Team 3" class="vision-img center">
                    </div>

                    <div class="vision-right">
                        <img src="https://leo.it.tab.digital/s/TGgWQerqg6J25pp/preview" alt="Boseatsafrica Team 4" class="vision-img">
                        <img src="https://leo.it.tab.digital/s/SC5RR3xpnyHKzsS/preview" alt="Boseatsafrica Team 5" class="vision-img">
                    </div>
                </div>

                <div class="vision-content">
                    <h2>Our Vision</h2>
                    <p>Boseatsafrica was founded with a singular vision: to be the premier, all-in-one platform for experiencing the best of Africa. We understand the unique beauty and vast potential of the continent, and we are dedicated to streamlining every aspect of your engagement – from arrival to departure, and every moment in between.</p>
                    <ul>
                        <li><strong>Efficient Flight Solutions:</strong> Connecting you effortlessly across the globe and within Africa.</li>
                        <li><strong>Inspired Event Planning:</strong> Transforming your concepts into flawless realities.</li>
                        <li><strong>Authentic Gastronomic Journeys:</strong> Sourcing and delivering the true flavors of Africa.</li>
                        <li><strong>Reliable Car Hire:</strong> Safe and comfortable mobility for your travels.</li>
                        <li><strong>Curated Hotel Stays:</strong> Partnering with the finest hotels to ensure your comfort.</li>
                    </ul>
                    <p>At Boseatsafrica, we don't just provide services; we craft complete, unforgettable African narratives. Our commitment to excellence, local expertise, and customer satisfaction drives everything we do, making us your ultimate partner for truly immersive and hassle-free African experiences.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php include_once "../includes/e_header.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - BoseaAfrica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- Main Content -->
    <section class="service-section">
        <!-- First Row: Intro Text Box and Image Box (2 boxes - no border) -->
        <div class="service-row">
            <!-- First Box: Introductory Text (No Border) -->
            <div class="service-box service-text no-border">
                <h1>Your Complete African Experience Partner</h1>
                <p>At Boseatsafrica, we're dedicated to simplifying your journey, enhancing your events, and enriching your experience across Africa. We offer a comprehensive suite of services designed to provide convenience, quality, and authentic local connection. Whatever you need, we're here to deliver excellence.</p>
            </div>

            <!-- Second Box: Image (No Border) -->
            <div class="service-box service-image no-border">
                <img src="https://leo.it.tab.digital/s/nd8aCzZ4gRiwHpo/preview" alt="African Experience" />
            </div>
        </div>

        <!-- Second Row: Services (3 boxes with brown border) -->
        <div class="service-row">
            <!-- Service Box 1: Flight Bookings -->
            <div class="service-box with-border">
                <h2>Flight Bookings: Your Journey Starts Here</h2>
                <p>Navigate air travel complexity with Boseatsafrica. We provide efficient and reliable flight booking services, ensuring you reach your destination comfortably and on schedule.</p>
                <ul>
                    <li><i class="fas fa-arrow-right"></i> Domestic & International Flights</li>
                    <li><i class="fas fa-arrow-right"></i> Competitive Rates</li>
                    <li><i class="fas fa-arrow-right"></i> Personalized Assistance</li>
                    <li><i class="fas fa-arrow-right"></i> Stress-Free Travel</li>
                </ul>
                <a href="../flight/index.php" class="book-flight-button">Book Flight</a>
            </div>

            <!-- Service Box 2: Events Management -->
            <div class="service-box with-border">
                <h2>Events Management: Crafting Unforgettable Moments</h2>
                <p>From intimate gatherings to grand celebrations, Boseatsafrica transforms your vision into a flawless reality. Our expert event management team handles every detail, ensuring your occasion is memorable and perfectly executed.</p>
                <ul>
                    <li><i class="fas fa-arrow-right"></i> Corporate Events</li>
                    <li><i class="fas fa-arrow-right"></i> Social Celebrations</li>
                    <li><i class="fas fa-arrow-right"></i> Cultural & Public Gatherings</li>
                    <li><i class="fas fa-arrow-right"></i> End-to-End Planning</li>
                </ul>
                <a href="../event/index.php" class="book-flight-button">Buy Ticket</a>
            </div>

            <!-- Service Box 3: Authentic African Cuisine -->
            <div class="service-box with-border">
                <h2>Authentic African Cuisine: A Taste of the Continent</h2>
                <p>Experience the rich, diverse, and vibrant flavors of Africa with Boseatsafrica's culinary services. We bring authentic African gastronomy directly to you, prepared with passion, traditional techniques, and the freshest ingredients.</p>
                <ul>
                    <li><i class="fas fa-arrow-right"></i> Catering Services</li>
                    <li><i class="fas fa-arrow-right"></i> Meal Delivery</li>
                    <li><i class="fas fa-arrow-right"></i> Custom Menus</li>
                    <li><i class="fas fa-arrow-right"></i> Culinary Experiences</li>
                </ul>
                <a href="../food/index.php" class="book-flight-button">Order Food</a>
            </div>
        </div>

        <!-- Third Row: Car Hiring, Hotel Reservations, and CTA (3 boxes - first two with border, last no border) -->
        <div class="service-row">
            <!-- Service Box 4: Car Hiring -->
            <div class="service-box with-border">
                <h2>Car Hiring: Your Ride, Your Freedom</h2>
                <p>Explore Africa at your own pace and in comfort with Boseatsafrica's reliable car hiring services. We offer a diverse fleet of well-maintained vehicles, perfectly suited for business travel, family adventures, or personal convenience.</p>
                <ul>
                    <li><i class="fas fa-arrow-right"></i> Diverse Fleet: Sedans, SUVs, Luxury Cars</li>
                    <li><i class="fas fa-arrow-right"></i> Flexible Options: Daily, Weekly, Long-term Rentals</li>
                    <li><i class="fas fa-arrow-right"></i> Chauffeur Services</li>
                    <li><i class="fas fa-arrow-right"></i> Easy Booking</li>
                </ul>
                <a href="../car/index.php" class="book-flight-button">Hire Car</a>
            </div>

            <!-- Service Box 5: Hotel Reservations -->
            <div class="service-box with-border">
                <h2>Hotel Reservations: Your Perfect Stay Awaits</h2>
                <p>Finding the right accommodation is key to a great trip. Boseatsafrica partners with a curated selection of hotels, from luxurious resorts to comfortable boutique stays, ensuring you find the perfect home away from home across Africa.</p>
                <ul>
                    <li><i class="fas fa-arrow-right"></i> Extensive Network: Access to a wide range of hotels</li>
                    <li><i class="fas fa-arrow-right"></i> Personalized Recommendations</li>
                    <li><i class="fas fa-arrow-right"></i> Exclusive Deals</li>
                    <li><i class="fas fa-arrow-right"></i> Seamless Booking</li>
                </ul>
                <a href="../hotel/index.php" class="book-flight-button">Reserve Hotel</a>
            </div>

            <!-- Service Box 6: Ready to Plan CTA (No Border) -->
            <div class="service-box service-cta no-border">
                <h2>Ready to Plan Your Next African Experience?</h2>
                <p>Contact Boseatsafrica today and let us help you craft unforgettable moments!</p>
                <a href="../index.php" class="book-flight-button">Get Started</a>
            </div>
        </div>
    </section>

</body>
</html>

<style>
/* General body styling */
body {
    font-family: 'Poppins', Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f9f9f9;
    line-height: 1.6;
}

/* Service Section */
.service-section {
    padding: 40px 20px;
    background-color: white;
    max-width: 1200px;
    margin: 0 auto;
}

/* Service Row Layout */
.service-row {
    display: grid;
    gap: 30px;
    margin-bottom: 40px;
    align-items: stretch;
}

/* First Row: 2 columns */
.service-row:first-child {
    grid-template-columns: 1fr 1fr;
}

/* Second Row: 3 columns */
.service-row:nth-child(2) {
    grid-template-columns: 1fr 1fr 1fr;
}

/* Third Row: 3 columns */
.service-row:nth-child(3) {
    grid-template-columns: 1fr 1fr 1fr;
}

/* Service Box Base Styles */
.service-box {
    background-color: #ffffff;
    padding: 30px;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    min-height: 420px;
}

.service-box:hover {
    transform: translateY(-5px);
}

/* No Border Boxes */
.service-box.no-border {
    border: none;
    box-shadow: none;
    background: transparent;
    min-height: auto;
}

/* Boxes with Black Border */
.service-box.with-border {
    border: 1px solid #000000;
    box-shadow: none;
    padding-top: 50px;
}

/* Circled Icons - Positioned on the border */
.service-box.with-border::before {
    content: '';
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 60px;
    background-size: 24px;
    background-repeat: no-repeat;
    background-position: center;
    border: 1px solid #000000;
    border-radius: 50%;
    background-color: #ffffff;
    z-index: 2;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Flight Bookings Icon */
.service-row:nth-child(2) .service-box.with-border:nth-child(1)::before {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%232c3e50"><path d="M22 16v-2l-8.5-5V3.5c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5V9L2 14v2l8.5-2.5V19L8 20.5V22l4-1 4 1v-1.5L13.5 19v-5.5L22 16z"/></svg>');
}

/* Events Management Icon */
.service-row:nth-child(2) .service-box.with-border:nth-child(2)::before {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%232c3e50"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>');
}

/* African Cuisine Icon */
.service-row:nth-child(2) .service-box.with-border:nth-child(3)::before {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%232c3e50"><path d="M8.1 13.34l2.83-2.83L3.91 3.5c-1.56 1.56-1.56 4.09 0 5.66l4.19 4.18zm6.78-1.81c1.53.71 3.68.21 5.27-1.38 1.91-1.91 2.28-4.65.81-6.12-1.46-1.46-4.2-1.1-6.12.81-1.59 1.59-2.09 3.74-1.38 5.27L3.7 19.87l1.41 1.41L12 14.41l6.88 6.88 1.41-1.41L13.41 13l1.47-1.47z"/></svg>');
}

/* Car Hiring Icon */
.service-row:nth-child(3) .service-box.with-border:nth-child(1)::before {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%232c3e50"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>');
}

/* Hotel Reservations Icon */
.service-row:nth-child(3) .service-box.with-border:nth-child(2)::before {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%232c3e50"><path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z"/></svg>');
}

/* Service Text Box */
.service-text h1 {
    font-size: 2.5rem;
    margin-bottom: 20px;
    text-align: left;
    color: #2c3e50;
    line-height: 1.3;
}

.service-text p {
    font-size: 1.1rem;
    text-align: left;
    color: #555;
    margin-bottom: 0;
    line-height: 1.6;
}

/* Service Image Box */
.service-image img {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 10px;
}

/* Service Box Content */
.service-box h2 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: #2c3e50;
    text-align: left;
    line-height: 1.4;
}

.service-box p {
    font-size: 1rem;
    color: #555;
    margin-bottom: 20px;
    text-align: left;
    line-height: 1.6;
    flex-grow: 1;
}

.service-box ul {
    list-style-type: none;
    padding-left: 0;
    margin-bottom: 25px;
    text-align: left;
    flex-grow: 1;
}

.service-box ul li {
    font-size: 0.95rem;
    color: #555;
    margin-bottom: 10px;
    display: flex;
    align-items: flex-start;
    line-height: 1.5;
}

/* Pointing Hand Arrow */
.service-box ul li i {
    margin-right: 10px;
    color: #28a745;
    margin-top: 3px;
    flex-shrink: 0;
}

/* Button Style - Aligned at bottom */
.book-flight-button {
    background-color: transparent;
    color: #218838;
    padding: 12px 25px;
    text-decoration: none;
    border-radius: 5px;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-block;
    margin-top: auto;
    border: 1px solid #218838;
    cursor: pointer;
    text-align: center;
    width: 100%;
    box-sizing: border-box;
}

.book-flight-button:hover {
    background-color: #0cb433ff;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}

/* CTA Box */
.service-cta {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-align: center;
    padding: 40px 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    border-radius: 10px;
    min-height: 200px;
}

.service-cta h2 {
    font-size: 1.8rem;
    margin-bottom: 15px;
    text-align: center;
    color: #555;;
    line-height: 1.3;
}

.service-cta p {
    font-size: 1.1rem;
    color: #555;
    text-align: center;
    margin-bottom: 25px;
    line-height: 1.6;
}

.service-cta .book-flight-button {
    background-color: white;
    color: #08b44aff;
    font-weight: 600;
    border: 1px solid white;
    width: auto;
    min-width: 150px;
    margin-top: 0;
}

.service-cta .book-flight-button:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
}

/* Mobile Responsive Design */
@media (max-width: 1024px) {
    .service-section {
        padding: 30px 15px;
    }
    
    .service-row:first-child,
    .service-row:nth-child(2),
    .service-row:nth-child(3) {
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }
    
    .service-text h1 {
        font-size: 2rem;
    }
    
    .service-box {
        min-height: 450px;
    }
}

@media (max-width: 768px) {
    .service-section {
        padding: 20px 15px;
    }
    
    .service-row:first-child,
    .service-row:nth-child(2),
    .service-row:nth-child(3) {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .service-box {
        padding: 25px 20px;
        min-height: auto;
    }
    
    .service-box.with-border {
        padding-top: 45px;
    }
    
    .service-box.with-border::before {
        top: -25px;
        width: 50px;
        height: 50px;
        background-size: 20px;
    }
    
    .service-text h1 {
        font-size: 1.8rem;
        text-align: center;
    }
    
    .service-text p {
        text-align: center;
    }
    
    .service-box h2 {
        text-align: center;
        font-size: 1.3rem;
    }
    
    .service-box p {
        text-align: center;
    }
    
    .service-box ul {
        text-align: center;
    }
    
    .service-box ul li {
        justify-content: center;
    }
    
    .service-image img {
        height: 250px;
    }
    
    .book-flight-button {
        width: 100%;
        margin-top: 20px;
    }
}

@media (max-width: 480px) {
    .service-section {
        padding: 15px 10px;
    }
    
    .service-box {
        padding: 20px 15px;
    }
    
    .service-box.with-border {
        padding-top: 40px;
    }
    
    .service-box.with-border::before {
        top: -20px;
        width: 40px;
        height: 40px;
        background-size: 16px;
    }
    
    .service-text h1 {
        font-size: 1.5rem;
    }
    
    .service-box h2 {
        font-size: 1.2rem;
    }
    
    .service-box p {
        font-size: 0.9rem;
    }
    
    .service-box ul li {
        font-size: 0.9rem;
    }
    
    .book-flight-button {
        padding: 10px 20px;
        font-size: 0.9rem;
    }
    
    .service-cta {
        padding: 30px 20px;
    }
    
    .service-cta h2 {
        font-size: 1.4rem;
    }
    
    .service-cta p {
        font-size: 1rem;
    }
}

/* Animation for smooth loading */
.service-box {
    animation: fadeInUp 0.6s ease-out;
}

.service-row:nth-child(2) .service-box {
    animation-delay: 0.2s;
}

.service-row:nth-child(3) .service-box {
    animation-delay: 0.4s;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
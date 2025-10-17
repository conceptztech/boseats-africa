<?php include_once "../includes/e_header.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet"> <!-- FontAwesome CDN -->
    <style>
        /* General Layout */
        .event-container {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            padding-left: 6%;
            padding-right: 6%;
        }

        .event-left {
            width: 45%;
              border: 1px solid #bebdbdff;
            border-radius: 10px;
             margin-bottom: 20px;
                }

        .event-right {
            width: 45%;
            padding: 20px;
            border-radius: 10px;
             border: 1px solid #bebdbdff;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .event-image {
            width: 90%;
            margin-left: 25px;
            margin-top: 15px;
            border-radius: 10px;
        }

        .event-details h1 {
            font-size: 28px;
            margin: 10px 0;
            margin-left: 25px;
        }

        .event-details p {
            font-size: 16px;
            margin: 5px 0;
            margin-left: 25px;
        }

        .event-actions {
            margin-top: 20px;
        }

        .google-calendar {
            background-color: #ff6f61;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            margin: 10px 0;
            margin-left: 25px;
        }

        .social-links a {
            margin-right: 10px;
        }

        .social-icon {
            font-size: 20px;
            color: #333;
            margin-left: 25px;
        }

        /* Ticket Section */
        .ticket-info h2 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .ticket-item {
            background-color: #F5F5F5;
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 20px;
            align-items: center;
        }

        .ticket-item label {
            font-size: 16px;
        }

        .ticket-input-container {
            display: flex;
            align-items: center;
        }

        .ticket-btn {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            width: 40px;
            height: 40px;
            text-align: center;
        }

        .ticket-quantity {
            width: 50px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 0 10px;
            border: 1px solid #ccc;
            padding: 5px;
        }

        .price {
            font-weight: bold;
            font-size: 18px;
            align-self: center;
        }

        .buy-tickets {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            width: 100%;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 18px;
        }

        /* Venue Section */
        .venue-info {
            margin-top: 20px;
        }

        .venue-info h2 {
            font-size: 20px;
        }

        .venue-info p {
            font-size: 16px;
            color: #555;
        }

        .nav1 {
            background-color: #4CAF50;
            border-color: #4CAF50;
            height: 40px;
            width: 49%;
        }

        .nav1 h2 {
            font-size: 16px;
            color: white;
        }

        .nav1:hover {
            background-color: transparent;
            color: black;
        }

        .nav1 h2:hover {
            color: black;
        }
        .event-prices{
          margin-left: 25px;
        }

        /* Mobile Responsiveness */
@media (max-width: 768px) {
    /* Stack event container sections on mobile */
    .body{
        overflow-x: none;
    }
    .event-container {
        flex-direction: column;
        align-items: center;
        padding-left: 5%;
        padding-right: 5%;
    }

    .event-left, .event-right {
        width: 100%;
        margin-bottom: 20px;
    }
.event-right {
        width: 93%;}
    /* Image and text adjustments */
    .event-image {
        width: 90%; /* Keep it responsive */
        margin-left: 15px;
        margin-top: 15px;
        margin-right: 15px;
        height: auto;
    }

    .event-details h1 {
        font-size: 20px; /* Smaller title font size for mobile */
        margin: 5px 10px;
    }

    .event-details p {
        font-size: 14px; /* Adjust text for better readability */
        margin: 5px 10px;
    }

    .google-calendar {
        background-color: #ff6f61;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        margin: 10px 13px;
    }

    /* Social Icons */
    .social-icon {
        margin: 5px 10px;
        font-size: 20px;
        color: #333;
    }

    /* Ticket Section Adjustments */
    .ticket-info, .venue-info {
        padding: 15px;
    }

    .ticket-btn {
        width: 30px;
        height: 30px;
        padding: 5px;
    }

    .ticket-quantity {
        width: 40px;
    }

    .ticket-item {
        flex-direction: column; /* Stack ticket items vertically */
        padding: 15px;
    }

    .ticket-item label {
        font-size: 14px; /* Adjust label size */
    }

    .buy-tickets {
        font-size: 16px; /* Reduce button font size */
        padding: 12px;
    }

    .nav1 {
        width: 100%;
        margin-bottom: 10px;
    }
}

/* Google Map Style */
#map {
    width: 100%;
    height: 400px;
    background-color: #f0f0f0;
}

    </style>
</head>
<script src="https://js.paystack.co/v1/inline.js"></script>

<body>

<div class="event-container">
    <!-- Left Section: Event Image and Details -->
    <div class="event-left">
        <img src="../assets/images/concert1.png" alt="Event Image" class="event-image">
        <div class="event-details">
            <h1>Stand Tall Concert</h1>
            <p class="event-owner">Owner: Divido</p>
            <p class="event-seat">Seat: Regular</p>
            <p class="event-date">
                <i class="fas fa-calendar-alt"></i> <strong>Sun. 10th Sep. 2025</strong> @ <i class="fas fa-clock"></i> <strong>10:00pm - 2:22am</strong>
            </p>
            <p class="event-venue">
                <i class="fas fa-map-marker-alt"></i> <strong>The Grand Nexus Event Centre, Garki, Abuja</strong>
            </p>
            <div class="event-prices">
              <ul>
                <li><p>Premium Table: ₦3,000,000</p></li>
                <li><p>Gold Table: ₦1,500,000</p> </li>
                <li><p>VIP: ₦120,000</p></li>
                <li><p>Regular: ₦5,000</p></li>
              </ul>
            </div>
            <div class="event-actions">
                <button class="google-calendar" onclick="addToGoogleCalendar()">Add to Google Calendar</button>
                <div class="social-links">
                    <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Section: Ticket Purchase -->
    <div class="event-right">
        <div class="ticket-info">
            <button class="nav1" onclick="showTickets()"><h2>TICKETS</h2></button>
            <button class="nav1" onclick="showVenue()"><h2>VENUE</h2></button>

            <div id="ticket-section">
                <div class="ticket-item">
                  
                    <div class="ticket-input-container">
                        <button class="ticket-btn" onclick="decrease('regular')">-</button>
                        <input type="number" id="regular" value="0" min="0" class="ticket-quantity">
                        <button class="ticket-btn" onclick="increase('regular')">+</button>
                    </div>
                      <label for="regular">Regular Tickets</label>
                    <span class="price">₦5,000</span>
                </div>

                <div class="ticket-item">
                    
                    <div class="ticket-input-container">
                        <button class="ticket-btn" onclick="decrease('vip')">-</button>
                        <input type="number" id="vip" value="0" min="0" class="ticket-quantity">
                        <button class="ticket-btn" onclick="increase('vip')">+</button>
                    </div>
                    <label for="vip">VIP Tickets</label>
                    <span class="price">₦120,000</span>
                </div>

                <div class="ticket-item">
                    
                    <div class="ticket-input-container">
                        <button class="ticket-btn" onclick="decrease('gold')">-</button>
                        <input type="number" id="gold" value="0" min="0" class="ticket-quantity">
                        <button class="ticket-btn" onclick="increase('gold')">+</button>
                    </div>
                    <label for="gold">Gold Table For 10 Tickets</label>
                    <span class="price">₦1,500,000</span>
                </div>

                <div class="ticket-item">
                    
                    <div class="ticket-input-container">
                        <button class="ticket-btn" onclick="decrease('seat-gold')">-</button>
                        <input type="number" id="seat-gold" value="0" min="0" class="ticket-quantity">
                        <button class="ticket-btn" onclick="increase('seat-gold')">+</button>
                    </div>
                    <label for="seat-gold">A Seat On Gold Table For 10</label>
                    <span class="price">₦59,000</span>
                </div>

                <div class="ticket-item">
                   
                    <div class="ticket-input-container">
                        <button class="ticket-btn" onclick="decrease('premium')">-</button>
                        <input type="number" id="premium" value="0" min="0" class="ticket-quantity">
                        <button class="ticket-btn" onclick="increase('premium')">+</button>
                    </div>
                     <label for="premium">Premium Table For 10</label>
                    <span class="price">₦3,000,000</span>
                </div>

                <div class="ticket-item">
                  
                    <div class="ticket-input-container">
                       
                        <button class="ticket-btn" onclick="decrease('seat-premium')">-</button>
                       <input type="number" id="seat-premium" value="0" min="0" class="ticket-quantity">
                        <button class="ticket-btn" onclick="increase('seat-premium')">+</button>
                    </div>
                      <label for="seat-premium">A Seat On Premium Table For 10</label>
                    <span class="price">₦70,000</span>
                </div>
               <a href="event_checkout.php"> <button class="buy-tickets">Buy tickets</button></a>
            </div>

            <div id="venue-section" style="display: none;">
                <h2>VENUE</h2>
                <p>The Grand Nexus Event Centre, Garki, Abuja</p>
                <div id="map"></div>
            </div>
        </div>
    </div>
</div>


<!-- JavaScript for toggling sections -->
<script>
    // Function to show the "Tickets" section and hide the "Venue" section
    function showTickets() {
        document.getElementById("ticket-section").style.display = "block";
        document.getElementById("venue-section").style.display = "none";
    }

    // Function to show the "Venue" section and hide the "Tickets" section
    function showVenue() {
        document.getElementById("ticket-section").style.display = "none";
        document.getElementById("venue-section").style.display = "block";
        initMap();  // Initialize the Google Map when the venue is shown
    }

    // Google Map function
    function initMap() {
        var location = { lat: 9.0579, lng: 7.49508 }; // Coordinates for the event location (Garki, Abuja)
        var map = new google.maps.Map(document.getElementById("map"), {
            zoom: 12,
            center: location,
        });
        var marker = new google.maps.Marker({
            position: location,
            map: map,
            title: "Event Venue",
        });
    }

    // Google Calendar function
    function addToGoogleCalendar() {
        var eventTitle = "Stand Tall Concert";
        var eventDate = "2025-09-10T22:00:00";  // Event date and time (format: YYYY-MM-DDTHH:MM:SS)
        var eventEndDate = "2025-09-10T02:22:00";  // Event end time
        var eventDescription = "Join us for the Stand Tall Concert!";
        var eventLocation = "The Grand Nexus Event Centre, Garki, Abuja";

        var calendarURL = `https://www.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(eventTitle)}&dates=${eventDate}/${eventEndDate}&details=${encodeURIComponent(eventDescription)}&location=${encodeURIComponent(eventLocation)}&sf=true&output=xml`;

        window.open(calendarURL, '_blank');
    }

    // Increase and Decrease functions for ticket quantity
    function increase(ticketId) {
        var input = document.getElementById(ticketId);
        input.value = parseInt(input.value) + 1;
    }

    function decrease(ticketId) {
        var input = document.getElementById(ticketId);
        if (input.value > 0) {
            input.value = parseInt(input.value) - 1;
        }
    }
</script>

<!-- Google Maps API -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBqrHb15So6GIRYy9GKvB54KhVvE5bFkz4&callback=initMap" async defer></script>

</body>
</html>

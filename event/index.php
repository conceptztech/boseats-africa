<?php include_once "../includes/e_header.php";?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoseaAfrica Event Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f8f8ff;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .search-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .search-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .search-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-label {
            font-size: 14px;
            color: #555;
        }

        .filter-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-option {
            padding: 8px 15px;
            background-color: #f1f1f1;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-option:hover, .filter-option.active {
            background-color: #28a745;
            color: white;
        }

        .search-bar {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-bar input[type="text"] {
            padding: 12px 15px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            flex-grow: 1;
        }

        .search-bar select {
            padding: 12px 15px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            background-color: white;
        }

        .search-bar button {
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 5px;
            border: none;
            background-color: #28a745;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-bar button:hover {
            background-color: #1c3a23ff;
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #ddd;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .card-body {
            padding: 20px;
        }

        .card-body h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 10px;
        }

        .card-owner {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }

        .card-details {
            margin: 15px 0;
        }

        .detail-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            font-size: 14px;
            color: #1d1c1cff;
        }

        .detail-item i {
            margin-right: 10px;
            color: #28a745;
            min-width: 16px;
        }

        .card-divider {
            border: none;
            height: 1px;
            background-color: #c4bebeff;
            margin: 15px 0;
        }

        .card-body .price {
            font-size: 16px;
            font-weight: 600;
            color: #28a745;
            margin: 10px 0;
        }

        .card-body .buy-button {
            background-color: #28a745;
            color: white;
            padding: 12px;
            text-align: center;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }

        .card-body .buy-button:hover {
            background-color: #28a745;
        }

     @media (max-width: 1200px) {
    .card-container {
        grid-template-columns: repeat(3, 1fr);
    }

    .container {
        max-width: 90%; /* Remove the restriction to max-width: 100px */
        margin: 20px auto;
        padding: 0 10px;
    }
}

@media (max-width: 900px) {
    .card-container {
        grid-template-columns: repeat(2, 1fr);
    }

    .search-filters {
        flex-direction: column;
        align-items: flex-start;
    }

    .filter-group {
        width: 100%;
    }

    .filter-options {
        font-size: 12px; /* Reduce the font size for better alignment */
        width: 100%;
        justify-content: flex-start;
    }
}

/* Ensure button takes full width on mobile */
/* Ensure cards are displayed in a single column on small screens */
@media (max-width: 600px) {
    .card-container {
        grid-template-columns: 1fr;  /* Display cards in one column */
    }

    .search-bar {
        flex-direction: column;  /* Stack elements vertically */
    }

    .search-bar input[type="text"],
    .search-bar select,
    .search-bar button {
        width: 100%; /* Make input, select, and button take full width */
        margin-bottom: 10px; /* Add spacing between them */
    }

    .search-bar button {
        font-size: 14px;  /* Reduce the font size of the button for better fit */
        padding: 12px;    /* Adjust padding for the button */
    }
}


    </style>
</head>
<body>

    <div class="container">
        <!-- Search Section -->
        <div class="search-section">
            <div class="search-title">Search your event ticket or artist</div>
            
            <div class="search-filters">
                <div class="filter-group">
                    <span class="filter-label">Category:</span>
                    <div class="filter-options">
                        <div class="filter-option active">All</div>
                        <div class="filter-option">New</div>
                        <div class="filter-option">Theater</div>
                        <div class="filter-option">Concert</div>
                        <div class="filter-option">Sport event</div>
                        <div class="filter-option">Exhibitions</div>
                    </div>
                </div>
                
                <div class="filter-group">
                   
                  
                </div>
            </div>
            
            <div class="search-bar">
                <input type="text" placeholder="Search events, artists, or venues...">
                <span class="filter-label">Sort by:</span>
                <select>
                    <option>Newest</option>
                    <option>Popular</option>
                    <option>Price: Low to High</option>
                    <option>Price: High to Low</option>
                </select>
                <button><i class="fas fa-search"></i> Search</button>
            </div>
        </div>

        <!-- Event Cards Section -->
        <div class="card-container">
            <!-- Card 1 -->
            <div class="card">
                <img src="/boseatsafrica/images/concert1.png" alt="Stand Tall Concert">
                <div class="card-body">
                    <h3>Stand Tall Concert</h3>
                    <p class="card-owner">Owner - Divido . Seat - Regular</p>
                    
                    <hr class="card-divider">
                    
                    <div class="card-details">
                        <div class="detail-item">
                            <i class="far fa-calendar-alt"></i>
                            <span>Sun. 10th Sep. 2025</span>
                        </div>
                        <div class="detail-item">
                            <i class="far fa-clock"></i>
                            <span>10:00pm - 2:22pm</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>The Grand Nexus Event Centre, Garki, Abuja</span>
                        </div>
                    </div>
                    
                    <hr class="card-divider">
                    
                    <p class="price">$30.00 - $70.00</p>
                   <a style="text-decoration:none; color: white;"href="event-details.php"> <button class="buy-button">Buy tickets</button></a>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="card">
                <img src="/boseatsafrica/images/concert2.png" alt="Stand Tall Concert">
                <div class="card-body">
                    <h3>Stand Tall Concert</h3>
                    <p class="card-owner">Owner - Divido . Seat - Regular</p>
                    
                    <hr class="card-divider">
                    
                    <div class="card-details">
                        <div class="detail-item">
                            <i class="far fa-calendar-alt"></i>
                            <span>Sat. 4th Oct. 2025</span>
                        </div>
                        <div class="detail-item">
                            <i class="far fa-clock"></i>
                            <span>6:00pm - 10:00pm</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>The Grand Nexus Event Centre, Garki, Abuja</span>
                        </div>
                    </div>
                    
                    <hr class="card-divider">
                    
                    <p class="price">$40.00 - $80.00</p>
                   <a style="text-decoration:none; color: white;"href="event-details.php"> <button class="buy-button">Buy tickets</button></a>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="card">
                <img src="/boseatsafrica/mages/concert3.png" alt="The Summer Concert">
                <div class="card-body">
                    <h3>The Summer Concert</h3>
                    <p class="card-owner">Owner - RockFest . Seat - Regular</p>
                    
                    <hr class="card-divider">
                    
                    <div class="card-details">
                        <div class="detail-item">
                            <i class="far fa-calendar-alt"></i>
                            <span>Fri. 5th Nov. 2025</span>
                        </div>
                        <div class="detail-item">
                            <i class="far fa-clock"></i>
                            <span>8:00pm - 12:00am</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>The Valley Party, Nimetty</span>
                        </div>
                    </div>
                    
                    <hr class="card-divider">
                    
                    <p class="price">$50.00 - $100.00</p>
                   <a style="text-decoration:none; color: white;"href="event-details.php"> <button class="buy-button">Buy tickets</button></a>
                </div>
            </div>

                        <!-- Card 3 -->
            <div class="card">
                <img src="images/concert4.png" alt="The Summer Concert">
                <div class="card-body">
                    <h3>The Summer Concert</h3>
                    <p class="card-owner">Owner - RockFest . Seat - Regular</p>
                    
                    <hr class="card-divider">
                    
                    <div class="card-details">
                        <div class="detail-item">
                            <i class="far fa-calendar-alt"></i>
                            <span>Fri. 5th Nov. 2025</span>
                        </div>
                        <div class="detail-item">
                            <i class="far fa-clock"></i>
                            <span>8:00pm - 12:00am</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>The Valley Party, Nimetty</span>
                        </div>
                    </div>
                    
                    <hr class="card-divider">
                    
                    <p class="price">$50.00 - $100.00</p>
                   <a style="text-decoration:none; color: white;"href="event-details.php"> <button class="buy-button">Buy ticket</button>s</a>
                </div>
            </div>
            
            <!-- Card 4 -->
            <div class="card">
                <img src="images/event4.jpg" alt="Fun Zone">
                <div class="card-body">
                    <h3>Fun Zone</h3>
                    <p class="card-owner">Owner - Playland . Seat - Regular</p>
                    
                    <hr class="card-divider">
                    
                    <div class="card-details">
                        <div class="detail-item">
                            <i class="far fa-calendar-alt"></i>
                            <span>Sun. 2nd Dec. 2025</span>
                        </div>
                        <div class="detail-item">
                            <i class="far fa-clock"></i>
                            <span>1:00pm - 6:00pm</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Garki Central Park, Abuja</span>
                        </div>
                    </div>
                    
                    <hr class="card-divider">
                    
                    <p class="price">$20.00 - $50.00</p>
                   <a style="text-decoration:none; color: white;"href="event-details.php"> <button class="buy-button">Buy tickets</button></a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add active class to filter options on click
        document.querySelectorAll('.filter-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove active class from siblings
                const siblings = this.parentElement.children;
                for (let i = 0; i < siblings.length; i++) {
                    siblings[i].classList.remove('active');
                }
                
                // Add active class to clicked option
                this.classList.add('active');
            });
        });
    </script>
    
<?php include_once "../includes/footer.php";?>
</body>
</html>
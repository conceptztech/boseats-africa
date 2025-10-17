<footer>
    <div class="footer-container">
        <!-- Logo Section -->
        <div class="footer-logo">
            <img src="https://leo.it.tab.digital/s/ab8zkSjypqKHNyd/preview" alt="Boseats Africa Logo" class="logo">
            <!-- Social Icons Section -->
            <div class="footer-social">
                <a href="#" class="social-icon"><i class="fab fa-tiktok"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
            </div>
        </div>
        
        <!-- Contact Info Section -->
        <div class="footer-contact">
            <p>2048 Wexford Way Wings SC 287290</p>
            <p>+012-334-5864</p>
            <p>info@boseatsafrica.com</p>
        </div>
      
        <!-- Navigation Section -->
        <div class="footer-nav">
            <ul class="main-nav">
                <li><a href="#">Home</a></li>
                <li><a href="#">How It Works</a></li>
                <li><a href="#">Rental Deals</a></li>
            </ul>
            
            <!-- Additional Links -->
            <div class="additional-nav">
                <ul>
                    <li><a href="#">Why Choose Us</a></li>
                    <li><a href="#">Testimonial</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<style>
    footer {
        background-color: #333;
        color: white;
        padding: 20px 0;
    }

    .footer-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        margin-left: 35px;
        padding: 0 20px;
    }

    .footer-logo img {
        width: 130px;
        margin-right: 80px;
        margin-left: 30px;
    }

    .footer-contact p {
        margin-right: 20px;
        margin-left: 0px;
    }

    .footer-social {
        display: flex;
        gap: 15px;
        margin-right: 50px;
        margin-left: 30px;
    }

    .social-icon {
        color: white;
        font-size: 20px;
        text-decoration: none;
    }

    .social-icon:hover {
        color: #72A458;
    }

    .footer-nav {
        display: flex;
        flex-direction: row;
        gap: 40px;
        margin-left: 30px;
    }

    .main-nav ul,
    .additional-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
        margin-left: 100px;
    }

    .main-nav li,
    .additional-nav li {
        display: block;
        margin-left: 20px;
    }

    .main-nav a,
    .additional-nav a {
        color: white;
        text-decoration: none;
        font-size: 16px;
        display: block;
        margin-left: 20px;
    }

    .main-nav a:hover,
    .additional-nav a:hover {
        color: #72A458;
    }

    .additional-nav {
        display: flex;
        flex-direction: column;
        margin-left: 20px;
        margin-right: 30px;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .footer-container {
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-left: 0;
            padding: 0 15px;
            gap: 25px;
        }
        
        .footer-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .footer-logo img {
            margin: 0 0 15px 0;
        }
        
        .footer-social {
            margin: 0;
            justify-content: center;
        }
        
        .footer-contact {
            text-align: center;
        }
        
        .footer-contact p {
            margin: 8px 0;
        }
        
        .footer-nav {
            flex-direction: column;
            margin-left: 0;
            gap: 20px;
        }
        
        .main-nav ul,
        .additional-nav ul {
            margin-left: 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
        
        .main-nav li,
        .additional-nav li {
            margin: 0;
        }
        
        .main-nav a,
        .additional-nav a {
            margin-left: 0;
        }
        
        .additional-nav {
            margin: 0;
        }
    }

    @media (max-width: 480px) {
        .footer-logo img {
            width: 110px;
        }
        
        .footer-contact p {
            font-size: 14px;
        }
        
        .main-nav ul,
        .additional-nav ul {
            flex-direction: column;
            gap: 10px;
        }
        
        .main-nav a,
        .additional-nav a {
            font-size: 15px;
        }
        
        .social-icon {
            font-size: 18px;
        }
    }

    /* Hide the primary footer on mobile screens */
footer {
  background-color: #333;
  color: white;
  padding: 20px 0;
  display: block; /* Ensure it is visible on desktop by default */
}

/* Mobile responsive styles */
@media (max-width: 768px) {
  footer {
    display: none; /* Hide the primary footer on mobile */
  }
}

/* Desktop styles (footer is already visible by default) */
@media (min-width: 769px) {
  .footer-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    margin-left: 35px;
    padding: 0 20px;
  }

  .footer-logo img {
    width: 130px;
    margin-right: 80px;
    margin-left: 30px;
  }

  .footer-contact p {
    margin-right: 20px;
    margin-left: 0px;
  }

  .footer-social {
    display: flex;
    gap: 15px;
    margin-right: 50px;
    margin-left: 30px;
  }

  .social-icon {
    color: white;
    font-size: 20px;
    text-decoration: none;
  }

  .social-icon:hover {
    color: #72A458;
  }

  .footer-nav {
    display: flex;
    flex-direction: row;
    gap: 40px;
    margin-left: 30px;
  }

  .main-nav ul,
  .additional-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
    margin-left: 100px;
  }

  .main-nav li,
  .additional-nav li {
    display: block;
    margin-left: 20px;
  }

  .main-nav a,
  .additional-nav a {
    color: white;
    text-decoration: none;
    font-size: 16px;
    display: block;
    margin-left: 20px;
  }

  .main-nav a:hover,
  .additional-nav a:hover {
    color: #72A458;
  }

  .additional-nav {
    display: flex;
    flex-direction: column;
    margin-left: 20px;
    margin-right: 30px;
  }
}

</style>
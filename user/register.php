<?php require_once 'create.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Carousel Message CSS -->
    <style>
        /* Message Carousel Styles */
        .message-carousel {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
            width: 90%;
        }

        .message-slide {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #28a745;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
            overflow: hidden;
        }

        .message-slide.show {
            transform: translateX(0);
            opacity: 1;
        }

        .message-slide.hide {
            transform: translateX(100%);
            opacity: 0;
        }

        .message-slide.success {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #f8fff9 100%);
        }

        .message-slide.error {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #f8d7da 0%, #fff5f6 100%);
        }

        .message-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .message-title {
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message-title.success {
            color: #155724;
        }

        .message-title.error {
            color: #721c24;
        }

        .message-icon {
            font-size: 18px;
        }

        .close-message {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #6c757d;
            padding: 0;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-message:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #000;
        }

        .message-content {
            color: #495057;
            font-size: 14px;
            line-height: 1.5;
        }

        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: #28a745;
            width: 100%;
            transform-origin: left;
            animation: progress 5s linear forwards;
        }

        .message-slide.error .progress-bar {
            background: #dc3545;
        }

        /* Success checkmark animation */
        .checkmark {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: #fff;
            stroke-miterlimit: 10;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #28a745;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes progress {
            from {
                transform: scaleX(1);
            }
            to {
                transform: scaleX(0);
            }
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #28a745;
            }
        }

        /* Loading indicator */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #28a745;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .message-carousel {
                right: 10px;
                left: 10px;
                max-width: none;
                width: auto;
            }
            
            .message-slide {
                padding: 15px;
            }
        }
    </style>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            display: flex;
            width: 80%;
            max-width: 1200px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .left {
            flex: 1;
            color: #28a745;
            background:  url('https://leo.it.tab.digital/s/jZYadWTRQXzJysC/preview');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            min-height: 400px;
        }

        .left-content {
            max-width: 500px;
        }

        .left h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .left p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            font-weight: 300;
        }

        .features {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .features img {
            width: 100px;
            margin-bottom: 20px;
        }

        .feature-text span {
            display: block;
            margin: 5px 0;
        }

        .right {
            flex: 1;
            padding: 40px;
        }

        .right h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        .right form {
            display: flex;
            flex-direction: column;
        }

        .right label {
            margin-top: 10px;
            font-weight: 500;
            color: #555;
        }

        .right input, .right select {
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            width: 100%;
            font-family: 'Poppins', sans-serif;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }

        .form-group {
            flex: 1;
        }

        .phone-container {
            display: flex;
            gap: 10px;
        }

        .phone-code {
            flex: 0 0 100px;
        }

        .phone-number {
            flex: 1;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }

        .right button {
            padding: 12px;
            margin-top: 25px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .right button:hover {
            background-color: #218838;
        }

        .social-login {
            text-align: center;
            margin-top: 20px;
        }

        .social-login p {
            margin-bottom: 15px;
        }

        .social-login .or-divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }

        .social-login .or-divider::before,
        .social-login .or-divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }

        .social-login .or-divider span {
            padding: 0 10px;
            color: #777;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }

        .social-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #f1f1f1;
            color: #333;
            text-decoration: none;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background-color: #28a745;
            color: white;
            transform: translateY(-3px);
        }

        .login-link {
            margin-top: 15px;
            color: #555;
        }

        .login-link a {
            color: #28a745;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .border1 {
            padding: 20px;
        }

        .home-link {
            margin-bottom: 15px;
        }

        .home-link a {
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
        }

        .home-link a:hover {
            text-decoration: underline;
        }

        .business-account {
            margin-top: 15px;
            text-align: center;
            color: #555;
        }

        .business-account a {
            color: #28a745;
            text-decoration: none;
        }

        .business-account a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .container {
                flex-direction: column;
                width: 90%;
            }

            .left, .right {
                flex: 1 1 100%;
                padding: 30px;
            }

            .left {
                min-height: 550px;
                padding: 60px 30px;
            }

            .left h1 {
                font-size: 2rem;
                margin-top: 0;
            }

            .right h2 {
                margin-top: 0;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        @media (max-width: 576px) {
            .container {
                width: 100%;
            }
            
            .left h1 {
                font-size: 1.8rem;
            }
            
            .left p {
                font-size: 1rem;
            }
            
            .phone-container {
                flex-direction: column;
            }
            
            .phone-code {
                flex: 1;
            }
            
            .right {
                padding: 20px;
            }
            
            .border1 {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Message Carousel Container -->
    <div class="message-carousel"></div>

    <div class="container">
        <div class="left">
            <div class="left-content">
                <div class="features">
                    <!-- Feature content can be added here -->
                </div>
            </div>
        </div>

        <div class="right">
            <div class="home-link">
                <p><a href="../index.php">← Home</a></p>
            </div>
            <div class="border1">
                <h2>Let's get started</h2>
                <form id="registrationForm" method="POST">
                    <!-- Company Name and Owner's Name in same column -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>
                        </div>
                    </div>

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Email" required>

                    <!-- Country and State in same column -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="country">Country of Residence</label>
                            <select id="country" name="country" required onchange="populateStates()">
                                <option value="">Select Country</option>
                                <!-- African countries will be populated here -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="state">State/Province</label>
                            <select id="state" name="state" required>
                                <option value="">Select State</option>
                                <!-- States will be populated based on country selection -->
                            </select>
                        </div>
                    </div>

                    <!-- Phone Number -->
                    <label for="phone">Phone Number</label>
                    <div class="phone-container">
                        <select id="phone_code" name="phone_code" class="phone-code" required>
                            <option value="">Code</option>
                            <!-- Phone codes will be populated based on country selection -->
                        </select>
                        <input type="tel" id="phone" name="phone" class="phone-number" placeholder="Phone number" required>
                    </div>

                    <!-- Password with show/hide toggle -->
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required>
                        <span class="toggle-password" id="togglePassword">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>

                    <button type="submit">Get Started</button>
                    
                    <div class="business-account">
                        <p><i>Need a Business Account? <a href="../merchant/create.php">Create One</a></i></p>
                    </div>

                    <div class="social-login">
                        <div class="or-divider">
                            <span>OR</span>
                        </div>
                        
                        <div class="social-icons">
                            <a href="#" class="social-icon">
                                <i class="fab fa-google"></i>
                            </a>
                            <a href="#" class="social-icon">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>

                        <p class="login-link">Already have an account? <a href="../login.php">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // African countries data with phone codes and states
        const africanCountries = [
            { name: "Nigeria", code: "NG", phoneCode: "+234", states: ["Abia", "Adamawa", "Akwa Ibom", "Anambra", "Bauchi", "Bayelsa", "Benue", "Borno", "Cross River", "Delta", "Ebonyi", "Edo", "Ekiti", "Enugu", "Federal Capital Territory", "Gombe", "Imo", "Jigawa", "Kaduna", "Kano", "Katsina", "Kebbi", "Kogi", "Kwara", "Lagos", "Nasarawa", "Niger", "Ogun", "Ondo", "Osun", "Oyo", "Plateau", "Rivers", "Sokoto", "Taraba", "Yobe", "Zamfara"] },
            { name: "Ghana", code: "GH", phoneCode: "+233", states: ["Ahafo", "Ashanti", "Bono", "Bono East", "Central", "Eastern", "Greater Accra", "North East", "Northern", "Oti", "Savannah", "Upper East", "Upper West", "Volta", "Western", "Western North"] },
            { name: "Kenya", code: "KE", phoneCode: "+254", states: ["Baringo", "Bomet", "Bungoma", "Busia", "Elgeyo-Marakwet", "Embu", "Garissa", "Homa Bay", "Isiolo", "Kajiado", "Kakamega", "Kericho", "Kiambu", "Kilifi", "Kirinyaga", "Kisii", "Kisumu", "Kitui", "Kwale", "Laikipia", "Lamu", "Machakos", "Makueni", "Mandera", "Marsabit", "Meru", "Migori", "Mombasa", "Murang'a", "Nairobi", "Nakuru", "Nandi", "Narok", "Nyamira", "Nyandarua", "Nyeri", "Samburu", "Siaya", "Taita-Taveta", "Tana River", "Tharaka-Nithi", "Trans Nzoia", "Turkana", "Uasin Gishu", "Vihiga", "Wajir", "West Pokot"] },
            { name: "South Africa", code: "ZA", phoneCode: "+27", states: ["Eastern Cape", "Free State", "Gauteng", "KwaZulu-Natal", "Limpopo", "Mpumalanga", "North West", "Northern Cape", "Western Cape"] }
        ];

        // Message Carousel System
        class MessageCarousel {
            constructor() {
                this.carousel = this.createCarousel();
                this.messageCount = 0;
                this.maxMessages = 5;
            }

            createCarousel() {
                let carousel = document.querySelector('.message-carousel');
                if (!carousel) {
                    carousel = document.createElement('div');
                    carousel.className = 'message-carousel';
                    document.body.appendChild(carousel);
                }
                return carousel;
            }

            show(message, type = 'info') {
                if (this.messageCount >= this.maxMessages) {
                    this.removeOldestMessage();
                }

                const messageId = 'msg-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                const messageSlide = document.createElement('div');
                
                messageSlide.className = `message-slide ${type}`;
                messageSlide.id = messageId;
                
                const icon = this.getIcon(type);
                const title = this.getTitle(type);
                
                messageSlide.innerHTML = `
                    <div class="message-header">
                        <div class="message-title ${type}">
                            ${icon}
                            <span>${title}</span>
                        </div>
                        <button class="close-message" aria-label="Close message">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="message-content">${this.escapeHtml(message)}</div>
                    <div class="progress-bar"></div>
                `;

                this.carousel.appendChild(messageSlide);
                this.messageCount++;

                // Add event listener for close button
                const closeBtn = messageSlide.querySelector('.close-message');
                closeBtn.addEventListener('click', () => this.close(messageId));

                // Animate in
                requestAnimationFrame(() => {
                    messageSlide.classList.add('show');
                });

                // Auto remove
                const autoRemove = setTimeout(() => {
                    this.close(messageId);
                }, 5000);

                messageSlide.dataset.autoRemove = autoRemove;

                return messageId;
            }

            close(messageId) {
                const messageSlide = document.getElementById(messageId);
                if (!messageSlide) return;

                // Clear auto-remove timer
                if (messageSlide.dataset.autoRemove) {
                    clearTimeout(parseInt(messageSlide.dataset.autoRemove));
                }

                // Animate out
                messageSlide.classList.remove('show');
                messageSlide.classList.add('hide');

                // Remove from DOM after animation
                setTimeout(() => {
                    if (messageSlide.parentNode) {
                        messageSlide.parentNode.removeChild(messageSlide);
                        this.messageCount--;
                    }
                }, 500);
            }

            removeOldestMessage() {
                const messages = this.carousel.querySelectorAll('.message-slide');
                if (messages.length > 0) {
                    this.close(messages[0].id);
                }
            }

            getIcon(type) {
                const icons = {
                    success: `<svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" aria-hidden="true">
                        <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>`,
                    error: '<i class="fas fa-exclamation-circle message-icon" aria-hidden="true"></i>',
                    info: '<i class="fas fa-info-circle message-icon" aria-hidden="true"></i>',
                    warning: '<i class="fas fa-exclamation-triangle message-icon" aria-hidden="true"></i>'
                };
                return icons[type] || icons.info;
            }

            getTitle(type) {
                const titles = {
                    success: 'Success!',
                    error: 'Error!',
                    info: 'Info',
                    warning: 'Warning!'
                };
                return titles[type] || 'Info';
            }

            escapeHtml(unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        }

        // Initialize message carousel
        const messageCarousel = new MessageCarousel();

        // Enhanced showMessage function
        function showMessage(message, type = 'info') {
            if (typeof message !== 'string') {
                console.warn('showMessage received non-string message:', message);
                message = String(message);
            }
            
            try {
                return messageCarousel.show(message, type);
            } catch (error) {
                console.error('Error showing message:', error);
                // Fallback to alert
                alert(`${type.toUpperCase()}: ${message}`);
            }
        }

        // Populate country dropdown
        const countrySelect = document.getElementById('country');
        africanCountries.forEach(country => {
            const option = document.createElement('option');
            option.value = country.code;
            option.textContent = country.name;
            countrySelect.appendChild(option);
        });

        // Function to populate states based on selected country
        function populateStates() {
            const countryCode = countrySelect.value;
            const stateSelect = document.getElementById('state');
            const phoneCodeSelect = document.getElementById('phone_code');
            
            // Clear previous states and phone codes
            stateSelect.innerHTML = '<option value="">Select State</option>';
            phoneCodeSelect.innerHTML = '<option value="">Code</option>';
            
            if (countryCode) {
                const selectedCountry = africanCountries.find(country => country.code === countryCode);
                
                // Populate states
                if (selectedCountry && selectedCountry.states) {
                    selectedCountry.states.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state;
                        option.textContent = state;
                        stateSelect.appendChild(option);
                    });
                }
                
                // Populate phone code
                if (selectedCountry && selectedCountry.phoneCode) {
                    const option = document.createElement('option');
                    option.value = selectedCountry.phoneCode;
                    option.textContent = selectedCountry.phoneCode;
                    phoneCodeSelect.appendChild(option);
                }
            }
        }

        // Password show/hide functionality
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Form validation
        function validateForm() {
            const fields = {
                first_name: 'First Name',
                last_name: 'Last Name', 
                email: 'Email',
                country: 'Country',
                state: 'State',
                phone: 'Phone Number',
                password: 'Password'
            };

            const missing = [];
            
            for (const [field, name] of Object.entries(fields)) {
                const element = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
                if (!element || !element.value.trim()) {
                    missing.push(name);
                }
            }

            if (missing.length > 0) {
                showMessage(`Please complete the following fields: ${missing.join(', ')}`, 'error');
                return false;
            }

            // Email validation
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showMessage('Please enter a valid email address', 'error');
                return false;
            }

            // Password strength
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                showMessage('Password must be at least 8 characters long', 'error');
                return false;
            }

            return true;
        }

        // Enhanced form submission
        document.getElementById('registrationForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            try {
                // Validate form
                if (!validateForm()) return;

                // Show loading state
                submitButton.innerHTML = '<span class="loading"></span> Registering...';
                submitButton.disabled = true;

                const formData = new FormData(this);
                
                // Add timeout to fetch
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000);
                
                const response = await fetch('register.php', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`Server error: ${response.status} ${response.statusText}`);
                }

                const responseText = await response.text();
                let data;
                
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError, 'Response:', responseText);
                    throw new Error('Invalid response from server');
                }

                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = '../login.php';
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Registration failed');
                }
                
            } catch (error) {
                console.error('Submission error:', error);
                
                let userMessage = 'Registration failed. ';
                
                if (error.name === 'AbortError') {
                    userMessage += 'Request timed out. Please check your connection.';
                } else if (error.message.includes('Server error')) {
                    userMessage += 'Server error occurred. Please try again later.';
                } else if (error.message.includes('Invalid response')) {
                    userMessage += 'Server returned invalid response. Please contact support.';
                } else {
                    userMessage += error.message;
                }
                
                showMessage(userMessage, 'error');
            } finally {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        });

        // Make functions globally available
        window.showMessage = showMessage;
        window.populateStates = populateStates;
    </script>
</body>
</html>
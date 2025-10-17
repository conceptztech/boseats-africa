<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/merchant_create.css">
</head>
<body>
    <div class="container">
        <div class="left">
            <div class="features">
               
            </div>
        </div>

        <div class="right">
            <div class="home-link">
                <p><a href="../user/register.php">← Back</a>
            </div>
            <div class="border1">
                <h2>Let's get started</h2>
                <form id="merchantRegistrationForm" method="POST" enctype="multipart/form-data">
    <!-- Company Name and Owner's Name in same column -->
    <div class="form-row">
        <div class="form-group">
            <label for="company_name">Company Name</label>
            <input type="text" id="company_name" name="company_name" placeholder="Company or Label Name" required>
        </div>
        <div class="form-group">
            <label for="owners_name">Owner's Name</label>
            <input type="text" id="owners_name" name="owners_name" placeholder="CEO Name or Artist name" required>
        </div>
    </div>

    <label for="email">Company Email</label>
    <input type="email" id="email" name="email" placeholder="company email or artist email" required>

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

    <!-- Phone Number and NIN in same column -->
    <div class="form-row">
        <div class="form-group">
            <label for="phone">Phone Number</label>
            <div class="phone-container">
                <select id="phone_code" name="phone_code" class="phone-code" required>
                    <option value="">Code</option>
                    <!-- Phone codes will be populated based on country selection -->
                </select>
                <input type="tel" id="phone" name="phone" class="phone-number" placeholder="Phone number" required>
            </div>
        </div>
        <div class="form-group">
            <label for="NIN">NIN or Any Valid Passport</label>
            <input type="text" id="NIN" name="NIN" placeholder="NIN or Any Valid Passport" required>
        </div>
    </div>


<!-- Services Offered Section with Icons -->
<label for="services">Services Offered (Select all that apply)</label>
<div class="services-container">
    <div class="services-checkboxes with-icons">
        <div class="service-option">
            <input type="checkbox" id="service_food" name="services[]" value="Food">
            <label for="service_food">
                <i class="fas fa-utensils"></i>
                <span>Food</span>
            </label>
        </div>
        <div class="service-option">
            <input type="checkbox" id="service_hotel" name="services[]" value="Hotel">
            <label for="service_hotel">
                <i class="fas fa-hotel"></i>
                <span>Hotel</span>
            </label>
        </div>
        <div class="service-option">
            <input type="checkbox" id="service_flight" name="services[]" value="Flight">
            <label for="service_flight">
                <i class="fas fa-plane"></i>
                <span>Flight</span>
            </label>
        </div>
        <div class="service-option">
            <input type="checkbox" id="service_car" name="services[]" value="Car">
            <label for="service_car">
                <i class="fas fa-car"></i>
                <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Car</span>
            </label>
        </div>
        <div class="service-option">
            <input type="checkbox" id="service_events" name="services[]" value="Events">
            <label for="service_events">
                <i class="fas fa-calendar-alt"></i>
                <span>Events</span>
            </label>
        </div>
    </div>
</div>

    <!-- Password with show/hide toggle -->
    <label for="password">Password</label>
    <div class="password-container">
        <input type="password" id="password" name="password" required>
        <span class="toggle-password" id="togglePassword">
            <i class="far fa-eye"></i>
        </span>
    </div>

    <!-- Camera interface -->
<label for="camera">Take Live Selfie Photo</label>
<div class="camera-container">
    <div class="camera-preview">
        <video id="video" width="100%" height="300" autoplay playsinline></video>
        <canvas id="canvas" style="display: none;"></canvas>
    </div>
    
    <div class="camera-controls">
        <button type="button" id="startCamera" class="camera-btn">
            <i class="fas fa-camera"></i> Start Camera
        </button>
        <button type="button" id="captureBtn" class="camera-btn" disabled>
            <i class="fas fa-camera-retro"></i> Capture Photo
        </button>
        <button type="button" id="retakeBtn" class="camera-btn" style="display: none;">
            <i class="fas fa-redo"></i> Retake
        </button>
    </div>
    
    <div id="preview" class="photo-preview" style="display: none;">
        <img id="photoPreview" src="" alt="Captured Photo">
        <p>Preview of your photo</p>
    </div>
    
    <input type="hidden" id="pictureData" name="picture_data" required>
</div>

    <button type="submit">Get Started</button>

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
   // Global variables
let cameraStream = null;
let countriesData = null;

// DOM Elements
const elements = {
    video: document.getElementById('video'),
    canvas: document.getElementById('canvas'),
    countrySelect: document.getElementById('country'),
    stateSelect: document.getElementById('state'),
    phoneCodeSelect: document.getElementById('phone_code'),
    form: document.getElementById('merchantRegistrationForm'),
    startCameraBtn: document.getElementById('startCamera'),
    captureBtn: document.getElementById('captureBtn'),
    retakeBtn: document.getElementById('retakeBtn'),
    preview: document.getElementById('preview'),
    photoPreview: document.getElementById('photoPreview'),
    pictureData: document.getElementById('pictureData'),
    togglePassword: document.getElementById('togglePassword'),
    password: document.getElementById('password')
};

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    loadCountriesAndStates();
    setupEventListeners();
}

// Load countries and states from database
async function loadCountriesAndStates() {
    try {
        showLoadingState('countries', true);
        
        const response = await fetch('get_countries.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            countriesData = data.data;
            populateCountryDropdown();
        } else {
            throw new Error(data.message || 'Failed to load countries');
        }
    } catch (error) {
        console.error('Error loading countries:', error);
        loadStaticCountries();
        showMessage('Using offline country data', 'error');
    } finally {
        showLoadingState('countries', false);
    }
}

// Fallback to static data
function loadStaticCountries() {
    countriesData = {
        countries: [
            { code: 'NG', name: 'Nigeria', phone_code: '+234' },
            { code: 'GH', name: 'Ghana', phone_code: '+233' },
            { code: 'KE', name: 'Kenya', phone_code: '+254' },
            { code: 'ZA', name: 'South Africa', phone_code: '+27' }
        ],
        states: [
            // Nigerian states
            { country_code: 'NG', name: 'Abia' }, { country_code: 'NG', name: 'Adamawa' },
            { country_code: 'NG', name: 'Akwa Ibom' }, { country_code: 'NG', name: 'Anambra' },
            { country_code: 'NG', name: 'Bauchi' }, { country_code: 'NG', name: 'Bayelsa' },
            { country_code: 'NG', name: 'Benue' }, { country_code: 'NG', name: 'Borno' },
            { country_code: 'NG', name: 'Cross River' }, { country_code: 'NG', name: 'Delta' },
            { country_code: 'NG', name: 'Ebonyi' }, { country_code: 'NG', name: 'Edo' },
            { country_code: 'NG', name: 'Ekiti' }, { country_code: 'NG', name: 'Enugu' },
            { country_code: 'NG', name: 'Gombe' }, { country_code: 'NG', name: 'Imo' },
            { country_code: 'NG', name: 'Jigawa' }, { country_code: 'NG', name: 'Kaduna' },
            { country_code: 'NG', name: 'Kano' }, { country_code: 'NG', name: 'Katsina' },
            { country_code: 'NG', name: 'Kebbi' }, { country_code: 'NG', name: 'Kogi' },
            { country_code: 'NG', name: 'Kwara' }, { country_code: 'NG', name: 'Lagos' },
            { country_code: 'NG', name: 'Nasarawa' }, { country_code: 'NG', name: 'Niger' },
            { country_code: 'NG', name: 'Ogun' }, { country_code: 'NG', name: 'Ondo' },
            { country_code: 'NG', name: 'Osun' }, { country_code: 'NG', name: 'Oyo' },
            { country_code: 'NG', name: 'Plateau' }, { country_code: 'NG', name: 'Rivers' },
            { country_code: 'NG', name: 'Sokoto' }, { country_code: 'NG', name: 'Taraba' },
            { country_code: 'NG', name: 'Yobe' }, { country_code: 'NG', name: 'Zamfara' },
            { country_code: 'NG', name: 'Federal Capital Territory' },
            
            // Other countries
            { country_code: 'GH', name: 'Accra' }, { country_code: 'GH', name: 'Kumasi' },
            { country_code: 'KE', name: 'Nairobi' }, { country_code: 'KE', name: 'Mombasa' },
            { country_code: 'ZA', name: 'Johannesburg' }, { country_code: 'ZA', name: 'Cape Town' }
        ]
    };
    populateCountryDropdown();
}

// Populate country dropdown
function populateCountryDropdown() {
    if (!countriesData || !countriesData.countries) return;
    
    elements.countrySelect.innerHTML = '<option value="">Select Country</option>';
    
    countriesData.countries.forEach(country => {
        const option = document.createElement('option');
        option.value = country.code;
        option.textContent = country.name;
        option.setAttribute('data-phone-code', country.phone_code);
        elements.countrySelect.appendChild(option);
    });
}

// Populate states based on selected country
function populateStates() {
    const countryCode = elements.countrySelect.value;
    
    elements.stateSelect.innerHTML = '<option value="">Select State</option>';
    elements.phoneCodeSelect.innerHTML = '<option value="">Code</option>';
    
    if (countryCode && countriesData) {
        // Populate states
        const countryStates = countriesData.states.filter(state => state.country_code === countryCode);
        countryStates.forEach(state => {
            const option = document.createElement('option');
            option.value = state.name;
            option.textContent = state.name;
            elements.stateSelect.appendChild(option);
        });
        
        // Set phone code
        const selectedCountry = countriesData.countries.find(country => country.code === countryCode);
        if (selectedCountry) {
            elements.phoneCodeSelect.innerHTML = `<option value="${selectedCountry.phone_code}">${selectedCountry.phone_code}</option>`;
        }
    }
}

// Setup event listeners
function setupEventListeners() {
    // Country change
    elements.countrySelect.addEventListener('change', populateStates);
    
    // Camera controls
    elements.startCameraBtn.addEventListener('click', startCamera);
    elements.captureBtn.addEventListener('click', capturePhoto);
    elements.retakeBtn.addEventListener('click', retakePhoto);
    
    // Password toggle
    elements.togglePassword.addEventListener('click', togglePasswordVisibility);
    
    // Form submission
    elements.form.addEventListener('submit', handleFormSubmission);
    
    // Clean up camera on page unload
    window.addEventListener('beforeunload', cleanupCamera);
}

// Camera functionality
async function startCamera() {
    try {
        showLoadingState('camera', true);
        elements.startCameraBtn.disabled = true;
        
        // Stop existing stream
        if (cameraStream) {
            cleanupCamera();
        }
        
        // Get camera access
        cameraStream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'user',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }, 
            audio: false 
        });
        
        elements.video.srcObject = cameraStream;
        elements.video.style.display = 'block';
        elements.captureBtn.disabled = false;
        elements.retakeBtn.style.display = 'none';
        elements.preview.style.display = 'none';
        
        elements.startCameraBtn.innerHTML = '<i class="fas fa-camera"></i> Camera Active';
        
    } catch (error) {
        console.error('Camera error:', error);
        handleCameraError(error);
    } finally {
        showLoadingState('camera', false);
        elements.startCameraBtn.disabled = false;
    }
}

function capturePhoto() {
    const context = elements.canvas.getContext('2d');
    
    // Set canvas size to match video
    elements.canvas.width = elements.video.videoWidth;
    elements.canvas.height = elements.video.videoHeight;
    
    // Draw current video frame to canvas
    context.drawImage(elements.video, 0, 0, elements.canvas.width, elements.canvas.height);
    
    // Convert to data URL
    const imageData = elements.canvas.toDataURL('image/jpeg', 0.8);
    
    // Show preview
    elements.photoPreview.src = imageData;
    elements.preview.style.display = 'block';
    
    // Store image data
    elements.pictureData.value = imageData;
    
    // Update UI
    elements.retakeBtn.style.display = 'inline-block';
    elements.captureBtn.disabled = true;
    
    // Stop camera to save resources
    cleanupCamera();
}

function retakePhoto() {
    elements.preview.style.display = 'none';
    elements.retakeBtn.style.display = 'none';
    elements.captureBtn.disabled = true;
    elements.pictureData.value = '';
    
    elements.startCameraBtn.innerHTML = '<i class="fas fa-camera"></i> Start Camera';
    elements.startCameraBtn.disabled = false;
}

function cleanupCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
}

function handleCameraError(error) {
    let errorMessage = 'Error accessing camera: ' + error.message;
    
    if (error.name === 'NotAllowedError') {
        errorMessage = 'Camera access denied. Please allow camera permissions in your browser settings.';
    } else if (error.name === 'NotFoundError') {
        errorMessage = 'No camera found on your device.';
    } else if (error.name === 'NotSupportedError') {
        errorMessage = 'Camera not supported in your browser.';
    }
    
    showMessage(errorMessage, 'error');
    elements.startCameraBtn.innerHTML = '<i class="fas fa-camera"></i> Start Camera';
}

// Password visibility toggle
function togglePasswordVisibility() {
    const type = elements.password.getAttribute('type') === 'password' ? 'text' : 'password';
    elements.password.setAttribute('type', type);
    
    const icon = elements.togglePassword.querySelector('i');
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}

// Form submission handler
async function handleFormSubmission(e) {
    e.preventDefault();

    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    try {
        // Validate form before submission
        if (!validateForm()) {
            return;
        }
        
        showLoadingState('submit', true);
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="loading"></span> Registering...';
        
        const formData = new FormData(elements.form);
        
        const response = await fetch('merchant_register.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => {
                window.location.href = '../login.php';
            }, 3000);
        } else {
            throw new Error(data.message);
        }
        
    } catch (error) {
        console.error('Submission error:', error);
        showMessage(
            error.message || 'Registration failed. Please check your connection and try again.', 
            'error'
        );
    } finally {
        showLoadingState('submit', false);
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
}

// Form validation
function validateForm() {
    const requiredFields = [
        'company_name', 'owners_name', 'email', 'country', 'state', 
        'phone', 'NIN', 'password', 'picture_data'
    ];
    
    const missingFields = [];
    
    requiredFields.forEach(field => {
        const element = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
        if (!element || !element.value.trim()) {
            missingFields.push(field.replace('_', ' '));
        }
    });
    
    // Check services
    const services = document.querySelectorAll('input[name="services[]"]:checked');
    if (services.length === 0) {
        missingFields.push('services');
    }
    
    if (missingFields.length > 0) {
        showMessage(`Please fill in all required fields: ${missingFields.join(', ')}`, 'error');
        return false;
    }
    
    // Email validation
    const email = document.getElementById('email').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showMessage('Please enter a valid email address', 'error');
        return false;
    }
    
    // Password length
    const password = document.getElementById('password').value;
    if (password.length < 8) {
        showMessage('Password must be at least 8 characters long', 'error');
        return false;
    }
    
    return true;
}

// Loading state management
function showLoadingState(context, isLoading) {
    const loaders = {
        countries: () => {
            const countrySelect = elements.countrySelect;
            if (isLoading) {
                countrySelect.disabled = true;
                countrySelect.innerHTML = '<option value="">Loading countries...</option>';
            } else {
                countrySelect.disabled = false;
            }
        },
        camera: () => {
            if (isLoading) {
                elements.startCameraBtn.innerHTML = '<span class="loading"></span> Accessing Camera...';
            }
        },
        submit: () => {
            // Handled in form submission
        }
    };
    
    if (loaders[context]) {
        loaders[context]();
    }
}

// Message Carousel System
function showMessage(message, type) {
    const carousel = document.querySelector('.message-carousel') || createMessageCarousel();
    const messageId = 'msg-' + Date.now();
    
    const messageSlide = document.createElement('div');
    messageSlide.className = `message-slide ${type}`;
    messageSlide.id = messageId;
    
    const icon = type === 'success' ? 
        `<svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
            <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
        </svg>` : 
        '<i class="fas fa-exclamation-circle message-icon"></i>';
    
    const title = type === 'success' ? 'Success!' : 'Error!';
    
    messageSlide.innerHTML = `
        <div class="message-header">
            <div class="message-title ${type}">
                ${icon}
                <span>${title}</span>
            </div>
            <button class="close-message" onclick="closeMessage('${messageId}')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="message-content">${message}</div>
        <div class="progress-bar"></div>
    `;
    
    carousel.appendChild(messageSlide);
    
    // Animate in
    setTimeout(() => {
        messageSlide.classList.add('show');
    }, 100);
    
    // Auto remove after 5 seconds
    const autoRemove = setTimeout(() => {
        closeMessage(messageId);
    }, 5000);
    
    // Store timer reference
    messageSlide.dataset.timer = autoRemove;
}

function createMessageCarousel() {
    const carousel = document.createElement('div');
    carousel.className = 'message-carousel';
    document.body.appendChild(carousel);
    return carousel;
}

function closeMessage(messageId) {
    const messageSlide = document.getElementById(messageId);
    if (!messageSlide) return;
    
    // Clear auto-remove timer
    if (messageSlide.dataset.timer) {
        clearTimeout(parseInt(messageSlide.dataset.timer));
    }
    
    // Animate out
    messageSlide.classList.remove('show');
    messageSlide.classList.add('hide');
    
    // Remove from DOM after animation
    setTimeout(() => {
        if (messageSlide.parentNode) {
            messageSlide.parentNode.removeChild(messageSlide);
        }
    }, 500);
}

// Utility function to debounce rapid calls
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export functions for global access (if needed in HTML onclick)
window.closeMessage = closeMessage;
window.populateStates = populateStates;
</script>
</body>
</html>
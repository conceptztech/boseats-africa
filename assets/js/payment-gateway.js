class PaymentGateway {
    constructor() {
        // Replace with your actual Paystack public key
        this.publicKey = 'pk_test_1b251229f5da6778289c78b9f73075dcd30003a9'; 
        this.isProcessing = false;
    }

    /**
     * Initialize payment with Paystack
     */
    initiatePayment(orderData) {
        return new Promise((resolve, reject) => {
            if (this.isProcessing) {
                reject('Payment already in progress');
                return;
            }

            this.isProcessing = true;
            
            // Show processing indicator
            this.showProcessing();

            // Generate unique reference
            const reference = 'BOS' + Math.floor((Math.random() * 1000000000) + 1) + Date.now();
            
            // Validate Paystack is loaded
            if (typeof PaystackPop === 'undefined') {
                this.isProcessing = false;
                this.hideProcessing();
                reject('Paystack payment library not loaded. Please refresh the page.');
                return;
            }

            // Validate we have user email
            if (!orderData.user_email) {
                this.isProcessing = false;
                this.hideProcessing();
                reject('User email is required for payment');
                return;
            }

            console.log('Starting Paystack payment for:', orderData.user_email);

            // Convert the total amount directly to Kobo (Paystack expects kobo as the smallest unit)
            const amountInKobo = Math.round(orderData.total * 100); // Convert to kobo (100 kobo = 1 Naira)

            const handler = PaystackPop.setup({
                key: this.publicKey,
                email: orderData.user_email,
                amount: amountInKobo, // Amount in kobo
                currency: 'NGN', // Naira
                ref: reference,
                metadata: {
                    custom_fields: [
                        {
                            display_name: "Order Details",
                            variable_name: "order_details",
                            value: JSON.stringify(orderData)
                        },
                        {
                            display_name: "User ID", 
                            variable_name: "user_id",
                            value: orderData.user_id
                        }
                    ]
                },
                callback: (response) => {
                    console.log('Payment successful:', response);
                    this.isProcessing = false;
                    this.hideProcessing();
                    
                    // Save order to database
                    this.saveOrderToDatabase(orderData, response.reference, 'completed')
                        .then(() => {
                            this.showPaymentStatus('success', 'Payment completed successfully!');
                            resolve(response);
                        })
                        .catch(error => {
                            console.error('Error saving order:', error);
                            this.showPaymentStatus('error', 'Payment successful but order saving failed. Reference: ' + response.reference);
                            resolve(response); // Still resolve since payment was successful
                        });
                },
                onClose: () => {
                    console.log('Payment window closed by user');
                    this.isProcessing = false;
                    this.hideProcessing();
                    reject('Payment cancelled by user');
                }
            });
            
            handler.openIframe();
        });
    }

    /**
     * Show processing indicator
     */
    showProcessing() {
        const processingEl = document.getElementById('payment-processing');
        if (processingEl) {
            processingEl.style.display = 'block';
        }
    }

    /**
     * Hide processing indicator
     */
    hideProcessing() {
        const processingEl = document.getElementById('payment-processing');
        if (processingEl) {
            processingEl.style.display = 'none';
        }
    }

    /**
     * Save order to database
     */
    async saveOrderToDatabase(orderData, reference, status) {
        const formData = new FormData();
        formData.append('user_id', orderData.user_id);
        formData.append('order_data', JSON.stringify(orderData));
        formData.append('total_amount', orderData.total); // Total in dollars or original currency
        formData.append('payment_reference', reference);
        formData.append('payment_status', status);
        formData.append('delivery_location', orderData.location);
        formData.append('delivery_address', orderData.note);
        formData.append('action', 'save_order');
        
        const response = await fetch('../includes/cart_handler.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            return data;
        } else {
            throw new Error(data.message);
        }
    }

    /**
     * Show payment status message
     */
    showPaymentStatus(type, message) {
        // Remove existing status messages
        const existingStatus = document.querySelector('.payment-status-message');
        if (existingStatus) {
            existingStatus.remove();
        }

        const statusDiv = document.createElement('div');
        statusDiv.className = `payment-status-message payment-${type}`;
        statusDiv.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            font-family: 'Poppins', sans-serif;
            text-align: center;
            min-width: 300px;
        `;

        // Set background color based on type
        switch(type) {
            case 'success':
                statusDiv.style.backgroundColor = '#28a745';
                statusDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
                break;
            case 'error':
                statusDiv.style.backgroundColor = '#dc3545';
                statusDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                break;
            case 'info':
                statusDiv.style.backgroundColor = '#17a2b8';
                statusDiv.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
                break;
            default:
                statusDiv.style.backgroundColor = '#6c757d';
                statusDiv.innerHTML = message;
        }

        document.body.appendChild(statusDiv);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (statusDiv.parentNode) {
                statusDiv.remove();
            }
        }, 5000);
    }

    /**
     * Validate payment form
     */
    validatePaymentForm(orderData) {
        const errors = [];

        if (!orderData.total || orderData.total <= 0) {
            errors.push('Invalid order total');
        }

        if (!orderData.location) {
            errors.push('Delivery location is required');
        }

        if (!orderData.note?.trim()) {
            errors.push('Delivery address is required');
        }

        if (!orderData.user_email) {
            errors.push('User email is required');
        }

        return errors;
    }

    /**
     * Set public key
     */
    setPublicKey(key) {
        this.publicKey = key;
    }
}

// Create global instance
const paymentGateway = new PaymentGateway();

/**
 * Global payment initiation function
 */
async function initiatePayment(orderData) {
    try {
        console.log('Starting payment process...', orderData);

        // Validate order data
        const errors = paymentGateway.validatePaymentForm(orderData);
        if (errors.length > 0) {
            alert('Please fix the following errors:\n' + errors.join('\n'));
            return;
        }

        console.log('Order data validated, initiating Paystack...');

        // Initiate payment
        const response = await paymentGateway.initiatePayment(orderData);
        
        console.log('Payment successful, clearing cart...');

        // Payment successful - clear cart and redirect
        clearCart();
        
        // Redirect to success page
        setTimeout(() => {
            window.location.href = 'order_success.php?reference=' + response.reference;
        }, 2000);

    } catch (error) {
        console.error('Payment error:', error);
        
        // Don't show alert for user cancellation
        if (error !== 'Payment cancelled by user') {
            paymentGateway.showPaymentStatus('error', 'Payment failed: ' + error);
        }
    }
}

// Make functions globally available
window.paymentGateway = paymentGateway;
window.initiatePayment = initiatePayment;
window.clearCart = clearCart;

<?php include_once "../includes/e_header.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>

<div class="checkout-container">
    <!-- Left Section: Cart Details -->
    <div class="checkout-left">
        <h3>Price updated?</h3>

        <!-- Wrapper for Product Boxes -->
        <div class="product-box-wrapper">
            <!-- Each product in a box -->
            <div class="product-box">
                <div class="product-label">Picture  
                                    </div>
                <div class="product-value">
                    <img src="../assets/images/concert1.png" alt="Product Image" class="product-image">
                </div>
            </div>

            <div class="product-box">
                <div class="product-label">Product 
                                    </div>
                <div class="product-value">Regular Tickets</div>
            </div>

            <div class="product-box">
                <div class="product-label">Price 

                </div>
                <div class="product-value">$70.00</div>
            </div>

            <div class="product-box">
                <div class="product-label">Quantity 

                </div>
                <div class="product-value">
                    <div class="quantity-selector">
                        <button class="quantity-btn" onclick="changeQuantity('decrease')"><span class="check-mark">-</span></button>
                        <input type="number" id="quantity" value="1" min="1" class="quantity-input">
                        <button class="quantity-btn" onclick="changeQuantity('increase')"><span class="check-mark">+</span></button>
                    </div>
                </div>
            </div>

            <div class="product-box">
                <div class="product-label">Subtotal 

                </div>
                <div class="product-value">$<span id="subtotal">70.00</span></div>
            </div>
        </div>

        <!-- Coupon Section -->
        <div class="coupon">
            <input type="text" id="coupon-code" placeholder="Enter coupon code">
            <button class="apply-coupon" onclick="applyCoupon()">Apply coupon</button>
        </div>
    </div>

    <!-- Right Section: Cart Totals -->
    <div class="checkout-right">
        <h3>Carts total</h3>
        
        <!-- Cart Totals in Boxes -->
        <div class="cart-total-box">
            <div class="total-label">Subtotal 
            </div>
            <div class="total-value">$<span id="total-subtotal">70.00</span></div>
        </div>

        <div class="cart-total-box">
            <div class="total-label">Total 

            </div>
            <div class="total-value">$<span id="total-amount">70.00</span></div>
        </div>

        <!-- Checkout Button -->
        <button class="checkout-btn" onclick="goToCheckout()">Press to checkout</button>
    </div>
</div>



<script>
    // Change quantity and update the subtotal
    function changeQuantity(action) {
        var quantityInput = document.getElementById('quantity');
        var quantity = parseInt(quantityInput.value);

        if (action === 'increase') {
            quantity++;
        } else if (action === 'decrease' && quantity > 1) {
            quantity--;
        }

        quantityInput.value = quantity;

        var pricePerTicket = 70; // Price per ticket in USD
        var subtotal = quantity * pricePerTicket;
        document.getElementById('subtotal').textContent = subtotal.toFixed(2);

        // Update the cart total
        updateCartTotal();
    }

    // Update the cart total (subtotal and total amount)
    function updateCartTotal() {
        var subtotal = parseFloat(document.getElementById('subtotal').textContent);
        document.getElementById('total-subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('total-amount').textContent = subtotal.toFixed(2);
    }

    // Apply coupon logic (for demonstration purposes, assuming a 10% discount)
    function applyCoupon() {
        var couponCode = document.getElementById('coupon-code').value;
        var totalAmount = parseFloat(document.getElementById('total-amount').textContent);

        // Check for a valid coupon (for demo purposes, the coupon code is "DISCOUNT10")
        if (couponCode === 'DISCOUNT10') {
            var discount = totalAmount * 0.1;
            var newTotal = totalAmount - discount;
            document.getElementById('total-amount').textContent = newTotal.toFixed(2);
            alert("Coupon applied! You saved 10%");
        } else {
            alert("Invalid coupon code.");
        }
    }

    // Redirect to checkout page (for demonstration purposes)
    function goToCheckout() {
        window.location.href = "checkout-process.php";
    }
</script>
<?php include_once "event_scroller.php"; ?>
</body>
</html>


<style>
/* General Layout */
.checkout-container {
    display: flex;
    justify-content: space-between;
    padding: 80px;
    font-family: Arial, sans-serif;
}

.checkout-left {
    width: 50%;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding-right: 10%;
    padding-left: 10%;
}

.checkout-right {
    width: 50%;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 10px;
    margin-left: 50px;
    margin-bottom: 20%;
}

.checkout-left h3, .checkout-right h3 {
    font-size: 20px;
    margin-bottom: 20px;
}

/* Product Boxes Layout */
.product-box-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 15px; /* Adds spacing between the boxes */
}

.product-box {
    width: 110%; /* Default to 100% on small screens */
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 8px;
    display: flex;
    flex-direction: row;
    justify-content: left;
    background-color: transparent;
    margin-bottom: 15px; /* Space between boxes */
    margin-left: 0px;
}

.product-box .product-label {
    font-weight: bold;
    margin-right: 10px; /* Add space after label */
    width: 250px; /* Fixed width for labels to maintain alignment */
}

.product-box .product-value {
    font-size: 16px;
    flex: 1; /* Allow value to take remaining space */
}

/* Product Image Styling */
.product-image {
    width: 60px;
    height: auto;
    margin: 0 auto;
}

/* Green Check Marks Inside Box for Quantity */
.check-mark {
    color: green;
    background-color: #f0f0f0;
    border: 2px solid green;
    padding: 5px;
    border-radius: 5px;
    cursor: pointer;
}

/* Quantity Selector */
.quantity-selector {
    display: flex;
    align-items: center;
    justify-content: left;
}

.quantity-btn {
    font-size: 15px;
    padding: 0px 5px;
    background-color: #f0f0f0;
    border: 1px solid #ccc;
    cursor: pointer;
    margin-left: -5px;
     margin-right: -5px;
}

.quantity-input {
    width: 60px;
    text-align: center;
    font-size: 18px;
    border: 1px solid #ccc;
    margin: 0 2px;
}

/* Cart Totals Section */
.cart-total-box {
    display: flex;
    flex-direction: row;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 8px;
    background-color: transparent;
    margin-bottom: 20px;
}

.total-label {
    font-weight: bold;
    width: 250px; /* Fixed width for labels to maintain alignment */
}

.total-value {
    font-size: 18px;
    flex: 1; /* Allow value to take remaining space */
}

/* Checkout Button */
.checkout-btn {
    padding: 15px;
    background-color: #4CAF50;
    color: white;
    width: 100%;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    font-size: 18px;
}

/* Coupon Section */
.coupon {
    display: flex;
    flex-direction: row;
}

.coupon input {
    padding: 10px;
    width: 80%;
    border: 1px solid #ccc;
    margin-right: 10px;
}

.apply-coupon {
    width: 50%;
    padding: 10px;
    background-color: #4CAF50;
    color: white;
    border: none;
    cursor: pointer;
    margin-right: 10px;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .checkout-container {
        flex-direction: column;
        align-items: center;
    }

    .checkout-left {
        width: 100%;
        margin-bottom: 20px;
    }

    .checkout-right {
        width: 110%;
        margin-bottom: 20px;
        margin-left: 5px;
    }

    /* Stack product boxes vertically */
    .product-box {
        width: 90%;
    }

    .cart-total-box {
        width: 90%;
    }

    .product-value {
        text-align: left; /* Align product details to left */
    }

    .product-image {
        width: 80px; /* Resize the image for smaller screens */
    }

    .quantity-selector {
        flex-direction: row;
        align-items: center;
        width: 10%;
    }

    .checkout-btn {
        font-size: 16px;
        padding: 12px;
        width: 98%;
    }
}

    </style>
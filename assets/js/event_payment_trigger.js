<script>
    // Function to calculate the total price for the tickets
    function calculateTotal() {
        var regularTickets = document.getElementById("regular").value * 5000; // Regular ticket price
        var vipTickets = document.getElementById("vip").value * 120000; // VIP ticket price
        var goldTickets = document.getElementById("gold").value * 1500000; // Gold Table price
        var premiumTickets = document.getElementById("premium").value * 3000000; // Premium Table price
        var seatGold = document.getElementById("seat-gold").value * 59000; // Seat on Gold Table
        var seatPremium = document.getElementById("seat-premium").value * 70000; // Seat on Premium Table

        // Total amount in Naira (convert the total to kobo by multiplying by 100)
        var totalAmount = regularTickets + vipTickets + goldTickets + premiumTickets + seatGold + seatPremium;

        return totalAmount;
    }

    // On "Buy tickets" click, trigger Paystack payment
    document.querySelector(".buy-tickets").addEventListener("click", function () {
        var amount = calculateTotal(); // Get total amount from ticket selections
        var email = "customer@example.com"; // You can fetch this from the user's profile or form input

        // If the total amount is greater than 0
        if (amount > 0) {
            var handler = PaystackPop.setup({
                key: "your-public-key",  // Replace with your Paystack public key
                email: email,
                amount: amount * 100,  // Amount is in kobo, so multiply by 100
                currency: "NGN",  // Set the currency (NGN for Naira, but use USD or another currency as needed)
                ref: "txn-" + Math.floor(Math.random() * 1000000),  // Generate a unique transaction reference
                callback: function (response) {
                    // Handle the successful payment
                    alert("Payment successful! Transaction reference: " + response.reference);
                    // You can now send the reference to your backend to verify the payment
                    window.location.href = "payment-success.php?reference=" + response.reference;  // Redirect to payment success page
                },
                onClose: function () {
                    alert("Payment was closed.");
                }
            });

            handler.openIframe(); // Show Paystack's payment modal
        } else {
            alert("Please select at least one ticket.");
        }
    });
</script>

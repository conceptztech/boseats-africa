# Boseat Africa

Welcome to **Boseat Africa**, an all-in-one platform that allows users to easily book flights, car rentals, hotels, and events across Africa. With an intuitive interface, this project aims to make travel and leisure experiences seamless, accessible, and efficient.

Website at: [boseatsafrica.com](https://boseatsafrica.com/)

## Features

* **Flight Booking**: Search and book flights from various airlines, with real-time pricing and availability.
* **Food**
* **Car Rentals**: Rent vehicles from top car rental providers across Africa.
* **Hotel Bookings**: Find and book hotels in major cities and tourist destinations.
* **Event Listings**: Discover and book events such as concerts, festivals, and conferences.

## Tech Stack

* **Backend**: PHP
* **Frontend**: HTML, CSS, JavaScript
* **Database**: MySQL
* **Payment Integration**: Paystack

## Installation

### Prerequisites

Before you can set up this project locally, ensure you have the following installed:

* **PHP 7.4+**
* **Composer** for managing PHP dependencies
* **MySQL** or any other database of your choice
* A local server like **XAMPP**, **MAMP**, or **Docker** (if running locally)

### Step 1: Clone the Repository

```bash
git clone https://github.com/boseatsafrica/boseat-project.git
cd boseat-project
```

### Step 2: Install Dependencies

Run the following command to install the required PHP dependencies:

```bash
composer install
```

### Step 3: Set Up Environment File

Copy the `.env.example` to `.env`:

```bash
cp .env.example .env
```

Now, open the `.env` file and update the database and other environment settings as per your local configuration.

### Step 4: Set Up the Database

Run the following command to create the necessary database tables:

```bash
php artisan migrate
```

### Step 5: Serve the Application

To start the development server, run:

```bash
php artisan serve
```

The application should now be running at `http://localhost:8000`.

## Contributing

For additional developers' contributions to this project, follow these steps:

1. Fork the repository.
2. Create a new branch (`git checkout -b feature/your-feature`).
3. Commit your changes (`git commit -m 'Add new feature'`).
4. Push to your fork (`git push origin feature/your-feature`).
5. Create a pull request from your fork to the main repository.

## License

This project is under proprietary license, and should not be used without permission from BoseatsAfrica management.

## Contact

For support or questions, reach out via:

* Email: [support@boseatsafrica.com](mailto:support@boseatsafrica.com)
* Website: [boseatsafrica.com](https://boseatsafrica.com/)

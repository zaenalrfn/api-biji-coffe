# API Biji Coffee

Backend API application for Biji Coffee Shop built with Laravel 12.

## Requirements

- PHP ^8.2
- Composer
- MySQL

## Installation

Follow these steps to set up the project locally:

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd api-biji-coffe
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment Setup**
   Copy the example environment file and configure your database credentials:
   ```bash
   cp .env.example .env
   ```
   Open `.env` and set your database connection details:
   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=api_biji_coffe
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

5. **Run Migrations**
   Create the database tables:
   ```bash
   php artisan migrate
   ```

6. **Serve the Application**
   Start the local development server:
   ```bash
   php artisan serve
   ```
   The API will be accessible at `http://localhost:8000`.

## API Documentation

### Base URL
`http://localhost:8000/api`

### Authentication (Public)
These endpoints do not require a token.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/register` | Register a new user account. |
| `POST` | `/login` | Login and receive an authentication token. |

### Protected Endpoints
Require `Authorization: Bearer <token>` header.

#### User Management
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/user` | Get authenticated user details. |
| `PUT` | `/user` | Update user profile. |
| `POST` | `/logout` | Logout and invalidate the token. |

#### Products & Categories
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/categories` | List all categories. |
| `GET` | `/categories/{id}` | Get specific category details. |
| `POST` | `/categories` | Create a new category. |
| `PUT` | `/categories/{id}` | Update a category. |
| `DELETE` | `/categories/{id}` | Delete a category. |
| `GET` | `/products` | List all products. |
| `GET` | `/products/{id}` | Get specific product details. |
| `POST` | `/products` | Create a new product. |
| `PUT` | `/products/{id}` | Update a product. |
| `DELETE` | `/products/{id}` | Delete a product. |

#### Cart & Orders
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/cart` | Get user's cart items. |
| `POST` | `/cart` | Add item into cart. |
| `PUT` | `/cart/{id}` | Update cart item. |
| `DELETE` | `/cart/{id}` | Remove item from cart. |
| `GET` | `/orders` | Get user's order history. |
| `POST` | `/orders` | Place a new order. |
| `GET` | `/orders/{id}` | Get specific order details. |

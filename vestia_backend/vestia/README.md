# VESTIA COUTURE — Backend Documentation
## PHP REST API + Admin Panel

---

## 📁 Project Structure

```
vestia/
├── api/                    ← REST API (point Flutter app here)
│   ├── index.php           ← Main router
│   ├── .htaccess
│   ├── config/
│   │   └── database.php    ← ⚙️ Set DB credentials here
│   ├── helpers/
│   │   ├── auth.php        ← Token auth middleware
│   │   └── response.php    ← JSON helpers
│   └── controllers/
│       ├── AuthController.php
│       ├── ProductController.php
│       ├── CategoryController.php
│       ├── CartController.php
│       ├── SavedController.php
│       ├── OrderController.php
│       ├── ReviewController.php
│       └── ProfileController.php
├── admin/                  ← Admin Panel (browser-based)
│   ├── login.php           ← /admin/login.php
│   ├── dashboard.php
│   ├── products.php
│   ├── categories.php
│   ├── orders.php
│   ├── users.php
│   ├── reviews.php
│   ├── logout.php
│   └── includes/
│       ├── db.php
│       ├── header.php
│       └── footer.php
└── sql/
    └── vestia_database.sql ← Import this first!
```

---

## 🚀 Server Setup

### Step 1 — Import Database
```bash
mysql -u root -p < sql/vestia_database.sql
```

### Step 2 — Configure DB Credentials
Edit **both** of these files and set your DB credentials:
- `api/config/database.php`
- `admin/includes/db.php`

### Step 3 — Upload to Server
```
/var/www/html/
├── api/          →  https://yourdomain.com/api/
└── admin/        →  https://yourdomain.com/admin/
```
Make sure `mod_rewrite` is enabled (for API routing).

### Step 4 — Admin Login
URL: `https://yourdomain.com/admin/login.php`
- Email: `admin@vestia.com`
- Password: `Admin@1234`
> ⚠️ Change password immediately after first login!

---

## 🔌 API Endpoints

**Base URL:** `https://yourdomain.com/api`

### Authentication
| Method | Endpoint     | Auth | Description |
|--------|-------------|------|-------------|
| POST   | /register   | ❌   | Create account |
| POST   | /login      | ❌   | Login, returns token |
| POST   | /logout     | ✅   | Logout |

**Register body:**
```json
{ "name": "John", "email": "john@mail.com", "password": "secret123" }
```

**Login body:**
```json
{ "email": "john@mail.com", "password": "secret123" }
```

**Auth Response:**
```json
{
  "success": true,
  "data": {
    "token": "abc123...",
    "user": { "id": 1, "name": "John", "email": "john@mail.com" }
  }
}
```

> All protected endpoints require: `Authorization: Bearer <token>`

---

### Products
| Method | Endpoint                         | Auth | Description |
|--------|----------------------------------|------|-------------|
| GET    | /products                        | ❌   | List products |
| GET    | /products?category=tshirts       | ❌   | Filter by category slug |
| GET    | /products?search=polo            | ❌   | Search |
| GET    | /products?page=1&limit=20        | ❌   | Paginated |
| GET    | /products/{id}                   | ❌   | Single product |
| GET    | /products/{id}/reviews           | ❌   | Product reviews |
| POST   | /products/{id}/reviews           | ✅   | Submit review |

**Review body:**
```json
{ "rating": 5, "text": "Great quality!", "order_id": 12 }
```

---

### Categories
| Method | Endpoint      | Auth | Description |
|--------|--------------|------|-------------|
| GET    | /categories  | ❌   | All categories |

---

### Saved / Wishlist
| Method | Endpoint | Auth | Description |
|--------|---------|------|-------------|
| GET    | /saved  | ✅   | Get wishlist |
| POST   | /saved  | ✅   | Toggle save/unsave |

**Toggle body:**
```json
{ "product_id": 3 }
```

---

### Cart
| Method | Endpoint      | Auth | Description |
|--------|-------------|------|-------------|
| GET    | /cart        | ✅   | Get cart with totals |
| POST   | /cart        | ✅   | Add item |
| PUT    | /cart/{id}   | ✅   | Update quantity |
| DELETE | /cart/{id}   | ✅   | Remove item |

**Add to cart body:**
```json
{ "product_id": 2, "quantity": 1, "size": "M" }
```

**Cart Response includes:**
```json
{
  "items": [...],
  "subtotal": 2390,
  "shipping_fee": 80,
  "vat": 0,
  "total": 2470,
  "item_count": 2
}
```

---

### Orders
| Method | Endpoint           | Auth | Description |
|--------|--------------------|------|-------------|
| GET    | /orders            | ✅   | All user orders |
| GET    | /orders?status=ongoing    | ✅   | Ongoing orders |
| GET    | /orders?status=completed  | ✅   | Completed orders |
| GET    | /orders/{id}       | ✅   | Single order detail |
| POST   | /orders            | ✅   | Place order (from cart) |

---

### Profile
| Method | Endpoint  | Auth | Description |
|--------|----------|------|-------------|
| GET    | /profile | ✅   | Get profile |
| PUT    | /profile | ✅   | Update name/email/password |

---

## 🖥️ Admin Panel Pages

| Page         | URL                       | Features |
|--------------|--------------------------|---------|
| Dashboard    | /admin/dashboard.php      | Revenue, orders, customers stats |
| Products     | /admin/products.php       | Add / Edit / Remove products |
| Categories   | /admin/categories.php     | Manage categories |
| Orders       | /admin/orders.php         | View orders, update status |
| Customers    | /admin/users.php          | View / Suspend customers |
| Reviews      | /admin/reviews.php        | View / Delete reviews |

---

## 📱 Flutter Integration

In your Flutter app, replace the hardcoded product data with API calls:

```dart
// api_service.dart
const String baseUrl = 'https://yourdomain.com/api';

// GET products
final response = await http.get(Uri.parse('$baseUrl/products?category=tshirts'));

// POST login
final response = await http.post(
  Uri.parse('$baseUrl/login'),
  headers: {'Content-Type': 'application/json'},
  body: jsonEncode({'email': email, 'password': password}),
);

// Authenticated request
final response = await http.get(
  Uri.parse('$baseUrl/cart'),
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer $token',
  },
);
```

---

## 🔒 Security Notes

- Passwords hashed with bcrypt (cost 12)
- CSRF protection on all admin POST forms
- Token-based auth for API (30-day expiry)
- Admin session-based auth
- PDO prepared statements throughout (SQL injection safe)
- Input sanitization on all user inputs

---

*VESTIA COUTURE Backend v1.0*

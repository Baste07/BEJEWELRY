# Bejewelry — Backend Setup Guide
> For the backend developer (classmate handoff)

---

## Folder Structure

```
bejewelry/
├── api/
│   ├── config.php       ← ⚠️ Edit this with your DB credentials
│   ├── helpers.php      ← JWT auth, response helpers
│   ├── index.php        ← API router (all /api/* requests go here)
│   ├── auth.php         ← Login, register, logout
│   ├── products.php     ← Products + categories
│   ├── cart.php         ← Cart management
│   └── resources.php    ← Wishlist, orders, user profile
├── js/
│   ├── api.js           ← Frontend HTTP client (calls the PHP API)
│   └── app.js           ← Frontend UI logic
├── css/
│   ├── styles.css
│   ├── fonts.css
│   └── bejewelry-design-system.css
├── uploads/products/    ← Product images go here (chmod 755)
├── schema.sql           ← Run this first to create the database
├── .htaccess            ← Apache URL rewriting (required)
└── *.html               ← Frontend pages
```

---

## Setup Steps

### 1. Create the database
In phpMyAdmin: create a DB called `bejewelry`, then Import → select `schema.sql`

### 2. Edit api/config.php
Fill in your DB host, name, user, password, and a random JWT_SECRET string.

### 3. Set uploads folder permissions
chmod 755 uploads/products/

### 4. Enable Apache mod_rewrite
In XAMPP: Edit httpd.conf, find the htdocs Directory block, change AllowOverride None to AllowOverride All.

### 5. Place project in server root
XAMPP: C:/xampp/htdocs/bejewelry/
Then visit: http://localhost/bejewelry/index.html

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth?action=login | Login |
| POST | /api/auth?action=register | Register |
| GET | /api/auth?action=me | Get current user |
| GET | /api/products | List products |
| GET | /api/products/{id} | Single product |
| POST | /api/products | Add product (admin only) |
| GET | /api/categories | List categories |
| GET | /api/cart | Get cart |
| POST | /api/cart | Add to cart |
| PATCH | /api/cart/{id} | Update qty |
| DELETE | /api/cart/{id} | Remove item |
| GET | /api/wishlist | Get wishlist |
| POST | /api/wishlist | Add to wishlist |
| DELETE | /api/wishlist/{id} | Remove from wishlist |
| GET | /api/orders | Get orders |
| POST | /api/orders | Place order |
| GET | /api/users/me | Get profile |
| PATCH | /api/users/me | Update profile |
| GET | /api/users/me/addresses | Get addresses |
| POST | /api/users/me/addresses | Add address |

---

## Default Admin Account
Email: admin@bejewelry.ph | Password: admin123
Change this after first login!

## Auth
Login returns a JWT token stored in localStorage as bj_token.
All protected endpoints need: Authorization: Bearer <token>

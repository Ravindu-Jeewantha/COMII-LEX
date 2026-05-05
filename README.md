# ⚡ Comii Lex — Premium Tech E-Commerce Store

> A clean, modern, and professional full-stack e-commerce website built with PHP, MySQL, and Vanilla JavaScript. Features stunning 3D UI effects, dark/light mode, real-time cart management, and a fully mobile-responsive design.

---

## 🎯 Project Overview

**Comii Lex** is a portfolio-grade tech e-commerce web application showcasing premium consumer electronics. It demonstrates full-stack web development skills including PHP backend with MySQL database integration, dynamic front-end interactions, and modern UX design principles.

---

## ✨ Features

### 🎨 UI / Design
- **Dark/Light mode toggle** — persisted across sessions via localStorage
- **3D hover effects** on product cards (rotateX, rotateY, perspective)
- **Glassmorphism navbar** with backdrop blur
- **Loading screen** animation on page load
- **Toast notifications** for cart actions
- **Smooth scroll reveal** animations as content enters viewport
- **Animated hero section** with floating gradient orbs
- **Gradient mesh background** with noise texture overlay

### 🛒 E-Commerce
- Full product listing with brand, name, price, and image
- Product detail page with quantity selector
- Shopping cart with add/remove/update functionality
- Real-time cart badge counter (AJAX)
- Cart total calculation with shipping estimate
- Session-based cart (no login required)

### 🔍 Search & Filter
- Live search (instant results as you type)
- Filter by brand (dropdown + chip buttons)
- Sort by price (low to high / high to low)
- Combined filters work simultaneously

### 📱 Responsive Design
- Mobile-first approach
- Hamburger menu for mobile navigation
- Responsive grid: 1 col (mobile) → 2-4 cols (desktop)
- Touch-friendly buttons and spacing

### ⚡ Performance
- Lazy-loaded images
- Minimal external dependencies (just Google Fonts)
- CSS variables for consistent theming
- Intersection Observer for scroll animations

---

## 📸 Screenshots

> _Add screenshots of your running application here_

| Home Page | Products | Product Detail | Cart |
|-----------|----------|---------------|------|
| ![Home](screenshots/home.png) | ![Products](screenshots/products.png) | ![Detail](screenshots/detail.png) | ![Cart](screenshots/cart.png) |

---

## 🗂️ File Structure

```
comii-lex/
│
├── index.php          # Home page (hero, featured products, brands)
├── products.php       # All products with search/filter
├── product.php        # Single product detail page
├── cart.php           # Cart management (AJAX backend + UI)
├── config.php         # DB connection + helper functions
│
├── css/
│   └── style.css      # All styles (3D effects, responsive, dark mode)
│
├── js/
│   └── script.js      # UI interactions, AJAX, filters, animations
│
├── images/            # Product images (add your own)
│
└── database/
    └── comii_lex.sql  # Database schema + sample products
```

---

## 🚀 Installation

### Prerequisites
- PHP 7.4+ (or 8.x)
- MySQL 5.7+ / MariaDB
- Web server: Apache (XAMPP/WAMP) or Nginx

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/comii-lex.git
   cd comii-lex
   ```

2. **Set up the database**
   - Open phpMyAdmin or your MySQL client
   - Create a new database called `comii_lex`
   - Import `database/comii_lex.sql`
   ```bash
   mysql -u root -p comii_lex < database/comii_lex.sql
   ```

3. **Configure database connection**
   - Open `config.php`
   - Update `DB_HOST`, `DB_USER`, `DB_PASS` to match your environment
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');       // your MySQL password
   define('DB_NAME', 'comii_lex');
   ```

4. **Move to web server root**
   - Place the `comii-lex/` folder in your `htdocs/` (XAMPP) or `www/` (WAMP)

5. **Visit in browser**
   ```
   http://localhost/comii-lex/
   ```

---

## 🗄️ Database Schema

### `products` table
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT PK | Product ID |
| name | VARCHAR(255) | Product name |
| price | DECIMAL(10,2) | Product price |
| image | VARCHAR(255) | Image filename (stored in /images/) |
| brand | VARCHAR(100) | Brand name |
| description | TEXT | Full product description |
| created_at | TIMESTAMP | Creation timestamp |

### `cart` table
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT PK | Cart row ID |
| session_id | VARCHAR(128) | PHP session ID |
| product_id | INT FK | References products.id |
| quantity | INT | Item quantity |
| added_at | TIMESTAMP | When added |

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3 (Flexbox + Grid), Vanilla JavaScript |
| Backend | PHP 8.x (procedural) |
| Database | MySQL 8.x |
| Fonts | Syne (display) + DM Sans (body) via Google Fonts |
| Animations | CSS transforms, Intersection Observer API |
| Cart | PHP Sessions + AJAX (Fetch API) |

---

## 🎨 Design Choices

- **Color palette**: Deep navy/charcoal dark theme with indigo (#6366f1) accent
- **Typography**: Syne (geometric display) + DM Sans (humanist sans)
- **3D effects**: CSS perspective + rotateX/rotateY on product cards
- **Glassmorphism**: Backdrop-filter blur on navbar
- **Noise texture**: SVG feTurbulence filter for subtle grain overlay

---

## 👤 Author

**Ravindu Jeewantha**  
📧 [Your Email]  
🌐 [Your Portfolio]  
🐙 [Your GitHub]

---

## 📄 License

MIT License — feel free to use for your portfolio or learning projects.

---

> Built with ❤️ for portfolio and internship applications

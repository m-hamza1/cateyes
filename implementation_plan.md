# Implementation Plan - Admin Enhancements & Variants

## 1. Security & CSRF
- [x] CSRF protection for Admin Login
- [x] CSRF protection for User Login
- [x] CSRF protection for Product Add/Edit
- [ ] CSRF protection for Settings pages
- [ ] CSRF protection for Categories pages
- [ ] CSRF protection for Brands pages

## 2. Reporting & Accuracy
- [x] Add `unit_cost` to `order_items` table
- [x] Update `Order::create` to save `unit_cost`
- [x] Fix column names in `sales-report.php`
- [x] Add Profit and Inventory Value cards to `sales-report.php`

## 3. Product Variants
- [ ] Create `admin/products/attributes.php` for global attribute management
- [ ] Update `admin/products/add.php` and `edit.php` to support variable products
- [ ] Implement variant generation logic in JS
- [ ] Implement backend saving for variants and their attributes

## 4. Admin Roles & Permissions
- [ ] Add `permissions` (text/json) column to `users` table
- [ ] Implement `hasPermission($permission)` helper
- [ ] Add permission checks to admin pages

## 5. Bulk Marketing Emails
- [ ] Update `Newsletter` class to use `sendEmail()` (PHPMailer/SMTP)
- [ ] Improve campaign UI (HTML editor)

## 6. UI/UX & RTL
- [x] Dynamic RTL Bootstrap loading
- [x] Fix Font Application issue (wrong settings group)
- [ ] Review checkout/payment flow RTL support

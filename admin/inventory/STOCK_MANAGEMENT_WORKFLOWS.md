# Stock Management — Workflows

## Tabs — Overview

- **Inventory Overview** — Dashboard: product count, warehouses, low stock, recent movements
- **Warehouses** — Add, edit, delete warehouses
- **Purchase Orders** — Purchase stock from suppliers
- **Production Orders** — Add stock via in-house production
- **Stock Transfers** — Move stock between warehouses
- **Supplier Returns** — Return defective or excess stock to suppliers
- **Sales Returns** — Process customer returns and restore stock
- **Stock Adjustments** — Manually add, subtract, or set stock levels
- **Suppliers** — Manage supplier records
- **Stock Movements** — Full history of stock changes (read-only)
- **Inventory Report** — Reports and analytics

---

## 1. Purchase Orders

**PO Create:**
- Select supplier, warehouse, items, and dates
- Order is saved as draft
- No stock change

**Receive (enter quantity per item):**
- Received quantity is added to PO line items
- Warehouse stock increases for each item
- Product total stock is updated
- Movement log entry (purchase)
- If all items received → status "received"; otherwise "partial"

---

## 2. Production Orders

**Production Create:**
- Select product, quantity, warehouse, and date
- Order is saved as pending
- No stock change

**Complete:**
- Product stock increases in the warehouse
- Product total stock is updated
- Movement log entry (production)
- Status set to "completed"
- Completion date and time are recorded

---

## 3. Stock Transfers

**Transfer Create:**
- Select source warehouse, destination warehouse, items, and quantity
- Stock availability is checked in source warehouse (error if insufficient)
- Transfer is saved as pending
- No stock change

**Complete:**
- Stock decreases in source warehouse for each item
- Stock increases in destination warehouse for each item
- Product totals are updated for both warehouses
- Two log entries per item: transfer_out, transfer_in
- Status set to "completed"

---

## 4. Supplier Returns

**Return Create:**
- Select supplier, warehouse, and items (from received POs)
- Return is saved as pending
- No stock change

**Approve:**
- Stock decreases in warehouse for each item
- Product total stock is updated
- Movement log entry (supplier_return)
- Status set to "processed"

---

## 5. Sales Returns

**Return Create:**
- Select order (delivered, completed, or shipped only)
- Select return items, quantity, warehouse, and reason
- Return is saved
- Add flow: stock is added immediately and movement log is created
- Approve flow: stock is added when return is approved

**Approve (if not updated on add):**
- Stock increases in warehouse for each returned item
- Product total stock is updated
- Movement log entry (sales_return)
- Status set to "approved"

---

## 6. Stock Adjustments

**Adjustment Create (Add):**
- Select warehouse, items, positive quantity, and reason
- On submit: warehouse stock and product total increase, movement log is created

**Adjustment Create (Subtract):**
- Select warehouse, items, negative quantity, and reason
- On submit: warehouse stock and product total decrease, movement log is created

**Adjustment Create (Set):**
- Select warehouse, items, new absolute quantity, and reason
- On submit: warehouse stock is set to the new value, product total is updated, movement log is created

---

## 7. Sales (Customer Orders)

**Order Place:**
- Order is created from cart
- No stock change

**Order Deliver / Ship:**
- Stock decreases in warehouse for each order item
- Product total stock is updated
- Movement log entry (sale)

---

## Effect of Every Stock Change

- Warehouse stock for that product is updated
- Product total stock (sum across all warehouses) is updated
- Variant stock is updated (if applicable)
- Movement log entry is created — type, quantity, user, date

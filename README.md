# Elite Trading ERP - Detailed Technical Documentation

Welcome to the detailed documentation for the Elite Trading ERP system. This document dives deep into the logic, formulas, workflows, and database concepts that power the application. 

---

## 1. Core Concepts & System Architecture

### The Inventory Ledger Principle
The backbone of this ERP is a robust, double-entry style **Inventory Ledger**. Rather than simply updating a stock number up or down when a sale or purchase happens, the system records an immutable transaction.
* **The `products` table** acts as a quick-reference for the current stock counters:
  - `available_stock`: Physical items currently in the warehouse, ready to be sold.
  - `reserved_stock`: Items that have been confirmed for a customer but not yet shipped.
* **The `inventory_ledgers` table** records the history. Every time stock changes, an entry is created detailing:
  - `type` (IN, OUT, PENDING)
  - `quantity` (How much moved)
  - `balance_after` (The running total after the transaction)
  - `reference_id` & `reference_type` (Which specific GRN, Invoice, or Dispatch caused this movement).

---

## 2. Sales & Quotation Lifecycle

### Quotation Management & Approval Workflow
The system enforces strict pricing control through an approval mechanism.
1. **Creation:** A sales representative (non-admin) creates a quotation for a customer.
2. **Approval Hold:** The system flags this quotation as **"Pending Approval"**.
3. **Notification:** An alert is fired to users with the `Admin` or `Super Admin` role.
4. **Action:** The Admin reviews the quotation (checking discounts, LP prices, etc.) from the **Pending Approvals** dashboard. They can Approve or Reject.
5. **Conversion:** Only *Approved* quotations can be converted into Sales Orders.

### Sales Order to Dispatch
1. **Sales Order:** Confirms the customer's intent to buy. At this stage, stock may be moved from `available_stock` to `reserved_stock`.
2. **Dispatch (Delivery Note):** When the warehouse physically ships the items. This is the exact moment when stock is fully deducted (an **OUT** entry is made in the ledger).
3. **Invoice:** The final billing document generated against the dispatch.

---

## 3. Procurement (Purchase Management)

### The Procurement Lifecycle
1. **Vendor Selection:** Vendors are managed in the **Vendor Mgmt** module, tracking their contact info and standard lead times.
2. **Purchase Order (PO):** A document sent to the vendor requesting material. POs do *not* increase stock.
3. **Goods Receipt Note (GRN):** When the truck arrives and material is unloaded, a GRN is created against the PO. 
   - **Logic:** Processing a GRN instantly increments the `available_stock` in the `products` table and writes an **IN** transaction to the `inventory_ledgers`.

---

## 4. Advanced Analytics & Reporting Engine

The ERP provides several deeply calculated reports to aid in business intelligence.

### A. Smart Reorder System
Prevents stock-outs by analyzing historical sales data to predict future needs.
* **Calculation Window:** The last 90 days.
* **Average Daily Consumption (ADC):** Calculates total `OUT` ledger movements over 90 days, divided by 90.
* **Average Yearly Consumption:** `ADC * 365`.
* **Dynamic Reorder Level (DRL):** `ADC * Lead Time (Days)`. (The system calculates exactly how much stock you need to survive while waiting for the vendor to deliver).
* **Status Flags:**
  - **Sufficient:** `Available Stock > DRL`.
  - **Upcoming:** `Available Stock < DRL` (Warning: Order soon).
  - **Ordered:** A PO already exists for this item (No action needed).

### B. Dead Stock Analysis
Identifies capital tied up in slow-moving inventory to help liquidate it.
* **Core Logic:** The system queries the `inventory_ledgers` for the most recent transaction date of every product.
* **Criteria:** If the difference between `Today` and the `Last Transaction Date` is **≥ 90 days**, the item is classified as Dead Stock.
* **Calculated Metrics:** 
  - **Frozen Capital:** `(Available Stock + Reserved Stock) * LP Price`.
  - **Target Leads:** The system queries the `SaleItem` histories to dynamically fetch the names of **Previous Customers** who have historically bought this item.

### C. Customer Profitability Report
Analyzes the true margin of a customer, factoring in discounts and actual purchase costs.
* **Estimated COGS (Cost of Goods Sold):** The system calculates the `Average Purchase Rate` of an item by averaging all historical `Approved/Received` Purchase Orders for that item.
* **Net Revenue:** Total value of invoices paid by the customer.
* **Gross Profit:** `Net Revenue - Estimated COGS`.
* **Margin %:** `(Gross Profit / Net Revenue) * 100`.

### D. Vendor Analysis
Aggregates vendor performance to assist in negotiation.
* **Metrics:** 
  - Total POs issued to the vendor.
  - Total volume (Quantity of items supplied).
  - Total Spend (Total ₹ value of all POs).

---

## 5. Audit & Security Modules

### LP Price Tracking (LP History)
The List Price (`lp_price`) of products is strictly monitored for accountability.
* **Trigger:** When a product is updated in the `ProductController`, the new `lp_price` is compared against the database.
* **Logging:** If a change is detected, a record is pushed to the `lp_histories` table containing:
  - `old_price`
  - `new_price`
  - `user_id` (Who made the change)
  - `created_at` (Timestamp)
* **Visibility:** Displayed in a dedicated "LP History" tab on the individual Product Details page.

### Global Audit Logs
Every major action (Create, Update, Delete) across critical models (Products, Quotations, Sales, Purchases) is recorded.
* The **Audit Logs** dashboard displays a chronological feed of who did what, and when.
* Includes a "View Details" modal to inspect the exact `old_values` and `new_values` (JSON payload) of an update operation.

### User Role Management & Permissions
* Uses robust role-based access control (RBAC). 
* **Super Admin / Admin:** Have full visibility and approval rights.
* **Custom Roles:** E.g., Users without "View Profit" permissions will not be able to see the Customer Profitability reports or COGS data.
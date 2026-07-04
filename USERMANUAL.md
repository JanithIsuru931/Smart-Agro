# Smart Agro User Manual

## 1. Introduction
Smart Agro is a modern, responsive e-commerce platform designed to sell premium Sri Lankan King Coconuts both locally and for international export. This user manual provides a step-by-step guide for customers and administrators to navigate and use the system effectively.

## 2. System Requirements
- **Device:** Desktop, Laptop, Tablet, or Smartphone
- **Browser:** Latest versions of Chrome, Firefox, Safari, or Edge
- **Internet:** Active internet connection
- **OS:** Compatible with Windows, macOS, Linux, iOS, Android

## 3. User Roles

- **Customer (Guest User)**
  Customers do not need to register or create an account to purchase products. The platform is designed for a seamless, guest-only checkout experience to reduce friction.
  *Available Features:* View Products, Add to Cart, Secure Checkout (Cash on Delivery & PayHere online payments), Submit Bulk Export Inquiries.

- **Admin User**
  Admin users are authenticated staff members with elevated privileges to manage the store and internal business operations. Their access is restricted to a secure admin dashboard.
  *Available Features:* Product Management, Local Order Management, Bulk Inquiry Management, Supplier & Purchase Tracking, Employee & Payroll Management, Dashboard Analytics (Net Revenue Tracking).

---

## 4. Customer Guidance

To start shopping, open a web browser and navigate to the official Smart Agro URL. You will land on the homepage featuring our premium king coconut products and bulk export options.

### 4.1. How to Place a Local Order
1. Navigate to the **Shop** section on the homepage to view available products.
2. Click the **Add to Cart** button on any product you wish to purchase.
3. You can view your cart by clicking the **Cart icon** in the top navigation bar. Inside the cart, you can adjust quantities or remove items.
4. Once ready, click the **Proceed to Checkout** button.
5. On the checkout page, provide your delivery details:
   - Full Name
   - Phone Number
   - Delivery Address
   - Optional Order Notes
6. Select your preferred **Payment Method**:
   - **Cash on Delivery (COD):** Pay when your order arrives.
   - **Pay Online (Card / Bank):** Secure online payment via the PayHere payment gateway.
7. Click the **Place Order** button to confirm. If you chose PayHere, you will be securely redirected to complete the payment.
8. Once completed, you will land on an **Order Success** page displaying your reference number.

### 4.2. How to Submit a Bulk Export Inquiry
If you are an international buyer looking to import king coconuts (minimum order > 200 units):
1. Navigate to the **Bulk Orders** section via the top navigation bar.
2. Fill out the Inquiry Details form, including:
   - Your Name & Company
   - Email & Country
   - Requested Quantity & Shipping Port
3. Click **Submit Inquiry**. 
4. You will receive a reference number upon successful submission. The Smart Agro team will review the request and contact you via email within 24 hours with FOB/CIF pricing and shipping details.

---

## 5. Admin User Guidance

To access the backend, navigate to the `/login` route and enter your administrator credentials. Once logged in, you will be directed to the Admin Dashboard.

### 5.1. Dashboard Overview
The dashboard gives you a high-level overview of the business performance. It includes dynamic charts tracking weekly and monthly **Net Revenue** (calculated by subtracting supplier purchase costs from confirmed sales).

### 5.2. Product & Order Management
- **Products:** Navigate to the Products section to add, edit, or remove king coconut variants available on the storefront.
- **Local Orders:** View all incoming local orders. You can see the customer's delivery details, payment status (Pending/Paid), and update the fulfillment status (e.g., Pending, Processing, Completed).
- **Bulk Inquiries:** Review messages and requests submitted by international buyers. Use this section to track which buyers you have responded to.

### 5.3. Internal Operations Management
- **Suppliers & Purchase Log:** Maintain a list of local farmers/suppliers. Log every batch of king coconuts purchased from them to accurately track inventory costs.
- **Employees & Attendance:** Manage your workforce. Track daily attendance and calculate pending salaries based on their daily/half-day rates.
- **Salary Log:** Record payments made to employees to ensure accurate bookkeeping.

---

## 6. For Developers & IT Staff

### 6.1. Project Installation & Setup

**Prerequisites:** PHP 8.2+, Composer, Node.js, and a MySQL/SQLite database.

**Backend & Frontend Setup:**
1. Clone the repository from GitHub:
   ```bash
   git clone <repository-url>
   cd SmartAgro
   ```
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Install JavaScript dependencies and build assets:
   ```bash
   npm install
   npm run build
   ```
4. Environment Setup:
   - Copy `.env.example` to `.env`
   - Generate application key: `php artisan key:generate`
   - Configure database credentials in the `.env` file.
   - Configure PayHere sandbox credentials in `.env` (`PAYHERE_MERCHANT_ID`, `PAYHERE_MERCHANT_SECRET`).
5. Run Database Migrations & Seeders (This will create the default Admin account):
   ```bash
   php artisan migrate --seed
   ```
6. Start the local development server:
   ```bash
   php artisan serve
   ```

---

## 7. FAQs

**Question: Do customers need to create an account to buy?**
Answer: No. To make the purchasing process as fast as possible, we only offer Guest Checkout. Users simply provide their delivery details at checkout.

**Question: Which payment methods are supported?**
Answer: We support Cash on Delivery (COD) and secure online payments (Visa, Mastercard, Bank Transfer) processed through the PayHere gateway.

**Question: What is the minimum quantity for bulk export?**
Answer: Bulk export inquiries require a minimum order of 200 units.

## 8. Troubleshooting

- **Issue:** Payment failed during PayHere checkout.
  **Solution:** Ensure the card details are correct. If testing in a local environment, ensure the `PAYHERE_SANDBOX` variable is set to `true` in your `.env` file and you are using PayHere test card details.
- **Issue:** Product images are not loading.
  **Solution:** Ensure the storage link has been created by running `php artisan storage:link` in the server terminal.

## 9. Contact Information

For technical support or inquiries regarding the Smart Agro platform:
- **Email:** smartagro2025@gmail.com
- **Phone:** 0715795206


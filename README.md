# Laravel Travel Invoicing

A unified, modern travel billing and commercial proposal package for Laravel combining **Quotes / Commercial Proposals** and **Invoices / Tax Bills**, with deposit milestone schedules, PDF rendering with dynamic QR code verification, and secure client self-service portals.

---

## 🌟 Key Features

* **Concurrency-Safe Sequential Numbering**: Dedicated locked counter table generating audit-ready gap-free sequences (`QT-2026-0001`, `INV-2026-0001`).
* **Quote $\rightarrow$ Invoice 1-Click State Machine**: Converts approved proposals directly into official tax invoices with agreed pricing.
* **Trek Deposit & Milestone Schedules**: Upfront deposit (e.g. 20%) vs. final balance tracking with automatic status transitions (`Draft`, `Issued`, `PartiallyPaid`, `Paid`, `Overdue`, `Void`).
* **Integer Cents Precision**: High-precision financial calculations across multiple currencies (`USD`, `NPR`, `EUR`, `GBP`, `AUD`, `CAD`).
* **Public Client Self-Service Portal**: High-entropy token URL (`/quotes/{token}`, `/invoices/{token}`) for review, digital acceptance, and online payment.
* **Branded PDF Generation**: Clean Blade templates with dynamic QR codes for authentic verification.
* **Cross-Package Compatibility**: Native integration with `laravel-travel-customers`, `laravel-media-manager`, `laravel-user-management`, and `laravel-payment-gateway`.

---

## 🚀 Installation

```bash
composer require kreetancraft/laravel-travel-invoicing
php artisan migrate
```

Publish configuration:

```bash
php artisan vendor:publish --tag=travel-invoicing-config
```

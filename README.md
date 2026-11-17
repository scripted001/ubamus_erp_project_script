# Ubamus ERP
### Integrated Business Management & Operations Platform

**One Platform. Multiple Operations. A More Connected Business.**

Ubamus ERP is an integrated business management platform developed to bring essential business operations into a unified digital environment.

Built with PHP and MySQL, the application connects multiple aspects of business management, including sales, inventory, production, finance, human resources, customer relationships, workforce operations and customer-facing services.

Rather than treating business departments as isolated systems, Ubamus brings their workflows and operational records together within a shared application.

And here's the interesting part: this wasn't a team project.

**One developer. Approximately one year. An entire ERP platform.**

Apparently, my GitHub journey is just beginning, but my decision to start small didn't quite go according to plan.

---

## Table of Contents

- [Project Overview](#project-overview)
- [The Story Behind Ubamus](#the-story-behind-ubamus)
- [Core Features](#core-features)
- [System Architecture](#system-architecture)
- [Technology Stack](#technology-stack)
- [Technical Highlights](#technical-highlights)
- [Project Structure](#project-structure)
- [Screenshots](#screenshots)
- [Installation and Setup](#installation-and-setup)
- [Security Considerations](#security-considerations)
- [Project Status](#project-status)
- [Copyright and Usage](#copyright-and-usage)
- [About the Developer](#about-the-developer)

---

## Project Overview

Ubamus ERP is designed to support businesses in managing their day-to-day activities through interconnected operational modules.

Its capabilities extend across:

- Business administration and company management.
- Sales processing and revenue monitoring.
- Inventory and warehouse operations.
- Production management and tracking.
- Financial records and expense management.
- Human resource and workforce administration.
- Customer relationship management.
- Internal business communication.
- Customer-facing product and service marketplace.

The application also incorporates company-level and location-level data organization, allowing business operations to be structured around different organizational units.

## The Story Behind Ubamus

### A year of development. One developer. No development team.

Ubamus was not developed by a large engineering department or a team of programmers dividing responsibilities across multiple departments.

It was independently designed, developed and integrated by one developer over approximately one year.

From database interactions and backend development to interface implementation, business workflows, AJAX operations and module integration, I worked across the different layers required to bring the application together.

The development journey involved understanding business requirements, translating operational processes into software logic, building individual modules and making those modules work together.

It wasn't always straightforward.

There were bugs to investigate, database relationships to reconsider, interfaces to improve and occasionally, the kind of errors that make you question your career choices.

But the work continued.

Ubamus represents more than a collection of PHP scripts. It represents a year of independent software development, problem-solving, experimentation and continuous improvement.

This repository is also part of my decision to begin documenting and sharing my development journey publicly.

So, while my GitHub profile may be relatively young, some of the projects arriving here have already had quite a long journey.

---

## Core Features

### 1. Sales & Revenue Management

Tools for recording, monitoring and analysing business sales activities.

- Sales creation and processing.
- Invoice generation and viewing.
- Payment and transaction records.
- Customer debt tracking.
- Partial and full debt payments.
- Daily, weekly, monthly and annual sales reporting.
- Sales search and filtering.
- Revenue dashboards and analytics.

### 2. Inventory & Warehouse Management

Inventory functionality covering stock records and movement throughout business operations.

- Product and category management.
- Stock creation and restocking.
- Stock assignments.
- Stock transfers between locations.
- Stock movement history.
- Stock usage tracking.
- Stock recovery and archiving.
- Warehouse management.
- Product units and unit configurations.
- Barcode generation and printing.
- Product image management.

### 3. Production Management

A dedicated production management area for organizing and monitoring production activities.

- Production creation and management.
- Production stage configuration.
- Production team management.
- Production records and updates.
- Raw material recording.
- Production-related service management.
- Production overview and statistics.
- Production search and tracking.

### 4. Financial Operations

Tools for recording and monitoring selected financial activities.

- Expense recording.
- Expense approval workflows.
- Expense tracking and searching.
- Transaction records.
- Customer debt management.
- Partial debt settlement.
- Payment tracking.
- Financial activity monitoring.

### 5. Human Resource Management

Workforce administration and employee management functionality.

- Staff registration.
- Staff profile management.
- Staff activation and suspension.
- Team management.
- Staff awards and recognition.
- Staff-related administrative operations.
- Role-oriented application workspaces.

### 6. Customer Relationship Management

Customer-related tools designed to support business relationships and service delivery.

- Customer registration and management.
- Customer records.
- Customer care management.
- Customer reminders.
- Customer awards and recognition.
- Customer debt monitoring.
- Customer search and records management.

### 7. Business & Organizational Management

Organizational administration features that help structure business operations.

- Company management.
- Branch management.
- Location management.
- Warehouse administration.
- Supplier management.
- Vendor registration and management.
- Team administration.
- Company information and settings.
- Terms and privacy policy management.

### 8. Internal Communication

Communication functionality integrated into the business application.

- Direct messaging.
- Group conversations.
- Staff communication.
- Manager-related communication.
- Customer care conversations.

### 9. Customer Marketplace

One of the customer-facing components of Ubamus is its integrated product and service marketplace.

It extends the ERP beyond internal business administration by providing customers with a separate digital interaction environment.

Its implemented functionality includes:

- Public product and service catalogues.
- Company-scoped marketplace records.
- Customer registration.
- Email-based OTP verification.
- Customer authentication.
- Session-based shopping cart.
- Cart persistence and restoration after authentication.
- Product purchasing workflows.
- Service requests with additional customer requirements.
- Customer purchase history.
- Customer-to-business messaging.
- Customer dashboard.

The marketplace uses the existing ERP product and service records rather than maintaining an entirely independent catalogue.

---

## System Architecture

Ubamus is organized into multiple application areas serving different operational responsibilities.

The supplied project structure includes:

| Directory | Application area |
|---|---|
| `admin/` | Administrative workspace |
| `hoa/` | Separate administrative workspace |
| `operator/` | Operator workspace |
| `production/` | Production workspace |
| `staff/` | Staff workspace |
| `company/` | Company administration |
| `client/` | Client-facing workspace |
| `terminal/` | Authentication and account recovery workflows |

The application also contains shared configuration, customer marketplace functionality, supporting controllers, views and third-party resources.

### Multi-company and multi-location organization

An important aspect of the application's data organization is its use of company, location and user identifiers.

These identifiers are incorporated into application operations to associate records with their relevant business context.

This provides a foundation for organizing business activities across companies, locations and users.

---

## Technology Stack

The application combines backend development, database management and frontend technologies.

| Technology | Purpose |
|---|---|
| PHP | Backend application development |
| MySQL | Relational database management |
| PDO | Database connectivity and prepared queries |
| JavaScript | Frontend interactivity |
| AJAX | Asynchronous application requests |
| HTML5 | Application structure |
| CSS3 | Interface styling |
| Bootstrap | Frontend interface components |
| jQuery | JavaScript utilities and interactions |
| Chart.js | Data visualization |
| Font Awesome | Interface icons |

### Supporting Libraries

The project also includes supporting resources for:

- PHPMailer for email communication.
- QR code generation.
- Barcode generation.
- Additional frontend and interface utilities.

---

## Technical Highlights

Some of the engineering areas represented in the application include:

- PHP-based server-side processing.
- PDO database interactions.
- Prepared database queries.
- AJAX-powered search and data retrieval.
- Modular application directories.
- Company and location-aware data operations.
- Role-oriented application workspaces.
- Dynamic sales reporting.
- Inventory movement and transfer workflows.
- Customer authentication and OTP verification.
- Session-based marketplace cart management.
- Integrated internal and customer messaging.

These are among the areas that made developing and integrating Ubamus an extensive undertaking.

---

## Project Structure

A simplified representation of the repository:

```text
ubamus-erp/
│
├── admin/
├── hoa/
├── operator/
├── production/
├── staff/
│
├── company/
├── client/
├── terminal/
│
├── config/
│   ├── db.php
│   ├── smtp.php
│   └── validation.php
│
├── controllers/
├── views/
│
├── customer_auth.php
├── customer_dashboard.php
├── customer_area_common.php
├── customer_verify_otp.php
├── marketplace.php
│
├── uploads/
├── invoices/
├── barcodes/
├── production_reports/
│
├── vendor/
├── PHPMailer/
├── barcode/
├── qrcode-generator-master/
│
├── index.php
├── company_data.php
├── database structures.php
└── README.md
```

*This is a simplified representation. The full application contains additional scripts, assets, libraries and resources.*

---

## Screenshots

Visual documentation of the application will be added here.

Planned showcase areas:

- Administrative dashboard.
- Sales and revenue analytics.
- Inventory management.
- Production management.
- Company administration.
- Staff workspace.
- Customer marketplace.
- Customer dashboard.

Screenshots will be added after sensitive business information and customer records have been removed or anonymized.

---

## Installation and Setup

Ubamus is a PHP/MySQL application.

A verified, clean installation package and complete database setup guide are not yet included in this public showcase.

Before attempting deployment, the application requires:

- A compatible PHP hosting environment.
- MySQL database access.
- PHP PDO MySQL support.
- Appropriate PHP extensions.
- Database configuration.
- Email/SMTP configuration for email-dependent functionality.
- Proper directory and file permissions.

**Important:** The supplied development archive is not yet a verified plug-and-play distribution. Database schema preparation, environment configuration and installation documentation need to be completed before independent deployment.

---

## Security Considerations

Security and data protection are important considerations when preparing this project for public distribution.

The public repository should not contain:

- Database passwords or connection credentials.
- SMTP passwords or API keys.
- Private business records.
- Customer information.
- Production database backups.
- Confidential company documents.
- Private uploaded files.

Sensitive configuration should be supplied through appropriate environment-specific configuration rather than publicly committed credentials.

---

## Project Status

Ubamus represents an independently developed ERP application with multiple implemented business management and customer-facing components.

Development, refinement, documentation and deployment preparation remain ongoing activities.

This repository is intended to document the project and demonstrate its engineering scope.

---

## Copyright and Usage

Ubamus ERP is presented publicly for portfolio demonstration, professional evaluation and technical review.

**All rights reserved by the rightful copyright holder.**

No open-source licence is granted through this repository.

Public visibility does not constitute permission to commercially exploit, redistribute, modify or create derivative products from the project's source code without prior written authorization.

Third-party libraries and components remain subject to their respective licences.

---

## About the Developer

I'm an independent software developer and business developer interested in building practical technology solutions around real business problems.

My approach to software development extends beyond writing code. I enjoy understanding operational challenges, identifying opportunities, designing solutions and translating business requirements into functional applications.

Ubamus is one of the projects through which I've explored that intersection of business operations and software engineering.

This repository marks the beginning of my public GitHub journey, where I intend to document my work, share my development experiences and continue growing as a software engineer.

**One developer. One year. One rather ambitious project.**

And apparently, this is only the beginning.

---

*Thank you for taking the time to explore Ubamus ERP.*

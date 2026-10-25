# AutoLux — PHP Backend Interface

**Author:** Diana Simões

## Description

The AutoLux PHP Backend Interface is a web application for managing an automotive parts warehouse. It allows users to browse automotive parts, register sales, manage stock and interact with suppliers and supplier orders through a Node.js REST API.

This project is part of the AutoLux application and works together with a separate Node.js backend responsible for supplier and supplier order management.

## Technologies

- PHP
- MySQL
- HTML and CSS
- JavaScript
- XAMPP
- Node.js REST API

## Features

- Browse the automotive parts catalogue.
- Filter parts by brand, type and price range.
- Register sales by selecting a customer, parts, quantities and payment method.
- Validate available stock and update stock levels after sales.
- List suppliers through the Node.js REST API.
- View supplier orders.
- Create supplier orders containing multiple parts.
- Select parts for supplier orders and calculate the order total.

## Requirements

- XAMPP, including Apache, PHP and MySQL.
- Node.js and npm.
- Git, to clone the repositories.
- A web browser.

## Installation and Execution

### 1. Start XAMPP

1. Open the XAMPP Control Panel.
2. Start **Apache** and **MySQL**.
3. Wait until both services are running.

### 2. Set up the PHP project

Clone the repository using the following command:

```bash
git clone https://github.com/abiliaframboesa/autolux-backend-interface-php.git autolux-interface
```

This creates a folder named `autolux-interface`.

Place the project folder inside the XAMPP `htdocs` directory if it is not already there.

### 3. Set up the PHP database

1. Open phpMyAdmin in your browser: `http://localhost/phpmyadmin/`.
2. Import the SQL scripts from the project's `sql/` directory in the following order:

   - `01_criar_base_dados.sql` — creates the `autolux_vendas` database and its tables for customers, parts, sales and sale items.
   - `03_inserir_dados_demo_vendas.sql` — inserts sample customers and parts.

3. Check the database connection settings in `config/database.php` and adjust them to match your MySQL configuration.

### 4. Set up the Node.js API

The supplier management functionality requires the separate Node.js API.

1. Clone the [AutoLux Node.js API repository](https://github.com/abiliaframboesa/autolux-backend-fornecedores-node).
2. Follow the instructions in its README to install the dependencies and configure the `.env` file.
3. Make sure the `autolux_fornecedores` database is created and populated using the SQL scripts provided in that repository.
4. Start the API from its project directory:

   ```bash
   npm start
   ```

5. Keep the API running while using the supplier-related features.

The API runs at `http://localhost:3000` by default.

### 5. Open the application

Open the following address in your browser:

`http://localhost/autolux-interface/`

If the project folder has a different name inside `htdocs`, replace `autolux-interface` in the URL with the actual folder name.

## Database Information

The application uses two separate MySQL databases:

- **`autolux_vendas`** — stores customers, parts, sales and sale items, managed by the PHP application.
- **`autolux_fornecedores`** — stores suppliers, supplier orders and order items, managed by the Node.js API.

The supplier-related features require the Node.js API and its database to be available.

## API Integration

The PHP interface communicates with the Node.js REST API at `http://localhost:3000` to retrieve supplier information and manage supplier orders.

Apache serves the PHP application, Node.js runs the API, and MySQL stores the application data.

## Repositories

- **PHP interface:** https://github.com/abiliaframboesa/autolux-backend-interface-php
- **Node.js API:** https://github.com/abiliaframboesa/autolux-backend-fornecedores-node

## Author

Diana Simões
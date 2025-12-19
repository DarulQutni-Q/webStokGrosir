Simple PHP inventory app.

Usage:
- Run with PHP built-in server: php -S localhost:8000
- Visit /index.php
- To enable MySQL (visible in phpMyAdmin), edit config.php and set 'use_mysql' => true, fill host/db/user/pass, then visit phpMyAdmin to view the database named in config.php

Files:
- config.php MySQL configuration (optional)
- db.php opens database (MySQL if enabled, otherwise SQLite data.db) and creates tables
- index.php dashboard
- items.php manage items (CRUD)
- stock_in.php record incoming stock
- stock_out.php record outgoing stock
- reports.php show reports and export CSV with .xlsx extension (openable in Excel)

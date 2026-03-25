# Creative Bootstrap Registration & Admin Panel (Dockerized)

## How to Run

1. **Clone this repo and `cd` into the folder.**
2. **Start the stack:**

   ```bash
   docker-compose up --build
   ```

3. **Initialize the database:**

   ```bash
   docker exec -i $(docker-compose ps -q db) mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < init.sql
   ```

4. **Open your browser:**

   - Registration Form: [http://localhost:8080/index.php](http://localhost:8080/index.php)
   - Admin Panel: [http://localhost:8080/admin_access_7f3k.php](http://localhost:8080/admin_access_7f3k.php)
     - Default admin login:
       **Username:** `admin`
       **Password:** `admin123`

## Features

- Mobile responsive Bootstrap registration form with photo upload & preview
- Admin login/logout
- Admin dashboard: view, update status, add comments to registrations
- Dashboard stats
- Creative, colorful, and modern UI

---

## Hostinger Deployment (No Docker)

1. Upload project files to `public_html` (or your domain document root).
2. Create MySQL database and user in Hostinger hPanel.
3. Edit `.env` in project root with real values:

   ```env
   ENVIRONMENT=production
   DB_HOST=localhost
   DB_NAME=your_db_name
   DB_USER=your_db_user
   DB_PASSWORD=your_db_password
   MYSQL_ROOT_PASSWORD=unused_on_hostinger
   ADMIN_LOGIN_PATH=admin_access_7f3k.php
   APP_SECRET=use_a_long_random_secret_here
   TELEGRAM_BOT_TOKEN=your_bot_token
   TELEGRAM_CHAT_ID=your_chat_id
   ```

4. Import `init.sql` into your Hostinger database from phpMyAdmin.
5. Ensure `uploads/` exists and is writable by PHP (`755` or `775` depending on host setup).
6. Login URL for admin is:
   - `https://your-domain.com/admin_access_7f3k.php`

If `curl` extension is disabled on hosting, Telegram notifications still work via `file_get_contents` fallback.

---

## To Zip the Project

- Place all files in your project folder.
- Use your OS zip tool or run:

  ```bash
  zip -r registration_project.zip .
  ```

  (from the parent directory)

---

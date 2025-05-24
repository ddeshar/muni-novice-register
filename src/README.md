# Creative Bootstrap Registration & Admin Panel (Dockerized)

## How to Run

1. **Clone this repo and `cd` into the folder.**
2. **Start the stack:**
   ```
   docker-compose up --build
   ```
3. **Initialize the database:**
   ```
   docker exec -i $(docker-compose ps -q db) mysql -ureguser -pregpass registration < src/init.sql
   ```
4. **Open your browser:**
   - Registration Form: [http://localhost:8080/index.php](http://localhost:8080/index.php)
   - Admin Panel: [http://localhost:8080/login.php](http://localhost:8080/login.php)
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

## To Zip the Project

- Place all files in your project folder (`src` and top-level Docker files).
- Use your OS zip tool or run:
  ```
  zip -r registration_project.zip .
  ```
  (from the parent directory)

---
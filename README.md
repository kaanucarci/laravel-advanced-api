# 🚀 Laravel Advanced API Project

This is a modern, scalable RESTful API built using **Laravel 12**, designed as a robust backend foundation for web and mobile applications. It includes secure authentication, Redis caching, Laravel Octane with RoadRunner for blazing-fast performance, and automated testing for code reliability.

---

## 📦 Features

- ✅ **User Authentication**
    - Powered by **Laravel Sanctum**
    - Token-based login, registration, logout, and profile endpoints

- 🛡 **Route Protection**
    - Middleware-based access control using `auth:sanctum`
    - Role-based permission system (Admin/User)

- ⚡ **Redis Integration**
    - Caching and session management
    - Improves performance on frequently accessed data

- 🔥 **High Performance with Laravel Octane**
    - Server powered by **RoadRunner**
    - Handles concurrent requests at scale with low latency

- 📄 **Swagger Documentation**
    - Full OpenAPI support using L5 Swagger
    - Self-documented REST endpoints

- 🧪 **Testing**
    - Unit and feature tests via **PHPUnit**
    - Authentication and cart logic fully covered

- 🔐 **Validation & Error Handling**
    - Request validation using `FormRequest`
    - Consistent and structured error responses

---

## 🧪 Benchmark Result (Octane + RoadRunner)

Tested with the command:

```bash
ab -n 1000 -c 50 http://127.0.0.1:8000/api/products
```
📈**Results:**
- ✅ 0 Failed Requests

- 🚀 1754.70 Requests/Second

- ⚡ Average Response Time: 28ms

- 🔁 1000 Total Requests with 50 Concurrent Connections

Octane + RoadRunner significantly boosts Laravel performance under load.


## 🛠 Technologies

| Tool         | Purpose                      |
| ------------ | ---------------------------- |
| Laravel 12   | PHP Framework                |
| PHP 8.3      | Language Runtime             |
| Sanctum      | API Authentication           |
| Redis        | Cache, Session, Queue Driver |
| MySQL        | Database                     |
| Octane       | High-performance HTTP server |
| RoadRunner   | Octane Server Backend        |
| PHPUnit      | Automated Testing            |
| Postman      | API Testing                  |
| Swagger (L5) | API Documentation (OpenAPI)  |


---

## 📂 Installation

```bash
git clone https://github.com/kaanucarci/laravel-advanced-api.git
cd laravel-advanced-api
```

## 🚀 Running with Octane + RoadRunner

Install necessary packages:


```bash
composer require laravel/octane spiral/roadrunner nyholm/psr7
php artisan octane:install
./vendor/bin/rr get
```
Start the application with RoadRunner:

```bash
php artisan octane:start --server=roadrunner
```
You should see:

```bash
INFO  Server running…
Local: http://127.0.0.1:8000
```

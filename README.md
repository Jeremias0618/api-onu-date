# Project: ONU Data Query API

This project is a PHP-based endpoint that exposes a REST API to retrieve the most recent entries from the `fiberprodata` database. It fetches the latest 10 records from the `onu_datos` table and returns them in JSON format, allowing easy integration with monitoring systems, dashboards, or other tools.

## 📌 Features

* Secure MySQL database connection using PDO.
* JSON-formatted output.
* Results ordered by descending date.
* Returns the 10 most recent entries.
* Ready for integration with frontends or external services.

## ⚙️ Requirements

* PHP 7.2 or higher.
* PDO extension for MySQL enabled.
* Web server (Apache, Nginx, etc.).
* Access to the production MySQL database.

## 📁 Script Structure

```php
// PDO database connection
// Query latest 10 records
// Error handling with HTTP status and JSON response
```

## 🔒 Security

* Proper HTTP status error handling.
* Secure connection using PDO exception handling.
* No internal error details exposed in production.

## 🧪 Example Response

```json
{
  "status": "success",
  "count": 10,
  "data": [
    {
      "id": 1,
      "snmpindexonu": "...",
      "fecha": "2025-06-10 12:34:56",
      ...
    }
  ]
}
```

## 🚀 Deployment

1. Copy the PHP file to your web server.
2. Configure database credentials.
3. Access the endpoint via HTTP/HTTPS.

## 🧩 Possible Extensions

* Token-based authentication.
* Dynamic filters (by date, serial, host).
* Result pagination.
* Audit logging for traceability.


### 🏷️ Tags

```
php
rest-api
mysql
pdo
json
network-monitoring
api-endpoint
fiber-network
telecom
backend
data-query
snmp
dashboard-integration

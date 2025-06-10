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
      "id": "48156200",
      "snmpindexonu": "4194340352.14",
      "act_susp": "1",
      "host": "PTP-17",
      "snmpindex": "4194340352",
      "slotportonu": "4/14",
      "onudesc": "74647113",
      "serialonu": null,
      "fecha": "2025-06-09 19:04:42",
      "onulogico": "14"
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

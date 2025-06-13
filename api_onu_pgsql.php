<?php
header('Content-Type: application/json; charset=utf-8');

// API para obtener datos de ONUs desde una base de datos PostgreSQL usando PDO by Yeremi Tantaraico
// Los datos se obtienen de la tabla onu_datos y se ordenan por fecha en orden descendente

// Parámetros de conexión
$host = '10.80.80.106';
$port = 5432;
$dbname = 'fiberprodata';
$user = 'fiberproadmin';
$pass = 'noc12363';

// Crear conexión PDO para PostgreSQL
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    exit;
}

// Consulta: obtener las últimas 100 entradas ordenadas por fecha descendente
$sql = "SELECT id, snmpindexonu, act_susp, host, snmpindex, slotportonu, onudesc, serialonu, fecha, onulogico, plan_onu, last_down_time, modelo_onu
        FROM onu_datos 
        ORDER BY fecha DESC 
        LIMIT 100";

try {
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'count' => count($result),
        'data' => $result
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la consulta a la base de datos: ' . $e->getMessage()]);
}
?>

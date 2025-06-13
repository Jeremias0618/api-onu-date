<?php
header('Content-Type: application/json; charset=utf-8');

//Aoi para obtener datos de ONUs desde una base de datos MySQL usando PDO by Yeremi Tantaraico
//los datos se obtienen de la tabla onu_datos y se ordenan por fecha en orden descendente
// Asegurarse de que el script se ejecute con la codificación UTF-8 
// Datos monstrados en pantalla como JSON

// Parámetros de conexión
$host = '10.80.80.175';
$port = 3306;
$dbname = 'fiberprodata';
$user = 'fiberproadmin';
$pass = 'noc12363';

// Crear conexión PDO
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Consulta: obtener las últimas 10 entradas ordenadas por fecha descendente
$sql = "SELECT id, snmpindexonu, act_susp, host, snmpindex, slotportonu, onudesc, serialonu, fecha, onulogico
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
    echo json_encode(['error' => 'Error en la consulta a la base de datos']);
}
?>

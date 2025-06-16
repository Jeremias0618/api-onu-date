<?php
header('Content-Type: application/json; charset=utf-8');

// Api para obtener total de activos y suspendidos, y por host en todas las OLT 

// Parámetros de conexión PostgreSQL
$host = '10.80.80.106';
$port = 5432;
$dbname = 'fiberprodata';
$user = 'fiberproadmin';
$pass = 'noc12363';

// Lista de hosts permitidos
$hostsPermitidos = [
    'SD-1', 'SD-2', 'SD-3', 'SD-4', 'INC-5', 'SD-7', 'JIC-8', 'JIC2-8', 'NEW_JIC-8',
    'ATE-9', 'SMP-10', 'CAMP-11', 'CAMP2-11', 'PTP-12', 'ANC-13', 'CHO-14',
    'LO-15', 'LO2-15', 'VIR-16', 'PTP-17', 'VENT-18'
];

// Crear conexión PDO para PostgreSQL
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Preparar la lista para la consulta SQL
$placeholders = implode(',', array_fill(0, count($hostsPermitidos), '?'));

// Consulta global: contar cantidad de activos y suspendidos en total SOLO para hosts permitidos
$sqlTotal = "
    SELECT act_susp, COUNT(*) AS cantidad
    FROM onu_datos
    WHERE host IN ($placeholders)
    GROUP BY act_susp
";

// Consulta por host: contar activos y suspendidos agrupados por host SOLO para hosts permitidos
$sqlPorHost = "
    SELECT host, act_susp, COUNT(*) AS cantidad
    FROM onu_datos
    WHERE host IN ($placeholders)
    GROUP BY host, act_susp
";

try {
    // Conteo total
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute($hostsPermitidos);
    $totalResult = [];
    while ($row = $stmtTotal->fetch(PDO::FETCH_ASSOC)) {
        $totalResult[$row['act_susp']] = (int)$row['cantidad'];
    }

    // Conteo por host
    $stmtHost = $pdo->prepare($sqlPorHost);
    $stmtHost->execute($hostsPermitidos);
    $hostRows = $stmtHost->fetchAll(PDO::FETCH_ASSOC);

    // Organizar por host
    $porHost = [];
    foreach ($hostRows as $row) {
        $host = $row['host'];
        $estado = $row['act_susp'] == 1 ? 'activos' : 'suspendidos';
        if (!isset($porHost[$host])) {
            $porHost[$host] = ['activos' => 0, 'suspendidos' => 0];
        }
        $porHost[$host][$estado] = (int)$row['cantidad'];
    }

    // Respuesta final
    echo json_encode([
        'status' => 'success',
        'total' => [
            'activos' => isset($totalResult[1]) ? (int)$totalResult[1] : 0,
            'suspendidos' => isset($totalResult[2]) ? (int)$totalResult[2] : 0
        ],
        'por_host' => $porHost
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la consulta a la base de datos']);
}
?>

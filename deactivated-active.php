<?php
header('Content-Type: application/json; charset=utf-8');

// Api para obtener total de activos y suspendidos, y por host en todas las OLT 

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

// Consulta global: contar cantidad de activos y suspendidos en total
$sqlTotal = "
    SELECT act_susp, COUNT(*) AS cantidad
    FROM onu_datos
    GROUP BY act_susp
";

// Consulta por host: contar activos y suspendidos agrupados por host
$sqlPorHost = "
    SELECT host, act_susp, COUNT(*) AS cantidad
    FROM onu_datos
    GROUP BY host, act_susp
";

try {
    // Conteo total
    $stmtTotal = $pdo->query($sqlTotal);
    $totalResult = $stmtTotal->fetchAll(PDO::FETCH_KEY_PAIR); // [act_susp => cantidad]

    // Conteo por host
    $stmtHost = $pdo->query($sqlPorHost);
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

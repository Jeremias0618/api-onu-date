<?php
function conectarDB() {
    $conexion = new mysqli('10.80.80.175', 'fiberproadmin', 'noc12363', 'fiberprodata', 3306);
    if ($conexion->connect_error) {
        die('Error de conexión: ' . $conexion->connect_error);
    }
    return $conexion;
}

//Api para consultar datos SNMP de una ONU y obtener su estado, potencia de retorno, recepción y plan by Yeremi Tantaraico

function formatearPotencia($tipo, $valor) {
    if (!is_numeric($valor)) return 'Valor inválido';

    $dbm = ($tipo === 'retorno') ? ($valor / 100 - 100) : ($valor / 100);
    $color = 'black';

    if ($dbm >= -17.99) $color = 'red';
    elseif ($dbm >= -24.99 && $dbm <= -18.00) $color = 'green';
    elseif ($dbm >= -27.99 && $dbm <= -25.00) $color = 'orange';
    elseif ($dbm <= -28.00) $color = 'red';

    return "<span style='color:$color'>{$dbm} dBm</span>";
}


function limpiarPlan($cadena) {
    if (preg_match('/(\d+)_Mbps/', $cadena, $match)) {
        return "Internet " . $match[1] . " Mbps";
    }
    return "Plan desconocido";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['snmp'])) {
    $tipo = $_POST['tipo'];
    $ip = $_POST['ip'];
    $index = $_POST['index'];
    $comunidad = "FiberPro2021";

    $oids = [
        'retorno'     => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.6.',
        'recepcion'   => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.4.',
        'desconexion' => '1.3.6.1.4.1.2011.6.128.1.1.2.101.1.7.',
        'estado'      => '1.3.6.1.4.1.2011.6.128.1.1.2.46.1.15.',
        'plan'        => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.7.'
    ];

    if (!isset($oids[$tipo])) {
        echo "❌ OID no válido.";
        exit;
    }

    // --- MODIFICACIÓN PARA ÚLTIMA CONEXIÓN ---
    if ($tipo === 'desconexion') {
        $fecha_valida = '';
        for ($i = 9; $i >= 0; $i--) {
            $oid = $oids[$tipo] . $index . '.' . $i;
            $resultado = @snmpget($ip, $comunidad, $oid);
            if ($resultado !== false && preg_match('/"([^"]+)"/', $resultado, $match) && !empty($match[1])) {
                $fecha_original = str_replace('Z', '', $match[1]);
                $dt = DateTime::createFromFormat('Y-m-d H:i:s', $fecha_original, new DateTimeZone('UTC'));
                if ($dt) {
                    $dt->modify('+8 hours');
                    $fecha_valida = $dt->format('Y-m-d H:i:s');
                } else {
                    $fecha_valida = $fecha_original;
                }
                break;
            }
        }
        if ($fecha_valida) {
            echo "✅ Última conexión: $fecha_valida";
        } else {
            echo "❌ No se pudo obtener la última conexión SNMP.";
        }
        exit;
    }

    // ...resto de tipos...
    $oid = $oids[$tipo] . $index;
    $resultado = @snmpget($ip, $comunidad, $oid);

    if ($resultado === false) {
        echo "❌ No se pudo obtener el dato SNMP.";
        exit;
    }

    if ($tipo === 'retorno' || $tipo === 'recepcion') {
        preg_match('/(-?\d+)/', $resultado, $match);
        $valor = $match[1] ?? 0;
        echo "✅ Potencia ($tipo): " . formatearPotencia($tipo, $valor);
    } elseif ($tipo === 'estado') {
        preg_match('/INTEGER:\s*(\d+)/', $resultado, $match);
        $estado = ($match[1] ?? 0) == 1 ? '✅ Online' : '❌ Offline';
        echo " Estado ONU: $estado";
    } elseif ($tipo === 'plan') {
        preg_match('/STRING:\s*"(.*?)"/', $resultado, $match);
        $plan = limpiarPlan($match[1] ?? '');
        echo " ✅ Plan Actual: $plan";
    } else {
        echo "✅ Resultado SNMP:<br>" . htmlspecialchars($resultado);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta ONU por DNI</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        input[type="text"] { padding: 8px; width: 300px; }
        button { padding: 8px 12px; margin: 5px; }
        .resultado { margin-top: 20px; padding: 10px; border: 1px solid #ccc; }
        .respuesta { margin-top: 10px; background: #f0f0f0; padding: 10px; }
    </style>
</head>
<body>

<h2>Consulta de ONU por DNI o RUC</h2>
<form method="POST">
    <input type="text" name="dni" placeholder="Ingrese DNI o RUC" required>
    <button type="submit">Consultar</button>
</form>

<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['dni'])) {
    $dni = trim($_POST['dni']);
    $conexion = conectarDB();
    $busqueda = $conexion->real_escape_string($dni);
    $query = "SELECT * FROM onu_datos WHERE onudesc LIKE '{$busqueda}%'";
    $res = $conexion->query($query);

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();

        $ips = [
            "SD-1" => "10.20.70.10",
            "SD-2" => "10.30.70.21",
            "SD-3" => "10.20.70.30",
            "SD-4" => "10.20.70.46",
            "INC-5" => "10.5.5.2",
            "SD-7" => "10.20.70.72",
            "JIC2-8" => "172.16.2.2",
            "NEW_JIC-8" => "172.17.2.2",
            "ATE-9" => "172.99.99.2",
            "SMP-10" => "10.170.7.2",
            "CAMP-11" => "10.111.11.2",
            "CAMP2-11" => "10.112.25.2",
            "PTP-12" => "20.20.5.1",
            "ANC-13" => "10.13.13.2",
            "CHO-14" => "172.18.2.2",
            "LO-15" => "10.70.7.2",
            "LO2-15" => "10.70.8.2",
            "VIR-16" => "30.150.130.2",
            "PTP-17" => "10.17.7.2",
            "VENT-18" => "18.18.1.2"
        ];

        $host = $row['host'];
        $ip_host = $ips[$host] ?? '';
        $snmpindex = $row['snmpindexonu'];

        echo "<div class='resultado'>";
        echo "<h3>Datos del Cliente</h3>";
        echo "DNI: {$row['onudesc']}<br>";
        echo "Estado: " . ($row['act_susp'] === '1' ? 'Activo' : 'Suspendido') . "<br>";
        echo "OLT: {$row['host']}<br>";
        echo "PON Lógico: {$row['host']}/{$row['slotportonu']}/{$row['onulogico']}<br>";
        echo "Última conexión: {$row['fecha']}<br>";
        echo "<h4>Consultas SNMP</h4>";

        echo "<button onclick=\"consultarSNMP('retorno', '$ip_host', '$snmpindex')\">Potencia de Retorno</button>";
        echo "<button onclick=\"consultarSNMP('recepcion', '$ip_host', '$snmpindex')\">Potencia de Recepción</button>";
        echo "<button onclick=\"consultarSNMP('desconexion', '$ip_host', '$snmpindex')\">Última Conexión</button>";
        echo "<button onclick=\"consultarSNMP('estado', '$ip_host', '$snmpindex')\">Estado Online/Offline</button>";
        echo "<button onclick=\"consultarSNMP('plan', '$ip_host', '$snmpindex')\">Plan Actual</button>";
        echo "<div id='respuesta' class='respuesta'></div>";
        echo "</div>";
    } else {
        echo "<p>No se encontraron resultados para el DNI/RUC ingresado.</p>";
    }
}
?>

<script>
function consultarSNMP(tipo, ip, index) {
    const respuesta = document.getElementById('respuesta');
    respuesta.innerHTML = '⏳ Consultando...';

    const formData = new FormData();
    formData.append('snmp', '1');
    formData.append('tipo', tipo);
    formData.append('ip', ip);
    formData.append('index', index);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(resp => resp.text())
    .then(data => {
        respuesta.innerHTML = data;
    })
    .catch(err => {
        respuesta.innerHTML = '❌ Error en la consulta SNMP.';
    });
}
</script>

</body>
</html>

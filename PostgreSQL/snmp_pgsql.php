<?php
function conectarDB() {
    $host = '10.80.80.106';
    $port = 5432;
    $dbname = 'fiberprodata';
    $user = 'fiberproadmin';
    $pass = 'noc12363';
    try {
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die('Error de conexión: ' . $e->getMessage());
    }
}

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
    <title>Consulta ONU por DNI/RUC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Google Fonts y Material Icons -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary: #1976d2;
            --primary-dark: #115293;
            --accent: #43a047;
            --danger: #e53935;
            --warning: #fbc02d;
            --bg: #f4f6fb;
            --white: #fff;
            --gray: #757575;
            --border: #e0e0e0;
        }
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background: var(--bg);
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 480px;
            margin: 40px auto;
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 4px 24px #0001;
            padding: 2.5rem 2rem 2rem 2rem;
        }
        h2 {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        form {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            justify-content: center;
        }
        input[type="text"] {
            flex: 1;
            padding: 0.7rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            background: #f9f9f9;
            transition: border 0.2s;
        }
        input[type="text"]:focus {
            border-color: var(--primary);
            outline: none;
        }
        button[type="submit"], .snmp-btn {
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 8px;
            padding: 0.7rem 1.2rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        button[type="submit"]:hover, .snmp-btn:hover {
            background: var(--primary-dark);
        }
        .resultado {
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 1.5rem 1rem 1rem 1rem;
            margin-top: 1.5rem;
            box-shadow: 0 2px 8px #0001;
        }
        .resultado h3 {
            margin-top: 0;
            color: var(--primary-dark);
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        .datos-cliente {
            margin-bottom: 1.2rem;
        }
        .datos-cliente span {
            display: inline-block;
            min-width: 110px;
            color: var(--gray);
            font-weight: 500;
        }
        .estado-activo {
            color: var(--accent);
            font-weight: bold;
        }
        .estado-suspendido {
            color: var(--danger);
            font-weight: bold;
        }
        .estado-desconocido {
            color: var(--warning);
            font-weight: bold;
        }
        .snmp-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .snmp-btn {
            flex: 1 1 40%;
            min-width: 140px;
            justify-content: center;
        }
        .respuesta {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
            min-height: 40px;
            color: var(--primary-dark);
            font-size: 1rem;
            border: 1px solid var(--primary);
        }
        @media (max-width: 600px) {
            .container {
                max-width: 98vw;
                padding: 1rem 0.5rem;
            }
            .snmp-btns {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2><span class="material-icons" style="vertical-align:middle;">search</span> Consulta ONU por DNI/RUC</h2>
    <form method="POST" autocomplete="off">
        <input type="text" name="dni" placeholder="Ingrese DNI o RUC" required>
        <button type="submit"><span class="material-icons">search</span> Consultar</button>
    </form>

<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['dni'])) {
    $dni = trim($_POST['dni']);
    $pdo = conectarDB();
    $stmt = $pdo->prepare("SELECT * FROM onu_datos WHERE onudesc LIKE :busqueda LIMIT 1");
    $busqueda = $dni . '%';
    $stmt->bindParam(':busqueda', $busqueda, PDO::PARAM_STR);
    $stmt->execute();

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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

        $estado = ($row['act_susp'] == 1) ? '<span class="estado-activo">Activo</span>' :
                  (($row['act_susp'] == 2) ? '<span class="estado-suspendido">Suspendido</span>' :
                  '<span class="estado-desconocido">Desconocido</span>');

        echo "<div class='resultado'>";
        echo "<h3><span class='material-icons' style='vertical-align:middle;'>person</span> Datos del Cliente</h3>";
        echo "<div class='datos-cliente'>";
        echo "<span><span class='material-icons' style='font-size:1.1em;'>badge</span> DNI/RUC:</span> {$row['onudesc']}<br>";
        echo "<span><span class='material-icons' style='font-size:1.1em;'>verified_user</span> Estado:</span> $estado<br>";
        echo "<span><span class='material-icons' style='font-size:1.1em;'>dns</span> OLT:</span> {$row['host']}<br>";
        echo "<span><span class='material-icons' style='font-size:1.1em;'>lan</span> PON Lógico:</span> {$row['host']}/{$row['slotportonu']}/{$row['onulogico']}<br>";
        echo "<span><span class='material-icons' style='font-size:1.1em;'>history</span> Última conexión:</span> {$row['fecha']}<br>";
        echo "</div>";
        echo "<h4 style='margin-bottom:0.7rem;'><span class='material-icons' style='vertical-align:middle;'>settings_ethernet</span> Consultas SNMP</h4>";
        echo "<div class='snmp-btns'>";
        echo "<button class='snmp-btn' onclick=\"consultarSNMP('retorno', '$ip_host', '$snmpindex')\"><span class='material-icons'>arrow_upward</span> Potencia de Retorno</button>";
        echo "<button class='snmp-btn' onclick=\"consultarSNMP('recepcion', '$ip_host', '$snmpindex')\"><span class='material-icons'>arrow_downward</span> Potencia de Recepción</button>";
        echo "<button class='snmp-btn' onclick=\"consultarSNMP('desconexion', '$ip_host', '$snmpindex')\"><span class='material-icons'>history</span> Última Conexión</button>";
        echo "<button class='snmp-btn' onclick=\"consultarSNMP('estado', '$ip_host', '$snmpindex')\"><span class='material-icons'>power_settings_new</span> Estado Online/Offline</button>";
        echo "<button class='snmp-btn' onclick=\"consultarSNMP('plan', '$ip_host', '$snmpindex')\"><span class='material-icons'>wifi</span> Plan Actual</button>";
        echo "</div>";
        echo "<div id='respuesta' class='respuesta'></div>";
        echo "</div>";
    } else {
        echo "<div class='resultado' style='color:var(--danger);text-align:center;'>No se encontraron resultados para el DNI/RUC ingresado.</div>";
    }
}
?>
</div>
<script>
function consultarSNMP(tipo, ip, index) {
    const respuesta = document.getElementById('respuesta');
    respuesta.innerHTML = '<span class="material-icons" style="vertical-align:middle;">hourglass_empty</span> Consultando...';
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
        respuesta.innerHTML = '<span class="material-icons" style="vertical-align:middle;">error</span> Error en la consulta SNMP.';
    });
}
</script>
</body>
</html>
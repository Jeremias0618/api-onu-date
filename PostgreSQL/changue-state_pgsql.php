<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Mapeo de host a IP ---
function obtenerIpPorHost($host) {
    $mapa = [
        'SD-1' => '10.20.70.10',
        'SD-2' => '10.30.70.21',
        'SD-3' => '10.20.70.30',
        'SD-4' => '10.20.70.46',
        'INC-5' => '10.5.5.2',
        'SD-7' => '10.20.70.72',
        'JIC-8' => '172.16.2.2',
        'JIC2-8' => '172.16.2.2',
        'NEW_JIC-8' => '172.17.2.2',
        'ATE-9' => '172.99.99.2',
        'SMP-10' => '10.170.7.2',
        'CAMP-11' => '10.111.11.2',
        'CAMP2-11' => '10.112.25.2',
        'PTP-12' => '20.20.5.1',
        'ANC-13' => '10.13.13.2',
        'CHO-14' => '172.18.2.2',
        'LO-15' => '10.70.7.2',
        'LO2-15' => '10.70.8.2',
        'VIR-16' => '30.150.130.2',
        'PTP-17' => '10.17.7.2',
        'VENT-18' => '18.18.1.2',
    ];
    return $mapa[$host] ?? '';
}

// --- Configuración de conexión a la base de datos PostgreSQL ---
$db_host = '10.80.80.106';
$db_port = 5432;
$db_name = 'fiberprodata';
$db_user = 'fiberproadmin';
$db_pass = 'noc12363';

$mensaje = '';
$datos_onu = null;
$dni = '';

// --- Paso 1: Buscar por DNI/RUC ---
if (isset($_POST['buscar']) && !empty($_POST['dni'])) {
    $dni = trim($_POST['dni']);
    try {
        $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $stmt = $pdo->prepare("SELECT * FROM onu_datos WHERE onudesc = :dni LIMIT 1");
        $stmt->bindParam(':dni', $dni, PDO::PARAM_STR);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $datos_onu = $row;
        } else {
            $mensaje = "⚠ No se encontró ningún registro para el DNI/RUC ingresado.";
        }
        $stmt = null;
        $pdo = null;
    } catch (PDOException $e) {
        $mensaje = "❌ Error de conexión a la base de datos.";
    }
}

// --- Paso 2: Ejecutar SNMP si se presiona Activar/Suspender ---
if (isset($_POST['accion']) && isset($_POST['snmpindexonu']) && isset($_POST['host'])) {
    $accion = $_POST['accion'];
    $snmpindexonu = $_POST['snmpindexonu'];
    $host = $_POST['host'];
    $ip = obtenerIpPorHost($host);
    $comunidad = "FiberPro2018";
    $oid = "1.3.6.1.4.1.2011.6.128.1.1.2.46.1.1.$snmpindexonu";
    $valor = ($accion === "activar") ? 1 : 2;

    if ($ip && $snmpindexonu) {
        $resultado = @snmpset($ip, $comunidad, $oid, "i", $valor);
        if ($resultado === false) {
            $mensaje = "❌ Error: No se pudo enviar el comando SNMP.";
        } else {
            $mensaje = "✅ Comando SNMP enviado correctamente: $accion (valor $valor)";
        }
        // Para mostrar los datos de la ONU después de la acción
        $datos_onu = [
            'host' => $host,
            'snmpindexonu' => $snmpindexonu,
            'onudesc' => $_POST['onudesc'] ?? '',
            'slotportonu' => $_POST['slotportonu'] ?? '',
            'fecha' => $_POST['fecha'] ?? '',
            'onulogico' => $_POST['onulogico'] ?? '',
            'id' => $_POST['id'] ?? '',
            'act_susp' => $valor // Refleja el nuevo estado
        ];
        $dni = $_POST['onudesc'] ?? '';
    } else {
        $mensaje = "❌ Datos insuficientes para ejecutar SNMP.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control SNMP por DNI/RUC</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 8px #0002; }
        .btn { padding: 0.5rem 1.5rem; border: none; border-radius: 5px; margin: 0.5rem 0.5rem 0 0; font-weight: bold; cursor: pointer; }
        .btn-activar { background: #27ae60; color: #fff; }
        .btn-suspender { background: #e67e22; color: #fff; }
        .msg { margin-top: 1rem; font-weight: bold; }
        .msg.success { color: #27ae60; }
        .msg.error { color: #c0392b; }
        .msg.warning { color: #e67e22; }
        .datos-onu { background: #f8f8f8; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        label { font-weight: bold; }
        input[type="text"] { width: 100%; margin-bottom: 1rem; padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        th, td { padding: 0.5rem; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
<div class="container">
    <h2>Control SNMP por DNI/RUC</h2>
    <form method="post" autocomplete="off">
        <label for="dni">Ingrese DNI/RUC:</label>
        <input type="text" name="dni" id="dni" value="<?= htmlspecialchars($dni) ?>" required />
        <button type="submit" name="buscar" class="btn btn-activar">Buscar</button>
    </form>

    <?php if ($mensaje): ?>
        <div class="msg <?= strpos($mensaje, '✅') !== false ? 'success' : (strpos($mensaje, '⚠') !== false ? 'warning' : 'error') ?>">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <?php if ($datos_onu): ?>
        <div class="datos-onu">
            <h4>Datos de la ONU</h4>
            <table>
                <tr><th>DNI/RUC</th><td><?= htmlspecialchars($datos_onu['onudesc']) ?></td></tr>
                <tr><th>Host</th><td><?= htmlspecialchars($datos_onu['host']) ?></td></tr>
                <tr><th>IP Host</th><td><?= htmlspecialchars(obtenerIpPorHost($datos_onu['host'])) ?></td></tr>
                <tr><th>snmpindexonu</th><td><?= htmlspecialchars($datos_onu['snmpindexonu']) ?></td></tr>
                <tr><th>Slot/Port/ONU</th><td><?= htmlspecialchars($datos_onu['slotportonu'] ?? '') ?></td></tr>
                <tr><th>Fecha</th><td><?= htmlspecialchars($datos_onu['fecha'] ?? '') ?></td></tr>
                <tr><th>ONU Lógico</th><td><?= htmlspecialchars($datos_onu['onulogico'] ?? '') ?></td></tr>
                <tr><th>ID</th><td><?= htmlspecialchars($datos_onu['id'] ?? '') ?></td></tr>
                <tr>
                    <th>Estado</th>
                    <td>
                        <?php
                            if (isset($datos_onu['act_susp'])) {
                                echo $datos_onu['act_susp'] == 1 ? 'Activo' : ($datos_onu['act_susp'] == 2 ? 'Suspendido' : 'Desconocido');
                            } else {
                                echo 'Desconocido';
                            }
                        ?>
                    </td>
                </tr>
            </table>
            <form method="post" style="display:inline;">
                <input type="hidden" name="snmpindexonu" value="<?= htmlspecialchars($datos_onu['snmpindexonu']) ?>">
                <input type="hidden" name="host" value="<?= htmlspecialchars($datos_onu['host']) ?>">
                <input type="hidden" name="onudesc" value="<?= htmlspecialchars($datos_onu['onudesc']) ?>">
                <input type="hidden" name="slotportonu" value="<?= htmlspecialchars($datos_onu['slotportonu'] ?? '') ?>">
                <input type="hidden" name="fecha" value="<?= htmlspecialchars($datos_onu['fecha'] ?? '') ?>">
                <input type="hidden" name="onulogico" value="<?= htmlspecialchars($datos_onu['onulogico'] ?? '') ?>">
                <input type="hidden" name="id" value="<?= htmlspecialchars($datos_onu['id'] ?? '') ?>">
                <button type="submit" name="accion" value="activar" class="btn btn-activar">🟢 Activar</button>
                <button type="submit" name="accion" value="suspender" class="btn btn-suspender">🔴 Suspender</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
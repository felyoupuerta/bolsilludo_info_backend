<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';
Auth::requireAdmin();

$title = "Gestión de Partidos y Resultados - Panel de Administración";
// Definir competiciones y estados disponibles
$competiciones = [
        'Torneo Intermedio',
        'Copa Libertadores',
        'Copa Uruguay',
        'Torneo Clausura'
];
$estados = [
        'Pendiente',
        'Finalizado'
];

// Inicializar variables de mensaje y error
$mensaje = '';
$error = '';
$partido_edicion = null;

// =========================================================
// BLOQUE DE DEBUG: Muestra los valores de $_POST
// Comenta o elimina este bloque una vez que la depuración haya terminado
// =========================================================
//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//    echo '<pre style="background-color: #f9f9f9; border: 1px solid #ccc; padding: 10px;">';
//    echo '<h3>DEBUG - Valores recibidos en $_POST:</h3>';
//    print_r($_POST);
//    echo '</pre>';
//}
// =========================================================
// FIN DEL BLOQUE DE DEBUG
// =========================================================


// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDBConnection();
        $db->autocommit(FALSE); // Iniciar transacción

        $id = $_POST['id'] ?? null;
        $competicion = trim($_POST['competicion'] ?? '');
        $jornada = trim($_POST['jornada'] ?? '');
        $fecha_partido = !empty($_POST['fecha_partido']) ? date('Y-m-d H:i:s', strtotime($_POST['fecha_partido'])) : null;
        $equipo_local = trim($_POST['equipo_local'] ?? '');
        $equipo_visitante = trim($_POST['equipo_visitante'] ?? '');
        $goles_local = $_POST['goles_local'] !== '' ? (int)$_POST['goles_local'] : null;
        $goles_visitante = $_POST['goles_visitante'] !== '' ? (int)$_POST['goles_visitante'] : null;
        $penales_local = $_POST['penales_local'] !== '' ? (int)$_POST['penales_local'] : null;
        $penales_visitante = $_POST['penales_visitante'] !== '' ? (int)$_POST['penales_visitante'] : null;
        $estado = $_POST['estado'] ?? 'Pendiente';

        // Validar campos requeridos
        if (empty($competicion) || empty($jornada) || empty($equipo_local) || empty($equipo_visitante) || empty($estado)) {
            throw new Exception("Por favor, complete todos los campos obligatorios.");
        }

        if (isset($_POST['crear_partido'])) {
            // Determinar resultado automáticamente para nueva creación
            $resultado = null;
            if ($estado == 'Finalizado' && $goles_local !== null && $goles_visitante !== null) {
                if ($goles_local > $goles_visitante) $resultado = 'Local';
                elseif ($goles_local < $goles_visitante) $resultado = 'Visitante';
                else $resultado = 'Empate';
            }

            // Crear nuevo partido
            $sql = "INSERT INTO fixtures
                (competicion, jornada, fecha_partido, equipo_local, equipo_visitante,
                 goles_local, goles_visitante, penales_local, penales_visitante, estado, resultado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $db->error);
            }
            $stmt->bind_param("sssssiiiiss",
                    $competicion, $jornada, $fecha_partido, $equipo_local, $equipo_visitante,
                    $goles_local, $goles_visitante, $penales_local, $penales_visitante, $estado, $resultado);

        } elseif (isset($_POST['editar_partido']) && $id) {
            // Obtener datos existentes para evitar sobrescribir con valores vacíos
            $stmt_select = $db->prepare("SELECT * FROM fixtures WHERE id = ?");
            $stmt_select->bind_param("i", $id);
            $stmt_select->execute();
            $result_select = $stmt_select->get_result();
            $partido_existente = $result_select->fetch_assoc();
            $stmt_select->close();

            if (!$partido_existente) {
                throw new Exception("Partido a editar no encontrado.");
            }

            // Usar valores del formulario si no están vacíos, de lo contrario, usar los existentes
            $competicion = $competicion ?: $partido_existente['competicion'];
            $jornada = $jornada ?: $partido_existente['jornada'];
            $fecha_partido = $fecha_partido ?: $partido_existente['fecha_partido'];
            $equipo_local = $equipo_local ?: $partido_existente['equipo_local'];
            $equipo_visitante = $equipo_visitante ?: $partido_existente['equipo_visitante'];
            $goles_local = $_POST['goles_local'] !== '' ? (int)$_POST['goles_local'] : ($partido_existente['goles_local'] !== null ? (int)$partido_existente['goles_local'] : null);
            $goles_visitante = $_POST['goles_visitante'] !== '' ? (int)$_POST['goles_visitante'] : ($partido_existente['goles_visitante'] !== null ? (int)$partido_existente['goles_visitante'] : null);
            $penales_local = $_POST['penales_local'] !== '' ? (int)$_POST['penales_local'] : ($partido_existente['penales_local'] !== null ? (int)$partido_existente['penales_local'] : null);
            $penales_visitante = $_POST['penales_visitante'] !== '' ? (int)$_POST['penales_visitante'] : ($partido_existente['penales_visitante'] !== null ? (int)$partido_existente['penales_visitante'] : null);
            $estado = $estado ?: $partido_existente['estado'];

            // Recalcular resultado para actualización
            $resultado = null;
            if ($estado == 'Finalizado' && $goles_local !== null && $goles_visitante !== null) {
                if ($goles_local > $goles_visitante) $resultado = 'Local';
                elseif ($goles_local < $goles_visitante) $resultado = 'Visitante';
                else $resultado = 'Empate';
            }

            // Editar partido existente
            $sql = "UPDATE fixtures SET
                competicion = ?, jornada = ?, fecha_partido = ?, equipo_local = ?, equipo_visitante = ?,
                goles_local = ?, goles_visitante = ?, penales_local = ?, penales_visitante = ?,
                estado = ?, resultado = ?
                WHERE id = ?";
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $db->error);
            }
            $stmt->bind_param("sssssiiiissi",
                    $competicion, $jornada, $fecha_partido, $equipo_local, $equipo_visitante,
                    $goles_local, $goles_visitante, $penales_local, $penales_visitante,
                    $estado, $resultado, $id);
        } else {
            throw new Exception("Petición no válida.");
        }

        if ($stmt->execute()) {
            $db->commit();
            $mensaje = $id ? "Partido actualizado exitosamente." : "Partido creado exitosamente.";
        } else {
            $db->rollback();
            throw new Exception("Error en la operación: " . $stmt->error);
        }

        $stmt->close();
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction) {
            $db->rollback();
        }
        $error = "Error: " . $e->getMessage();
    }
}

// Eliminar partido
if (isset($_GET['eliminar'])) {
    try {
        $db = getDBConnection();
        $id = $_GET['eliminar'];
        $stmt = $db->prepare("DELETE FROM fixtures WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $mensaje = "Partido eliminado correctamente.";
        } else {
            $error = "Error al eliminar partido: " . $db->error;
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = "Error al eliminar partido: " . $e->getMessage();
    }
}

// Obtener partido para edición
if (isset($_GET['editar'])) {
    try {
        $db = getDBConnection();
        $id = $_GET['editar'];
        $stmt = $db->prepare("SELECT * FROM fixtures WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $partido_edicion = $result->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        $error = "Error al cargar partido para edición: " . $e->getMessage();
    }
}

// ----- LISTADO CON PAGINACIÓN -----
$partidos = [];
$totalRegistros = 0;
$totalPaginas = 1;
$paginaActual = 1;
try {
    $db = getDBConnection();

    $totalStmt = $db->query("SELECT COUNT(*) as total FROM fixtures");
    $totalRow = $totalStmt->fetch_assoc();
    $totalRegistros = $totalRow['total'];

    $porPagina = 20;
    $totalPaginas = ceil($totalRegistros / $porPagina);
    $paginaActual = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($paginaActual - 1) * $porPagina;

    $sql = "
        SELECT id, competicion, 
               jornada, fecha_partido, equipo_local, equipo_visitante,
               goles_local, goles_visitante, penales_local, penales_visitante, estado, resultado
        FROM fixtures
        ORDER BY FIELD(competicion, 'Torneo Intermedio', 'Copa Libertadores', 'Copa Uruguay', 'Torneo Clausura'),
                 fecha_partido DESC
        LIMIT ?, ?
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta de listado: " . $db->error);
    }
    $stmt->bind_param("ii", $offset, $porPagina);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $partidos = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();

} catch (Exception $e) {
    $error = "Error al cargar partidos: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <style>
        :root {
            --nacional-blue: #003366;
            --nacional-white: #FFFFFF;
            --nacional-red: #CC0000;
            --nacional-light-blue: #0066CC;
            --nacional-gray: #F0F2F5;
            --nacional-dark-gray: #343A40;
            --border-color: #e0e0e0;
        }

        body {
            font-family: 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--nacional-gray);
            margin: 0;
            padding: 0;
            color: #333;
        }

        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        h1, h2, h3 {
            color: var(--nacional-blue);
            text-align: center;
            font-weight: 500;
        }

        h1 {
            border-bottom: 2px solid var(--nacional-blue);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #e6f7e9;
            color: #198754;
            border: 1px solid #bce1c9;
        }

        .alert-error {
            background-color: #ffe6e6;
            color: #dc3545;
            border: 1px solid #f9b4b4;
        }

        .form-section {
            background-color: #F8F9FA;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--nacional-dark-gray);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--nacional-light-blue);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.2);
            outline: none;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: background-color 0.3s, transform 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background-color: var(--nacional-blue);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--nacional-light-blue);
        }

        .btn-danger {
            background-color: var(--nacional-red);
            color: white;
        }

        .btn-danger:hover {
            background-color: #a00;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--nacional-blue);
            color: white;
            text-transform: uppercase;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f0f0f0;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .resultado-local {
            color: #28a745;
            font-weight: bold;
        }

        .resultado-visitante {
            color: #dc3545;
            font-weight: bold;
        }

        .resultado-empate {
            color: #ffc107;
            font-weight: bold;
        }

        .estado-pendiente, .estado-finalizado {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
        }

        .estado-pendiente {
            background-color: #e2f0fb;
            color: #0d6efd;
        }

        .estado-finalizado {
            background-color: #d1e7dd;
            color: #198754;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .pagination {
            margin-top: 25px;
            text-align: center;
        }

        .pagination a, .pagination span {
            display: inline-block;
            padding: 10px 16px;
            margin: 0 4px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: var(--nacional-blue);
            transition: background-color 0.3s, color 0.3s;
        }

        .pagination a:hover {
            background-color: var(--nacional-light-blue);
            color: white;
        }

        .pagination .active {
            background-color: var(--nacional-blue);
            color: white;
            font-weight: bold;
            border-color: var(--nacional-blue);
        }

        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .admin-container {
                margin: 10px;
                padding: 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            tr {
                border: 1px solid #ccc;
                margin-bottom: 15px;
                border-radius: 8px;
            }

            td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }

            td:before {
                content: attr(data-label);
                position: absolute;
                left: 6px;
                width: 45%;
                padding-left: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                color: var(--nacional-dark-gray);
            }

            .actions {
                flex-direction: column;
                gap: 5px;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <h1><i class="fas fa-futbol"></i> Gestión de Partidos y Fixtures ⚽</h1>


    <?php if ($mensaje): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-section">
        <h2><?php echo $partido_edicion ? 'Editar Partido' : 'Crear Nuevo Partido'; ?></h2>
        <form method="POST" action="">
            <?php if ($partido_edicion): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($partido_edicion['id']); ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="competicion">Competición <span style="color:var(--nacional-red);">*</span></label>
                    <select id="competicion" name="competicion" required>
                        <option value="">Seleccionar competición</option>
                        <?php foreach ($competiciones as $comp): ?>
                            <option value="<?php echo htmlspecialchars($comp); ?>"
                                    <?php echo ($partido_edicion && $partido_edicion['competicion'] === $comp) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($comp); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="jornada">Jornada <span style="color:var(--nacional-red);">*</span></label>
                    <input type="text" id="jornada" name="jornada"
                           value="<?php echo htmlspecialchars($partido_edicion ? $partido_edicion['jornada'] : ''); ?>"
                           placeholder="Ej: Fecha 1, Semifinal, etc." required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_partido">Fecha y Hora del Partido</label>
                    <input type="datetime-local" id="fecha_partido" name="fecha_partido"
                           value="<?php echo $partido_edicion && $partido_edicion['fecha_partido'] ?
                                   date('Y-m-d\TH:i', strtotime($partido_edicion['fecha_partido'])) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="estado">Estado <span style="color:var(--nacional-red);">*</span></label>
                    <select id="estado" name="estado" required>
                        <?php foreach ($estados as $est): ?>
                            <option value="<?php echo htmlspecialchars($est); ?>"
                                    <?php echo ($partido_edicion && $partido_edicion['estado'] == $est) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($est); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="equipo_local">Equipo Local <span style="color:var(--nacional-red);">*</span></label>
                    <input type="text" id="equipo_local" name="equipo_local"
                           value="<?php echo htmlspecialchars($partido_edicion ? $partido_edicion['equipo_local'] : ''); ?>"
                           placeholder="Ej: Nacional" required>
                </div>
                <div class="form-group">
                    <label for="equipo_visitante">Equipo Visitante <span style="color:var(--nacional-red);">*</span></label>
                    <input type="text" id="equipo_visitante" name="equipo_visitante"
                           value="<?php echo htmlspecialchars($partido_edicion ? $partido_edicion['equipo_visitante'] : ''); ?>"
                           placeholder="Ej: Peñarol" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="goles_local">Goles Local</label>
                    <input type="number" id="goles_local" name="goles_local" min="0"
                           value="<?php echo htmlspecialchars($partido_edicion ? $partido_edicion['goles_local'] : ''); ?>">
                </div>
                <div class="form-group">
                    <label for="goles_visitante">Goles Visitante</label>
                    <input type="number" id="goles_visitante" name="goles_visitante" min="0"
                           value="<?php echo htmlspecialchars($partido_edicion ? $partido_edicion['goles_visitante'] : ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="penales_local">Penales Local</label>
                    <input type="number" id="penales_local" name="penales_local" min="0"
                           value="<?php echo htmlspecialchars($partido_edicion ? $partido_edicion['penales_local'] : ''); ?>">
                </div>
                <div class="form-group">
                    <label for="penales_visitante">Penales Visitante</label>
                    <input type="number" id="penales_visitante" name="penales_visitante" min="0"
                           value="<?php echo htmlspecialchars($partido_edicion ? $partido_edicion['penales_visitante'] : ''); ?>">
                </div>
            </div>

            <div class="form-group" style="text-align: center;">
                <?php if ($partido_edicion): ?>
                    <button type="submit" name="editar_partido" class="btn btn-primary">🔄 Actualizar Partido</button>
                    <a href="panel_partidos.php" class="btn btn-secondary">❌ Cancelar</a>
                <?php else: ?>
                    <button type="submit" name="crear_partido" class="btn btn-primary">➕ Crear Partido</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="partidos-list">
        <h2>Partidos Existentes (<?php echo $totalRegistros; ?> total)</h2>

        <?php if (count($partidos) > 0): ?>
            <table>
                <thead>
                <tr>
                    <th>Competición</th>
                    <th>Jornada</th>
                    <th>Partido</th>
                    <th>Fecha</th>
                    <th>Resultado</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($partidos as $partido): ?>
                    <tr>
                        <td data-label="Competición"><?php echo htmlspecialchars($partido['competicion']); ?></td>
                        <td data-label="Jornada"><?php echo htmlspecialchars($partido['jornada']); ?></td>
                        <td data-label="Partido">
                            <strong><?php echo htmlspecialchars($partido['equipo_local']); ?></strong> vs
                            <strong><?php echo htmlspecialchars($partido['equipo_visitante']); ?></strong>
                        </td>
                        <td data-label="Fecha">
                            <?php echo $partido['fecha_partido'] ? date('d/m/Y H:i', strtotime($partido['fecha_partido'])) : 'Sin fecha'; ?>
                        </td>
                        <td data-label="Resultado">
                            <?php
                            // Cambio: Usar == en lugar de === para una comparación menos estricta
                            if ($partido['estado'] == 'Finalizado'):
                                ?>
                                <span class="resultado-<?php echo strtolower($partido['resultado']); ?>">
                                    <?php echo htmlspecialchars($partido['goles_local']); ?> - <?php echo htmlspecialchars($partido['goles_visitante']); ?>
                                    <?php if ($partido['penales_local'] !== null && $partido['penales_visitante'] !== null): ?>
                                        (<?php echo htmlspecialchars($partido['penales_local']); ?>-<?php echo htmlspecialchars($partido['penales_visitante']); ?>)
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td data-label="Estado">
                            <?php
                            // Cambio: Usar == en lugar de === para el estilo del estado
                            $estadoClase = ($partido['estado'] == 'Finalizado') ? 'finalizado' : 'pendiente';
                            $estadoTexto = ($partido['estado'] == 'Finalizado') ? 'Finalizado' : 'Pendiente';
                            ?>
                            <span class="estado-<?php echo $estadoClase; ?>">
                                <?php echo htmlspecialchars($estadoTexto); ?>
                            </span>
                        </td>
                        <td data-label="Acciones" class="actions">
                            <a href="panel_partidos.php?editar=<?php echo htmlspecialchars($partido['id']); ?>" class="btn btn-primary btn-sm">Editar</a>
                            <a href="panel_partidos.php?eliminar=<?php echo htmlspecialchars($partido['id']); ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('¿Estás seguro de que quieres eliminar este partido?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($totalPaginas > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <a href="?page=<?php echo $i; ?>"
                           class="<?php echo $i === $paginaActual ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="text-align: center; font-style: italic;">No hay partidos registrados. ¡Crea uno para empezar!</p>
        <?php endif; ?>
    </div>
    <div class="back-link">
        <a href="index.php" class="btn btn-secondary">← Volver al panel de admin</a>
    </div>
</div>
<script>
    // Pequeño script para evitar reenvío del formulario al recargar la página
    if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
    }
</script>
</body>
</html> 

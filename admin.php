<?php
session_start();
// Redirección si no es el administrador principal
if (!isset($_SESSION['user_spen']) || $_SESSION['user_spen'] !== 'admin') {
    header("Location: index.php?error=acceso_denegado");
    exit;
}

// Conexión a la Base de Datos
$host = 'localhost'; $db = 'estudio_spen'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) { die("Error de conexión: " . $e->getMessage()); }

$msg = '';

// --- LÓGICA DEL CRUD ---

// 1. Acciones para LEYES
if (isset($_POST['action_ley'])) {
    if ($_POST['action_ley'] === 'crear') {
        $stmt = $pdo->prepare("INSERT INTO leyes (nombre, siglas) VALUES (?, ?)");
        $stmt->execute([$_POST['nombre_ley'], strtoupper($_POST['siglas_ley'])]);
        $msg = "Ley registrada con éxito.";
    } elseif ($_POST['action_ley'] === 'eliminar') {
        $stmt = $pdo->prepare("DELETE FROM leyes WHERE id = ?");
        $stmt->execute([$_POST['id_ley']]);
        $msg = "Ley eliminada del sistema.";
    }
}

// 2. Acciones para USUARIOS DE CONSULTA
if (isset($_POST['action_user'])) {
    if ($_POST['action_user'] === 'crear') {
        $pass_hash = password_hash($_POST['password_user'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (username, password) VALUES (?, ?)");
        try {
            $stmt->execute([$_POST['username_user'], $pass_hash]);
            $msg = "Usuario de consulta registrado.";
        } catch (Exception $e) { $msg = "Error: El usuario ya existe."; }
    } elseif ($_POST['action_user'] === 'eliminar') {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND username != 'admin'");
        $stmt->execute([$_POST['id_user']]);
        $msg = "Usuario eliminado.";
    }
}

// 3. Acciones para ARTÍCULOS
if (isset($_POST['action_articulo'])) {
    if ($_POST['action_articulo'] === 'crear') {
        $sql = "INSERT INTO articulos (ley_id, libro_numero, libro_nombre, cabecera_descanso, articulo_numero, articulo_nombre, texto_explicito, sentido_ley, puntos_importantes, observaciones, fechas_periodos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['ley_id'], $_POST['libro_numero'], $_POST['libro_nombre'],
            !empty($_POST['cabecera']) ? $_POST['cabecera'] : null,
            $_POST['art_num'], $_POST['art_nom'], $_POST['texto'],
            $_POST['sentido'], $_POST['puntos'], $_POST['observaciones'],
            !empty($_POST['fechas']) ? $_POST['fechas'] : null
        ]);
        $msg = "Artículo indexado correctamente.";
    } elseif ($_POST['action_articulo'] === 'eliminar') {
        $stmt = $pdo->prepare("DELETE FROM articulos WHERE id = ?");
        $stmt->execute([$_POST['id_articulo']]);
        $msg = "Artículo removido del catálogo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración CRUD - SPEN</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .admin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; width: 100%; max-width: 1300px; margin-top: 20px; }
        .full-width { grid-column: span 2; }
        .item-list { max-height: 200px; overflow-y: auto; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #ddd; margin-top: 10px; }
        .item-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee; align-items: center; font-size: 13px; }
        .btn-danger { background-color: #d14949; padding: 4px 8px; font-size: 11px; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>Panel de Control Core (CRUD)</h1>
        <p class="subtitle">Gestión de Leyes, Artículos Electorales y Permisos de Acceso</p>
        <p><a href="index.php" class="btn" style="background:#4a5568;">⬅ Volver al Portal de Estudio</a></p>
        <?php if($msg): ?><div class="period-box" style="margin-top:10px;"><?php echo $msg; ?></div><?php endif; ?>
    </header>

    <div class="admin-grid">
        <!-- SECCIÓN: GESTIÓN DE LEYES -->
        <div class="admin-card">
            <h3>📂 Catálogo de Leyes</h3>
            <form method="POST">
                <input type="hidden" name="action_ley" value="crear">
                <div class="form-group"><label>Nombre Completo de la Ley:</label><input type="text" name="nombre_ley" required placeholder="Ej: Ley General de Partidos Políticos"></div>
                <div class="form-group"><label>Siglas Oficiales:</label><input type="text" name="siglas_ley" required placeholder="Ej: LGPP"></div>
                <button type="submit" class="btn">Agregar Nueva Ley</button>
            </form>
            <div class="item-list">
                <strong>Leyes en la Base de Datos:</strong>
                <?php 
                $leyes = $pdo->query("SELECT * FROM leyes")->fetchAll(PDO::FETCH_ASSOC);
                foreach($leyes as $l): ?>
                    <div class="item-row">
                        <span><strong><?php echo $l['siglas']; ?></strong> - <?php echo $l['nombre']; ?></span>
                        <form method="POST" onsubmit="return confirm('¿Borrar esta ley borrará todos sus artículos?');" style="margin:0;">
                            <input type="hidden" name="action_ley" value="eliminar"><input type="hidden" name="id_ley" value="<?php echo $l['id']; ?>">
                            <button type="submit" class="btn btn-danger">Borrar</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- SECCIÓN: GESTIÓN DE USUARIOS DE CONSULTA -->
        <div class="admin-card">
            <h3>👥 Usuarios con Acceso de Lectura</h3>
            <form method="POST">
                <input type="hidden" name="action_user" value="crear">
                <div class="form-group"><label>Nombre de Usuario:</label><input type="text" name="username_user" required></div>
                <div class="form-group"><label>Contraseña de Acceso:</label><input type="password" name="password_user" required></div>
                <button type="submit" class="btn" style="background-color:#2b6cb0;">Dar de Alta Usuario</button>
            </form>
            <div class="item-list">
                <strong>Usuarios Autorizados:</strong>
                <?php 
                $users = $pdo->query("SELECT * FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
                foreach($users as $u): ?>
                    <div class="item-row">
                        <span>👤 <?php echo $u['username']; ?> <?php echo $u['username'] === 'admin' ? '(Principal)' : ''; ?></span>
                        <?php if($u['username'] !== 'admin'): ?>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="action_user" value="eliminar"><input type="hidden" name="id_user" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-danger">Revocar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- SECCIÓN: ALTA DE ARTÍCULOS (FULL WIDTH) -->
        <div class="admin-card full-width">
            <h3>📝 Indexador de Artículos y Cabeceras de Descanso</h3>
            <form method="POST">
                <input type="hidden" name="action_articulo" value="crear">
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Asociar a Ley:</label>
                        <select name="ley_id">
                            <?php foreach($leyes as $l) { echo "<option value='{$l['id']}'>{$l['siglas']}</option>"; } ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Número de Libro:</label><input type="number" name="libro_numero" required></div>
                    <div class="form-group"><label>Nombre del Libro:</label><input type="text" name="libro_nombre" required placeholder="Ej: Del Proceso Electoral"></div>
                </div>
                <div class="form-group"><label>Cabecera de Descanso / Subtítulo Intermedio de Tabla (Opcional):</label><input type="text" name="cabecera" placeholder="Ej: Sistemas de elección, fórmulas y principios rectores"></div>
                <div style="display:grid; grid-template-columns:1fr 3fr; gap:15px;">
                    <div class="form-group"><label>No. Artículo:</label><input type="text" name="art_num" placeholder="Ej: Art. 12" required></div>
                    <div class="form-group"><label>Nombre de la Disposición:</label><input type="text" name="art_nom" required></div>
                </div>
                <div class="form-group"><label>Texto Explícito Obligatorio (Ley Completa sin resumir):</label><textarea name="texto" rows="5" required></textarea></div>
                <div class="form-group"><label>Sentido de la Ley:</label><textarea name="sentido" rows="2" required></textarea></div>
                <div class="form-group"><label>Puntos Importantes (Síntesis completa):</label><textarea name="puntos" rows="3" required></textarea></div>
                <div class="form-group"><label>Observaciones Estratégicas (Trampas del examen):</label><textarea name="observaciones" rows="2" required></textarea></div>
                <div class="form-group"><label>Fechas o Periodos de Referencia (Opcional):</label><input type="text" name="fechas"></div>
                <button type="submit" class="btn" style="width:100%; padding:15px;">Guardar e Indexar Artículo en Base de Datos</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>

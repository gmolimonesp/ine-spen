<?php
session_start();
error_reporting(E_ALL); ini_set('display_errors', 1);

// Conexión a la Base de Datos
$host = 'localhost'; $db = 'estudio_spen'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) { die("Error de conexión base: " . $e->getMessage()); }

// Procesamiento de Login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $usuario = $stmt->fetch();
    if ($usuario && password_verify($_POST['password'], $usuario['password'])) {
        $_SESSION['user_spen'] = $usuario['username'];
        header("Location: index.php"); exit;
    } else { $login_error = 'Acceso incorrecto.'; }
}

// Cierre de sesión
if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit; }

// Ley seleccionada en el menú (Por defecto la primera que encuentre)
$leyes_disponibles = $pdo->query("SELECT * FROM leyes ORDER BY id ASC")->fetchAll();
$ley_actual_id = isset($_GET['ley']) ? intval($_GET['ley']) : (!empty($leyes_disponibles) ? $leyes_disponibles[0]['id'] : 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plataforma Suprema de Estudio SPEN</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

    <!-- MENÚ SUPERIOR CORPORATIVO RESALTADO -->
    <div class="top-navbar">
        <div class="nav-brand">⚖️ Analítica Electoral</div>
        <div class="nav-menu">
            <!-- Selector Dinámico de Leyes Indexadas -->
            <div class="dropdown">
                <button class="dropbtn">📚 Consultar Ley: 
                    <?php 
                    foreach($leyes_disponibles as $l) { 
                        if($l['id'] == $ley_actual_id) echo $l['siglas']; 
                    } 
                    ?> ▾
                </button>
                <div class="dropdown-content">
                    <?php foreach($leyes_disponibles as $l): ?>
                        <a href="?ley=<?php echo $l['id']; ?>"><?php echo $l['siglas']; ?> - <?php echo $l['nombre']; ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Botón de Login Altamente Resaltado -->
            <?php if(!isset($_SESSION['user_spen'])): ?>
                <div class="login-trigger-container">
                    <button class="btn-login-trigger" onclick="document.getElementById('loginModal').style.display='flex'">🔒 Iniciar Sesión</button>
                </div>
            <?php else: ?>
                <div class="user-badge-nav">
                    <span>👤 <?php echo htmlspecialchars($_SESSION['user_spen']); ?></span>
                    <?php if($_SESSION['user_spen'] === 'admin'): ?>
                        <a href="admin.php" class="admin-link">⚙️ Panel CRUD</a>
                    <?php endif; ?>
                    <a href="?logout=1" class="logout-link">Salir</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL DE INICIO DE SESIÓN -->
    <div id="loginModal" class="modal-overlay" style="display: <?php echo $login_error?'flex':'none'; ?>;">
        <div class="modal-card">
            <h3>Ingreso Protegido al Contenido</h3>
            <form method="POST">
                <div class="form-group"><label>Usuario:</label><input type="text" name="username" required></div>
                <div class="form-group"><label>Contraseña:</label><input type="password" name="password" required></div>
                <button type="submit" name="login_submit" class="btn" style="width:100%;">Validar Credenciales</button>
                <button type="button" class="btn" style="background:#ccc; margin-top:5px; width:100%; color:#333;" onclick="document.getElementById('loginModal').style.display='none'">Cancelar</button>
            </form>
            <?php if($login_error): ?><p style="color:red; text-align:center; font-weight:bold;"><?php echo $login_error; ?></p><?php endif; ?>
        </div>
    </div>

    <div class="container" style="margin-top: 80px;">
        <header>
            <h1>Plataforma del Concurso Público SPEN</h1>
            <p class="subtitle">Análisis técnico estructurado de la legislación federal y local</p>
        </header>

        <!-- RESTRICCIÓN TOTAL DE LECTURA (Solo usuarios autenticados) -->
        <?php if(!isset($_SESSION['user_spen'])): ?>
            <div class="admin-card" style="text-align:center; padding:50px 20px;">
                <h2>🔒 Contenido Restringido</h2>
                <p>Para visualizar el desglose de los artículos, las observaciones del examen y los plazos, debes estar registrado por el administrador.</p>
                <button class="btn" onclick="document.getElementById('loginModal').style.display='flex'" style="padding:15px 30px; font-size:16px;">Click aquí para iniciar sesión</button>
            </div>
        <?php else: ?>
            
            <!-- BUSCADOR -->
            <div class="search-container">
                <input type="text" id="searchInput" onkeyup="searchInActiveTab()" placeholder="🔍 Filtrar base de datos del libro activo por concepto o artículo...">
            </div>

            <!-- PESTAÑAS DINÁMICAS -->
            <nav class="tabs-nav">
                <?php for($i=1; $i<=7; $i++): ?>
                    <button class="tab-button <?php echo $i===1?'active':''; ?>" onclick="openBook(event, 'libro<?php echo $i; ?>')">Libro <?php echo $i; ?></button>
                <?php endfor; ?>
            </nav>

            <!-- CONTENEDORES DE TABLAS PHP -->
            <?php for($b=1; $b<=7; $b++): ?>
                <div id="libro<?php echo $b; ?>" class="book-content <?php echo $b===1?'active':''; ?>">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM articulos WHERE ley_id = ? AND libro_numero = ? ORDER BY id ASC");
                    $stmt->execute([$ley_actual_id, $b]);
                    $articulos = $stmt->fetchAll();
                    
                    if(empty($articulos)):
                        echo "<div class='placeholder-text'>No hay artículos registrados para el Libro $b de esta ley.</div>";
                    else:
                    ?>
                    <div class="table-header">Libro <?php echo $b; ?>: <?php echo htmlspecialchars($articulos[0]['libro_nombre']); ?></div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 8%;">Art.</th>
                                <th style="width: 12%;">Nombre</th>
                                <th style="width: 25%;">Texto Explícito</th>
                                <th style="width: 25%;">Puntos Importantes</th>
                                <th style="width: 18%;">Observaciones</th>
                                <th style="width: 12%;">Fechas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $ultima_cabecera = '';
                            foreach($articulos as $art): 
                                if(!empty($art['cabecera_descanso']) && $art['cabecera_descanso'] !== $ultima_cabecera):
                                    $ultima_cabecera = $art['cabecera_descanso'];
                            ?>
                                <tr class="row-descanso">
                                    <td colspan="6" class="cabecera-descanso">📌 <?php echo htmlspecialchars($ultima_cabecera); ?></td>
                                </tr>
                            <?php endif; ?>
                                <tr>
                                    <td><span class="badge-art"><?php echo htmlspecialchars($art['articulo_numero']); ?></span></td>
                                    <td class="highlight-text"><?php echo htmlspecialchars($art['articulo_nombre']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($art['texto_explicito'])); ?></td>
                                    <td>
                                        <strong>Sentido de la ley:</strong> <?php echo htmlspecialchars($art['sentido_ley']); ?><br><br>
                                        <?php echo nl2br(htmlspecialchars($art['puntos_importantes'])); ?>
                                    </td>
                                    <td><div class="alert-box"><?php echo htmlspecialchars($art['observaciones']); ?></div></td>
                                    <td>
                                        <?php if(!empty($art['fechas_periodos'])): ?>
                                            <div class="period-box"><?php echo htmlspecialchars($art['fechas_periodos']); ?></div>
                                        <?php else: echo "-"; endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        <?php endif; ?>
    </div>

    <!-- FOOTER EN MODO OSCURO ELEGANTE -->
    <footer>
        <div class="footer-content">
            <p>Elaborado orgullosamente con IA de G - 2026</p>
            <p class="tech-stack">PHP / CSS3 / MySQL (PDO) / JavaScript Nativo</p>
            <p style="font-size:11px; color:#a0aec0; margin-top:5px;">Plataforma de Alta Disponibilidad para el Funcionario del SPEN.</p>
        </div>
    </footer>

    <script src="buscador.js"></script>
</body>
</html>

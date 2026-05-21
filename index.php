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
    } else { 
        $login_error = 'Usuario o contraseña incorrectos.'; 
    }
}

// Cierre de sesión
if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit; }

// Obtener todas las leyes dadas de alta en el sistema
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

    <!-- MENÚ SUPERIOR CORPORATIVO ALTAMENTE RESALTADO -->
    <div class="top-navbar">
        <div class="nav-brand">⚖️ Analítica Electoral</div>
        <div class="nav-menu">
            
            <!-- Selector de Leyes (Opciones dinámicas para cambiar de ley) -->
            <div class="dropdown">
                <button class="dropbtn">📚 Ley Activa: 
                    <?php 
                    $nombre_ley_actual = "No hay leyes indexadas";
                    foreach($leyes_disponibles as $l) { 
                        if($l['id'] == $ley_actual_id) {
                            echo htmlspecialchars($l['siglas']); 
                            $nombre_ley_actual = $l['nombre'];
                        }
                    } 
                    ?> ▾
                </button>
                <div class="dropdown-content">
                    <?php if(empty($leyes_disponibles)): ?>
                        <a href="#">No hay leyes en el sistema</a>
                    <?php else: ?>
                        <?php foreach($leyes_disponibles as $l): ?>
                            <a href="?ley=<?php echo $l['id']; ?>">👉 <strong><?php echo htmlspecialchars($l['siglas']); ?></strong> - <?php echo htmlspecialchars($l['nombre']); ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Botón de Login con Resalte Premium de Contraste -->
            <?php if(!isset($_SESSION['user_spen'])): ?>
                <div class="login-trigger-container">
                    <button class="btn-login-trigger" onclick="document.getElementById('loginModal').style.display='flex'">🔒 Acceso Privado</button>
                </div>
            <?php else: ?>
                <div class="user-badge-nav">
                    <span class="user-name-tag">👤 <?php echo htmlspecialchars($_SESSION['user_spen']); ?></span>
                    <?php if($_SESSION['user_spen'] === 'admin'): ?>
                        <a href="admin.php" class="admin-link-btn">⚙️ Control CRUD</a>
                    <?php endif; ?>
                    <a href="?logout=1" class="logout-link">Salir</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL DE INTERFAZ DE LOGUEO -->
    <div id="loginModal" class="modal-overlay" style="display: <?php echo $login_error?'flex':'none'; ?>;">
        <div class="modal-card">
            <h3 style="margin-top:0; color:var(--primary-color);">Ingresar a la Plataforma</h3>
            <p style="font-size:12px; color:#666;">Introduce tus claves para desbloquear las tablas y resúmenes de estudio.</p>
            <form method="POST">
                <div class="form-group">
                    <label>Usuario Autorizado:</label>
                    <input type="text" name="username" required placeholder="Ej: admin">
                </div>
                <div class="form-group">
                    <label>Contraseña:</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" name="login_submit" class="btn" style="width:100%; padding:12px;">Validar Credenciales</button>
                <button type="button" class="btn" style="background:#e2e8f0; margin-top:8px; width:100%; color:#4a5568;" onclick="document.getElementById('loginModal').style.display='none'">Cerrar</button>
            </form>
            <?php if($login_error): ?><p style="color:#e53e3e; text-align:center; font-weight:bold; font-size:13px; margin-top:10px;"><?php echo $login_error; ?></p><?php endif; ?>
        </div>
    </div>

    <!-- CONTENEDOR PRINCIPAL DE LA MATERIA -->
    <div class="container">
        
        <!-- PANTALLA DE BLOQUEO (Si no hay sesión iniciada) -->
        <?php if(!isset($_SESSION['user_spen'])): ?>
            <div class="admin-card" style="text-align:center; padding:60px 20px; border-top: 4px solid var(--accent-color); margin-top: 40px;">
                <div style="font-size: 50px; margin-bottom:15px;">🔒</div>
                <h2>Contenido Bajo Llave</h2>
                <p style="color:#666; max-width:500px; margin: 0 auto 25px auto;">El catálogo completo de artículos indexados, observaciones de examen y plazos está restringido. Por favor, inicia sesión con tus credenciales de estudio.</p>
                <button class="btn" onclick="document.getElementById('loginModal').style.display='flex'" style="padding:15px 40px; font-size:15px; background:linear-gradient(135deg, #0d2b45, #203c56); box-shadow: 0 4px 10px rgba(0,0,0,0.15);">Click Aquí para Identificarte</button>
            </div>
        <?php else: ?>
            
            <!-- CONTENEDOR UNIFICADO PARA CONGELAR MANDOS DE GUI EN PANTALLA -->
            <div class="sticky-dashboard-header">
                <header>
                    <h1>Plataforma del Concurso Público SPEN</h1>
                    <p class="subtitle" style="font-size:16px; color:#4a5568;">
                        Estudiando actualmente: <strong style="color:var(--accent-color);"><?php echo htmlspecialchars($nombre_ley_actual); ?></strong>
                    </p>
                </header>

                <!-- BUSCADOR CON FILTRADO EN TIEMPO REAL -->
                <div class="search-container">
                    <input type="text" id="searchInput" onkeyup="searchInActiveTab()" placeholder="🔍 Filtra la base de datos dentro del libro activo (ej: 'paridad', 'voto', 'Art. 4')...">
                </div>

                <!-- PESTAÑAS DINÁMICAS DE SELECCIÓN DE LIBRO -->
                <nav class="tabs-nav">
                    <?php for($i=1; $i<=7; $i++): ?>
                        <button class="tab-button <?php echo $i===1?'active':''; ?>" onclick="openBook(event, 'libro<?php echo $i; ?>')">Libro <?php echo $i; ?></button>
                    <?php endfor; ?>
                </nav>
            </div>

            <!-- CONTENEDORES DE TABLAS EXTRAÍDOS DESDE MYSQL -->
            <?php for($b=1; $b<=7; $b++): ?>
                <div id="libro<?php echo $b; ?>" class="book-content <?php echo $b===1?'active':''; ?>">
                    <?php
                    /*$stmt = $pdo->prepare("SELECT * FROM articulos WHERE ley_id = ? AND libro_numero = ? ORDER BY id ASC");*/
                    $stmt = $pdo->prepare("SELECT * FROM articulos ORDER BY ley_id ASC, libro_numero ASC, CAST(REGEXP_SUBSTR(articulo_numero, '[0-9]+') AS UNSIGNED) ASC, articulo_numero ASC, cabecera_descanso ASC");
                    $stmt->execute([$ley_actual_id, $b]);
                    $articulos = $stmt->fetchAll();
                    
                    if(empty($articulos)):
                        echo "<div class='placeholder-text'>No se han dado de alta artículos en el Libro $b para esta ley. Utiliza el panel de administración.</div>";
                    else:
                        // Extraemos el nombre del libro del primer registro encontrado de forma segura
                        $nombre_del_libro_seccion = $articulos[0]['libro_nombre'];
                    ?>
                    <div class="table-header">Libro <?php echo $b; ?>: <?php echo htmlspecialchars($nombre_del_libro_seccion); ?></div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 8%;">Art.</th>
                                <th style="width: 12%;">Nombre</th>
                                <th style="width: 25%;">Ley</th>
                                <th style="width: 25%;">Sentido de la ley</th>
                                <th style="width: 18%;">Observaciones</th>
                                <th style="width: 12%;">Fechas o periodos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $ultima_cabecera = '';
                            foreach($articulos as $art): 
                                // Renderizado de las cabeceras de descanso dinámicas (Subtítulos de tabla)
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
                                    <td style="font-size:13px; text-align:justify;"><?php echo nl2br(htmlspecialchars($art['texto_explicito'])); ?></td>
                                    <td>
                                        <!--<strong>Sentido de la ley:</strong>--> <?php echo htmlspecialchars($art['sentido_ley']); ?><br><br>
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

    <!-- FOOTER OSCURO ELEGANTE Y PERSONALIZADO -->
    <footer>
        <div class="footer-content">
            <p style="font-weight: bold; font-size:15px; letter-spacing:0.5px;">Elaborado orgullosamente con IA de G - 2026</p>
            <p class="tech-stack">PHP / CSS3 / MySQL (PDO) / JavaScript Nativo</p>
            <p style="font-size:11px; color:#718096; margin-top:8px; border-top: 1px solid #2d3748; padding-top:8px; max-width:400px; margin-left:auto; margin-right:auto;">
                Herramienta de indexación jurídica avanzada para el Concurso Público del SPEN.
            </p>
        </div>
    </footer>

    <!-- BOTÓN FLOTANTE INTERACTIVO TOP -->
    <button onclick="topFunction()" id="btnTop" title="Ir al principio de la página">▲</button>

    <script src="buscador.js"></script>
</body>
</html>

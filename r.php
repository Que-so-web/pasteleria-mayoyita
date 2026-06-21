<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

define('ADMIN_PASSWORD', 'allaenlafuentehabiaunchorrito'); 
$db = new SQLite3(__DIR__ . '../../data/postres.db');

$db->enableExceptions(true);

$error = '';
$success = '';

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
    } else {
        $error = 'Contraseña incorrecta.';
    }
}

$loggedIn = !empty($_SESSION['admin']);

if ($loggedIn) {

    // ACCIÓN: Actualizar Banner e Identidad de Inicio
   if (isset($_POST['update_banner'])) {
        $logo_posicion = $_POST['logo_posicion'];
        $logo_visible  = isset($_POST['logo_visible']) ? 1 : 0;
        $color_fondo   = $_POST['color_fondo'] ?? '#f7eaf0';
        $color_acento  = $_POST['color_acento'] ?? '#f58cd2'; // NUEVO: Captura el color de acento

        // Obtener valores actuales por si no se suben nuevos archivos
        $cfg_res = $db->query("SELECT * FROM configuracion WHERE id = 1");
        $cfg = $cfg_res->fetchArray(SQLITE3_ASSOC);
        $fondo_final = $cfg['fondo_banner'] ?? 'index_media/pan_conchitas.jpg';
        $logo_final  = $cfg['logo_banner'] ?? 'logo_circulo.png';

        $dir = 'index_media/';

        // Subida de imagen de fondo
        if (isset($_FILES['fondo_archivo']) && $_FILES['fondo_archivo']['error'] === UPLOAD_ERR_OK) {
            $nom_fondo = 'bg_' . time() . '_' . basename($_FILES['fondo_archivo']['name']);
if (move_uploaded_file($_FILES['fondo_archivo']['tmp_name'], __DIR__ . '/' . $dir . $nom_fondo)) {                $fondo_final = $dir . $nom_fondo;
            }
        }

        // Subida de imagen de logo
        if (isset($_FILES['logo_archivo']) && $_FILES['logo_archivo']['error'] === UPLOAD_ERR_OK) {
            $nom_logo = 'logo_' . time() . '_' . basename($_FILES['logo_archivo']['name']);
            if (move_uploaded_file($_FILES['logo_archivo']['tmp_name'], '../' . $dir . $nom_logo)) {
                $logo_final = $dir . $nom_logo;
            }
        }

        $stmt = $db->prepare("UPDATE configuracion SET fondo_banner = :f, logo_banner = :l, logo_posicion = :p, logo_visible = :v, color_fondo = :color, color_acento = :acento WHERE id = 1");
        $stmt->bindValue(':f', $fondo_final);
        $stmt->bindValue(':l', $logo_final);
        $stmt->bindValue(':p', $logo_posicion);
        $stmt->bindValue(':v', $logo_visible, SQLITE3_INTEGER);
        $stmt->bindValue(':color', $color_fondo); 
        $stmt->bindValue(':acento', $color_acento); // Vincula el nuevo color rosa/acento
        $stmt->execute();
        $success = "Diseño de portada e identidad actualizados.";
    }

    if (isset($_POST['add_categoria'])) {
        $nombre = trim($_POST['nombre_categoria']);
        if ($nombre !== '') {
            try {
                $stmt = $db->prepare("INSERT INTO categorias (nombre) VALUES (:n)");
                $stmt->bindValue(':n', $nombre);
                $stmt->execute();
                $success = "Categoría \"$nombre\" agregada.";
            } catch (Exception $e) {
                $error = "Esa categoría ya existe.";
            }
        }
    }

    if (isset($_POST['delete_categoria'])) {
        $id = (int)$_POST['cat_id'];
        $db->exec("DELETE FROM categorias WHERE id = $id");
        $success = "Categoría eliminada.";
    }

    // ACCIÓN: Agregar producto con visibilidad toggleable
    if (isset($_POST['add_producto'])) {
        $nombre           = trim($_POST['nombre']);
        $precio           = (float)$_POST['precio'];
        $precio_descuento = $_POST['precio_descuento'] !== '' ? (float)$_POST['precio_descuento'] : null;
        $id_cat           = (int)$_POST['id_categoria'];
        $fecha_ini        = $_POST['fecha_inicio'] ?: null;
        $fecha_fin        = $_POST['fecha_fin']    ?: null;
        $visible          = isset($_POST['visible']) ? 1 : 0; 

        $ruta_final = null;
        if (isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
            $directorio_destino = 'index_media/';
            $nombre_archivo = time() . '_' . basename($_FILES['imagen_archivo']['name']);
            $target_path = $directorio_destino . $nombre_archivo;

            if (move_uploaded_file($_FILES['imagen_archivo']['tmp_name'], '../' . $target_path)) {
                $ruta_final = $target_path; 
            } else {
                $error = "Error al mover la imagen al servidor.";
            }
        }

        if ($nombre !== '' && $ruta_final !== null) {
            $stmt = $db->prepare("
                INSERT INTO productos (id_categoria, nombre, precio, precio_descuento, ruta_de_imagen, fecha_inicio, fecha_fin, visible)
                VALUES (:cat, :nom, :pre, :predesc, :img, :fi, :ff, :vis)
            ");
            $stmt->bindValue(':cat', $id_cat);
            $stmt->bindValue(':nom', $nombre);
            $stmt->bindValue(':pre', $precio);
            $stmt->bindValue(':predesc', $precio_descuento);
            $stmt->bindValue(':img', $ruta_final); 
            $stmt->bindValue(':fi',  $fecha_ini);
            $stmt->bindValue(':ff',  $fecha_fin);
            $stmt->bindValue(':vis', $visible, SQLITE3_INTEGER);
            $stmt->execute();
            $success = "Producto \"$nombre\" agregado con su imagen.";
        } else {
            if (!$error) $error = "Debes seleccionar una imagen válida.";
        }
    }

    if (isset($_POST['toggle_visibilidad'])) {
        $id = (int)$_POST['prod_id'];
        $nuevo_estado = (int)$_POST['nuevo_estado'];
        $db->exec("UPDATE productos SET visible = $nuevo_estado WHERE id = $id");
        $success = "Visibilidad del producto actualizada.";
    }

    if (isset($_POST['delete_producto'])) {
        $id = (int)$_POST['prod_id'];
        $db->exec("DELETE FROM productos WHERE id = $id");
        $success = "Producto eliminado.";
    }
}

// Carga de datos para renderizar la interfaz
$categorias = [];
$res = $db->query("SELECT * FROM categorias ORDER BY nombre");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $categorias[] = $row;
}

$productos = [];
$cfg = ['logo_posicion' => 'center', 'logo_visible' => 1, 'color_fondo' => '#f7eaf0', 'color_acento' => '#f58cd2', 'fondo_banner' => 'index_media/pan_conchitas.jpg', 'logo_banner' => 'logo_circulo.png'];
if ($loggedIn) {
    $res = $db->query("
        SELECT p.*, c.nombre AS categoria_nombre
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id
        ORDER BY c.nombre, p.nombre
    ");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $productos[] = $row;
    }

    // Traer la configuración actual del banner e identidad
    $cfg_res = $db->query("SELECT * FROM configuracion WHERE id = 1");
    if ($cfg_row = $cfg_res->fetchArray(SQLITE3_ASSOC)) {
        $cfg = $cfg_row;
    }
}

$today = date('Y-m-d');

function isActive($p, $today) {
    $manual_visible = !isset($p['visible']) || $p['visible'] == 1;
    $after  = !$p['fecha_inicio'] || $p['fecha_inicio'] <= $today;
    $before = !$p['fecha_fin']    || $p['fecha_fin']    >= $today;
    return $manual_visible && $after && $before;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin · Los Postres de Mayoyita</title>
<style>
    :root {
        --bg:       #f4f4f4;
        --card:     #ffffff;
        --accent:   #fbff00;
        --dark:     #1a1a1a;
        --mid:      #555;
        --border:   #e0e0e0;
        --danger:   #e53e3e;
        --success:  #0da75a;
        --radius:   16px;
        --shadow:   0 4px 24px rgba(0,0,0,0.08);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Arial', sans-serif;
        background: var(--bg);
        color: var(--dark);
        min-height: 100vh;
    }

    header {
        background: var(--dark);
        padding: 1.2rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    header h1 {
        color: #fff;
        font-size: 1.4rem;
        letter-spacing: 0.02em;
    }
    header h1 span { color: var(--accent); }
    .btn-logout {
        background: transparent;
        border: 1px solid rgba(255,255,255,0.3);
        color: #fff;
        padding: .45rem 1.1rem;
        border-radius: 8px;
        cursor: pointer;
        font-size: .85rem;
        transition: background .2s;
    }
    .btn-logout:hover { background: rgba(255,255,255,0.1); }

    .container {
        max-width: 1100px;
        margin: 2.5rem auto;
        padding: 0 1.5rem;
    }

    .alert {
        padding: .9rem 1.2rem;
        border-radius: var(--radius);
        margin-bottom: 1.5rem;
        font-size: .95rem;
        font-weight: 500;
    }
    .alert-error   { background: #fff5f5; color: var(--danger); border: 1px solid #fed7d7; }
    .alert-success { background: #f0fff4; color: var(--success); border: 1px solid #c6f6d5; }

    .card {
        background: var(--card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    .card h2 {
        font-size: 1.3rem;
        margin-bottom: 1.5rem;
        padding-bottom: .75rem;
        border-bottom: 2px solid var(--accent);
        display: inline-block;
    }

    .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
        align-items: flex-end;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: .3rem;
        flex: 1;
        min-width: 140px;
    }
    label {
        font-size: .8rem;
        font-weight: 500;
        color: var(--mid);
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    input[type=text],
    input[type=number],
    input[type=date],
    select {
        border: 1.5px solid var(--border);
        border-radius: 10px;
        padding: .6rem .9rem;
        font-size: .95rem;
        color: var(--dark);
        background: #fafafa;
        transition: border-color .2s;
        width: 100%;
    }
    input:focus, select:focus {
        outline: none;
        border-color: #aaa;
        background: #fff;
    }

    .btn {
        padding: .6rem 1.4rem;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 500;
        font-size: .9rem;
        transition: transform .15s, box-shadow .15s;
        white-space: nowrap;
    }
    .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
    .btn-primary { background: var(--dark); color: #fff; }
    .btn-danger  { background: var(--danger); color: #fff; padding: .4rem .9rem; font-size: .8rem; }
    .btn-toggle { padding: .3rem .6rem; font-size: .8rem; border-radius: 6px; }

    .login-wrap {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--dark);
    }
    .login-box {
        background: var(--card);
        border-radius: 24px;
        padding: 3rem 2.5rem;
        width: 100%;
        max-width: 380px;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .login-box h2 {
        font-size: 1.8rem;
        margin-bottom: .4rem;
    }
    .login-box p { color: var(--mid); font-size: .9rem; margin-bottom: 2rem; }
    .login-box input[type=password] {
        border: 1.5px solid var(--border);
        border-radius: 10px;
        padding: .75rem 1rem;
        font-size: 1rem;
        width: 100%;
        margin-bottom: 1rem;
    }
    .login-box input[type=password]:focus { outline: none; border-color: #aaa; }
    .login-box .btn-primary { width: 100%; padding: .85rem; font-size: 1rem; }

    .table-wrap { overflow-x: auto; }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: .9rem;
    }
    th {
        text-align: left;
        padding: .6rem .9rem;
        background: var(--bg);
        color: var(--mid);
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .07em;
        font-weight: 500;
    }
    td { padding: .7rem .9rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #fafafa; }

    .badge {
        display: inline-block;
        padding: .2rem .7rem;
        border-radius: 99px;
        font-size: .75rem;
        font-weight: 500;
    }
    .badge-active   { background: #c6f6d5; color: var(--success); }
    .badge-inactive { background: #fed7d7; color: var(--danger); }

    .img-thumb {
        width: 44px;
        height: 44px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid var(--border);
    }
    .no-img {
        width: 44px; height: 44px;
        background: var(--bg);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #bbb; font-size: .7rem;
        border: 1px dashed var(--border);
    }

    .cat-list { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1.2rem; }
    .cat-chip {
        display: flex; align-items: center; gap: .5rem;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 99px;
        padding: .35rem .9rem;
        font-size: .88rem;
    }
    .cat-chip form { margin: 0; }
    .cat-chip button {
        background: none; border: none; cursor: pointer;
        color: var(--danger); font-size: 1rem; line-height: 1;
        padding: 0;
    }

    @media (max-width: 600px) {
        .form-group { min-width: 100%; }
        .card { padding: 1.2rem; }
    }
</style>
</head>
<body>

<?php if (!$loggedIn): ?>
<div class="login-wrap">
  <div class="login-box">
    <h2>Mayoyita</h2>
    <p>Panel de administración</p>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="password" name="password" placeholder="Contraseña" autofocus required>
      <button type="submit" class="btn btn-primary">Entrar</button>
    </form>
  </div>
</div>

<?php else: ?>
<header>
  <h1>Los Postres de <span>Mayoyita</span> · Admin</h1>
  <form method="POST">
    <button name="logout" class="btn-logout">Cerrar sesión</button>
  </form>
</header>

<div class="container">

  <?php if ($error):   ?><div class="alert alert-error"><?=   htmlspecialchars($error)   ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="card">
    <h2>Personalizar Portada e Identidad de Inicio</h2>
    <form method="POST" enctype="multipart/form-data">
      <div class="form-row">
        <div class="form-group">
          <label>Cambiar Imagen de Fondo (Mosaico/Banner)</label>
          <input type="file" name="fondo_archivo" accept="image/*">
        </div>
        <div class="form-group">
          <label>Cambiar Logo Central</label>
          <input type="file" name="logo_archivo" accept="image/*">
        </div>
      </div>
      <div class="form-row" style="margin-top:1rem; align-items: center;">
        <div class="form-group">
          <label>Posición del Logo</label>
          <select name="logo_posicion">
            <option value="center" <?= ($cfg['logo_posicion'] == 'center') ? 'selected' : '' ?>>Centrado</option>
            <option value="left" <?= ($cfg['logo_posicion'] == 'left') ? 'selected' : '' ?>>Izquierda</option>
            <option value="right" <?= ($cfg['logo_posicion'] == 'right') ? 'selected' : '' ?>>Derecha</option>
          </select>
        </div>
        <div class="form-group">
          <label>Color de Fondo del Sitio</label>
          <input type="color" name="color_fondo" value="<?= htmlspecialchars($cfg['color_fondo'] ?? '#f7eaf0') ?>" style="width:100%; height:40px; padding:0; cursor:pointer;">
        </div>
        <div class="form-group">
          <label>Color de Fondo del Sitio</label>
          <input type="color" name="color_fondo" value="<?= htmlspecialchars($cfg['color_fondo'] ?? '#f7eaf0') ?>" style="width:100%; height:40px; padding:0; cursor:pointer;">
        </div>
        <div class="form-group">
          <label>Color de Barra y Detalles (Rosa)</label>
          <input type="color" name="color_acento" value="<?= htmlspecialchars($cfg['color_acento'] ?? '#f58cd2') ?>" style="width:100%; height:40px; padding:0; cursor:pointer;">
        </div>


        <div class="form-group" style="justify-content: center; min-width:150px;">
          <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer; text-transform:none;">
            <input type="checkbox" name="logo_visible" value="1" <?= ($cfg['logo_visible'] == 1) ? 'checked' : '' ?> style="width:auto;"> Mostrar Logo sobre la imagen
          </label>
        </div>
        <button type="submit" name="update_banner" class="btn btn-primary">Actualizar Identidad</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h2>Categorías</h2>
    <div class="cat-list">
      <?php foreach ($categorias as $cat): ?>
        <div class="cat-chip">
          <?= htmlspecialchars($cat['nombre']) ?>
          <form method="POST">
            <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
            <button name="delete_categoria" title="Eliminar" onclick="return confirm('¿Eliminar categoría?')">×</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>

    <form method="POST">
      <div class="form-row">
        <div class="form-group">
          <label>Nueva categoría</label>
          <input type="text" name="nombre_categoria" placeholder="Ej: Donas" required>
        </div>
        <button type="submit" name="add_categoria" class="btn btn-primary">Agregar</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h2>Agregar producto</h2>
    <form method="POST" enctype="multipart/form-data">
      <div class="form-row">
        <div class="form-group" style="flex:2; min-width:180px;">
          <label>Nombre</label>
          <input type="text" name="nombre" placeholder="Pastel de chocolate" required>
        </div>
        <div class="form-group">
          <label>Precio (MXN)</label>
          <input type="number" name="precio" step="0.01" min="0" placeholder="150.00" required>
        </div>
        <div class="form-group">
          <label>Precio Descuento (Opcional)</label>
          <input type="number" name="precio_descuento" step="0.01" min="0" placeholder="120.00">
        </div>
        <div class="form-group">
          <label>Categoría</label>
          <select name="id_categoria" required>
            <option value="" disabled selected>Seleccionar…</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="flex:2; min-width:200px;">
          <label>Subir imagen del producto</label>
          <input type="file" name="imagen_archivo" accept="image/*" required>
        </div>
      </div>

      <div class="form-row" style="margin-top:.75rem;">
        <div class="form-group">
          <label>Disponible desde (opcional)</label>
          <input type="date" name="fecha_inicio">
        </div>
        <div class="form-group">
          <label>Disponible hasta (opcional)</label>
          <input type="date" name="fecha_fin">
        </div>
        <div class="form-group" style="justify-content: center; min-width:150px;">
          <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer; text-transform:none;">
            <input type="checkbox" name="visible" value="1" checked style="width:auto;"> Visible inmediatamente
          </label>
        </div>
        <button type="submit" name="add_producto" class="btn btn-primary" style="align-self:flex-end;">Guardar producto</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h2>Productos (<?= count($productos) ?>)</h2>
    <?php if (empty($productos)): ?>
      <p style="color:var(--mid); font-size:.9rem;">Aún no hay productos.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Img</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Precio</th>
            <th>Disponibilidad</th>
            <th>Estado hoy</th>
            <th>Visibilidad Manual</th> <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($productos as $p): ?>
          <tr>
            <td>
              <?php if ($p['ruta_de_imagen']): ?>
                <img src="../<?= htmlspecialchars($p['ruta_de_imagen']) ?>" class="img-thumb" onerror="this.style.display='none'">
              <?php else: ?>
                <div class="no-img">sin img</div>
              <?php endif; ?>
            </td>
            <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
            <td><?= htmlspecialchars($p['categoria_nombre'] ?? '—') ?></td>
            <td>
              <?php if (!empty($p['precio_descuento'])): ?>
                <span style="text-decoration: line-through; color: var(--mid); font-size: 0.85rem;">$<?= number_format($p['precio'], 2) ?></span>
                <span style="color: var(--danger); font-weight: bold;">$<?= number_format($p['precio_descuento'], 2) ?></span>
              <?php else: ?>
                <?= $p['precio'] ? '$' . number_format($p['precio'], 2) : '—' ?>
              <?php endif; ?>
            </td>
            <td style="font-size:.8rem; color:var(--mid);">
              <?php if ($p['fecha_inicio'] || $p['fecha_fin']): ?>
                <?= $p['fecha_inicio'] ?: '∞' ?> → <?= $p['fecha_fin'] ?: '∞' ?>
              <?php else: ?>
                Siempre
              <?php endif; ?>
            </td>
            <td>
              <?php if (isActive($p, $today)): ?>
                <span class="badge badge-active">Activo</span>
              <?php else: ?>
                <span class="badge badge-inactive">Inactivo</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="POST" style="margin:0;">
                <input type="hidden" name="prod_id" value="<?= $p['id'] ?>">
                <?php if (!isset($p['visible']) || $p['visible'] == 1): ?>
                  <input type="hidden" name="nuevo_estado" value="0">
                  <button type="submit" name="toggle_visibilidad" class="btn btn-toggle badge-active" style="border:1px solid var(--success); color:var(--success);">👁️ Público</button>
                <?php else: ?>
                  <input type="hidden" name="nuevo_estado" value="1">
                  <button type="submit" name="toggle_visibilidad" class="btn btn-toggle badge-inactive" style="border:1px solid var(--danger); color:var(--danger);">🚫 Oculto</button>
                <?php endif; ?>
              </form>
            </td>
            <td>
              <form method="POST">
                <input type="hidden" name="prod_id" value="<?= $p['id'] ?>">
                <button name="delete_producto" class="btn btn-danger" onclick="return confirm('¿Eliminar producto?')">Eliminar</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>
<?php endif; ?>
</body>
</html>

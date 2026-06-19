<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db = new SQLite3('/home/a220214757/data/postres.db');

$today = date('Y-m-d');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: index.php'); exit; }

$cat_res = $db->query("SELECT * FROM categorias WHERE id = $id");
$categoria = $cat_res ? $cat_res->fetchArray(SQLITE3_ASSOC) : null;
if (!$categoria) { header('Location: index.php'); exit; }

$productos = [];
$res = $db->query("
    SELECT * FROM productos
    WHERE id_categoria = $id
      AND (fecha_inicio IS NULL OR fecha_inicio <= '$today')
      AND (fecha_fin   IS NULL OR fecha_fin   >= '$today')
      AND (visible = 1)
    ORDER BY nombre
");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $productos[] = $row;
}
$db->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($categoria['nombre']) ?> · Categoria</title>
    <link rel="stylesheet" href="style2.css">
</head>
<body style="background-color: rgb(247, 234, 240);">

    <div class="contenidoCategoria">
        <h1 id="titulo"><?= mb_strtoupper(htmlspecialchars($categoria['nombre']), 'UTF-8') ?></h1>

        <div id="display">
            <?php if (empty($productos)): ?>
                <div class="productoA-espacio">
                    <div class="productoA-informacion">
                        <h2>Próximamente</h2>
                        <p>No hay productos disponibles en este momento para esta categoría.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($productos as $p): ?>
                    <div class="productoA-espacio">
                        <?php if (!empty($p['ruta_de_imagen'])): ?>
                            <img src="<?= htmlspecialchars($p['ruta_de_imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>">
                        <?php else: ?>
                            <img src="imagenes/default.jpeg" alt="Postre alternativo">
                        <?php endif; ?>

                        <div class="productoA-informacion">
                            <h2><?= htmlspecialchars($p['nombre']) ?></h2>
                            
                            <?php if (!empty($p['descripcion'])): ?>
                                <p><?= htmlspecialchars($p['descripcion']) ?></p>
                            <?php else: ?>
                                <p>Suave, fresco y artesanal.</p>
                            <?php endif; ?>

                            <?php if (!empty($p['precio'])): ?>
                            <div class="precio-contenedor">
                                <?php if (!empty($p['precio_descuento'])): ?>
                                    <span class="precio" style="text-decoration: line-through; color: #888; font-size: 0.9rem;">$<?= number_format($p['precio'], 2) ?> MXN</span>
                                    <span class="precio" style="color: #e53e3e; font-weight: bold;">$<span class="precio-num"><?= number_format($p['precio_descuento'], 2) ?></span> MXN</span>
                                <?php else: ?>
                                    <span class="precio">$<span class="precio-num"><?= number_format($p['precio'], 2) ?></span> MXN</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                            <button>Me interesa</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>

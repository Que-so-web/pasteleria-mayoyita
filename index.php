<?php
/* =============================================================================
   BACKEND - Conexion a la Base de datos y operaciones de lectura.
   nada de lo que esté aqui se envia al navegador. solo lo lee la base de datos.
   aqui se conecta a la base de datos sqlite y obtiene todas las categorias. en este archivo "POR EL MOMENTO" la unica variable pasada del bkac al front es $categorias.
   si quieren agregar una variable mas diganle al cons y el checa como.
============================================================================= */
$db = new SQLite3(__DIR__ . '../../data/postres.db');
$today = date('Y-m-d');

$categorias = [];
$res = $db->query("SELECT * FROM categorias ORDER BY nombre");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $categorias[] = $row;
}

$cfg_res = $db->query("SELECT * FROM configuracion WHERE id = 1");
$cfg = $cfg_res->fetchArray(SQLITE3_ASSOC);

// Obtenemos imágenes de productos activos para el carrusel
$prod_imgs = [];
$img_res = $db->query("SELECT ruta_de_imagen FROM productos WHERE visible = 1 AND ruta_de_imagen != '' AND ruta_de_imagen IS NOT NULL");
while ($img_row = $img_res->fetchArray(SQLITE3_ASSOC)) {
    $prod_imgs[] = $img_row['ruta_de_imagen'];
}

// Si hay pocas imágenes, duplicamos el array para que el bucle infinito funcione fluido
if (count($prod_imgs) > 0 && count($prod_imgs) < 12) {
    $prod_imgs = array_merge($prod_imgs, $prod_imgs, $prod_imgs);
}

$db->close();
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title> Pasteleria Mayoyita </title>
        <link rel="stylesheet" href="style2.css?v=<?= time() ?>">
        
        <style>
            body .navbar, 
            body .divisionCategorias, 
            body .item2 p,
            body .productoA-informacion h2,
            body #titulo {
                background-color: <?= htmlspecialchars($cfg['color_acento'] ?? '#f58cd2') ?> !important;
            }
        </style>
    </head>
    
   <body style="background-color: <?= htmlspecialchars($cfg['color_fondo'] ?? '#f7eaf0') ?>;">
    
        <div class="navbar">
            <div class="logo">Pasteleria Mayoyita</div>
            <ul class="menu">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="#categorias-section">Categorias</a></li>
                <li><a href="#contacto-section">Contacto</a></li>
            </ul>
        </div>

        <div class="biggercontainer">
            <?php if (($cfg['tipo_portada'] ?? 'static') === 'carrusel' && count($prod_imgs) > 0): ?>
                <?php 
                    $col1_imgs = $prod_imgs;
                    $col2_imgs = array_reverse($prod_imgs);
                    $col3_imgs = $prod_imgs;

                    shuffle($col1_imgs);
                    shuffle($col3_imgs);
                ?>
                <div class="mosaic-scroll-container">
                    
                    <div class="mosaic-col col-up">
                        <div class="track">
                            <?php foreach ($col1_imgs as $img): ?><img src="<?= htmlspecialchars($img) ?>"><?php endforeach; ?>
                            <?php foreach ($col1_imgs as $img): ?><img src="<?= htmlspecialchars($img) ?>"><?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mosaic-col col-down">
                        <div class="track">
                            <?php foreach ($col2_imgs as $img): ?><img src="<?= htmlspecialchars($img) ?>"><?php endforeach; ?>
                            <?php foreach ($col2_imgs as $img): ?><img src="<?= htmlspecialchars($img) ?>"><?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mosaic-col col-up">
                        <div class="track">
                            <?php foreach ($col3_imgs as $img): ?><img src="<?= htmlspecialchars($img) ?>"><?php endforeach; ?>
                            <?php foreach ($col3_imgs as $img): ?><img src="<?= htmlspecialchars($img) ?>"><?php endforeach; ?>
                        </div>
                    </div>
                    
                </div>
            <?php else: ?>
                <div class="banner-wrapper" style="background-image: url('<?= htmlspecialchars($cfg['fondo_banner'] ?? 'index_media/pan_conchitas.jpg') ?>');"></div>
            <?php endif; ?>
            
            <?php if (!isset($cfg['logo_visible']) || $cfg['logo_visible'] == 1): ?>
                <img class="logoteaMayoyita pos-<?= htmlspecialchars($cfg['logo_posicion'] ?? 'center') ?>" src="<?= htmlspecialchars($cfg['logo_banner'] ?? 'logo_circulo.png') ?>" alt="Logo" onerror="this.style.display='none'">
            <?php endif; ?>
        </div>

        <div class="divisionCategorias" id="categorias-section">
            <p>Categorias</p>
        </div>

        <div class="container2">
            <?php foreach ($categorias as $cat): ?>
                <?php
                    $cid = (int)$cat['id'];
                    $cat_db = new SQLite3(__DIR__ . '../../data/postres.db');
                    
                    $img_res = $cat_db->query("
                        SELECT ruta_de_imagen FROM productos
                        WHERE id_categoria = $cid
                          AND visible = 1
                          AND ruta_de_imagen != ''
                          AND ruta_de_imagen IS NOT NULL
                          AND (fecha_inicio IS NULL OR fecha_inicio <= '$today')
                          AND (fecha_fin   IS NULL OR fecha_fin   >= '$today')
                        LIMIT 1
                        ");                   
                    $img_row = $img_res ? $img_res->fetchArray(SQLITE3_ASSOC) : false;
                    $cover = $img_row ? $img_row['ruta_de_imagen'] : 'index_media/pan_conchitas.jpg';
                    $cat_db->close();

                    $slug = strtolower(trim($cat['nombre']));
                    $emoji_class = "emoji-$slug";
                ?>

                <a href="categoria.php?id=<?= $cat['id'] ?>" class="item2">
                    <img src="<?= htmlspecialchars($cover) ?>" onerror="this.src='index_media/pan_conchitas.jpg'">
                    <p><?= htmlspecialchars($cat['nombre']) ?></p>
                </a>

            <?php endforeach; ?>
        </div>

        <div class="contacto" id="contacto-section">
            <p> Contacto </p>
            <p> Teléfono: 123-456-7890 </p>
            <p> Correo electrónico: </p>
        </div>

    </body>
</html>
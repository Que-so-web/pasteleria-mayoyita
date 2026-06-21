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

$db->close();
?>

<!-- ===========================================================================
  AQUI EMPIEZA EL FRONTEND.
  Todo lo que pongan a partir de aqui puede ser accedido por el navegador.
=========================================================================== -->


<!DOCTYPE html>
<html lang="es">
   <head>
    <meta charset="UTF-8">
    <title> Pasteleria Mayoyita </title>
    <link rel="stylesheet" href="style2.css?v=<?= time() ?>">
</head>    
   <body style="background-color: <?= htmlspecialchars($cfg['color_fondo'] ?? '#f7eaf0') ?>;">        <!--navbar-->
        <nav class ="navbar">
        <div class = "logo"> Pasteleria Mayoyita </div>
            <!--menu para la barra-->
            <ul class="menu">
                <li><a href = "#inicio-section"> Inicio </a></li>
                <li><a href = "#menu-section"> Menú </a></li>
                <li><a href = "#contacto-section"> Contacto </a></li>
            </ul>
        </nav>

       <div class="biggercontainer">
            <style>
            .navbar, 
            .divisionCategorias, 
            .item2 p,
            .productoA-informacion h2,
            #titulo {
                background-color: <?= htmlspecialchars($cfg['color_acento'] ?? '#f58cd2') ?> !important;
            }
            
        
        </style>

            <div class="banner-wrapper" style="background-image: url('<?= htmlspecialchars($cfg['fondo_banner'] ?? 'index_media/pan_conchitas.jpg') ?>');"></div>
            
            <?php if (!isset($cfg['logo_visible']) || $cfg['logo_visible'] == 1): ?>
                <img class="logoteaMayoyita pos-<?= htmlspecialchars($cfg['logo_posicion'] ?? 'center') ?>" src="<?= htmlspecialchars($cfg['logo_banner'] ?? 'logo_circulo.png') ?>" alt="Logo" onerror="this.style.display='none'">
            <?php endif; ?>
        </div>

        <p class="divisionCategorias"> Categorias </p>

        <!--Grid para el display de abajo-->

<!-- this section...-->
 <div class = "lowmenu" id="menu-section">
  <!-- BACKEND: en esta seccion se accede a $categorias para poder hacer el dispay de mosaicos con las categorias de productos en cada iteracipon del ciclo for tambien se pide una imagen para mostrar en cada mosaico en caso de que no haya uno definido -->
	    <?php foreach ($categorias as $cat): ?>

                <?php
                    /* BACKEND: aqui es donde se determina si un producto esta dosponible para ser mostrado
                     *  Active significa que puede ser mostrado porque o no a expirado o no tiene limite de fecha.
		     */ 

                    $cat_db = new SQLite3(__DIR__ . '../../data/postres.db');
                    $cid = (int)$cat['id'];
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
                    $cover = $img_row ? $img_row['ruta_de_imagen'] : null;
                    $cat_db->close();

                    $slug = strtolower(trim($cat['nombre']));
                    $emoji_class = "emoji-$slug";
                ?>

		<!-- FRONTEND - Category card.
                     href points to categoria.php passing the category id as a GET parameter.
		     The id is read by the backend in categoria.php to filter products. -->

                <a href="categoria.php?id=<?= $cat['id'] ?>" class="item2">
                    <!-- BACKEND: se conecta a la base de datos para obtener la imagen de cada producto y si no tiene pone un placeholder-->
		<img src="<?= htmlspecialchars($cover) ?>">
                 <p><?= htmlspecialchars($cat['nombre']) ?></p>

                      <!-- cambiar por una imagen por defecto -->
                  
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

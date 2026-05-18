<?php
$db = new SQLite3(__DIR__ . '/data/postres.db');
$today = date('Y-m-d');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: index.php'); exit; }

// Get category
$cat_res = $db->query("SELECT * FROM categorias WHERE id = $id");
$categoria = $cat_res ? $cat_res->fetchArray(SQLITE3_ASSOC) : null;
if (!$categoria) { header('Location: index.php'); exit; }

// Get active products
$productos = [];
$res = $db->query("
    SELECT * FROM productos
    WHERE id_categoria = $id
      AND (fecha_inicio IS NULL OR fecha_inicio <= '$today')
      AND (fecha_fin   IS NULL OR fecha_fin   >= '$today')
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
    <meta name="viewport" content="width=device-width">
    <title><?= htmlspecialchars($categoria['nombre']) ?> · Los Postres de Mayoyita</title>
    <style>
        * {
            font-family: 'Times New Roman';
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background-color: #f4f4f4;
            min-height: 100vh;
        }

        /* ── NAV ── */
        .top-bar {
            position: fixed;
            top: 0;
            width: 100%;
            background-color: transparent;
            display: flex;
            justify-content: right;
            padding: 20px 0;
            z-index: 10;
        }

        .top-bar a {
            color: #ffffff;
            text-decoration: none;
            margin: 0 20px;
            font-weight: bold;
            font-size: 2.1rem;
            text-shadow: 1px 1px 4px #000000e6;
            transition: color 0.3s;
        }

        .top-bar a:hover { color: #fbff00; }

        /* ── HERO BANNER ── */
        .hero {
            width: 100%;
            min-height: 28vh;
            background: #1a1a1a;
            display: flex;
            align-items: flex-end;
            padding: 2.5rem 3rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #1a1a1a 60%, #2e2e2e);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            color: #fbff00;
            text-decoration: none;
            font-size: 1rem;
            margin-bottom: .8rem;
            opacity: .8;
            transition: opacity .2s;
        }
        .back-link:hover { opacity: 1; }

        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 5rem);
            color: #fff;
            font-weight: normal;
            letter-spacing: -.01em;
            line-height: 1;
        }

        .hero h1 span {
            color: #fbff00;
        }

        .hero-count {
            color: rgba(255,255,255,0.4);
            font-size: 1rem;
            margin-top: .5rem;
        }

        .divisor-gradiente {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 80px;
            background: linear-gradient(to bottom, transparent, #f4f4f4);
            z-index: 3;
        }

        /* ── MOSAIC ── */
        .mosaic {
            max-width: 92vw;
            margin: 3rem auto 5rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 2rem;
            justify-items: center;
        }

        /* ── POLAROID CARD ── */
        .polaroid {
            background: #fff;
            padding: 12px 12px 40px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.12), 0 1px 3px rgba(0,0,0,0.08);
            border-radius: 2px;
            width: 100%;
            max-width: 280px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            cursor: default;
            position: relative;
        }

        /* slight random tilt per card via nth-child */
        .polaroid:nth-child(3n+1) { transform: rotate(-1.2deg); }
        .polaroid:nth-child(3n+2) { transform: rotate(0.8deg); }
        .polaroid:nth-child(3n+3) { transform: rotate(-0.4deg); }

        .polaroid:hover {
            transform: rotate(0deg) scale(1.04) translateY(-6px) !important;
            box-shadow: 0 16px 40px rgba(0,0,0,0.18);
            z-index: 2;
        }

        .polaroid-img {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            display: block;
            background: #eee;
        }

        /* placeholder when no image */
        .polaroid-placeholder {
            width: 100%;
            aspect-ratio: 1 / 1;
            background: linear-gradient(135deg, #ececec, #d8d8d8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
        }

        .polaroid-caption {
            padding-top: .9rem;
            text-align: center;
        }

        .polaroid-caption .nombre {
            font-size: 1.05rem;
            font-weight: bold;
            color: #222;
            display: block;
            margin-bottom: .3rem;
        }

        .polaroid-caption .precio {
            font-size: 1rem;
            color: #555;
        }

        /* price tag accent */
        .polaroid-caption .precio-num {
            font-weight: bold;
            color: #1a1a1a;
        }

        /* ── EMPTY STATE ── */
        .empty {
            text-align: center;
            padding: 5rem 2rem;
            color: #aaa;
            font-size: 1.1rem;
            grid-column: 1 / -1;
        }
        .empty span { font-size: 3rem; display: block; margin-bottom: 1rem; }
    </style>
</head>
<body>

    <nav class="top-bar">
        <a href="index.php">inicio</a>
        <a href="index.php#menu">menú</a>
        <a href="index.php#contacto">contacto</a>
    </nav>

    <div class="hero">
        <div class="hero-content">
            <a href="index.php" class="back-link">← volver</a>
            <h1><?= htmlspecialchars($categoria['nombre']) ?></h1>
            <p class="hero-count"><?= count($productos) ?> producto<?= count($productos) !== 1 ? 's' : '' ?> disponible<?= count($productos) !== 1 ? 's' : '' ?></p>
        </div>
        <div class="divisor-gradiente"></div>
    </div>

    <div class="mosaic">
        <?php if (empty($productos)): ?>
            <div class="empty">
                <span>🍰</span>
                Próximamente productos en esta categoría.
            </div>
        <?php else: ?>
            <?php foreach ($productos as $p): ?>
                <div class="polaroid">
                    <?php if ($p['ruta_de_imagen']): ?>
                        <img
                            src="<?= htmlspecialchars($p['ruta_de_imagen']) ?>"
                            alt="<?= htmlspecialchars($p['nombre']) ?>"
                            class="polaroid-img"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >
                        <div class="polaroid-placeholder" style="display:none;">🍰</div>
                    <?php else: ?>
                        <div class="polaroid-placeholder">🍰</div>
                    <?php endif; ?>

                    <div class="polaroid-caption">
                        <span class="nombre"><?= htmlspecialchars($p['nombre']) ?></span>
                        <?php if ($p['precio']): ?>
                            <span class="precio">$<span class="precio-num"><?= number_format($p['precio'], 2) ?></span> MXN</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>

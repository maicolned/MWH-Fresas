<?php
// =====================================================================
//  pie.php — el pie de página, igual en todas las páginas.
//  Los datos salen de la tabla configuracion: se cambian desde el panel.
// =====================================================================
?>
    <!-- CONTACTO -->
    <footer id="contacto">
        <h2>Haz tu pedido</h2>

        <p>
            <a class="boton boton-whatsapp" href="<?= limpiar(enlace_whatsapp("Hola, quiero hacer un pedido 🍓")) ?>" target="_blank" rel="noopener">
                WhatsApp: <?= limpiar(config("whatsapp")) ?>
            </a>
        </p>
        <p><?= limpiar(config("ciudad")) ?></p>
        <p><?= limpiar(config("horario")) ?></p>

        <p class="creditos">Página hecha por: <?= limpiar(config("integrantes")) ?> · <a href="admin/login.php">Ingreso del personal</a></p>
    </footer>

    <?php if (!empty($scripts)): foreach ($scripts as $s): ?>
        <script src="<?= version($s) ?>"></script>
    <?php endforeach; endif; ?>
</body>
</html>

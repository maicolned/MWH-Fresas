<?php
// =====================================================================
//  nosotros.php — quiénes somos (antes era nosotros.html).
//  El texto es el que escribieron ustedes.
// =====================================================================
require "includes/funciones.php";

$titulo = "Nosotros";
$activa = "nosotros";
require "includes/encabezado.php";
?>

    <!-- NOSOTROS -->
    <section class="nosotros" id="nosotros">
        <h2>Sobre nosotros 🍓</h2>
        <p>
            Somos un emprendimiento dedicado a preparar
            deliciosas fresas con crema, utilizando ingredientes
            frescos y los mejores toppings para crear tu antojo perfecto.
        </p>
    </section>

    <section class="porque-mwh">
        <h2>¿Por qué elegir <?= limpiar(config("nombre")) ?>? 🍓</h2>

        <div class="beneficios-mwh">
            <div class="beneficio-mwh">
                <span>🍓</span>
                <h3>Fresas frescas</h3>
                <p>Preparadas con mucho amor y dedicación.</p>
            </div>

            <div class="beneficio-mwh">
                <span>🍫</span>
                <h3>A tu gusto</h3>
                <p>Elige tus toppings y tu salsa favorita.</p>
            </div>

            <div class="beneficio-mwh">
                <span>💗</span>
                <h3>Hecho para ti</h3>
                <p>Creamos cada pedido pensando en tus gustos.</p>
            </div>
        </div>
    </section>

<?php require "includes/pie.php"; ?>

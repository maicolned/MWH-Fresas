function hacerPedido() {

    alert(
        "🍓 ¡Gracias por tu interés!\n\n" +
        "Puedes realizar tu pedido por WhatsApp."
    );

}


function seleccionarProducto(producto) {

    alert(
        "🍓 Seleccionaste:\n\n" +
        producto +
        "\n\nAhora puedes personalizar tu pedido."
    );

    document.getElementById("personaliza").scrollIntoView({
        behavior: "smooth"
    });

}


function personalizar() {

    const seleccionados = document.querySelectorAll(
        'input[name="topping"]:checked'
    );

    const salsa = document.querySelector(
        'input[name="salsa"]:checked'
    );

    if (seleccionados.length < 1 || seleccionados.length > 2) {
    alert("Debes elegir 1 o 2 toppings 🍓");
    return;
}

    if (!salsa) {
        alert("Debes elegir una salsa 🥄");
        return;
    }

    let toppings = [];

    seleccionados.forEach(topping => {
        toppings.push(topping.value);
    });

    // Datos del pedido
    const datos = new FormData();

    datos.append("producto", "Fresas con crema");
    datos.append("toppings", toppings.join(", "));
    datos.append("salsa", salsa.value);
    datos.append("precio", "0");

    // Guardar pedido en la base de datos
    fetch("guardar_pedido.php", {
        method: "POST",
        body: datos
    })
    .then(respuesta => respuesta.text())
    .then(resultado => {

        console.log(resultado);

        // Abrir WhatsApp
        let mensaje =
            " Hola, quiero hacer un pedido.%0A%0A" +
            " Toppings: " + toppings.join(", ") + "%0A" +
            " Salsa: " + salsa.value;

        let numero = "573238263347";

        window.open(
            "https://web.whatsapp.com/send?phone=" +
            numero +
            "&text=" +
            mensaje,
            "_blank"
        );

    })
    .catch(error => {
        console.error(error);
        alert("No se pudo guardar el pedido 😢");
    });
}

<<<<<<< HEAD
    
=======
    let mensaje = "🍓 TU PEDIDO 🍓\n\n";

    mensaje += "Salsa: " + salsa.value + "\n";



    if (toppings.length > 0) {

        mensaje +=
            "Toppings: " +
            toppings.join(", ");

    } else {

        mensaje += "Sin toppings";

    }



    alert(mensaje + "\n\n¡Pedido confirmado! 💕");



>>>>>>> b3d56ad8098e48c125edc0d9cbc50f1ba4165e3c

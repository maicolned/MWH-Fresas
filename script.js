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

    let toppings = [];

    document.querySelectorAll('input[type="checkbox"]:checked')
    .forEach(function(topping) {
        toppings.push(topping.value);
    });

    let salsa = document.querySelector('input[name="salsa"]:checked');

    if (toppings.length === 0) {
        alert("Selecciona al menos un topping");
        return;
    }

    if (!salsa) {
        alert("Selecciona una salsa");
        return;
    }

    let mensaje =
        "🍓 NUEVO PEDIDO - MWH FRESAS\n\n" +
        "Toppings: " + toppings.join(", ") + "\n" +
        "Salsa: " + salsa.value + "\n\n" +
        "Hola, quiero realizar este pedido.";

    let numero = "573238263347";

    let enlace =
        "https://web.whatsapp.com/send?phone=" +
        numero +
        "&text=" +
        encodeURIComponent(mensaje);

    window.open(enlace, "_blank");
}

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


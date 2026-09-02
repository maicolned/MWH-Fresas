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
//toppings seleccionados
    let toppings = [];

    //Busca los toppings que estén marcados
    document.querySelectorAll('')

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


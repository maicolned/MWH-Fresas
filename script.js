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

    document.querySelectorAll('input[name = "topping"]: checked')
    .forEach(function(topping){
        toppings.push(topping.value);
    });
//salsa seleccionada
let salsa = document.querySelector(
    'input[name = "salsa" ]: checked'
);
if (toppings.length === 0){
    alert (" 🍓 Selecciona al menos un topping");
    return;
}
if (!salsa){
    alert("🍓 Selecciona una salsa");
    return;

}
//crear mensaje
let mensaje =
 " 🍓 *NUEVO PEDIDO - MWH FRESAS* %0A%0A"+
 "toppings: "+
 encodeURIComponent(toppings,join(" , "))+
 "%0A" +
 "Salsa: "+
 encodeURIComponent(salsa.value)+
 "%0A%0A" +
 "¡Hola! Quiero realizar este pedido ☺";

 //Abrir WhatsApp
 window.open(
    "https://wa.me/573238263347?text="+ mensaje,
    "_blank"
 );

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


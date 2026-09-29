<?php
// admin/salir.php — cierra la sesión y vuelve al ingreso.
require "seguridad.php";

$_SESSION = [];
session_destroy();
header("Location: login.php");
exit;

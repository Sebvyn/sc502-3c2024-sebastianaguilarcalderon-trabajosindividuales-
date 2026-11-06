<?php
session_start();
require_once "funciones.php";

if (!isset($_SESSION["transacciones"])) {
    $_SESSION["transacciones"] = [];
    $_SESSION["siguienteId"] = 1;
    $cargarIniciales = true;
} else {
    $cargarIniciales = false;
}
$transacciones = &$_SESSION["transacciones"];   

$error = "";
$exito = "";

if (isset($_SESSION["mensaje"])) {
    $error = $_SESSION["mensaje"]["error"];
    $exito = $_SESSION["mensaje"]["exito"];
    unset($_SESSION["mensaje"]);
}

if ($cargarIniciales) {
    registrarTransaccion($_SESSION["siguienteId"]++, "Supermercado Automercado", 45250.50);
    registrarTransaccion($_SESSION["siguienteId"]++, "Gasolinera Delta", 28000);
    registrarTransaccion($_SESSION["siguienteId"]++, "Restaurante Il Pomodoro", 35900);
    registrarTransaccion($_SESSION["siguienteId"]++, "Suscripción streaming", 7500);
    registrarTransaccion($_SESSION["siguienteId"]++, "Farmacia Fischel", 12450.75);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    if ($accion === "agregar") {
        $descripcion = trim($_POST["descripcion"] ?? "");
        $montoTexto  = trim($_POST["monto"] ?? "");

        if ($descripcion === "" || $montoTexto === "") {
            $error = "Todos los campos son obligatorios.";
        } elseif (!is_numeric($montoTexto) || (float) $montoTexto <= 0) {
            $error = "El monto debe ser un número mayor a cero.";
        } else {
            registrarTransaccion($_SESSION["siguienteId"]++, $descripcion, $montoTexto);
            $exito = "Transacción registrada correctamente.";
        }

        $_SESSION["mensaje"] = ["error" => $error, "exito" => $exito];
        header("Location: index.php");
        exit;
    } elseif ($accion === "reiniciar") {
        session_destroy();
        header("Location: index.php");
        exit;
    }
}

$estado = generarEstadoDeCuenta();
$archivoOk = true;
$errorArchivo = "";
try {
    guardarEstadoDeCuenta($estado["texto"]);
} catch (Exception $e) {
    $archivoOk = false;
    $errorArchivo = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Estado de cuenta – Tarjeta de crédito</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
  <header class="encabezado">
    <h1>Estado de cuenta</h1>
    <p>SC-502 Ambiente Web Cliente/Servidor · Práctica Programada #3 (PHP)</p>
  </header>

  <main>
    <section class="panel" aria-labelledby="t-registro">
      <h2 id="t-registro">Registrar transacción</h2>
      <form method="post" action="index.php">
        <input type="hidden" name="accion" value="agregar">
        <div class="campo">
          <label for="descripcion">Descripción</label>
          <input type="text" id="descripcion" name="descripcion" autocomplete="off">
        </div>
        <div class="campo">
          <label for="monto">Monto (₡)</label>
          <input type="number" id="monto" name="monto" min="0" step="0.01">
        </div>
        <button type="submit" class="btn">Registrar</button>
      </form>
      <?php if ($error !== ""): ?>
        <p class="mensaje error" role="alert"><?= htmlspecialchars($error) ?></p>
      <?php elseif ($exito !== ""): ?>
        <p class="mensaje exito" role="status"><?= htmlspecialchars($exito) ?></p>
      <?php endif; ?>
    </section>

    <section class="panel" aria-labelledby="t-trans">
      <h2 id="t-trans">Transacciones registradas</h2>
      <div class="tabla-contenedor">
        <table>
          <thead>
            <tr><th scope="col">ID</th><th scope="col">Descripción</th><th scope="col">Monto</th></tr>
          </thead>
          <tbody>
            <?php foreach ($transacciones as $t): ?>
              <tr>
                <td><?= (int) $t["id"] ?></td>
                <td><?= htmlspecialchars($t["descripcion"]) ?></td>
                <td class="monto"><?= colones($t["monto"]) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="panel" aria-labelledby="t-resumen">
      <h2 id="t-resumen">Resumen del estado de cuenta</h2>
      <dl class="resumen">
        <div><dt>Monto total de contado</dt><dd><?= colones($estado["contado"]) ?></dd></div>
        <div><dt>Interés aplicado (2.6 %)</dt><dd><?= colones($estado["intereses"]) ?></dd></div>
        <div><dt>Monto total con interés</dt><dd><?= colones($estado["conInteres"]) ?></dd></div>
        <div><dt>Cash back (0.1 % del contado)</dt><dd class="descuento">- <?= colones($estado["cashback"]) ?></dd></div>
        <div class="final"><dt>Monto final a pagar</dt><dd><?= colones($estado["montoFinal"]) ?></dd></div>
      </dl>

      <?php if ($archivoOk): ?>
        <p class="mensaje exito">Se generó el archivo <a href="estado_cuenta.txt" target="_blank">estado_cuenta.txt</a> con el detalle completo.</p>
      <?php else: ?>
        <p class="mensaje error"><?= htmlspecialchars($errorArchivo) ?> Revise los permisos de la carpeta.</p>
      <?php endif; ?>

      <form method="post" action="index.php" class="reiniciar">
        <input type="hidden" name="accion" value="reiniciar">
        <button type="submit" class="btn secundario">Reiniciar ejemplo</button>
      </form>
    </section>
  </main>

  <footer class="pie"><p>Universidad Fidélitas · Práctica Programada #3</p></footer>
</body>
</html>

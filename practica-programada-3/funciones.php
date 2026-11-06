<?php

const TASA_INTERES = 0.026;   
const TASA_CASHBACK = 0.001;  

$transacciones = [];

function registrarTransaccion($id, $descripcion, $monto)
{
    global $transacciones;

    array_push($transacciones, [
        "id"          => $id,
        "descripcion" => $descripcion,
        "monto"       => (float) $monto
    ]);
}

function colones($monto)
{
    return "₡" . number_format($monto, 2, ",", ".");
}

function rellenar($texto, $ancho, $derecha = false)
{
    $faltan = max(0, $ancho - mb_strlen((string) $texto));
    $espacios = str_repeat(" ", $faltan);
    return $derecha ? $espacios . $texto : $texto . $espacios;
}

function generarEstadoDeCuenta()
{
    global $transacciones;

    $contado = 0;
    foreach ($transacciones as $t) {
        $contado += $t["monto"];
    }

    $intereses  = $contado * TASA_INTERES;
    $conInteres = $contado + $intereses;

    $cashback = $contado * TASA_CASHBACK;

    $montoFinal = $conInteres - $cashback;

    $linea = str_repeat("=", 64) . "\n";
    $texto  = $linea;
    $texto .= "                 ESTADO DE CUENTA - TARJETA DE CREDITO\n";
    $texto .= $linea;
    $texto .= "Fecha de emision: " . date("d/m/Y H:i") . "\n\n";
    $texto .= "TRANSACCIONES REGISTRADAS\n";
    $texto .= str_repeat("-", 64) . "\n";
    $texto .= rellenar("ID", 5) . rellenar("Descripcion", 38) . rellenar("Monto", 21, true) . "\n";
    $texto .= str_repeat("-", 64) . "\n";

    foreach ($transacciones as $t) {
        $texto .= rellenar($t["id"], 5)
                . rellenar(mb_substr($t["descripcion"], 0, 36), 38)
                . rellenar(colones($t["monto"]), 21, true)
                . "\n";
    }

    $texto .= str_repeat("-", 64) . "\n\n";
    $texto .= "RESUMEN\n";
    $texto .= str_repeat("-", 64) . "\n";
    $texto .= "Cantidad de transacciones:        " . count($transacciones) . "\n";
    $texto .= "Monto total de contado:           " . colones($contado) . "\n";
    $texto .= "Interes aplicado (2.6%):          " . colones($intereses) . "\n";
    $texto .= "Monto total con interes:          " . colones($conInteres) . "\n";
    $texto .= "Cash back (0.1% del contado):     -" . colones($cashback) . "\n";
    $texto .= str_repeat("-", 64) . "\n";
    $texto .= "MONTO FINAL A PAGAR:              " . colones($montoFinal) . "\n";
    $texto .= $linea;

    return [
        "contado"    => $contado,
        "intereses"  => $intereses,
        "conInteres" => $conInteres,
        "cashback"   => $cashback,
        "montoFinal" => $montoFinal,
        "texto"      => $texto
    ];
}

function guardarEstadoDeCuenta($texto)
{
    $ruta = __DIR__ . DIRECTORY_SEPARATOR . "estado_cuenta.txt";

    $archivo = @fopen($ruta, "w");
    if ($archivo === false) {
        throw new Exception("No se pudo abrir estado_cuenta.txt para escritura.");
    }

    fwrite($archivo, $texto);
    fclose($archivo);
}

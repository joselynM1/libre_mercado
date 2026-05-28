<?php
session_start();
require_once 'Conexion.php';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

if (isset($_GET['accion']) && $_GET['accion'] === 'agregar') {
    $id_producto = intval($_GET['id_producto']);

    $db = Conexion::conectar();
    $stmt = $db->prepare("SELECT id_producto, producto, precio_unitario FROM productos WHERE id_producto = :id AND activo = 1");
    $stmt->execute([':id' => $id_producto]);
    $producto = $stmt->fetch();

    if ($producto) {
        if (isset($_SESSION['carrito'][$id_producto])) {
            $_SESSION['carrito'][$id_producto]['cantidad']++;
        } else {
            $_SESSION['carrito'][$id_producto] = [
                'id_producto'    => $producto['id_producto'],
                'producto'       => $producto['producto'],
                'precio_unitario' => $producto['precio_unitario'],
                'cantidad'       => 1
            ];
        }
    }
    header("Location: index.php");
    exit;
}

if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar') {
    $id_producto = intval($_GET['id_producto']);
    if (isset($_SESSION['carrito'][$id_producto])) {
        unset($_SESSION['carrito'][$id_producto]);
    }
    header("Location: ver_carrito.php");
    exit;
}

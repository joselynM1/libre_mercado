<?php
session_start();
require_once 'Conexion.php';

// Inicializar el carrito en la sesión si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

/**
 * ACCIÓN 1: AGREGAR AL CARRITO (Viene vía POST desde el formulario de index.php)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_producto'])) {
    $id_producto = intval($_POST['id_producto']);
    $producto_nombre = $_POST['producto'] ?? '';
    $precio_unitario = floatval($_POST['precio'] ?? 0);

    if ($id_producto > 0) {
        // Si el producto ya está en el carrito, aumentamos la cantidad
        if (isset($_SESSION['carrito'][$id_producto])) {
            $_SESSION['carrito'][$id_producto]['cantidad']++;
        } else {
            // Si es nuevo, lo registramos usando los datos limpios que envió el formulario
            $_SESSION['carrito'][$id_producto] = [
                'id_producto'     => $id_producto,
                'producto'        => $producto_nombre,
                'precio_unitario' => $precio_unitario,
                'cantidad'        => 1
            ];
        }
    }
    
    // Redirigir de inmediato al catálogo para actualizar el contador de la Navbar
    header("Location: index.php");
    exit;
}

/**
 * ACCIÓN 2: AGREGAR AL CARRITO VÍA GET (Por si se usan enlaces directos en alguna parte)
 */
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
                'id_producto'     => $producto['id_producto'],
                'producto'        => $producto['producto'],
                'precio_unitario' => $producto['precio_unitario'],
                'cantidad'        => 1
            ];
        }
    }
    header("Location: index.php");
    exit;
}

/**
 * ACCIÓN 3: ELIMINAR DEL CARRITO (Viene vía GET desde la vista ver_carrito.php)
 */
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar') {
    $id_producto = intval($_GET['id_producto']);
    if (isset($_SESSION['carrito'][$id_producto])) {
        unset($_SESSION['carrito'][$id_producto]);
    }
    header("Location: ver_carrito.php");
    exit;
}
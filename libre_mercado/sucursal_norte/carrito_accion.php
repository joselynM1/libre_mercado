<?php
// C:\xampp\htdocs\sucursal_norte\carrito_accion.php
session_start();
require_once 'Conexion.php';

// Si no existe el carrito en la sesión, lo creamos como un arreglo vacío
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// 1. AÑADIR PRODUCTO AL CARRITO
if (isset($_GET['accion']) && $_GET['accion'] === 'agregar') {
    $id_prod = intval($_GET['id_prod']);
    
    // Consultar a la base de datos para traer los datos reales del producto
    $db = Conexion::conectar();
    $stmt = $db->prepare("SELECT id_prod, producto, precio FROM productos WHERE id_prod = :id AND activo = 1");
    $stmt->execute([':id' => $id_prod]);
    $producto = $stmt->fetch();
    
    if ($producto) {
        // Si el producto ya está en el carrito, sumamos la cantidad
        if (isset($_SESSION['carrito'][$id_prod])) {
            $_SESSION['carrito'][$id_prod]['cantidad']++;
        } else {
            // Si es nuevo, lo registramos con cantidad 1
            $_SESSION['carrito'][$id_prod] = [
                'id_prod'  => $producto['id_prod'],
                'producto' => $producto['producto'],
                'precio'   => $producto['precio'],
                'cantidad' => 1
            ];
        }
    }
    // se queda en la pagina principal despues de agregar al carrito
    header("Location: index.php");
    exit;
}

// 2. ELIMINAR PRODUCTO DEL CARRITO (Icono del basurero)
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar') {
    $id_prod = intval($_GET['id_prod']);
    if (isset($_SESSION['carrito'][$id_prod])) {
        unset($_SESSION['carrito'][$id_prod]);
    }
    header("Location: ver_carrito.php");
    exit;
}

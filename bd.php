<?php
/**
 * Script para exportar datos de hoy de la base de datos
 * Guarda un archivo SQL con pedidos, detalles, pagos y movimientos de caja del día actual
 */

// Configuración de la base de datos
$host = 'localhost';
$usuario = 'majocafe_system';
$password = 'm4WjPgtwdZsuLxCxRtAG'; // Cambia si tienes contraseña
$database = 'majocafe_system';


$fecha_hoy = '2025-10-21'; // Fecha explícita


// Nombre del archivo de salida
$archivo_salida = 'datos_' . date('Y-m-d') . '.sql';

try {
    // Conexión a la base de datos
    $conn = new mysqli($host, $usuario, $password, $database);
    
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    echo "Conectado a la base de datos...\n";
    
    // Abrir archivo para escribir
    $file = fopen($archivo_salida, 'w');
    
    if (!$file) {
        die("Error al crear el archivo de salida");
    }
    
    // Encabezado del archivo SQL
    fwrite($file, "-- Exportación de datos del " . $fecha_hoy . "\n");
    fwrite($file, "-- Generado: " . date('Y-m-d H:i:s') . "\n");
    fwrite($file, "-- Base de datos: " . $database . "\n\n");
    fwrite($file, "SET FOREIGN_KEY_CHECKS=0;\n\n");
    
    // ==================== PEDIDOS ====================
    echo "Exportando pedidos...\n";
    
    $query = "SELECT * FROM pedidos WHERE DATE(fecha_pedido) = '$fecha_hoy'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        fwrite($file, "-- Tabla: pedidos\n");
        fwrite($file, "INSERT INTO `pedidos` (`id`, `fecha_pedido`, `total`, `descuento`, `notas`, `estado`, `ubicacion`, `metodo_pago`) VALUES\n");
        
        $pedidos_ids = [];
        $first = true;
        
        while ($row = $result->fetch_assoc()) {
            $pedidos_ids[] = $row['id'];
            
            if (!$first) {
                fwrite($file, ",\n");
            }
            $first = false;
            
            fwrite($file, "(");
            fwrite($file, $row['id'] . ", ");
            fwrite($file, "'" . $conn->real_escape_string($row['fecha_pedido']) . "', ");
            fwrite($file, $row['total'] . ", ");
            fwrite($file, $row['descuento'] . ", ");
            $notas = $row['notas'] ? "'" . $conn->real_escape_string($row['notas']) . "'" : "NULL";
            fwrite($file, $notas . ", ");
            fwrite($file, "'" . $row['estado'] . "', ");
            fwrite($file, "'" . $conn->real_escape_string($row['ubicacion']) . "', ");
            fwrite($file, "'" . $conn->real_escape_string($row['metodo_pago']) . "'");
            fwrite($file, ")");
        }
        
        fwrite($file, ";\n\n");
        echo "✓ Pedidos exportados: " . count($pedidos_ids) . "\n";
        
        // ==================== PEDIDO DETALLE ====================
        if (!empty($pedidos_ids)) {
            echo "Exportando detalles de pedidos...\n";
            
            $ids_string = implode(',', $pedidos_ids);
            $query = "SELECT * FROM pedido_detalle WHERE pedido_id IN ($ids_string)";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                fwrite($file, "-- Tabla: pedido_detalle\n");
                fwrite($file, "INSERT INTO `pedido_detalle` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_modificado`, `cantidad_modificada`, `modificacion_tipo`, `modificacion_valor`, `notas_item`) VALUES\n");
                
                $first = true;
                $count = 0;
                
                while ($row = $result->fetch_assoc()) {
                    $count++;
                    if (!$first) {
                        fwrite($file, ",\n");
                    }
                    $first = false;
                    
                    fwrite($file, "(");
                    fwrite($file, $row['id'] . ", ");
                    fwrite($file, $row['pedido_id'] . ", ");
                    $producto_id = $row['producto_id'] ? $row['producto_id'] : "NULL";
                    fwrite($file, $producto_id . ", ");
                    fwrite($file, $row['cantidad'] . ", ");
                    fwrite($file, $row['precio_unitario'] . ", ");
                    $precio_mod = $row['precio_modificado'] ? $row['precio_modificado'] : "NULL";
                    fwrite($file, $precio_mod . ", ");
                    $cant_mod = $row['cantidad_modificada'] ? $row['cantidad_modificada'] : 0;
                    fwrite($file, $cant_mod . ", ");
                    $mod_tipo = $row['modificacion_tipo'] ? "'" . $row['modificacion_tipo'] . "'" : "NULL";
                    fwrite($file, $mod_tipo . ", ");
                    $mod_valor = $row['modificacion_valor'] ? $row['modificacion_valor'] : 0.00;
                    fwrite($file, $mod_valor . ", ");
                    $notas_item = $row['notas_item'] ? "'" . $conn->real_escape_string($row['notas_item']) . "'" : "NULL";
                    fwrite($file, $notas_item);
                    fwrite($file, ")");
                }
                
                fwrite($file, ";\n\n");
                echo "✓ Detalles exportados: " . $count . "\n";
            }
        }
    } else {
        echo "No hay pedidos para la fecha: " . $fecha_hoy . "\n";
    }
    
    // ==================== PEDIDO PAGOS ====================
    echo "Exportando pagos...\n";
    
    $query = "SELECT * FROM pedido_pagos WHERE DATE(fecha_pago) = '$fecha_hoy'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        fwrite($file, "-- Tabla: pedido_pagos\n");
        fwrite($file, "INSERT INTO `pedido_pagos` (`id`, `pedido_id`, `metodo_pago`, `monto`, `fecha_pago`) VALUES\n");
        
        $first = true;
        $count = 0;
        
        while ($row = $result->fetch_assoc()) {
            $count++;
            if (!$first) {
                fwrite($file, ",\n");
            }
            $first = false;
            
            fwrite($file, "(");
            fwrite($file, $row['id'] . ", ");
            fwrite($file, $row['pedido_id'] . ", ");
            fwrite($file, "'" . $row['metodo_pago'] . "', ");
            fwrite($file, $row['monto'] . ", ");
            fwrite($file, "'" . $conn->real_escape_string($row['fecha_pago']) . "'");
            fwrite($file, ")");
        }
        
        fwrite($file, ";\n\n");
        echo "✓ Pagos exportados: " . $count . "\n";
    }
    
    // ==================== CAJA MOVIMIENTOS ====================
    echo "Exportando movimientos de caja...\n";
    
    $query = "SELECT * FROM caja_movimientos WHERE DATE(fecha) = '$fecha_hoy'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        fwrite($file, "-- Tabla: caja_movimientos\n");
        fwrite($file, "INSERT INTO `caja_movimientos` (`id`, `fecha`, `tipo`, `monto`, `descripcion`, `usuario`) VALUES\n");
        
        $first = true;
        $count = 0;
        
        while ($row = $result->fetch_assoc()) {
            $count++;
            if (!$first) {
                fwrite($file, ",\n");
            }
            $first = false;
            
            fwrite($file, "(");
            fwrite($file, $row['id'] . ", ");
            fwrite($file, "'" . $conn->real_escape_string($row['fecha']) . "', ");
            fwrite($file, "'" . $row['tipo'] . "', ");
            fwrite($file, $row['monto'] . ", ");
            $descripcion = $row['descripcion'] ? "'" . $conn->real_escape_string($row['descripcion']) . "'" : "NULL";
            fwrite($file, $descripcion . ", ");
            fwrite($file, "'" . $conn->real_escape_string($row['usuario']) . "'");
            fwrite($file, ")");
        }
        
        fwrite($file, ";\n\n");
        echo "✓ Movimientos de caja exportados: " . $count . "\n";
    }
    
    // Footer del archivo
    fwrite($file, "SET FOREIGN_KEY_CHECKS=1;\n");
    
    fclose($file);
    $conn->close();
    
    echo "\n========================================\n";
    echo "✓ Exportación completada exitosamente\n";
    echo "Archivo generado: " . $archivo_salida . "\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
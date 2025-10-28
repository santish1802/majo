<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/auth.php'; ?>
<?php require_once 'config.php';

// Obtener ID del pedido desde GET
$pedidoId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($pedidoId <= 0) {
    header('Location: pedidos.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAJO - Editar Pedido #<?= $pedidoId ?></title>
    <?php 
    include $_SERVER['DOCUMENT_ROOT'] . "/assets/img/favicon.php";
    ?>   
    <?php $version = date('YmdHi');?>
    <link href="/assets/scss/bootstrap.css?v=<?php echo $version; ?>" rel="stylesheet">
    <link href="/assets/style.css?v=<?php echo $version; ?>" rel="stylesheet">
    <link href="/assets/css/all.css" rel="stylesheet">
</head>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/nav.php"; ?>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-search"></i> Buscar y Añadir Productos</h5>
                    </div>
                    <div class="card-body">
                        <div class="position-relative mb-3">
                            <input type="text" id="buscarProducto" class="form-control" placeholder="Buscar productos o combos..." autocomplete="off">
                            <div id="sugerencias" class="suggestions" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card m-3">
                    <div class="card-header">
                        <h5><i class="fas fa-shopping-cart"></i> Items del Pedido</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Ubicación:</label>
                                <select id="tipoUbicacion" class="form-select">
                                    <option value="libre">Libre</option>
                                    <option value="barra">Barra</option>
                                    <option value="mesa">Mesa</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Número:</label>
                                <input type="number" id="numeroMesa" class="form-control" placeholder="Número" style="display: none;" min="1">
                            </div>
                        </div>
                        
                        <div id="carritoLista" class="mb-3">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                <p>Cargando pedido...</p>
                            </div>
                        </div>
                        
                        <div class="modern-summary">
                            <div class="row">
                                <div class="col-6">
                                    <span class="text-muted">Subtotal:</span>
                                </div>
                                <div class="col-6 text-end">
                                    <span id="subtotal" class="fw-semibold">S/. 0.00</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <span class="text-muted">Descuento:</span>
                                </div>
                                <div class="col-6 text-end">
                                    <span id="descuentoMonto" class="fw-semibold">S/. 0.00</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <span class="fw-bold">Total:</span>
                                </div>
                                <div class="col-6 text-end">
                                    <span id="total" class="fw-bold text-success">S/. 0.00</span>
                                </div>
                            </div>
                        </div>
                        
                        <textarea id="notasPedido" class="form-control my-3" rows="2" placeholder="Notas del pedido..."></textarea>
                        
                        <div class="input-group mb-3">
                            <input type="number" id="descuentoValor" class="form-control" placeholder="Descuento" step="0.01">
                            <button class="btn btn-gray" onclick="aplicarDescuento('soles')">S/.</button>
                            <button class="btn btn-gray" onclick="aplicarDescuento('porcentaje')">%</button>
                        </div>
                        
                        <button onclick="actualizarPedido()" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="pedidos.php" class="btn btn-secondary w-100 mt-2">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para modificar items -->
    <div class="modal fade" id="modalModificar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Modificar Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let carrito = [];
let descuentoGlobal = { tipo: '', valor: 0 };
let itemIdCounter = 1;
const pedidoId = <?= $pedidoId ?>;
let pedidoOriginal = null;
let listaProductos = [];

document.addEventListener('DOMContentLoaded', function() {
    cargarPedido();
    cargarProductos();
    
    // Controlar visibilidad del número de mesa
    document.getElementById('tipoUbicacion').addEventListener('change', function() {
        const numeroMesa = document.getElementById('numeroMesa');
        numeroMesa.style.display = this.value === 'mesa' ? 'block' : 'none';
        if (this.value !== 'mesa') numeroMesa.value = '';
    });

    // Buscar productos localmente
    document.getElementById('buscarProducto').addEventListener('input', filtrarProductos);
});

function cargarProductos() {
    fetch('editar_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'listar_productos' })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.error) {
            listaProductos = data;
        }
    })
    .catch(error => {
        console.error('Error al cargar productos:', error);
    });
}

function filtrarProductos() {
    const query = document.getElementById('buscarProducto').value.trim().toLowerCase();
    const sugerenciasDiv = document.getElementById('sugerencias');
    
    if (query.length < 2) {
        sugerenciasDiv.style.display = 'none';
        return;
    }
    
    const resultados = listaProductos
        .filter(p => p.nombre.toLowerCase().includes(query))
        .slice(0, 10);
    
    mostrarSugerencias(resultados);
}

function cargarPedido() {
    fetch('editar_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'obtener_pedido',
            id: pedidoId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
            window.location.href = 'pedidos.php';
            return;
        }
        
        pedidoOriginal = data;
        cargarDatosPedido(data.info, data.items);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cargar el pedido');
    });
}

function cargarDatosPedido(info, items) {
    // Cargar información general
    document.getElementById('notasPedido').value = info.notas || '';
    
    // Configurar ubicación
    const ubicacion = info.ubicacion.toLowerCase();
    if (ubicacion.includes('mesa')) {
        document.getElementById('tipoUbicacion').value = 'mesa';
        document.getElementById('numeroMesa').style.display = 'block';
        const numero = info.ubicacion.match(/\d+/);
        if (numero) document.getElementById('numeroMesa').value = numero[0];
    } else {
        document.getElementById('tipoUbicacion').value = ubicacion;
    }
    
    // Configurar descuento global si existe
    if (info.descuento > 0) {
        descuentoGlobal = { tipo: 'soles', valor: parseFloat(info.descuento) };
        document.getElementById('descuentoValor').value = info.descuento;
    }
    
    // Cargar items al carrito
    carrito = items.map(item => ({
        id: itemIdCounter++,
        nombre: item.nombre,
        precio: item.precio_modificado ? parseFloat(item.precio_modificado) : parseFloat(item.precio_unitario),
        precioOriginal: parseFloat(item.precio_unitario),
        cantidad: parseInt(item.cantidad),
        cantidadModificada: parseInt(item.cantidad_modificada) || 0,
        modificado: item.precio_modificado !== null,
        modificacion: {
            tipo: item.modificacion_tipo || '',
            valor: parseFloat(item.modificacion_valor) || 0,
            cantidadModificada: parseInt(item.cantidad_modificada) || 0,
            esDescuento: parseFloat(item.modificacion_valor) < 0
        },
        notas: item.notas_item || '',
        tipo: item.tipo || 'producto', // Ahora puede ser: frape, cafe, etc.
        producto_id: item.producto_id,
        item_bd_id: item.id // ID del item en la BD para referencia
    }));
    
    actualizarCarrito();
}

function mostrarSugerencias(items) {
    const sugerenciasDiv = document.getElementById('sugerencias');
    sugerenciasDiv.innerHTML = items.map(item => `
        <div class="sugerencia-item" onclick='agregarAlCarrito(${JSON.stringify(item).replace(/'/g, "\\'").replace(/"/g, "&quot;")})'>
            <span>${item.nombre}</span>
            <span>S/. ${parseFloat(item.precio).toFixed(2)}</span>
            <span class="badge bg-primary">${(item.tipo || 'general').toUpperCase()}</span>
        </div>
    `).join('');
    sugerenciasDiv.style.display = items.length > 0 ? 'block' : 'none';
}

function agregarAlCarrito(itemData) {
    // Buscar si ya existe el producto por ID
    let itemExistente = carrito.find(item => item.producto_id === itemData.id);

    if (itemExistente) {
        itemExistente.cantidad++;
    } else {
        const newItem = {
            id: itemIdCounter++,
            nombre: itemData.nombre,
            precio: parseFloat(itemData.precio),
            precioOriginal: parseFloat(itemData.precio),
            cantidad: 1,
            cantidadModificada: 0,
            modificado: false,
            modificacion: {
                tipo: '',
                valor: 0,
                cantidadModificada: 0,
                esDescuento: false
            },
            notas: '',
            tipo: itemData.tipo || 'producto', // frape, cafe, etc.
            producto_id: itemData.id
        };

        carrito.push(newItem);
    }

    actualizarCarrito();
    document.getElementById('buscarProducto').value = '';
    document.getElementById('sugerencias').style.display = 'none';
}

function actualizarCarrito() {
    const carritoDiv = document.getElementById('carritoLista');

    if (carrito.length === 0) {
        carritoDiv.innerHTML = `<div class="text-center text-muted py-4"><i class="fas fa-shopping-cart fa-3x mb-2"></i><p>El pedido está vacío</p></div>`;
    } else {
        carritoDiv.innerHTML = carrito.map(item => {
            let precioMostrado = '';
            if (item.modificado && item.cantidadModificada > 0) {
                if (item.cantidadModificada === item.cantidad) {
                    precioMostrado = `<del>S/. ${item.precioOriginal.toFixed(2)}</del> <strong>S/. ${item.precio.toFixed(2)}</strong>`;
                } else {
                    const cantidadNormal = item.cantidad - item.cantidadModificada;
                    precioMostrado = `<strong>S/. ${item.precioOriginal.toFixed(2)}</strong> (${cantidadNormal}x) + <strong>S/. ${item.precio.toFixed(2)}</strong> (${item.cantidadModificada}x)`;
                }
            } else {
                precioMostrado = `S/. ${item.precio.toFixed(2)}`;
            }

            const tipoCapitalizado = item.tipo ? item.tipo.charAt(0).toUpperCase() + item.tipo.slice(1) : 'Producto';

            return `
                <div class="carrito-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${item.nombre}</strong>
                            <br><small class="text-muted">${tipoCapitalizado}</small>
                            <div class="text-success">
                                ${precioMostrado}
                            </div>
                            ${item.notas ? `<small class="text-muted"><i class="fa-light fa-note-sticky me-1"></i> ${item.notas}</small>` : ''}
                        </div>
                        <div class="text-end">
                            <div id="btn-cant" class="btn-group mb-2">
                                <button class="btn text-primary btn-outline-white btn-sm btn-cantidad" onclick="cambiarCantidad(${item.id}, -1)">-</button>
                                <span class="btn btn-primary btn-sm" style="z-index: 9;">${item.cantidad}</span>
                                <button class="btn text-primary btn-outline-white btn-sm btn-cantidad" onclick="cambiarCantidad(${item.id}, 1)">+</button>
                            </div>
                            <div class="btns-pc">
                                <button class="btn px-4 btn-custom btn-dark" onclick="modificarItem(${item.id})"><i class="fa-regular fa-pen-to-square me-2"></i>Precio</button>
                                <button class="btn px-4 btn-custom btn-info btn-nota" onclick="agregarNotaItem(${item.id})">
                                <i class="fa-regular fa-notes me-2"></i><span class="texto-nota">Nota</span>
                                </button>
                                <button class="btn px-4 btn-custom  btn-danger" onclick="eliminarItem(${item.id})"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="btns-mv d-flex justify-content-between align-items-center mt-2 gap-2">
                        <button class="btn px-4 btn-custom flex-fill btn-dark" onclick="modificarItem(${item.id})"><i class="fa-regular fa-pen-to-square me-2"></i>Precio</button>
                        <button class="btn px-4 btn-custom flex-fill btn-info btn-nota" onclick="agregarNotaItem(${item.id})">
                        <i class="fa-regular fa-notes me-2"></i><span class="texto-nota">Nota</span>
                        </button>
                        <button class="btn px-4 btn-custom flex-fill btn-danger" onclick="eliminarItem(${item.id})"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `;
        }).join('');
    }

    calcularTotales();
}

function cambiarCantidad(itemId, cambio) {
    const item = carrito.find(i => i.id === itemId);
    if (item) {
        const nuevaCantidad = item.cantidad + cambio;
        if (nuevaCantidad <= 0) {
            eliminarItem(itemId);
        } else {
            if (cambio < 0 && item.cantidadModificada > nuevaCantidad) {
                item.cantidadModificada = nuevaCantidad;
            }
            item.cantidad = nuevaCantidad;
            actualizarCarrito();
        }
    }
}

function eliminarItem(itemId) {
    carrito = carrito.filter(item => item.id !== itemId);
    actualizarCarrito();
}

function modificarItem(itemId) {
    const item = carrito.find(i => i.id === itemId);
    if (!item) return;

    const modal = new bootstrap.Modal(document.getElementById('modalModificar'));
    const modalContent = document.getElementById('modalContent');

    // Determinar valores previos si existe modificación
    let valorPrevio = '';
    let cantidadPreviaMod = item.modificado ? (item.cantidadModificada || item.cantidad) : 1;
    let precioFijoPrevio = '';
    let esDescuentoPrevio = true;
    
    if (item.modificado && item.modificacion) {
        cantidadPreviaMod = item.modificacion.cantidadModificada || item.cantidadModificada || item.cantidad;
        
        if (item.modificacion.tipo === 'soles') {
            valorPrevio = Math.abs(item.modificacion.valor);
            esDescuentoPrevio = item.modificacion.valor < 0;
        } else if (item.modificacion.tipo === 'porcentaje') {
            valorPrevio = Math.abs(item.modificacion.valor);
            esDescuentoPrevio = item.modificacion.valor < 0;
        } else if (item.modificacion.tipo === 'fijo') {
            precioFijoPrevio = item.modificacion.valor;
        }
    }

    modalContent.innerHTML = `
        <h6 class="fw-bold">${item.nombre}</h6>
        <div class="d-flex justify-content-between">
            <p>Precio original: S/. ${item.precioOriginal.toFixed(2)}</p>
            <p>Cantidad total: ${item.cantidad}</p>
        </div>
        ${item.modificado ? `<p class="text-info">Cantidad con precio modificado: ${item.cantidadModificada}</p>` : ''}
        ${item.modificado ? `<p class="text-success">Precio actual modificado: S/. ${item.precio.toFixed(2)}</p>` : ''}
        
        <div class="mb-3 d-flex justify-content-between">
            <label for="cantidadModificar" class="form-label mb-0 d-flex align-items-center">Cantidad a modificar:</label>
            <div class="btn-group btn-cant" role="group">
                <button type="button" class="btn text-primary btn-outline-white btn-sm" onclick="cambiarCantidadModificar(-1, ${item.cantidad})">-</button>
                <span class="btn btn-primary btn-sm flex-fill" style="z-index: 9;" id="cantidadModificarDisplay">${cantidadPreviaMod}</span>
                <button type="button" class="btn text-primary btn-outline-white btn-sm" onclick="cambiarCantidadModificar(1, ${item.cantidad})">+</button>
            </div>
            <input type="hidden" id="cantidadModificar" value="${cantidadPreviaMod}">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Tipo de modificación:</label>
            <div class="d-flex gap-4">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="tipoModificacion" id="descuentoRadio" value="descuento" ${esDescuentoPrevio ? 'checked' : ''}>
                    <label class="form-check-label text-danger" for="descuentoRadio">
                        <i class="fas fa-arrow-down"></i> Descuento
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="tipoModificacion" id="aumentoRadio" value="aumento" ${!esDescuentoPrevio ? 'checked' : ''}>
                    <label class="form-check-label text-success" for="aumentoRadio">
                        <i class="fas fa-arrow-up"></i> Aumento
                    </label>
                </div>
            </div>
        </div>
        
        <div class="input-group mb-3">
            <input type="number" id="modValor" class="form-control" placeholder="Valor" step="0.01" value="${valorPrevio}">
            <button class="btn btn-gray" onclick="aplicarModificacion(${itemId}, 'soles')">S/.</button>
            <button class="btn btn-gray" onclick="aplicarModificacion(${itemId}, 'porcentaje')">%</button>
        </div>
        
        <div class="input-group mb-3">
            <span class="input-group-text">O establecer precio fijo:</span>
            <input type="number" id="modPrecioFijo" class="form-control" placeholder="Nuevo precio fijo" step="0.01" value="${precioFijoPrevio}">
            <button class="btn btn-gray" onclick="aplicarModificacion(${itemId}, 'fijo')">Fijar S/.</button>
        </div>
        
        <div class="d-flex gap-2">
            <button class="btn btn-primary flex-fill" onclick="aplicarModificacionRapida(${itemId})">
                <i class="fas fa-check"></i> Aplicar
            </button>
            <button class="btn btn-secondary" onclick="resetearPrecio(${itemId})">
                <i class="fas fa-undo"></i> Resetear
            </button>
        </div>
    `;

    modal.show();
}

function aplicarModificacion(itemId, tipo) {
    const item = carrito.find(i => i.id === itemId);
    if (!item) return;

    const cantidadModificar = parseInt(document.getElementById('cantidadModificar').value) || item.cantidad;
    
    if (cantidadModificar > item.cantidad) {
        alert('La cantidad a modificar no puede ser mayor a la cantidad total');
        return;
    }

    let nuevoPrecio = item.precioOriginal;
    let valorOriginal = parseFloat(document.getElementById('modValor').value) || 0;
    
    // Determinar si es aumento o descuento
    const esDescuento = document.getElementById('descuentoRadio').checked;
    let valorFinal = esDescuento ? -Math.abs(valorOriginal) : Math.abs(valorOriginal);

    if (tipo === 'soles') {
        nuevoPrecio = item.precioOriginal + valorFinal;
    } else if (tipo === 'porcentaje') {
        nuevoPrecio = item.precioOriginal * (1 + valorFinal / 100);
    } else if (tipo === 'fijo') {
        const valor = parseFloat(document.getElementById('modPrecioFijo').value);
        if (!isNaN(valor)) {
            nuevoPrecio = valor;
            valorFinal = valor; // Para precio fijo, guardamos el valor exacto
        }
    }

    // Validar que el precio no sea negativo
    if (nuevoPrecio < 0) {
        alert('El precio resultante no puede ser negativo');
        return;
    }

    item.precio = nuevoPrecio;
    item.cantidadModificada = cantidadModificar;
    item.modificado = (item.precio.toFixed(2) !== item.precioOriginal.toFixed(2));
    item.modificacion = {
        tipo: tipo,
        valor: valorFinal,
        cantidadModificada: cantidadModificar,
        esDescuento: esDescuento && tipo !== 'fijo'
    };

    actualizarCarrito();
    bootstrap.Modal.getInstance(document.getElementById('modalModificar')).hide();
}

function aplicarModificacionRapida(itemId) {
    const valorInput = document.getElementById('modValor');
    const precioFijoInput = document.getElementById('modPrecioFijo');
    
    if (precioFijoInput.value && precioFijoInput.value.trim() !== '') {
        aplicarModificacion(itemId, 'fijo');
    } else if (valorInput.value && valorInput.value.trim() !== '') {
        aplicarModificacion(itemId, 'soles');
    } else {
        alert('Por favor, ingrese un valor para modificar el precio');
    }
}

function cambiarCantidadModificar(cambio, maxCantidad) {
    const input = document.getElementById('cantidadModificar');
    const display = document.getElementById('cantidadModificarDisplay');
    
    let valorActual = parseInt(input.value) || 1;
    let nuevoValor = valorActual + cambio;
    
    if (nuevoValor < 1) nuevoValor = 1;
    if (nuevoValor > maxCantidad) nuevoValor = maxCantidad;
    
    input.value = nuevoValor;
    display.textContent = nuevoValor;
}

function resetearPrecio(itemId) {
    const item = carrito.find(i => i.id === itemId);
    if (!item) return;
    
    item.precio = item.precioOriginal;
    item.cantidadModificada = 0;
    item.modificado = false;
    item.modificacion = {
        tipo: '',
        valor: 0,
        cantidadModificada: 0,
        esDescuento: false
    };

    actualizarCarrito();
    bootstrap.Modal.getInstance(document.getElementById('modalModificar')).hide();
}

function agregarNotaItem(itemId) {
    const item = carrito.find(i => i.id === itemId);
    if (!item) return;
    const nota = prompt('Nota para este item:', item.notas);
    if (nota !== null) {
        item.notas = nota;
        actualizarCarrito();
    }
}

function aplicarDescuento(tipo) {
    const valor = parseFloat(document.getElementById('descuentoValor').value) || 0;
    descuentoGlobal = { tipo, valor };
    calcularTotales();
}

function calcularTotales() {
    let subtotal = 0;
    
    carrito.forEach(item => {
        if (item.modificado && item.cantidadModificada > 0) {
            const cantidadNormal = item.cantidad - item.cantidadModificada;
            subtotal += (cantidadNormal * item.precioOriginal) + (item.cantidadModificada * item.precio);
        } else {
            subtotal += item.precio * item.cantidad;
        }
    });

    let descuento = 0;
    if (descuentoGlobal.tipo === 'soles') {
        descuento = descuentoGlobal.valor;
    } else if (descuentoGlobal.tipo === 'porcentaje') {
        descuento = subtotal * (descuentoGlobal.valor / 100);
    }

    const total = Math.max(0, subtotal - descuento);

    document.getElementById('subtotal').textContent = `S/. ${subtotal.toFixed(2)}`;
    document.getElementById('descuentoMonto').textContent = `S/. ${descuento.toFixed(2)}`;
    document.getElementById('total').textContent = `S/. ${total.toFixed(2)}`;
}

function obtenerUbicacion() {
    const tipo = document.getElementById('tipoUbicacion').value;
    const numero = document.getElementById('numeroMesa').value;

    if (tipo === 'mesa' && numero) {
        return `Mesa ${numero}`;
    }
    return tipo.charAt(0).toUpperCase() + tipo.slice(1);
}

function actualizarPedido() {
    if (carrito.length === 0) {
        alert('El pedido no puede estar vacío.');
        return;
    }

    const pedidoData = {
        pedido_id: pedidoId,
        items: carrito.map(item => ({
            id: item.producto_id,
            tipo: item.tipo,
            cantidad: item.cantidad,
            precio: item.precio,
            precioOriginal: item.precioOriginal,
            cantidadModificada: item.cantidadModificada || 0,
            modificado: item.modificado,
            modificacion: item.modificacion,
            notas: item.notas,
            producto_id: item.producto_id
        })),
        descuento: descuentoGlobal.valor,
        notas: document.getElementById('notasPedido').value,
        total: parseFloat(document.getElementById('total').textContent.replace('S/. ', '')),
        ubicacion: obtenerUbicacion()
    };

    fetch('editar_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'actualizar_pedido',
            pedido: pedidoData
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            window.location.href = 'pedidos.php';
        } else {
            alert('Error al actualizar el pedido: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al intentar actualizar el pedido.');
    });
}

// Cerrar sugerencias al hacer clic fuera
document.addEventListener('click', function(event) {
    if (!event.target.closest('#buscarProducto') && !event.target.closest('#sugerencias')) {
        document.getElementById('sugerencias').style.display = 'none';
    }
});
</script>
</body>

</html>
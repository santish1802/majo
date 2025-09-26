<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAJO - Gestión de Pedidos</title>
    <?php $version = date('YmdHi');?>
    <link href="/assets/scss/bootstrap.css?v=<?php echo $version; ?>" rel="stylesheet">
    <link href="/assets/style.css?v=<?php echo $version; ?>" rel="stylesheet">
    <link href="/assets/css/all.css" rel="stylesheet">
</head>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/nav.php"; ?>

    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="header-card bg-primary text-white p-4 rounded-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mb-3 mb-md-0">
                            <h1 class="mb-2"><i class="fa-light fa-clipboard-list-check me-2"></i>
                                Gestión de Pedidos
                            </h1>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="index.php" class="btn btn-white rounded-pill px-4">
                                <i class="fa-regular fa-circle-plus me-2"></i>Nuevo Pedido
                            </a>
                            <button class="btn btn-outline-light rounded-pill px-4" onclick="cargarPedidos();">
                                <i class="fa-regular fa-arrow-rotate-right me-2"></i>Actualizar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <label class="form-label fw-semibold mb-2">Estado</label>
                <select id="filtroEstado" class="form-select" onchange="filtrarPedidos()">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" selected>Pendientes</option>
                    <option value="completado">Completados</option>
                    <option value="cancelado">Cancelados</option>
                </select>
                </select>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <label class="form-label fw-semibold mb-2">Ubicación</label>
                <select id="filtroUbicacion" class="form-select" onchange="filtrarPedidos()">
                    <option value="">Todas las ubicaciones</option>
                    <option value="libre">Libre</option>
                    <option value="barra">Barra</option>
                    <option value="mesa">Mesas</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6 mb-3" style="<?= (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') ? '' : 'display:none;' ?>">
                <label class="form-label fw-semibold mb-2">Fecha</label>
                <input type="date" id="filtroFecha" class="form-control" onchange="cargarPedidos()" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <label class="form-label fw-semibold mb-2">Buscar</label>
                <div class="input-group">
                    <input type="text" id="buscarPedido" class="form-control" placeholder="Buscar pedido #..." onkeyup="filtrarPedidos()">
                    <span class="input-group-text bg-primary border-0">
                        <i class="fa-solid fa-magnifying-glass text-white"></i>
                    </span>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->

        <div class="row mb-5">
            <div class="col-xl-3 col-md-6 mb-2">
                <div class="card stat-card bg-warning text-white rounded-4 border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 text-uppercase fw-bold mb-2">Pendientes</h6>
                                <h2 class="mb-0 fw-bold" id="statPendientes">0</h2>
                            </div>
                            <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                <i class="fa-solid fa-clock fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-2">
                <div class="card stat-card bg-success text-white rounded-4 border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 text-uppercase fw-bold mb-2">Completados</h6>
                                <h2 class="mb-0 fw-bold" id="statCompletados">0</h2>
                            </div>
                            <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                <i class="fa-solid fa-circle-check fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($_SESSION['rol'] == 'admin'): ?>
                <div class="col-xl-3 col-md-6 mb-2">
                    <div class="card stat-card bg-info text-white rounded-4 border-0">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50 text-uppercase fw-bold mb-2">Total Ventas</h6>
                                    <h2 class="mb-0 fw-bold" id="statVentas">S/. 0.00</h2>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="fa-solid fa-dollar-sign fs-3"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-2">
                    <div class="card stat-card bg-dark text-white rounded-4 border-0">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50 text-uppercase fw-bold mb-2">Total Pedidos</h6>
                                    <h2 class="mb-0 fw-bold" id="statTotal">0</h2>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                                    <i class="fa-light fa-receipt fs-3"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Lista de Pedidos -->
        <div id="listaPedidos" class="row">
        </div>
    </div>

    <!-- Modal de Detalles -->
    <div class="modal fade" id="modalDetalles" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalTitulo">
                        <i class="fa-solid fa-eye me-2 text-primary"></i>
                        Detalles del Pedido
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalDetallesContent">
                </div>
                <div class="modal-footer border-0 pt-0" id="modalFooter">
                    <button type="button" class="btn btn-success btn-custom px-4" id="btnGuardarMetodoPago" style="display:none;">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Método de Pago
                    </button>
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        let pedidos = [];

        document.addEventListener('DOMContentLoaded', function() {
            cargarPedidos();
            setInterval(cargarPedidos, 300000);

            document.getElementById('btnGuardarMetodoPago').addEventListener('click', function() {
                const pedidoId = this.dataset.pedidoId;
                const metodoPago = document.getElementById('metodoPagoSelect').value;
                guardarMetodoPagoEnModal(pedidoId, metodoPago);
            });
        });

        function cargarPedidos() {
            const fecha = document.getElementById('filtroFecha').value;

            fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'obtener_pedidos',
                        fecha: fecha
                    })
                })
                .then(response => response.json())
                .then(data => {
                    pedidos = data;
                    mostrarPedidos();
                    actualizarEstadisticas();
                    filtrarPedidos();

                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar pedidos');
                });
        }

        function mostrarPedidos() {
            const container = document.getElementById('listaPedidos');

            if (pedidos.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="no-pedidos text-center">
                            <i class="fa-light fa-clipboard-question display-1 text-muted mb-4"></i>
                            <h3 class="text-muted mb-2">No hay pedidos para mostrar</h3>
                            <p class="text-muted">Los pedidos aparecerán aquí cuando se registren</p>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = pedidos.map(pedido => `
                <div class="col-xl-4 col-lg-6 col-md-6 mb-4 pedido-item" 
                     data-estado="${pedido.estado}" 
                     data-ubicacion="${pedido.ubicacion.toLowerCase()}" 
                     data-id="${pedido.id}">
                    <div class="card pedido-card estado-${pedido.estado} border-0 rounded-4 h-100">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center p-4 pb-2">
                            <div class="d-flex align-items-center gap-3">
                                <h5 class="mb-0 fw-bold">${pedido.ubicacion}</h5>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-light text-muted fw-normal">Pedido #${pedido.id}</span>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fa-solid fa-gear"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="verDetalles(${pedido.id})">
                                            <i class="fa-solid fa-eye me-2"></i>Ver Detalles</a></li>
                                        <li><a class="dropdown-item" href="editar.php?id=${pedido.id}">
                                            <i class="fa-solid fa-pen me-2"></i>Editar</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="cambiarEstado(${pedido.id}, 'pendiente')">
                                            <i class="fa-solid fa-clock text-warning me-2"></i>Marcar Pendiente</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="cambiarEstado(${pedido.id}, 'completado')">
                                            <i class="fa-solid fa-circle-check text-success me-2"></i>Marcar Completado</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="cambiarEstado(${pedido.id}, 'cancelado')">
                                            <i class="fa-solid fa-circle-xmark text-danger me-2"></i>Cancelar</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="eliminarPedido(${pedido.id})">
                                            <i class="fa-solid fa-trash me-2"></i>Eliminar</a></li>
                                    </ul>
                                </div>
                            </div>

                        </div>
                        
                        <div class="card-body p-4 pt-2">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-${getEstadoColor(pedido.estado)} status-badge">${pedido.estado.toUpperCase()}</span>
                                <small class="text-muted fw-medium">${formatearFecha(pedido.fecha_pedido)}</small>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <span class="price-display">S/. ${parseFloat(pedido.total).toFixed(2)}</span>
                                    ${pedido.metodo_pago === 'Pendiente' 
                                        ? `<span class="badge bg-danger rounded-pill">No pagado</span>` 
                                        : `<span class="badge bg-success rounded-pill">${pedido.metodo_pago}</span>`
                                    }
                                </div>
                                ${pedido.descuento > 0 ? `<small class="text-muted">Descuento aplicado: S/. ${parseFloat(pedido.descuento).toFixed(2)}</small>` : ''}
                            </div>
                            
                            <div class="items-preview mb-3">
                                ${pedido.items.slice(0, 3).map(item => `
                                    <div class="detalle-item d-flex gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary rounded-pill">${item.cantidad}</span>
                                            <small class="fw-semibold">${item.nombre}</small>
                                        </div>
                                        ${item.notas_item ? `<small class="text-muted d-block"><i class="fa-light fa-note-sticky me-1" style="line-height: normal;"></i>${item.notas_item}</small>` : ''}
                                    </div>
                                `).join('')}
                                ${pedido.items.length > 3 ? `
                                    <div class="text-center mt-2">
                                        <small class="text-muted fw-medium">+${pedido.items.length - 3} productos más...</small>
                                    </div>
                                ` : ''}
                            </div>
                            
                            ${pedido.notas ? `
                                <div class="alert alert-light border-0 rounded-3 p-3 mb-0">
                                    <small class="fw-semibold"><i class="fa-solid fa-comment me-1"></i>Notas:</small>
                                    <small class="d-block mt-1">${pedido.notas}</small>
                                </div>
                            ` : ''}
                        </div>
                        
                        <div class="card-footer bg-transparent border-0 p-4 pt-0">
                            <div class="d-grid gap-2 d-md-flex">
                                ${pedido.estado === 'pendiente' ? `
                                    <button class="btn btn-success btn-custom flex-fill" onclick="cambiarEstado(${pedido.id}, 'completado')">
                                        <i class="fa-solid fa-circle-check me-2"></i>Completar
                                    </button>
                                ` : ''}
                                <button class="btn btn-info btn-custom flex-fill" onclick="verDetalles(${pedido.id})">
                                    <i class="fa-solid fa-eye me-2"></i>Ver
                                </button>
                                <a href="editar.php?id=${pedido.id}" class="btn btn-dark btn-custom flex-fill">
                                    <i class="fa-solid fa-pen me-2"></i>Editar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function getEstadoColor(estado) {
            switch (estado) {
                case 'pendiente':
                    return 'warning';
                case 'completado':
                    return 'success';
                case 'cancelado':
                    return 'danger';
                default:
                    return 'secondary';
            }
        }

        function formatearFecha(fecha) {
            return new Date(fecha).toLocaleString('es-PE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function actualizarEstadisticas() {
            const pendientes = pedidos.filter(p => p.estado === 'pendiente').length;
            const completados = pedidos.filter(p => p.estado === 'completado').length;
            const totalVentas = pedidos.filter(p => p.estado !== 'cancelado')
                .reduce((sum, p) => sum + parseFloat(p.total), 0);

            document.getElementById('statPendientes').textContent = pendientes;
            document.getElementById('statCompletados').textContent = completados;
            <?php if ($_SESSION['rol'] == 'admin'): ?>
                
            document.getElementById('statVentas').textContent = `S/. ${totalVentas.toFixed(2)}`;
            document.getElementById('statTotal').textContent = pedidos.length;
                
            <?php endif; ?>

        }

        function filtrarPedidos() {
            const estado = document.getElementById('filtroEstado').value;
            const ubicacion = document.getElementById('filtroUbicacion').value;
            const buscar = document.getElementById('buscarPedido').value.toLowerCase();

            document.querySelectorAll('.pedido-item').forEach(item => {
                const itemEstado = item.dataset.estado;
                const itemUbicacion = item.dataset.ubicacion;
                const itemId = item.dataset.id;

                let mostrar = true;

                if (estado && itemEstado !== estado) mostrar = false;

                if (ubicacion === 'mesa' && !itemUbicacion.includes('mesa')) mostrar = false;
                else if (ubicacion && ubicacion !== 'mesa' && itemUbicacion !== ubicacion) mostrar = false;

                if (buscar && !itemId.includes(buscar)) mostrar = false;

                item.style.display = mostrar ? 'block' : 'none';
            });
        }

        function cambiarEstado(pedidoId, nuevoEstado) {
            if (!confirm(`¿Cambiar estado a "${nuevoEstado}"?`)) return;

            fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'cambiar_estado',
                        pedido_id: pedidoId,
                        estado: nuevoEstado
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        cargarPedidos();
                    } else {
                        alert('Error al cambiar estado');
                    }
                });
        }

        function eliminarPedido(pedidoId) {
            if (!confirm('¿Estás seguro de eliminar este pedido? Esta acción no se puede deshacer.')) return;

            fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'eliminar_pedido',
                        pedido_id: pedidoId
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        cargarPedidos();
                    } else {
                        alert('Error al eliminar pedido');
                    }
                });
        }

function verDetalles(pedidoId) {
    const pedido = pedidos.find(p => p.id === pedidoId);
    const content = document.getElementById('modalDetallesContent');
    const btnGuardar = document.getElementById('btnGuardarMetodoPago');
    const metodoPago = pedido.metodo_pago;

    content.innerHTML = `
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-hashtag text-primary"></i>
                    <strong>Pedido:</strong>
                    <span class="badge bg-primary rounded-pill">#${pedido.id}</span>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-flag text-primary"></i>
                    <strong>Estado:</strong>
                    <span class="status-badge badge-${pedido.estado}">${pedido.estado.toUpperCase()}</span>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-map-pin text-primary"></i>
                    <strong>Ubicación:</strong>
                    <span class="status-badge badge-ubicacion">${pedido.ubicacion.toUpperCase()}</span>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-calendar-days text-primary"></i>
                    <strong>Fecha:</strong>
                    <span class="text-muted">${formatearFecha(pedido.fecha_pedido)}</span>
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <h6 class="fw-bold mb-3">
                <i class="fa-solid fa-bag-shopping text-primary me-2"></i>
                Items del pedido
            </h6>
            <div class="list-group">
                ${pedido.items.map(item => {
                    // Calcular precios para mostrar
                    const precioUnitario = parseFloat(item.precio_unitario);
                    const precioModificado = item.precio_modificado ? parseFloat(item.precio_modificado) : null;
                    const cantidadNormal = item.cantidad - (item.cantidad_modificada || 0);
                    const cantidadModificada = item.cantidad_modificada || 0;
                    
                    let subtotalItem = 0;
                    if (cantidadModificada > 0 && precioModificado !== null) {
                        subtotalItem = (cantidadNormal * precioUnitario) + (cantidadModificada * precioModificado);
                    } else {
                        subtotalItem = item.cantidad * precioUnitario;
                    }

                    return `
                    <div class="list-group-item border-0 rounded-3 mb-2" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-primary rounded-pill">${item.cantidad}</span>
                                    <strong class="text-dark">${item.nombre}</strong>
                                    ${cantidadModificada > 0 ? `
                                        <span class="badge bg-light text-dark rounded-pill">
                                            ${cantidadModificada} modificados
                                        </span>
                                    ` : ''}
                                </div>
                                
                                ${cantidadModificada > 0 && precioModificado !== null ? `
                                    <div class="mb-2">
                                        <div class="row text-sm">
                                            <div class="col-12">
                                                ${cantidadNormal !== 0 ? `<small class="text-muted me-4">
                                                    ${cantidadNormal} × S/. ${precioUnitario.toFixed(2)} = S/. ${(cantidadNormal * precioUnitario).toFixed(2)}
                                                </small>` : ''}
                                                <small class="text-warning">
                                                    ${cantidadModificada} × S/. ${precioModificado.toFixed(2)} = S/. ${(cantidadModificada * precioModificado).toFixed(2)}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${item.notas_item ? `
                                    <div>
                                        <small class="text-muted">
                                            <i class="fa-light fa-note-sticky me-1"></i>
                                            ${item.notas_item}
                                        </small>
                                    </div>
                                ` : ''}
                            </div>
                            <span class="text-success fw-bold fs-6">
                                S/. ${subtotalItem.toFixed(2)}
                            </span>
                        </div>
                    </div>
                    `;
                }).join('')}
            </div>
        </div>
        
        ${pedido.notas ? `
            <div class="alert alert-info rounded-3 mb-4">
                <h6 class="alert-heading">
                    <i class="fa-solid fa-comment me-2"></i>
                    Notas del pedido
                </h6>
                <p class="mb-0">${pedido.notas}</p>
            </div>
        ` : ''}
        
        <div class="card rounded-3 mb-4" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
            <div class="card-body">
                <h6 class="card-title fw-bold mb-3">
                    <i class="fa-solid fa-calculator text-primary me-2"></i>
                    Resumen de pago
                </h6>
                <div class="row">
                    <div class="col-6 mb-2">
                        <strong>Subtotal:</strong>
                    </div>
                    <div class="col-6 text-end mb-2">
                        <span class="fw-semibold">S/. ${(parseFloat(pedido.total) + parseFloat(pedido.descuento)).toFixed(2)}</span>
                    </div>
                    ${pedido.descuento > 0 ? `
                        <div class="col-6 mb-2">
                            <strong class="text-warning">Descuento:</strong>
                        </div>
                        <div class="col-6 text-end mb-2">
                            <span class="text-warning fw-semibold">-S/. ${parseFloat(pedido.descuento).toFixed(2)}</span>
                        </div>
                    ` : ''}
                    <hr class="my-2">
                    <div class="col-6">
                        <h5 class="mb-0 fw-bold text-dark">Total:</h5>
                    </div>
                    <div class="col-6 text-end">
                        <h4 class="mb-0 fw-bold text-success">S/. ${parseFloat(pedido.total).toFixed(2)}</h4>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card rounded-3">
            <div class="card-body">
                <h6 class="card-title fw-bold mb-3">
                    <i class="fa-solid fa-credit-card text-primary me-2"></i>
                    Método de Pago
                </h6>
                <div class="input-group">
                    <label class="input-group-text" for="metodoPagoSelect" style="background: var(--primary-color);">
                        <i class="fa-solid fa-wallet me-2"></i>
                        Método
                    </label>
                    <select class="form-select" id="metodoPagoSelect" style="background: var(--glass-bg);">
                        <option value="Efectivo" ${metodoPago === 'Efectivo' ? 'selected' : ''}>💵 Efectivo</option>
                        <option value="QR" ${metodoPago === 'QR' ? 'selected' : ''}>📱 QR</option>
                        <option value="Tarjeta" ${metodoPago === 'Tarjeta' ? 'selected' : ''}>💳 Tarjeta</option>
                        <option value="Pendiente" ${metodoPago === 'Pendiente' ? 'selected' : ''}>⏳ Pendiente</option>
                    </select>
                </div>
            </div>
        </div>
    `;

    btnGuardar.dataset.pedidoId = pedidoId;
    btnGuardar.style.display = 'block';

    new bootstrap.Modal(document.getElementById('modalDetalles')).show();
}
        function guardarMetodoPagoEnModal(pedidoId, metodoPago) {
            fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'actualizar_metodo_pago',
                        pedido_id: pedidoId,
                        metodo_pago: metodoPago
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Mostrar mensaje de éxito con estilo moderno
                        const Toast = bootstrap.Toast.getOrCreateInstance(document.getElementById('successToast') || createToast('Método de pago actualizado correctamente', 'success'));
                        if (!document.getElementById('successToast')) {
                            document.body.appendChild(createToast('Método de pago actualizado correctamente', 'success'));
                        }

                        bootstrap.Modal.getInstance(document.getElementById('modalDetalles')).hide();
                        cargarPedidos();
                    } else {
                        alert('Error al actualizar el método de pago: ' + result.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión.');
                });
        }

        function createToast(message, type = 'success') {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" id="successToast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fa-solid fa-circle-check me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;

            const toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.innerHTML = toastHtml;

            return toastContainer;
        }
    </script>
</body>

</html>
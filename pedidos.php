<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/auth.php'; ?>
<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAJO - Gestión de Pedidos</title>
    <?php
    include $_SERVER['DOCUMENT_ROOT'] . "/assets/img/favicon.php";
    ?>
    <?php $version = date('YmdHi'); ?>
    <link href="/assets/scss/bootstrap.css?v=<?php echo $version; ?>" rel="stylesheet">
    <link href="/assets/style.css?v=<?php echo $version; ?>" rel="stylesheet">
    <link href="/assets/css/all.css" rel="stylesheet">
</head>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/nav.php"; ?>

    <div class="container-fluid p-4">
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

        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <label class="form-label fw-semibold mb-2">Estado</label>
                <select id="filtroEstado" class="form-select" onchange="filtrarPedidos()">
                    <option value="" <?= ($_SESSION['rol'] === 'admin') ? 'selected' : '' ?>>Todos los estados</option>
                    <option value="pendiente" <?= ($_SESSION['rol'] !== 'admin') ? 'selected' : '' ?>>Pendientes</option>
                    <option value="completado">Completados</option>
                    <option value="cancelado">Cancelados</option>
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
            <div class="col-lg-3 col-md-6 mb-3"
                style="<?= (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') ? '' : 'display:none;' ?>">
                <label class="form-label fw-semibold mb-2">Fecha</label>
                <input type="date" id="filtroFecha" class="form-control" onchange="cargarPedidos()"
                    value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <label class="form-label fw-semibold mb-2">Buscar</label>
                <div class="input-group">
                    <input type="text" id="buscarPedido" class="form-control" placeholder="Buscar pedido #..."
                        onkeyup="filtrarPedidos()">
                    <span class="input-group-text bg-primary border-0">
                        <i class="fa-solid fa-magnifying-glass text-white"></i>
                    </span>
                </div>
            </div>
        </div>

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

        <div id="listaPedidos" class="row">
        </div>
    </div>

    <div class="modal fade" id="modalDetalles" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
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
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPago" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-cash-register me-2 text-primary"></i>
                        Registrar Pago: Pedido #<span id="pagoPedidoId"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-primary d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Total a Pagar:</h5>
                        <h4 class="mb-0 fw-bold text-success">S/. <span id="pagoTotalMonto">0.00</span></h4>
                    </div>

                    <div class="form-check form-switch fs-5 my-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="pagoDivididoSwitch">
                        <label class="form-check-label" for="pagoDivididoSwitch">Habilitar pago dividido</label>
                    </div>

                    <div id="pagoNormalContainer">
                        <h6 class="fw-bold mb-3">Método de Pago Único</h6>
                        <select class="form-select" id="metodoPagoUnico">
                            <option value="Efectivo">💵 Efectivo</option>
                            <option value="QR">📱 QR</option>
                            <option value="Tarjeta">💳 Tarjeta</option>
                        </select>
                    </div>

                    <div id="pagoDivididoContainer" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Detalle de Pagos</h6>
                            <h5 class="mb-0">Restante: <span class="badge bg-warning" id="pagoRestante">S/.
                                    0.00</span></h5>
                        </div>
                        <div id="pagosDivididosList" class="mb-3">
                        </div>
                        <button class="btn btn-outline-primary rounded-pill w-100" id="btnAgregarPago">
                            <i class="fa-solid fa-plus me-2"></i>Añadir Otro Pago
                        </button>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success btn-custom px-4" id="btnConfirmarPago">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Registrar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        let pedidos = [];
        let modalPagoInstance = null; // Instancia del modal de pago

        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar la instancia del modal de pago una vez
            const modalPagoEl = document.getElementById('modalPago');
            if (modalPagoEl) {
                modalPagoInstance = new bootstrap.Modal(modalPagoEl);
            }

            cargarPedidos();
            setInterval(cargarPedidos, 300000);

            // Event listener para el switch de pago dividido
            document.getElementById('pagoDivididoSwitch').addEventListener('change', function() {
                const pagoNormal = document.getElementById('pagoNormalContainer');
                const pagoDividido = document.getElementById('pagoDivididoContainer');
                if (this.checked) {
                    pagoNormal.style.display = 'none';
                    pagoDividido.style.display = 'block';
                    // Si la lista está vacía, agregar el primer campo de pago
                    if (document.getElementById('pagosDivididosList').childElementCount === 0) {
                        agregarCampoPago();
                    }
                } else {
                    pagoNormal.style.display = 'block';
                    pagoDividido.style.display = 'none';
                }
            });

            // Event listener para el botón "Añadir Otro Pago"
            document.getElementById('btnAgregarPago').addEventListener('click', agregarCampoPago);

            // Event listener para el botón de confirmar pago
            document.getElementById('btnConfirmarPago').addEventListener('click', function() {
                const pedidoId = this.dataset.pedidoId;
                registrarPago(pedidoId);
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
                </div>`;
                return;
            }

            container.innerHTML = pedidos.map(pedido => {
                // Determinar si hay pagos registrados para este pedido
                const tienePagos = pedido.pagos && pedido.pagos.length > 0;
                const pagoInfo = tienePagos ?
                    pedido.pagos.map(p =>
                        `<span class="badge bg-success rounded-pill">${p.metodo_pago}</span>`).join(' ') :
                    `<span class="badge bg-danger rounded-pill">No pagado</span>`;

                return `
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
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="verDetalles(${pedido.id})"><i class="fa-solid fa-eye me-2"></i>Ver Detalles</a></li>
                                    <li><a class="dropdown-item" href="editar.php?id=${pedido.id}"><i class="fa-solid fa-pen me-2"></i>Editar</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="abrirModalPago(${pedido.id}, ${pedido.total})"><i class="fa-solid fa-cash-register me-2"></i>Editar Pago</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cambiarEstado(${pedido.id}, 'pendiente')"><i class="fa-solid fa-clock text-warning me-2"></i>Marcar Pendiente</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cambiarEstado(${pedido.id}, 'completado')"><i class="fa-solid fa-circle-check text-success me-2"></i>Marcar Completado</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cambiarEstado(${pedido.id}, 'cancelado')"><i class="fa-solid fa-circle-xmark text-danger me-2"></i>Cancelar</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="eliminarPedido(${pedido.id})"><i class="fa-solid fa-trash me-2"></i>Eliminar</a></li>
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
                                <div class="d-flex flex-wrap gap-1">${pagoInfo}</div>
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
                            ${pedido.items.length > 3 ? `<div class="text-center mt-2"><small class="text-muted fw-medium">+${pedido.items.length - 3} productos más...</small></div>` : ''}
                        </div>
                        
                        ${pedido.notas ? `<div class="alert alert-light border-0 rounded-3 p-3 mb-0"><small class="fw-semibold"><i class="fa-solid fa-comment me-1"></i>Notas:</small><small class="d-block mt-1">${pedido.notas}</small></div>` : ''}
                    </div>
                    
                    <div class="card-footer bg-transparent border-0 p-4 pt-0">
                        <div class="d-grid gap-2 d-md-flex">
                            ${pedido.estado === 'pendiente' ? `
                                <button class="btn btn-success btn-custom flex-fill" onclick="abrirModalPago(${pedido.id}, ${pedido.total})">
                                    <i class="fa-solid fa-cash-register me-2"></i>Pagar
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
            </div>`;
            }).join('');
        }

        // Funciones de ayuda
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
                .then(result => result.success ? cargarPedidos() : alert('Error al cambiar estado'));
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
                .then(result => result.success ? cargarPedidos() : alert('Error al eliminar pedido'));
        }

        function verDetalles(pedidoId) {
            const pedido = pedidos.find(p => p.id == pedidoId); // usar == por si es string vs número
            if (!pedido) {
                console.error('Pedido no encontrado', pedidoId);
                return;
            }
            const content = document.getElementById('modalDetallesContent');

            // --- INICIO: LÓGICA para procesar pagos ---
            const tienePagos = pedido.pagos && pedido.pagos.length > 0;
            const esPagoDividido = tienePagos && pedido.pagos.length > 1;

            let pagosDetalleHTML = '';

            if (esPagoDividido) {
                // Agrupar los pagos por método y sumar los montos
                const pagosAgrupados = pedido.pagos.reduce((acc, pago) => {
                    acc[pago.metodo_pago] = (acc[pago.metodo_pago] || 0) + parseFloat(pago.monto);
                    return acc;
                }, {});

                // Generar el HTML para el detalle del pago dividido
                pagosDetalleHTML = `
                    <div class="card rounded-3 mb-4 border-info bg-info-subtle">
                        <div class="card-body">
                            <h6 class="card-title fw-bold mb-3 text-info"><i class="fa-solid fa-receipt me-2"></i>Detalle de Pago Dividido</h6>
                            <ul class="list-group list-group-flush bg-transparent">
                                ${Object.entries(pagosAgrupados).map(([metodo, monto]) => `
                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-1 border-0">
                                        <span class="text-dark fw-medium">${metodo}:</span>
                                        <span class="fw-bold">S/. ${monto.toFixed(2)}</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>
                `;
            }
            // --- FIN: LÓGICA para procesar pagos ---

            // --- INICIO: LÓGICA MEJORADA para renderizar items ---
            const itemsHTML = pedido.items.map(item => {
                let itemTotal = 0;
                let precioDetalleHTML = '';

                const cantTotal = parseInt(item.cantidad, 10);
                const precioUnitario = parseFloat(item.precio_unitario);
                const cantModificada = parseInt(item.cantidad_modificada, 10);
                const precioModificado = parseFloat(item.precio_modificado);

                const tieneOferta = precioModificado && cantModificada > 0;

                if (tieneOferta) {
                    const cantNormal = cantTotal - cantModificada;
                    itemTotal = (cantModificada * precioModificado) + (cantNormal * precioUnitario);

                    let detallePartes = [];

                    // 1. Añadir la parte a precio normal, si existe
                    if (cantNormal > 0) {
                        detallePartes.push(`${cantNormal} x S/. ${precioUnitario.toFixed(2)}`);
                    }

                    // 2. Añadir la parte con la oferta
                    detallePartes.push(`<span class="text-info">${cantModificada} x S/. ${precioModificado.toFixed(2)} (Oferta)</span>`);

                    // 3. Unir todo en una sola línea con un '+'
                    precioDetalleHTML = `<div class="mt-1"><small class="text-muted">${detallePartes.join(' + ')}</small></div>`;

                } else {
                    // Lógica original si no hay oferta
                    itemTotal = cantTotal * precioUnitario;
                    precioDetalleHTML = `
                <div class="mt-1">
                    <small class="text-muted">${cantTotal} x S/. ${precioUnitario.toFixed(2)}</small>
                </div>`;
                }
                return `
            <div class="list-group-item border-0 rounded-3 mb-2" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary rounded-pill">${item.cantidad}</span>
                            <strong class="text-dark">${item.nombre}</strong>
                        </div>
                        ${precioDetalleHTML}
                        ${item.notas_item ? `<div class="mt-2"><small class="text-muted"><i class="fa-light fa-note-sticky me-1"></i>${item.notas_item}</small></div>` : ''}
                    </div>
                    <span class="text-success fw-bold fs-6">S/. ${itemTotal.toFixed(2)}</span>
                </div>
            </div>`;
            }).join('');
            // --- FIN: LÓGICA MEJORADA para renderizar items ---

            content.innerHTML = `
        <div class="row mb-4">
            <div class="col-md-6 mb-3"><div class="d-flex align-items-center gap-2"><i class="fa-solid fa-hashtag text-primary"></i><strong>Pedido:</strong><span class="badge bg-primary rounded-pill">#${pedido.id}</span></div></div>
            <div class="col-md-6 mb-3"><div class="d-flex align-items-center gap-2"><i class="fa-solid fa-flag text-primary"></i><strong>Estado:</strong><span class="status-badge badge-${pedido.estado}">${pedido.estado.toUpperCase()}</span></div></div>
            <div class="col-md-6 mb-3"><div class="d-flex align-items-center gap-2"><i class="fa-solid fa-map-pin text-primary"></i><strong>Ubicación:</strong><span class="status-badge badge-ubicacion">${pedido.ubicacion.toUpperCase()}</span></div></div>
            <div class="col-md-6 mb-3"><div class="d-flex align-items-center gap-2"><i class="fa-solid fa-calendar-days text-primary"></i><strong>Fecha:</strong><span class="text-muted">${formatearFecha(pedido.fecha_pedido)}</span></div></div>
        </div>
        <div class="mb-4">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-bag-shopping text-primary me-2"></i>Items del pedido</h6>
            <div class="list-group">${itemsHTML}</div>
        </div>
        ${pedido.notas ? `<div class="alert alert-info rounded-3 mb-4"><h6 class="alert-heading"><i class="fa-solid fa-comment me-2"></i>Notas del pedido</h6><p class="mb-0">${pedido.notas}</p></div>` : ''}
        
        ${pagosDetalleHTML}

        <div class="card rounded-3 mb-4" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
            <div class="card-body">
                <h6 class="card-title fw-bold mb-3"><i class="fa-solid fa-calculator text-primary me-2"></i>Resumen de pago</h6>
                <div class="row">
                    <div class="col-6 mb-2"><strong>Subtotal:</strong></div><div class="col-6 text-end mb-2"><span class="fw-semibold">S/. ${(parseFloat(pedido.total) + parseFloat(pedido.descuento)).toFixed(2)}</span></div>
                    ${pedido.descuento > 0 ? `<div class="col-6 mb-2"><strong class="text-warning">Descuento:</strong></div><div class="col-6 text-end mb-2"><span class="text-warning fw-semibold">-S/. ${parseFloat(pedido.descuento).toFixed(2)}</span></div>` : ''}
                    <hr class="my-2"><div class="col-6"><h5 class="mb-0 fw-bold text-dark">Total:</h5></div><div class="col-6 text-end"><h4 class="mb-0 fw-bold text-success">S/. ${parseFloat(pedido.total).toFixed(2)}</h4></div>
                </div>
            </div>
        </div>`;
            new bootstrap.Modal(document.getElementById('modalDetalles')).show();
        }


        // --- LÓGICA DEL MODAL DE PAGO ---

        function abrirModalPago(pedidoId, total) {
            const totalAPagar = parseFloat(total);

            // Resetear el modal
            document.getElementById('pagoPedidoId').textContent = pedidoId;
            document.getElementById('pagoTotalMonto').textContent = totalAPagar.toFixed(2);
            document.getElementById('btnConfirmarPago').dataset.pedidoId = pedidoId;
            document.getElementById('btnConfirmarPago').dataset.total = totalAPagar;

            // Resetear switch y contenedores
            const switchEl = document.getElementById('pagoDivididoSwitch');
            switchEl.checked = false;
            document.getElementById('pagoNormalContainer').style.display = 'block';
            document.getElementById('pagoDivididoContainer').style.display = 'none';
            document.getElementById('pagosDivididosList').innerHTML = '';
            document.getElementById('pagoRestante').textContent = `S/. ${totalAPagar.toFixed(2)}`;

            // Mostrar el modal usando la instancia global
            if (modalPagoInstance) {
                modalPagoInstance.show();
            }
        }

        function agregarCampoPago() {
            const container = document.getElementById('pagosDivididosList');
            const pagoId = Date.now(); // ID único para el nuevo campo de pago

            const nuevoPagoHtml = `
            <div class="row g-2 align-items-center mb-2" id="pago-row-${pagoId}">
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text">S/.</span>
                        <input type="number" class="form-control monto-dividido" placeholder="Monto" oninput="actualizarPagoRestante()">
                    </div>
                </div>
                <div class="col">
                    <select class="form-select metodo-dividido">
                        <option value="Efectivo">💵 Efectivo</option>
                        <option value="QR">📱 QR</option>
                        <option value="Tarjeta">💳 Tarjeta</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-danger" onclick="document.getElementById('pago-row-${pagoId}').remove(); actualizarPagoRestante();">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>`;
            container.insertAdjacentHTML('beforeend', nuevoPagoHtml);
            actualizarPagoRestante();
        }

        function actualizarPagoRestante() {
            const totalAPagar = parseFloat(document.getElementById('btnConfirmarPago').dataset.total) || 0;
            let totalIngresado = 0;
            document.querySelectorAll('.monto-dividido').forEach(input => {
                totalIngresado += parseFloat(input.value) || 0;
            });
            const restante = totalAPagar - totalIngresado;
            const restanteEl = document.getElementById('pagoRestante');
            restanteEl.textContent = `S/. ${restante.toFixed(2)}`;
            restanteEl.classList.toggle('bg-success', restante <= 0);
            restanteEl.classList.toggle('bg-warning', restante > 0);
        }

        function registrarPago(pedidoId) {
            const totalAPagar = parseFloat(document.getElementById('btnConfirmarPago').dataset.total);
            const esDividido = document.getElementById('pagoDivididoSwitch').checked;
            let pagos = [];
            let error = null;

            if (esDividido) {
                const filasPago = document.querySelectorAll('#pagosDivididosList .row');
                let sumaPagos = 0;

                filasPago.forEach(fila => {
                    const monto = parseFloat(fila.querySelector('.monto-dividido').value);
                    const metodo = fila.querySelector('.metodo-dividido').value;
                    if (isNaN(monto) || monto <= 0) {
                        error = "Todos los montos deben ser números positivos.";
                        return;
                    }
                    pagos.push({
                        metodo_pago: metodo,
                        monto: monto
                    });
                    sumaPagos += monto;
                });

                if (Math.abs(sumaPagos - totalAPagar) > 0.01) { // Tolerancia para decimales
                    error = `La suma de los pagos (S/. ${sumaPagos.toFixed(2)}) no coincide con el total a pagar (S/. ${totalAPagar.toFixed(2)}).`;
                }

            } else {
                const metodo = document.getElementById('metodoPagoUnico').value;
                pagos.push({
                    metodo_pago: metodo,
                    monto: totalAPagar
                });
            }

            if (error) {
                alert(error);
                return;
            }

            // Enviar a la API
            fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'registrar_pago',
                        pedido_id: pedidoId,
                        pagos: pagos
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        if (modalPagoInstance) {
                            modalPagoInstance.hide();
                        }
                        cargarPedidos();

                        // ----------------------------
                        // Llamada interna para stock crítico y notificación
                        // ----------------------------
                        fetch('/config/stocknoti.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    pedido_id: pedidoId
                                })
                            })
                            .catch(err => console.error('Error enviando stock crítico:', err));

                    } else {
                        alert('Error al registrar el pago: ' + (result.error || 'Error desconocido'));
                    }
                })

                .catch(err => {
                    console.error('Error en fetch:', err);
                    alert('Hubo un problema de conexión al registrar el pago.');
                });
        }
    </script>
</body>

</html>
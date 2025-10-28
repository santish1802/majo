<?php require $_SERVER['DOCUMENT_ROOT'] . '/auth.php'; ?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php"; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAJO - Control de Stock</title>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/assets/img/favicon.php"; ?>
    <link href="/assets/scss/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/style.css" rel="stylesheet">
    <style>
        .nav-tabs .nav-link {
            color: #6c757d;
        }

        .nav-tabs .nav-link.active {
            color: #495057;
            font-weight: bold;
        }

        .stock-badge {
            font-size: 0.9rem;
            padding: 0.4rem 0.8rem;
        }

        .stock-bajo {
            background-color: #dc3545;
            color: white;
        }

        .stock-medio {
            background-color: #ffc107;
            color: #000;
        }

        .stock-alto {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/nav.php"; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-warehouse"></i> Control de Stock</h2>
            </div>
        </div>

        <ul class="nav nav-tabs mt-3" id="stockTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="productos-tab" data-bs-toggle="tab" data-bs-target="#productos" type="button">
                    <i class="fas fa-box"></i> Productos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="ingredientes-tab" data-bs-toggle="tab" data-bs-target="#ingredientes" type="button">
                    <i class="fas fa-flask"></i> Ingredientes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="frutas-tab" data-bs-toggle="tab" data-bs-target="#frutas" type="button">
                    <i class="fas fa-apple-alt"></i> Frutas
                </button>
            </li>
        </ul>

        <div class="tab-content mt-3" id="stockTabContent">

            <!-- @c-red PRODUCTOS -->
            <div class="tab-pane fade show active" id="productos" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <!-- 🔽 Dropdown de filtros -->
                    <div class="dropdown mb-2">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownFiltroCategorias" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-filter"></i> Filtrar por categoría
                        </button>
                        <div class="dropdown-menu p-3" id="filtroCategorias" style="max-height: 300px; overflow-y: auto;">
                            <!-- Checkboxes dinámicos aquí -->
                        </div>
                    </div>

                    <button class="btn btn-success" onclick="exportarProductosAWhatsApp()">
                        <i class="fab fa-whatsapp"></i> Exportar a WhatsApp
                    </button>
                </div>

                <div class="table-responsive">
                    <table id="tablaProductos" class="table table-striped table-hover display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th class="all">Nombre</th>
                                <th>Categoría</th>
                                <th class="all">Stock Actual</th>
                                <th class="all">Stock Mínimo</th>
                                <th>Estado</th>
                                <th class="none">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="ingredientes" role="tabpanel">
                <div class="table-responsive">
                    <table id="tablaIngredientes" class="table table-striped table-hover display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th class="all">Nombre</th>
                                <th class="all">Stock Actual</th>
                                <th class="all">Stock Mínimo</th>
                                <th>Unidad</th>
                                <th>Estado</th>
                                <th class="none">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="frutas" role="tabpanel">
                <div class="table-responsive">
                    <table id="tablaFrutas" class="table table-striped table-hover display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th class="all">Nombre</th>
                                <th class="all">Stock Actual</th>
                                <th class="all">Stock Mínimo</th>
                                <th>Estado</th>
                                <th class="none">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarStock" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Editar Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editId">
                    <input type="hidden" id="editTipo">

                    <div class="mb-3">
                        <label class="form-label"><strong id="labelNombre">Nombre</strong></label>
                        <p id="itemNombre" class="form-control-plaintext"></p>
                    </div>

                    <div class="mb-3">
                        <label for="editStockActual" class="form-label">Stock Actual</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" onclick="ajustarStock(-10)">-10</button>
                            <button class="btn btn-outline-secondary" type="button" onclick="ajustarStock(-1)">-1</button>
                            <input type="number" class="form-control text-center" id="editStockActual" step="0.01" min="0">
                            <button class="btn btn-outline-secondary" type="button" onclick="ajustarStock(1)">+1</button>
                            <button class="btn btn-outline-secondary" type="button" onclick="ajustarStock(10)">+10</button>
                        </div>
                    </div>

                    <div id="seccionStockMinimo" class="mb-3">
                        <label for="editStockMinimo" class="form-label">Stock Mínimo</label>
                        <input type="number" class="form-control" id="editStockMinimo" step="0.01" min="0">
                    </div>

                    <div class="mb-3">
                        <label for="editUnidad" class="form-label">Unidad de Medida</label>
                        <input type="text" class="form-control" id="editUnidad" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarStock()">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <script>
        let tablaIngredientes, tablaProductos, tablaFrutas;

        $(document).ready(function() {
            inicializarTablas();

            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('data-bs-target');
                if (target === '#productos') tablaProductos.ajax.reload();
                else if (target === '#ingredientes') tablaIngredientes.ajax.reload();
                else if (target === '#frutas') tablaFrutas.ajax.reload();
            });
        });

        function inicializarTablas() {
            // ✅ Tabla Productos con filtro por categoría
            tablaProductos = $('#tablaProductos').DataTable({
                responsive: true,
                pageLength: 25,
                ajax: {
                    url: 'api_control_stock.php?action=listar_productos_stock',
                    dataSrc: 'data'
                },
                columns: [{
                        data: 'nombre'
                    },
                    {
                        data: 'categoria'
                    },
                    {
                        data: 'stock_actual'
                    },
                    {
                        data: 'stock_minimo'
                    },
                    {
                        data: null,
                        render: function(data) {
                            const stock = parseFloat(data.stock_actual) || 0;
                            const minimo = parseFloat(data.stock_minimo) || 0;

                            // Si no hay stock mínimo definido, clasificamos de forma lógica
                            if (minimo <= 0) {
                                if (stock === 0) return '<span class="badge stock-bajo">Sin stock</span>';
                                if (stock <= 3) return '<span class="badge stock-medio">Poco</span>';
                                return '<span class="badge stock-alto">Suficiente</span>';
                            }

                            // Calcular porcentaje del stock actual respecto al mínimo
                            const ratio = stock / minimo;

                            if (stock === 0) return '<span class="badge stock-bajo">Sin stock</span>';
                            if (ratio <= 1) return '<span class="badge stock-bajo">Bajo</span>';
                            if (ratio <= 2) return '<span class="badge stock-medio">Medio</span>';
                            return '<span class="badge stock-alto">Alto</span>';
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `<button class="btn btn-sm btn-primary" onclick='editarItem(${data.id}, "producto", "${data.nombre.replace(/'/g, "\\'")}",${data.stock_actual}, ${data.stock_minimo}, "unidad")'>
                        <i class="fas fa-edit"></i> Editar
                    </button>`;
                        }
                    }
                ],
                language: {
                    url: "/assets/es_es.json"
                },
                order: [
                    [1, 'asc']
                ],
                initComplete: function() {
                    const api = this.api();
                    const contenedor = $('#filtroCategorias').empty();
                    const columnaCategorias = api.column(1); // columna categoría

                    // Obtener valores únicos de categorías
                    const categorias = columnaCategorias.data().unique().sort();

                    // Crear checkboxes dinámicos
                    categorias.each(function(cat) {
                        // Desmarcar por defecto la categoría "bebidas"
                        const checked = cat.toLowerCase() === 'bebidas' ? '' : 'checked';
                        const nombreCapitalizado = cat.charAt(0).toUpperCase() + cat.slice(1);

                        contenedor.append(`
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="${cat}" id="cat-${cat}" ${checked}>
                        <label class="form-check-label" for="cat-${cat}">${nombreCapitalizado}</label>
                    </div>
                `);
                    });

                    // Evento para filtrar por checkboxes seleccionados
                    contenedor.on('change', 'input[type="checkbox"]', function() {
                        const seleccionadas = contenedor.find('input:checked').map(function() {
                            return '^' + $.fn.dataTable.util.escapeRegex($(this).val()) + '$';
                        }).get();

                        if (seleccionadas.length > 0) {
                            columnaCategorias.search(seleccionadas.join('|'), true, false).draw();
                        } else {
                            // Si no hay ninguna seleccionada, muestra todo
                            columnaCategorias.search('').draw();
                        }
                    });
                    contenedor.find('input[type="checkbox"]').trigger('change');

                }
            });

            // Tabla Ingredientes
            tablaIngredientes = $('#tablaIngredientes').DataTable({
                responsive: true,
                pageLength: 25,
                ajax: {
                    url: 'api_control_stock.php?action=listar_ingredientes_stock',
                    dataSrc: 'data'
                },
                columns: [
                    {
                        data: 'nombre'
                    },
                    {
                        data: 'stock_actual',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'stock_minimo',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'unidad_medida'
                    },
                    {
                        data: null,
                        render: function(data) {
                            const stock = parseFloat(data.stock_actual) || 0;
                            const minimo = parseFloat(data.stock_minimo) || 0;

                            // Si no hay stock mínimo definido, clasificamos de forma lógica
                            if (minimo <= 0) {
                                if (stock === 0) return '<span class="badge stock-bajo">Sin stock</span>';
                                if (stock <= 3) return '<span class="badge stock-medio">Poco</span>';
                                return '<span class="badge stock-alto">Suficiente</span>';
                            }

                            // Calcular porcentaje del stock actual respecto al mínimo
                            const ratio = stock / minimo;

                            if (stock === 0) return '<span class="badge stock-bajo">Sin stock</span>';
                            if (ratio <= 1) return '<span class="badge stock-bajo">Bajo</span>';
                            if (ratio <= 2) return '<span class="badge stock-medio">Medio</span>';
                            return '<span class="badge stock-alto">Alto</span>';
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `<button class="btn btn-sm btn-primary" onclick='editarItem(${data.id}, "ingrediente", "${data.nombre.replace(/'/g, "\\'")}",${data.stock_actual}, ${data.stock_minimo}, "${data.unidad_medida}")'>
                                <i class="fas fa-edit"></i> Editar
                            </button>`;
                        }
                    }
                ],
                language: {
                    url: "/assets/es_es.json"
                },
                order: [
                    [1, 'asc']
                ]
            });


            // Tabla Frutas
            tablaFrutas = $('#tablaFrutas').DataTable({
                responsive: true,
                pageLength: 25,
                ajax: {
                    url: 'api_control_stock.php?action=listar_frutas_stock',
                    dataSrc: 'data'
                },
                columns: [
                    {
                        data: 'nombre'
                    },
                    {
                        data: 'stock_actual',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'stock_minimo',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            const stock = parseFloat(data.stock_actual) || 0;
                            const minimo = parseFloat(data.stock_minimo) || 0;

                            // Si no hay stock mínimo definido, clasificamos de forma lógica
                            if (minimo <= 0) {
                                if (stock === 0) return '<span class="badge stock-bajo">Sin stock</span>';
                                if (stock <= 3) return '<span class="badge stock-medio">Poco</span>';
                                return '<span class="badge stock-alto">Suficiente</span>';
                            }

                            // Calcular porcentaje del stock actual respecto al mínimo
                            const ratio = stock / minimo;

                            if (stock === 0) return '<span class="badge stock-bajo">Sin stock</span>';
                            if (ratio <= 1) return '<span class="badge stock-bajo">Bajo</span>';
                            if (ratio <= 2) return '<span class="badge stock-medio">Medio</span>';
                            return '<span class="badge stock-alto">Alto</span>';
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `<button class="btn btn-sm btn-primary" onclick='editarItem(${data.id}, "fruta", "${data.nombre.replace(/'/g, "\\'")}",${data.stock_actual}, 0, "fruta")'>
                                <i class="fas fa-edit"></i> Editar
                            </button>`;
                        }
                    }
                ],
                language: {
                    url: "/assets/es_es.json"
                },
                order: [
                    [1, 'asc']
                ]
            });

            // Manejar clicks en controles responsive para cerrar otros
            $('#tablaIngredientes, #tablaProductos, #tablaFrutas').on('click', 'tr td.dtr-control', function() {
                var table = $(this).closest('table').DataTable();
                var tr = $(this).closest('tr');
                var row = table.row(tr);

                if (!row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('dtr-expanded');
                } else {
                    table.rows('.dtr-expanded').every(function() {
                        $(this.node()).find('td.dtr-control').trigger('click');
                    });
                    row.child.show();
                    tr.addClass('dtr-expanded');
                }
            });
        }

        function editarItem(id, tipo, nombre, stockActual, stockMinimo, unidad) {
            $('#editId').val(id);
            $('#editTipo').val(tipo);
            $('#itemNombre').text(nombre);
            $('#editStockActual').val(stockActual);
            $('#editStockMinimo').val(stockMinimo);
            $('#editUnidad').val(unidad);

            // Mostrar siempre stock mínimo (tanto para productos como ingredientes)
            $('#seccionStockMinimo').show();

            new bootstrap.Modal(document.getElementById('modalEditarStock')).show();
        }

        function ajustarStock(cantidad) {
            const input = $('#editStockActual');
            let valorActual = parseFloat(input.val()) || 0;
            let nuevoValor = valorActual + cantidad;
            if (nuevoValor < 0) nuevoValor = 0;
            input.val(nuevoValor);
        }

        function guardarStock() {
            const id = $('#editId').val();
            const tipo = $('#editTipo').val();
            const stockActual = $('#editStockActual').val();
            const stockMinimo = $('#editStockMinimo').val();

            const data = {
                id: id,
                tipo: tipo,
                stock_actual: stockActual,
                stock_minimo: stockMinimo
            };

            $.ajax({
                url: 'api_control_stock.php?action=actualizar_stock',
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(result) {
                    if (result.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalEditarStock')).hide();

                        // Recargar la tabla correspondiente
                        if (tipo === 'ingrediente') tablaIngredientes.ajax.reload();
                        else if (tipo === 'producto') tablaProductos.ajax.reload();
                        else if (tipo === 'fruta') tablaFrutas.ajax.reload();
                    }
                }
            });
        }

        // Nueva función para exportar los productos a WhatsApp
function exportarProductosAWhatsApp() {
    // Obtener categorías seleccionadas (usa jQuery para tu markup actual)
    const categoriasSeleccionadas = $('#filtroCategorias input[type="checkbox"]:checked')
        .map(function() { return $(this).val().toString().toLowerCase(); })
        .get();

    if (categoriasSeleccionadas.length === 0) {
        alert('Selecciona al menos una categoría en el filtro para exportar.');
        return;
    }

    // Obtener todos los productos desde la DataTable
    const datos = tablaProductos.rows().data().toArray();

    // Filtrar productos por las categorías seleccionadas (case-insensitive)
    const productosFiltrados = datos.filter(item => {
        const catItem = (item.categoria || '').toString().toLowerCase();
        return categoriasSeleccionadas.includes(catItem);
    });

    if (productosFiltrados.length === 0) {
        alert('No hay productos para las categorías seleccionadas.');
        return;
    }

    // Construir el mensaje (agrupado por categoría opcionalmente)
    let mensaje = '*🧾 Reporte de Stock de Productos Seleccionados:*\n\n';
    // Opcional: agrupar por categoría
    const agrupado = {};
    productosFiltrados.forEach(p => {
        const cat = (p.categoria || 'sin categoría').toString();
        agrupado[cat] = agrupado[cat] || [];
        agrupado[cat].push(p);
    });

    Object.keys(agrupado).forEach(cat => {
        mensaje += `*${cat.charAt(0).toUpperCase() + cat.slice(1)}*\n`;
        agrupado[cat].forEach(p => {
            mensaje += `- ${p.nombre}: ${p.stock_actual}\n`;
        });
        mensaje += '\n';
    });

    // Número de WhatsApp
    const numeroWhatsApp = "51956263107";
    const mensajeCodificado = encodeURIComponent(mensaje);
    const urlWhatsApp = `https://wa.me/${numeroWhatsApp}?text=${mensajeCodificado}`;

    window.open(urlWhatsApp, '_blank');
}

    </script>
        <script type="module">
        // ---------------------------------------------------------------
        // Firebase Cloud Messaging (FCM) - Obtener y Enviar Token + Fingerprint (con cache)
        // ---------------------------------------------------------------
        import {
            initializeApp
        } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js";
        import {
            getMessaging,
            getToken
        } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-messaging.js";

        // Configuración Firebase
        const firebaseConfig = {
            apiKey: "AIzaSyBulpMP6bbJ66DjiHTPkYTDFnOGLofxwrE",
            authDomain: "majo-19e66.firebaseapp.com",
            projectId: "majo-19e66",
            storageBucket: "majo-19e66.firebasestorage.app",
            messagingSenderId: "335384882055",
            appId: "1:335384882055:web:12a545b31ea22c91f60122",
            measurementId: "G-T1PEZLGN7N"
        };
        const VAPID_KEY = 'BANRq3owV2f4D_1iri6qQVdVn5igZ_5m2RcqH9kmH0S_67gIzUL3nasWI5cedjJPEIMIlm2egz_Cs-7lqGYXrIo';

        // Inicializar Firebase
        const app = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        // ---------------------------------------------------------------
        // Generar Fingerprint Único (cacheado en localStorage)
        // ---------------------------------------------------------------
        async function generateFingerprint() {
            const cachedFingerprint = localStorage.getItem('deviceFingerprint');
            if (cachedFingerprint) {
                console.log('✅ Fingerprint obtenido desde cache:', cachedFingerprint);
                return cachedFingerprint;
            }

            console.log('🌀 Generando nuevo fingerprint del navegador...');
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = "top";
            ctx.font = "14px Arial";
            ctx.fillStyle = "#f60";
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = "#069";
            ctx.fillText("fingerprint_test", 2, 2);
            const canvasData = canvas.toDataURL();

            const parts = [
                navigator.userAgent || '',
                navigator.platform || '',
                navigator.language || '',
                screen.width + 'x' + screen.height,
                screen.colorDepth || '',
                navigator.hardwareConcurrency || '',
                canvasData
            ];
            const raw = parts.join('||');

            const enc = new TextEncoder().encode(raw);
            const hashBuffer = await crypto.subtle.digest('SHA-256', enc);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

            localStorage.setItem('deviceFingerprint', hashHex);
            console.log('✅ Fingerprint generado y guardado en cache:', hashHex);
            return hashHex;
        }

        // ---------------------------------------------------------------
        // Obtener Token y Enviar al Servidor
        // ---------------------------------------------------------------
        async function requestAndSendToken() {
            console.log('🚀 Iniciando proceso FCM...');

            const fingerprint = await generateFingerprint();
            const cachedData = JSON.parse(localStorage.getItem('fcmData') || '{}');

            // Si ya hay token y pertenece al mismo fingerprint, no se genera de nuevo
            if (cachedData.token && cachedData.fingerprint === fingerprint) {
                console.log('✅ Token y fingerprint ya cacheados. No se solicitará nuevo token.');
                console.log('🔑 Token FCM (cache):', cachedData.token);
                return;
            }

            try {
                const registration = await navigator.serviceWorker.register("/push/sw.js");
                console.log('✅ Service Worker registrado correctamente.');

                const currentToken = await getToken(messaging, {
                    serviceWorkerRegistration: registration,
                    vapidKey: VAPID_KEY
                });

                if (!currentToken) {
                    console.error('❌ No se pudo obtener el token. Habilita las notificaciones en tu navegador.');
                    return;
                }

                console.log('🔑 Token FCM obtenido:', currentToken);

                await sendTokenToServer(currentToken, fingerprint);

                // Guardar token y fingerprint en cache
                localStorage.setItem('fcmData', JSON.stringify({
                    token: currentToken,
                    fingerprint: fingerprint,
                    timestamp: Date.now()
                }));

                console.log('🎉 Token y fingerprint enviados y guardados correctamente.');

            } catch (err) {
                console.error('❌ Error en el proceso FCM:', err.message);
            }
        }

        // ---------------------------------------------------------------
        // Enviar Token y Fingerprint al Servidor PHP
        // ---------------------------------------------------------------
        async function sendTokenToServer(token, fingerprint) {
            console.log('📡 Enviando token y fingerprint a /push/token.php...');

            const response = await fetch('/push/token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    fcm_token: token,
                    device_id: fingerprint
                }),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(`Fallo del servidor (${response.status}): ${errorData.message || 'Error desconocido'}`);
            }

            const result = await response.json();
            console.log('📬 Respuesta del servidor PHP:', result.message || JSON.stringify(result));
        }

        // ---------------------------------------------------------------
        // Ejecutar automáticamente al cargar
        // ---------------------------------------------------------------
        requestAndSendToken();
    </script>
</body>

</html>
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

        <!-- Tabs -->
        <ul class="nav nav-tabs mt-3" id="stockTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="ingredientes-tab" data-bs-toggle="tab" data-bs-target="#ingredientes" type="button">
                    <i class="fas fa-flask"></i> Ingredientes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="productos-tab" data-bs-toggle="tab" data-bs-target="#productos" type="button">
                    <i class="fas fa-box"></i> Productos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="frutas-tab" data-bs-toggle="tab" data-bs-target="#frutas" type="button">
                    <i class="fas fa-apple-alt"></i> Frutas
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content mt-3" id="stockTabContent">
            <!-- Tab Ingredientes -->
            <div class="tab-pane fade show active" id="ingredientes" role="tabpanel">
                <div class="table-responsive">
                    <table id="tablaIngredientes" class="table table-striped table-hover display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th class="all">Nombre</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th>Unidad</th>
                                <th class="all">Estado</th>
                                <th class="none">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Productos -->
            <div class="tab-pane fade" id="productos" role="tabpanel">
                <div class="table-responsive">
                    <table id="tablaProductos" class="table table-striped table-hover display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th class="all">Nombre</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th class="all">Estado</th>
                                <th class="none">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Frutas -->
            <div class="tab-pane fade" id="frutas" role="tabpanel">
                <div class="table-responsive">
                    <table id="tablaFrutas" class="table table-striped table-hover display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th class="all">Nombre</th>
                                <th>Stock Actual</th>
                                <th class="all">Estado</th>
                                <th class="none">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Stock -->
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
            // Inicializar tablas
            inicializarTablas();

            // Recargar datos al cambiar de tab
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('data-bs-target');
                if (target === '#ingredientes') tablaIngredientes.ajax.reload();
                else if (target === '#productos') tablaProductos.ajax.reload();
                else if (target === '#frutas') tablaFrutas.ajax.reload();
            });
        });

        function inicializarTablas() {
            // Tabla Ingredientes
            tablaIngredientes = $('#tablaIngredientes').DataTable({
                responsive: true,
                ajax: {
                    url: 'api_control_stock.php?action=listar_ingredientes_stock',
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'id' },
                    { data: 'nombre' },
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
                    { data: 'unidad_medida' },
                    {
                        data: null,
                        render: function(data) {
                            const stock = parseFloat(data.stock_actual);
                            const minimo = parseFloat(data.stock_minimo);
                            if (stock <= minimo) return '<span class="badge stock-bajo">Bajo</span>';
                            if (stock <= minimo * 2) return '<span class="badge stock-medio">Medio</span>';
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
                order: [[1, 'asc']]
            });

            // Tabla Productos
            tablaProductos = $('#tablaProductos').DataTable({
                responsive: true,
                ajax: {
                    url: 'api_control_stock.php?action=listar_productos_stock',
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'id' },
                    { data: 'nombre' },
                    { data: 'stock_actual' },
                    { data: 'stock_minimo' },
                    {
                        data: null,
                        render: function(data) {
                            const stock = parseInt(data.stock_actual);
                            const minimo = parseInt(data.stock_minimo);
                            if (stock <= minimo) return '<span class="badge stock-bajo">Bajo</span>';
                            if (stock <= minimo * 2) return '<span class="badge stock-medio">Medio</span>';
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
                order: [[1, 'asc']]
            });

            // Tabla Frutas
            tablaFrutas = $('#tablaFrutas').DataTable({
                responsive: true,
                ajax: {
                    url: 'api_control_stock.php?action=listar_frutas_stock',
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'id' },
                    { data: 'nombre' },
                    {
                        data: 'stock_actual',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            const stock = parseFloat(data.stock_actual);
                            if (stock <= 5) return '<span class="badge stock-bajo">Bajo</span>';
                            if (stock <= 20) return '<span class="badge stock-medio">Medio</span>';
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
                order: [[1, 'asc']]
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
    </script>
</body>

</html>
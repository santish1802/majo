<?php require $_SERVER['DOCUMENT_ROOT'] . '/auth.php'; ?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php"; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAJO - Gestión de Productos</title>
    <?php
    include $_SERVER['DOCUMENT_ROOT'] . "/assets/img/favicon.php";
    ?>
    <link href="/assets/scss/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/style.css" rel="stylesheet">
    <style>
        .ingredient-row {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .btn-check:checked+.btn small.d-block.text-muted {
            color: white !important;
        }

        .tipo-stock-btn {
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
        }

        .tipo-stock-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/nav.php"; ?>
    <div class="container-fluid">

        <div class="row mt-3">
            <div class="col-12 text-end mb-3">
                <button class="btn btn-primary" onclick="abrirModal()">
                    <i class="fas fa-plus"></i> Añadir Producto
                </button>
            </div>
            <div class="col-12">
                <div class="table-responsive">
                    <table id="productosTable" class="table table-striped table-bordered display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th class="all">Nombre</th>
                                <th>Categoría</th>
                                <th class="all">Precio</th>
                                <th class="none">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Producto -->
    <div class="modal fade" id="modalProducto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Añadir Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="productoId">
                    <div class="mb-3">
                        <label for="productoNombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="productoNombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="productoCategoria" class="form-label">Categoría</label>
                        <select class="form-select" id="productoCategoria">
                            <option value="cafe">Café</option>
                            <option value="dulce">Dulce</option>
                            <option value="dulces">Dulces</option>
                            <option value="empanadas">Empanadas</option>
                            <option value="extras">Extras</option>
                            <option value="frappes">Frappes</option>
                            <option value="frios">Fríos</option>
                            <option value="infusiones">Infusiones</option>
                            <option value="jugos">Jugos</option>
                            <option value="sanguches">Sanguches</option>
                            <option value="waffles">Waffles</option>
                            <option value="otros">Otros</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="productoPrecio" class="form-label">Precio</label>
                        <input type="number" step="0.01" class="form-control" id="productoPrecio" required>
                    </div>
                    <div class="mb-3">
                        <label for="productoDetalle" class="form-label">Detalle</label>
                        <input type="text" class="form-control" id="productoDetalle">
                    </div>
                    <div class="mb-3">
                        <label for="productoNotas" class="form-label">Notas</label>
                        <textarea class="form-control" id="productoNotas" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarProducto()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalles -->
    <div class="modal fade" id="modalVerDetalles" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVerDetallesTitle">Detalles del Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Detalle:</strong> <span id="verDetalle"></span></p>
                    <p><strong>Notas:</strong> <span id="verNotas"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Gestión de Stock/Ingredientes -->
    <div class="modal fade" id="modalStock" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalStockTitle">Gestión de Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="stockProductoId">

                    <!-- Selector de tipo de stock -->
                    <div class="mb-4">
                        <label class="form-label"><strong>Tipo de gestión de stock:</strong></label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="tipoStock" id="tipoUnidad" value="unidad" autocomplete="off">
                                <label class="btn btn-outline-primary tipo-stock-btn w-100 h-100" for="tipoUnidad">
                                    <i class="fas fa-box fa-2x d-block mb-2"></i>
                                    <strong>Por Unidad</strong>
                                    <small class="d-block text-muted">Control unitario del producto</small>
                                </label>
                            </div>

                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="tipoStock" id="tipoIngredientes" value="ingredientes" autocomplete="off">
                                <label class="btn btn-outline-success tipo-stock-btn w-100 h-100" for="tipoIngredientes">
                                    <i class="fas fa-flask fa-2x d-block mb-2"></i>
                                    <strong>Por Ingredientes</strong>
                                    <small class="d-block text-muted">Descuenta ingredientes</small>
                                </label>
                            </div>

                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="tipoStock" id="tipoSinStock" value="sin_stock" autocomplete="off">
                                <label class="btn btn-outline-secondary tipo-stock-btn w-100 h-100" for="tipoSinStock">
                                    <i class="fas fa-infinity fa-2x d-block mb-2"></i>
                                    <strong>Sin Control</strong>
                                    <small class="d-block text-muted">No controla inventario</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de stock por unidad -->
                    <div id="seccionUnidad" style="display: none;">
                        <h6><i class="fas fa-box"></i> Control de Stock por Unidad</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Stock Actual</label>
                                <input type="number" class="form-control" id="stockActual" min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Mínimo</label>
                                <input type="number" class="form-control" id="stockMinimo" min="0" value="0">
                                <small class="text-muted">Alerta cuando esté por debajo</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Máximo (opcional)</label>
                                <input type="number" class="form-control" id="stockMaximo" min="0" placeholder="Sin límite">
                            </div>
                        </div>
                    </div>

                    <!-- Sección de ingredientes -->
                    <div id="seccionIngredientes" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6><i class="fas fa-list"></i> Ingredientes del Producto</h6>
                            <button type="button" class="btn btn-sm btn-success" onclick="agregarIngrediente()">
                                <i class="fas fa-plus"></i> Agregar Ingrediente
                            </button>
                        </div>

                        <div id="listaIngredientes">
                            <!-- Los ingredientes se cargarán aquí dinámicamente -->
                        </div>
                    </div>

                    <!-- Mensaje informativo según tipo seleccionado -->
                    <div id="infoTipoStock" class="alert" style="display: none;">
                        <i class="fas fa-info-circle"></i>
                        <span id="infoTexto"></span>
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
    <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/dt-1.11.3/datatables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

    <script>
        let tablaProductos;
        let todosLosIngredientes = []; // Cache de ingredientes disponibles

        $(document).ready(function() {
            // Cargar ingredientes disponibles
            cargarIngredientesDisponibles();

            tablaProductos = $('#productosTable').DataTable({
                "language": {
                    "url": "/assets/es_es.json"
                },
                "pagingType": "numbers",
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "api_productos.php",
                    "type": "POST",
                    "data": function(d) {
                        d.action = 'obtener_productos_dt';
                        return d;
                    },
                    "dataSrc": "data"
                },
                "columns": [{
                        "data": "id"
                    },
                    {
                        "data": "nombre"
                    },
                    {
                        "data": "categoria"
                    },
                    {
                        "data": "precio",
                        "render": $.fn.dataTable.render.number(',', '.', 2, 'S/. ')
                    },
                    {
                        "data": null,
                        "render": function(data, type, row) {
                            const detalle = (row.detalle || '').replace(/'/g, "\\'");
                            const notas = (row.notas || '').replace(/'/g, "\\'");
                            const nombre = (row.nombre || '').replace(/'/g, "\\'");
                            const categoria = (row.categoria || '').replace(/'/g, "\\'");

                            return `
                                <button class="btn btn-info btn-sm" onclick="verDetalles('${detalle}', '${notas}')" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-success btn-sm" onclick="abrirModalStock(${row.id}, '${nombre}')" title="Gestionar stock">
                                    <i class="fas fa-boxes"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="abrirModal(${row.id}, '${nombre}', '${categoria}', ${row.precio}, '${detalle}', '${notas}')" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="eliminarProducto(${row.id})" title="Eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>`;
                        },
                        "orderable": false,
                        "searchable": false
                    }
                ]
            });

            $('#productosTable').on('click', 'tr td.dtr-control', function() {
                var tr = $(this).closest('tr');
                var row = tablaProductos.row(tr);

                if (!row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('dtr-expanded');
                } else {
                    tablaProductos.rows('.dtr-expanded').every(function() {
                        $(this.node()).find('td.dtr-control').trigger('click');
                    });
                    row.child.show();
                    tr.addClass('dtr-expanded');
                }
            });

            // Listener para cambio de tipo de stock
            $('input[name="tipoStock"]').change(function() {
                const tipo = $(this).val();
                const infoBox = $('#infoTipoStock');
                const infoTexto = $('#infoTexto');

                // Ocultar todas las secciones
                $('#seccionUnidad').hide();
                $('#seccionIngredientes').hide();

                if (tipo === 'unidad') {
                    $('#seccionUnidad').slideDown();
                    infoTexto.text('El stock se controlará por unidades individuales del producto.');
                } else if (tipo === 'ingredientes') {
                    $('#seccionIngredientes').slideDown();
                    infoTexto.text('Los ingredientes se descontarán automáticamente al realizar ventas.');
                } else if (tipo === 'sin_stock') {
                    infoTexto.text('Este producto no tendrá control de inventario. Ideal para productos difíciles de cuantificar.');
                }
            });
        });

        // Cargar todos los ingredientes disponibles
        function cargarIngredientesDisponibles() {
            $.ajax({
                url: 'api_stock.php?action=listar_ingredientes',
                type: 'GET',
                success: function(result) {
                    if (result.success) {
                        todosLosIngredientes = result.data;
                    }
                },
                error: function() {
                    console.error('Error al cargar ingredientes');
                }
            });
        }

        function abrirModal(id = null, nombre = '', categoria = '', precio = '', detalle = '', notas = '') {
            const modalTitle = document.getElementById('modalTitle');
            const productoId = document.getElementById('productoId');
            const productoNombre = document.getElementById('productoNombre');
            const productoCategoria = document.getElementById('productoCategoria');
            const productoPrecio = document.getElementById('productoPrecio');
            const productoDetalle = document.getElementById('productoDetalle');
            const productoNotas = document.getElementById('productoNotas');

            if (id) {
                modalTitle.textContent = 'Editar Producto';
                productoId.value = id;
                productoNombre.value = nombre;
                productoCategoria.value = categoria;
                productoPrecio.value = precio;
                productoDetalle.value = detalle;
                productoNotas.value = notas;
            } else {
                modalTitle.textContent = 'Añadir Producto';
                productoId.value = '';
                productoNombre.value = '';
                productoCategoria.value = 'dulce';
                productoPrecio.value = '';
                productoDetalle.value = '';
                productoNotas.value = '';
            }

            new bootstrap.Modal(document.getElementById('modalProducto')).show();
        }

        function verDetalles(detalle, notas) {
            document.getElementById('verDetalle').textContent = detalle || 'N/A';
            document.getElementById('verNotas').textContent = notas || 'N/A';
            new bootstrap.Modal(document.getElementById('modalVerDetalles')).show();
        }

        function guardarProducto() {
            const id = document.getElementById('productoId').value;
            const nombre = document.getElementById('productoNombre').value;
            const categoria = document.getElementById('productoCategoria').value;
            const precio = document.getElementById('productoPrecio').value;
            const detalle = document.getElementById('productoDetalle').value;
            const notas = document.getElementById('productoNotas').value;

            if (!nombre || !precio) {
                alert('El nombre y el precio son obligatorios.');
                return;
            }

            const action = id ? 'actualizar_producto' : 'crear_producto';
            const data = {
                action,
                nombre,
                categoria,
                precio,
                detalle,
                notas
            };
            if (id) {
                data.id = id;
            }

            $.ajax({
                url: 'api_productos.php',
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(result) {
                    if (result.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalProducto')).hide();
                        tablaProductos.ajax.reload();
                    } else {
                        alert('Error: ' + result.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error en la petición:', error);
                    console.error('Respuesta del servidor:', xhr.responseText);
                    alert('Error de conexión al guardar el producto.');
                }
            });
        }

        function eliminarProducto(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este producto?')) {
                $.ajax({
                    url: 'api_productos.php',
                    type: 'POST',
                    data: JSON.stringify({
                        action: 'eliminar_producto',
                        id: id
                    }),
                    contentType: 'application/json',
                    success: function(result) {
                        if (result.success) {
                            alert('Producto eliminado exitosamente.');
                            tablaProductos.ajax.reload();
                        } else {
                            alert('Error: ' + result.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error en la petición:', error);
                        alert('Error de conexión al eliminar el producto.');
                    }
                });
            }
        }

        function abrirModalStock(productoId, nombre) {
            document.getElementById('stockProductoId').value = productoId;
            document.getElementById('modalStockTitle').textContent = 'Gestión de Stock - ' + nombre;

            // Limpiar secciones
            document.getElementById('listaIngredientes').innerHTML = '';
            $('#seccionUnidad').hide();
            $('#seccionIngredientes').hide();
            $('#infoTipoStock').hide();

            // Cargar tipo de stock actual
            $.ajax({
                url: 'api_stock.php?action=obtener_tipo_stock&producto_id=' + productoId,
                type: 'GET',
                success: function(result) {
                    if (result.success) {
                        const tipo = result.tipo_stock;

                        if (tipo === 'unidad') {
                            document.getElementById('tipoUnidad').checked = true;
                            $('#seccionUnidad').show();
                            cargarStockUnidad(productoId);
                        } else if (tipo === 'ingredientes') {
                            document.getElementById('tipoIngredientes').checked = true;
                            $('#seccionIngredientes').show();
                            cargarIngredientesProducto(productoId);
                        } else {
                            document.getElementById('tipoSinStock').checked = true;
                        }

                        $('input[name="tipoStock"]:checked').trigger('change');
                    }
                },
                error: function() {
                    document.getElementById('tipoSinStock').checked = true;
                    $('input[name="tipoStock"]:checked').trigger('change');
                }
            });

            new bootstrap.Modal(document.getElementById('modalStock')).show();
        }

        function cargarStockUnidad(productoId) {
            $.ajax({
                url: 'api_stock.php?action=obtener_stock_unidad&producto_id=' + productoId,
                type: 'GET',
                success: function(result) {
                    if (result.success) {
                        $('#stockActual').val(result.data.stock_actual || 0);
                        $('#stockMinimo').val(result.data.stock_minimo || 0);
                        $('#stockMaximo').val(result.data.stock_maximo || '');
                    }
                },
                error: function() {
                    // En caso de error, valores por defecto
                    $('#stockActual').val(0);
                    $('#stockMinimo').val(0);
                    $('#stockMaximo').val('');
                }
            });
        }

        function cargarIngredientesProducto(productoId) {
            $.ajax({
                url: 'api_stock.php?action=listar_ingredientes_producto&producto_id=' + productoId,
                type: 'GET',
                success: function(result) {
                    if (result.success && result.data.length > 0) {
                        result.data.forEach(function(ing) {
                            agregarIngredienteConDatos(ing.ingrediente_id, ing.nombre, ing.cantidad_por_unidad);
                        });
                    } else {
                        // Si no hay ingredientes, agregar una fila vacía
                        agregarIngrediente();
                    }
                }
            });
        }

        function agregarIngrediente() {
            agregarIngredienteConDatos(0, '', 0);
        }

        function agregarIngredienteConDatos(ingredienteId, nombreIngrediente, cantidad) {
            const container = document.getElementById('listaIngredientes');
            const index = container.children.length;

            const row = document.createElement('div');
            row.className = 'ingredient-row';
            row.innerHTML = `
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label class="form-label">Ingrediente</label>
                        <select class="form-select ingrediente-select" data-index="${index}">
                            <option value="">Seleccionar...</option>
                            ${todosLosIngredientes.map(ing => 
                                `<option value="${ing.id}" ${ing.id == ingredienteId ? 'selected' : ''}>
                                    ${ing.nombre} (${ing.stock_actual} ${ing.unidad_medida})
                                </option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cantidad por unidad</label>
                        <input type="number" step="0.01" min="0" class="form-control cantidad-input" 
                               value="${cantidad}" placeholder="0.00">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="eliminarIngredienteRow(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            container.appendChild(row);
        }

        function eliminarIngredienteRow(btn) {
            $(btn).closest('.ingredient-row').remove();
        }

        function guardarIngredientes(productoId) {
            const ingredientes = [];

            $('#listaIngredientes .ingredient-row').each(function() {
                const ingredienteId = $(this).find('.ingrediente-select').val();
                const cantidad = $(this).find('.cantidad-input').val();

                if (ingredienteId && cantidad > 0) {
                    ingredientes.push({
                        ingrediente_id: parseInt(ingredienteId),
                        cantidad: parseFloat(cantidad)
                    });
                }
            });

            $.ajax({
                url: 'api_stock.php',
                type: 'POST',
                data: JSON.stringify({
                    action: 'asignar_ingredientes',
                    producto_id: productoId,
                    ingredientes: ingredientes
                }),
                contentType: 'application/json',
                success: function(result) {
                    if (result.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalStock')).hide();
                    } else {
                        alert('Error: ' + result.error);
                    }
                },
                error: function() {
                    alert('Error al guardar los ingredientes.');
                }
            });
        }

        function guardarStock() {
            const productoId = document.getElementById('stockProductoId').value;
            const tipoStock = document.querySelector('input[name="tipoStock"]:checked').value;

            // Validaciones según tipo
            if (tipoStock === 'unidad') {
                const stockActual = $('#stockActual').val();
                if (stockActual === '' || stockActual < 0) {
                    alert('Debes ingresar un stock actual válido.');
                    return;
                }
            } else if (tipoStock === 'ingredientes') {
                let tieneIngredientesValidos = false;
                $('#listaIngredientes .ingredient-row').each(function() {
                    const ingredienteId = $(this).find('.ingrediente-select').val();
                    const cantidad = $(this).find('.cantidad-input').val();
                    if (ingredienteId && cantidad > 0) {
                        tieneIngredientesValidos = true;
                    }
                });

                if (!tieneIngredientesValidos) {
                    alert('Debes agregar al menos un ingrediente válido.');
                    return;
                }
            }

            // Guardar tipo de stock
            $.ajax({
                url: 'api_stock.php',
                type: 'POST',
                data: JSON.stringify({
                    action: 'actualizar_tipo_stock',
                    producto_id: productoId,
                    tipo: tipoStock
                }),
                contentType: 'application/json',
                success: function(result) {
                    if (result.success) {
                        if (tipoStock === 'unidad') {
                            guardarStockUnidad(productoId);
                        } else if (tipoStock === 'ingredientes') {
                            guardarIngredientes(productoId);
                        } else {
                            alert('Stock actualizado exitosamente.');
                            bootstrap.Modal.getInstance(document.getElementById('modalStock')).hide();
                        }
                    } else {
                        alert('Error: ' + result.error);
                    }
                },
                error: function() {
                    alert('Error al guardar el tipo de stock.');
                }
            });
        }

        function guardarStockUnidad(productoId) {
            const stockActual = parseInt($('#stockActual').val());
            const stockMinimo = parseInt($('#stockMinimo').val());
            const stockMaximo = $('#stockMaximo').val() ? parseInt($('#stockMaximo').val()) : null;

            $.ajax({
                url: 'api_stock.php',
                type: 'POST',
                data: JSON.stringify({
                    action: 'actualizar_stock_unidad',
                    producto_id: productoId,
                    stock_actual: stockActual,
                    stock_minimo: stockMinimo,
                    stock_maximo: stockMaximo
                }),
                contentType: 'application/json',
                success: function(result) {
                    if (result.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalStock')).hide();
                    } else {
                        alert('Error: ' + result.error);
                    }
                },
                error: function() {
                    alert('Error al guardar el stock.');
                }
            });
        }
    </script>
</body>

</html>
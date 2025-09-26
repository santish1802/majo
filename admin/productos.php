<?php require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php"; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAJO - Gestión de Productos</title>
    <?php 
    include $_SERVER['DOCUMENT_ROOT'] . "/assets/img/favicon/favicon.php";
    ?>   
    <link href="/assets/scss/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/style.css" rel="stylesheet">
</head>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/nav.php";
    ?>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/dt-1.11.3/datatables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

    <script>
        let tablaProductos;

        $(document).ready(function() {
            tablaProductos = $('#productosTable').DataTable({
                "language": {
                    "url": "/assets/es_es.json"
                },
                "pagingType": "numbers", // 👈 aquí está la magia

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
                            // Escapar comillas para evitar problemas en JavaScript
                            const detalle = (row.detalle || '').replace(/'/g, "\\'");
                            const notas = (row.notas || '').replace(/'/g, "\\'");
                            const nombre = (row.nombre || '').replace(/'/g, "\\'");
                            const categoria = (row.categoria || '').replace(/'/g, "\\'");

                            return `
                                <button class="btn btn-info btn-sm" onclick="verDetalles('${detalle}', '${notas}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="abrirModal(${row.id}, '${nombre}', '${categoria}', ${row.precio}, '${detalle}', '${notas}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="eliminarProducto(${row.id})">
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

                // Si la fila clickeada está expandida, colapsarla
                if (!row.child.isShown()) {
                    row.child.hide();
                    console.log("xd");
                    tr.removeClass('dtr-expanded');
                } else {
                    // Cerrar otros simulando click
                    tablaProductos.rows('.dtr-expanded').every(function() {
                        $(this.node()).find('td.dtr-control').trigger('click');
                    });

                    // Expandir la fila clickeada
                    row.child.show();
                    console.log("xd4");
                    tr.addClass('dtr-expanded');
                }
            });
        });

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
                productoCategoria.value = categoria; // Esto cargará la categoría correcta
                productoPrecio.value = precio;
                productoDetalle.value = detalle;
                productoNotas.value = notas;
            } else {
                modalTitle.textContent = 'Añadir Producto';
                productoId.value = '';
                productoNombre.value = '';
                productoCategoria.value = 'dulce'; // Valor por defecto
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
                        alert('Producto guardado exitosamente.');
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
    </script>
</body>

</html>
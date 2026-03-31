<div class="wrap test-intentos-wrap">
	<h1 class="wp-heading-inline">Gestión de Intentos de Tests/Simulacros</h1>
	<hr class="wp-header-end">

	<div class="test-intentos-card">
		<h2>1. Seleccionar Usuario</h2>
		<p class="description">Utiliza el buscador para encontrar un usuario por nombre descriptivo o correo electrónico.</p>
		
		<div class="test-intentos-search-box">
			<select id="test-intentos-user-select" class="regular-text" style="width: 100%;">
				<!-- Opción autocompletada via AJAX -->
			</select>
		</div>
	</div>

	<div id="test-intentos-results" class="test-intentos-card" style="display: none;">
		<div class="test-intentos-header">
			<h2>2. Intentos Realizados</h2>
			<button class="button" id="test-intentos-refresh" title="Refrescar">
				<span class="dashicons dashicons-update"></span> Actualizar Tabla
			</button>
		</div>
		<p class="description">A continuación se muestran los intentos realizados por el usuario seleccionado en diferentes tests y simulacros. Puedes borrar intentos individuales para que el usuario pueda volver a realizarlos antes de los 10 días estipulados.</p>
		
		<div class="table-responsive">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-primary">Test / Simulacro</th>
						<th scope="col" class="manage-column" style="width: 100px;">ID Test</th>
						<th scope="col" class="manage-column" style="width: 150px;">Fecha del Intento</th>
						<th scope="col" class="manage-column" style="width: 80px;">Score</th>
						<th scope="col" class="manage-column" style="width: 100px;">Acciones</th>
					</tr>
				</thead>
				<tbody id="test-intentos-table-body">
					<!-- Resultados via AJAX -->
				</tbody>
			</table>
		</div>
	</div>

	<!-- Plantilla para fila vacía -->
	<script type="text/template" id="tmpl-test-intentos-empty">
		<tr class="no-items">
			<td class="colspanchange" colspan="5">No se encontraron intentos para este usuario.</td>
		</tr>
	</script>

	<!-- Plantilla para fila cargando -->
	<script type="text/template" id="tmpl-test-intentos-loading">
		<tr class="no-items">
			<td class="colspanchange" colspan="5">
				<span class="spinner is-active" style="float:none; margin: 0 5px 0 0;"></span> Cargando datos...
			</td>
		</tr>
	</script>
</div>

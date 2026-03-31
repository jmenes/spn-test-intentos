jQuery(document).ready(function($) {

	// 1. Inicializar Select2 en el buscador de usuarios
	$('#test-intentos-user-select').select2({
		placeholder: 'Escribe un nombre o email...',
		minimumInputLength: 3,
		ajax: {
			url: TestIntentosObj.ajax_url,
			dataType: 'json',
			delay: 250,
			data: function (params) {
				return {
					action: 'test_intentos_search_users',
					search: params.term,
					nonce: TestIntentosObj.nonce
				};
			},
			processResults: function (data) {
				return {
					results: data.results
				};
			},
			cache: true
		}
	});

	// 2. Evento al seleccionar un usuario -> Cargar sus intentos
	$('#test-intentos-user-select').on('select2:select', function (e) {
		cargarIntentos();
	});

	// Botón refrescar
	$('#test-intentos-refresh').on('click', function(e) {
		e.preventDefault();
		cargarIntentos();
	});

	function cargarIntentos() {
		var userId = $('#test-intentos-user-select').val();
		if ( ! userId ) return;

		var $tbody = $('#test-intentos-table-body');
		var $resultsDiv = $('#test-intentos-results');
		
		$resultsDiv.show();
		$tbody.html( $('#tmpl-test-intentos-loading').html() );

		$.ajax({
			url: TestIntentosObj.ajax_url,
			method: 'POST',
			dataType: 'json',
			data: {
				action: 'test_intentos_get_attempts',
				user_id: userId,
				nonce: TestIntentosObj.nonce
			},
			success: function(response) {
				if ( response.success ) {
					renderizarTabla( response.data.attempts );
				} else {
					Swal.fire('Error', response.data, 'error');
					$tbody.html( $('#tmpl-test-intentos-empty').html() );
				}
			},
			error: function() {
				Swal.fire('Error', TestIntentosObj.error, 'error');
				$tbody.html( $('#tmpl-test-intentos-empty').html() );
			}
		});
	}

	function renderizarTabla( attempts ) {
		var $tbody = $('#test-intentos-table-body');
		$tbody.empty();

		if ( ! attempts || attempts.length === 0 ) {
			$tbody.html( $('#tmpl-test-intentos-empty').html() );
			return;
		}

		attempts.forEach(function(att) {
			var tr = $('<tr></tr>');
			
			tr.append( $('<td></td>').text( att.test_name ).addClass('column-primary') );
			tr.append( $('<td></td>').text( att.test_id ) );
			tr.append( $('<td></td>').text( att.date ) );
			tr.append( $('<td></td>').text( att.score ) );
			
			var btnDelete = $('<button></button>')
				.addClass('button button-danger delete-attempt-btn')
				.html('<span class="dashicons dashicons-trash"></span> Borrar')
				.data('testid', att.test_id)
				.data('index', att.index);

			tr.append( $('<td></td>').append( btnDelete ) );

			$tbody.append(tr);
		});
	}

	// 3. Borrar Intento con SweetAlert2
	$(document).on('click', '.delete-attempt-btn', function(e) {
		e.preventDefault();

		var btn = $(this);
		var testId = btn.data('testid');
		var index = btn.data('index');
		var userId = $('#test-intentos-user-select').val();

		Swal.fire({
			title: '¿Estás seguro?',
			text: "Este intento será borrado permanentemente y se recularán los puntos si corresponde.",
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#d33',
			cancelButtonColor: '#3085d6',
			confirmButtonText: 'Sí, borrar intento',
			cancelButtonText: 'Cancelar'
		}).then((result) => {
			if (result.isConfirmed) {

				btn.prop('disabled', true).text('Borrando...');

				$.ajax({
					url: TestIntentosObj.ajax_url,
					method: 'POST',
					dataType: 'json',
					data: {
						action: 'test_intentos_delete_attempt',
						user_id: userId,
						test_id: testId,
						index: index,
						nonce: TestIntentosObj.nonce
					},
					success: function(response) {
						if ( response.success ) {
							Swal.fire('Borrado!', 'El intento ha sido borrado exitosamente.', 'success');
							cargarIntentos(); // Recargar tras borrar
						} else {
							Swal.fire('Error', response.data, 'error');
							btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Borrar');
						}
					},
					error: function() {
						Swal.fire('Error', TestIntentosObj.error, 'error');
						btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Borrar');
					}
				});
			}
		});

	});

});

<?php

class Test_Intentos_Ajax {

	public function init() {
		// Búsqueda de usuarios
		add_action( 'wp_ajax_test_intentos_search_users', array( $this, 'search_users' ) );
		
		// Obtener intentos de un usuario
		add_action( 'wp_ajax_test_intentos_get_attempts', array( $this, 'get_attempts' ) );
		
		// Borrar un intento
		add_action( 'wp_ajax_test_intentos_delete_attempt', array( $this, 'delete_attempt' ) );
	}

	private function check_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permisos insuficientes.' );
		}
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'test_intentos_nonce' ) ) {
			wp_send_json_error( 'Token de seguridad inválido.' );
		}
	}

	public function search_users() {
		$this->check_permission();

		$search = isset( $_REQUEST['search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search'] ) ) : '';
		
		$args = array(
			'search'         => '*' . $search . '*',
			'search_columns' => array( 'user_login', 'user_nicename', 'user_email' ),
			'number'         => 20,
			'fields'         => 'all'
		);

		$users_query = new WP_User_Query( $args );
		$users       = $users_query->get_results();

		$results = array();
		if ( ! empty( $users ) ) {
			foreach ( $users as $user ) {
				$results[] = array(
					'id'   => $user->ID,
					'text' => $user->display_name . ' (' . $user->user_email . ')'
				);
			}
		}

		wp_send_json( array( 'results' => $results ) );
	}

	public function get_attempts() {
		$this->check_permission();

		$user_id = isset( $_REQUEST['user_id'] ) ? intval( $_REQUEST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_send_json_error( 'ID de usuario inválido.' );
		}

		$test_ids = get_user_meta( $user_id, 'test_attempts', true );
		if ( ! is_array( $test_ids ) || empty( $test_ids ) ) {
			wp_send_json_success( array( 'attempts' => array() ) );
		}

		$all_attempts = array();

		foreach ( $test_ids as $test_id ) {
			$attempt_history = get_user_meta( $user_id, 'test_attempt_' . $test_id, true );
			
			if ( ! is_array( $attempt_history ) || empty( $attempt_history['attempts'] ) ) {
				continue;
			}

			// Asegurarse de tener el nombre del test
			$test_name = isset( $attempt_history['name'] ) ? $attempt_history['name'] : get_the_title( $test_id );
			if ( empty( $test_name ) ) {
				$test_name = 'Test / Simulacro ID: ' . $test_id;
			}

			// Listar cada intento con su índice real en el array
			foreach ( $attempt_history['attempts'] as $index => $attempt ) {
				$score = isset( $attempt['score'] ) ? floatval( $attempt['score'] ) : 0;
				$date  = isset( $attempt['attempted_at'] ) ? $attempt['attempted_at'] : 'Fecha desconocida';

				$all_attempts[] = array(
					'test_id'   => $test_id,
					'test_name' => $test_name,
					'index'     => $index, // Guardamos el índice para poder borrarlo
					'score'     => number_format( $score, 2 ),
					'date'      => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date ) )
				);
			}
		}

		// Ordenar globalmente por fecha descendente (simulado ya que date viene de WP options, mejor ordenar antes de formatear o usar usort)
		usort( $all_attempts, function( $a, $b ) {
			// El índice es cronológico en el array original de api-spn, pero si juntamos múltiples tests:
			$time_a = strtotime( str_replace('/', '-', $a['date']) ); // Rough est
			$time_b = strtotime( str_replace('/', '-', $b['date']) );
			return $time_b - $time_a;
		});

		wp_send_json_success( array( 'attempts' => $all_attempts ) );
	}

	public function delete_attempt() {
		$this->check_permission();

		$user_id = isset( $_REQUEST['user_id'] ) ? intval( $_REQUEST['user_id'] ) : 0;
		$test_id = isset( $_REQUEST['test_id'] ) ? intval( $_REQUEST['test_id'] ) : 0;
		$index   = isset( $_REQUEST['index'] ) ? intval( $_REQUEST['index'] ) : -1;

		if ( ! $user_id || ! $test_id || $index < 0 ) {
			wp_send_json_error( 'Faltan parámetros requeridos.' );
		}

		// 1. Obtener el historial
		$attempt_history = get_user_meta( $user_id, 'test_attempt_' . $test_id, true );
		if ( ! is_array( $attempt_history ) || ! isset( $attempt_history['attempts'][$index] ) ) {
			wp_send_json_error( 'No se pudo encontrar el intento especificado o ya fue borrado.' );
		}

		// 2. Extraer el elemento
		unset( $attempt_history['attempts'][$index] );
		
		// Reindexar el array para evitar agujeros en la clave
		$attempt_history['attempts'] = array_values( $attempt_history['attempts'] );

		// 3. Procesar las consecuencias
		if ( empty( $attempt_history['attempts'] ) ) {
			// A) Ya no quedan intentos -> Limpiar todo
			delete_user_meta( $user_id, 'test_attempt_' . $test_id );
			
			// Remover `$test_id` del global `test_attempts`
			$global_attempts = get_user_meta( $user_id, 'test_attempts', true );
			if ( is_array( $global_attempts ) && ( $key = array_search( $test_id, $global_attempts, true ) ) !== false ) {
				unset( $global_attempts[$key] );
				update_user_meta( $user_id, 'test_attempts', array_values( $global_attempts ) );
			}

			// Actualizar user_progress para reflejar que está inacabado
			$this->clean_user_progress( $user_id, $test_id );

		} else {
			// B) Quedan intentos -> Actualizar
			update_user_meta( $user_id, 'test_attempt_' . $test_id, $attempt_history );

			// Sincronizar user_progress con el último test que queda
			$last_attempt = end( $attempt_history['attempts'] );
			$this->update_user_progress_score( $user_id, $test_id, $last_attempt );
		}

		// IMPORTANTE: Borrar la sesión de expiración activa para que el temporizador del campus se resetee.
		delete_user_meta( $user_id, 'test_expiration_' . $test_id );

		wp_send_json_success( 'Intento borrado con éxito.' );
	}

	/**
	 * Limpia el progreso de este test en el array `user_progress` nativo del plugin api-spn.
	 */
	private function clean_user_progress( $user_id, $test_id ) {
		$progress = get_user_meta( $user_id, 'user_progress', true );
		if ( is_array( $progress ) && isset( $progress['items'][ (string) $test_id ] ) ) {
			// Removemos el score y decimos que ya no está completo
			$progress['items'][ (string) $test_id ]['completed'] = false;
			$progress['items'][ (string) $test_id ]['score'] = null;
			$progress['items'][ (string) $test_id ]['updated_at'] = gmdate('c');
			
			update_user_meta( $user_id, 'user_progress', $progress );
		}
	}

	/**
	 * Actualiza el score del progreso con el último intento real.
	 */
	private function update_user_progress_score( $user_id, $test_id, $last_attempt ) {
		$progress = get_user_meta( $user_id, 'user_progress', true );
		if ( is_array( $progress ) && isset( $progress['items'][ (string) $test_id ] ) ) {
			
			$score = isset( $last_attempt['score'] ) ? floatval( $last_attempt['score'] ) : null;
			
			// Modificar los valores
			$progress['items'][ (string) $test_id ]['score'] = $score;
			$progress['items'][ (string) $test_id ]['updated_at'] = gmdate('c');

			update_user_meta( $user_id, 'user_progress', $progress );
		}
	}
}

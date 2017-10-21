<?php

class Debug_Bar_Queries extends Debug_Bar_Panel {

	protected function init() {
		$this->title( __( 'Queries', 'debug-bar' ) );
	}

	public function prerender() {
		$this->set_visible( defined( 'SAVEQUERIES' ) && SAVEQUERIES || ! empty( $GLOBALS['EZSQL_ERROR'] ) );
	}

	public function debug_bar_classes( $classes ) {
		if ( ! empty( $GLOBALS['EZSQL_ERROR'] ) ) {
			$classes[] = 'debug-bar-php-warning-summary';
		}

		return $classes;
	}

	public function render() {
		global $wpdb, $EZSQL_ERROR;

		$out        = '';
		$total_time = 0;

		if ( ! empty( $wpdb->queries ) ) {
			$show_many = isset( $_GET['debug_queries'] );

			if ( $wpdb->num_queries > 500 && ! $show_many ) {
				/* translators: %s = a url. */
				$out .= '<p>' . sprintf( __( 'There are too many queries to show easily! <a href="%s">Show them anyway</a>', 'debug-bar' ), esc_url( add_query_arg( 'debug_queries', 'true' ) ) ) . '</p>';
			}

			$out    .= '<ol class="wpd-queries">';
			$counter = 0;

			foreach ( $wpdb->queries as $q ) {
				list( $query, $elapsed, $debug ) = $q;

				$total_time += $elapsed;

				if ( ++$counter > 500 && ! $show_many ) {
					continue;
				}

				$debug = explode( ', ', $debug );
				$debug = array_diff( $debug, array( 'require_once', 'require', 'include_once', 'include' ) );
				$debug = implode( ', ', $debug );
				$debug = str_replace( array( 'do_action, call_user_func_array' ), array( 'do_action' ), $debug );
				$debug = esc_html( $debug );
				$query = nl2br( esc_html( $query ) );

				/* translators: %s = duration in milliseconds. */
				$time = esc_html( sprintf( __( '%s ms', 'debug-bar' ), number_format_i18n( ( $elapsed * 1000 ), 1 ) ) );

				$out .= "<li>$query<br/><div class='qdebug'>$debug <span>#$counter ($time)</span></div></li>\n";
			}
			$out .= '</ol>';
		} elseif ( 0 === $wpdb->num_queries ) {
			$out .= '<p><strong>' . __( 'There are no queries on this page.', 'debug-bar' ) . '</strong></p>';
		} else {
			$out .= '<p><strong>' . __( 'SAVEQUERIES must be defined to show the query log.', 'debug-bar' ) . '</strong></p>';
		}

		if ( ! empty( $EZSQL_ERROR ) ) {
			$out .= '<h3>' . __( 'Database Errors', 'debug-bar' ) . '</h3>';
			$out .= '<ol class="wpd-queries">';

			foreach ( $EZSQL_ERROR as $error ) {
				$query   = nl2br( esc_html( $error['query'] ) );
				$message = esc_html( $error['error_str'] );
				$out    .= "<li>$query<br/><div class='qdebug'>$message</div></li>\n";
			}
			$out .= '</ol>';
		}

		if ( $wpdb->num_queries ) {
			$this->render_panel_info_block( __( 'Total Queries:', 'debug-bar' ), number_format_i18n( $wpdb->num_queries ) );
		}
		if ( $total_time ) {
			/* translators: %s = duration in milliseconds. */
			$duration = sprintf( __( '%s ms', 'debug-bar' ), number_format_i18n( ( $total_time * 1000 ), 1 ) );
			$this->render_panel_info_block( __( 'Total query time:', 'debug-bar' ), $duration );
		}
		if ( ! empty( $EZSQL_ERROR ) ) {
			$this->render_panel_info_block( __( 'Total DB Errors:', 'debug-bar' ), count( $EZSQL_ERROR ) );
		}

		echo $out;
	}
}

( function( wp, $ ) {
	$( document ).on( 'click', '#wp-gcs-offload-sync-attachment', function( e ) {
		e.preventDefault();

		var attachment_id = $( '#post_ID' ).val();
		if ( ! attachment_id ) {
			return;
		}

		attachment_id = parseInt( attachment_id, 10 );

		function create_sync_notice( message, type ) {
			var $notice_section = $( '<div id="wp-gcs-offload-sync-notice-wrap" class="misc-pub-section" />' );
			var $notice = $( '<div id="wp-gcs-offload-sync-notice" class="notice notice-' + type + '" />' );

			$notice.html( '<p>' + message + '</p>' );
			$notice_section.append( $notice );

			$( '#wp-gcs-offload-data' ).append( $notice_section );
		}

		function remove_sync_notice() {
			var $notice_wrap = $( '#wp-gcs-offload-sync-notice-wrap' );
			if ( ! $notice_wrap.length ) {
				return;
			}

			$notice_wrap.remove();
		}

		wp.ajax.post( 'wpgcso_sync_attachment', {
			attachment_id: attachment_id
		}).done( function( response ) {
			var $wrap = $( '#wp-gcs-offload-data' );

			$( '#wp-gcs-offload-data' ).html( response.html );

			create_sync_notice( response.message, 'success' );
		}).fail( function( message ) {
			remove_sync_notice();

			create_sync_notice( message, 'error' );
		});
	});
}( wp, jQuery ) );

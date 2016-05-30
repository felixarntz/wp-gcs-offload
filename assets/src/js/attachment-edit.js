( function( wp, $, data ) {

	function create_sync_notice( message, type, notice_before_selector ) {
		var $notice_section = $( '<div id="wp-gcs-offload-sync-notice-wrap" class="misc-pub-section" />' );
		var $notice = $( '<div id="wp-gcs-offload-sync-notice" class="notice notice-' + type + '" />' );

		if ( 'error' === type ) {
			message = '<strong>' + data.error_prefix + '</strong> ' + message;
		}

		$notice.html( '<p>' + message + '</p>' );
		$notice_section.append( $notice );

		$( notice_before_selector ).after( $notice_section );
	}

	function remove_sync_notice() {
		var $notice_wrap = $( '#wp-gcs-offload-sync-notice-wrap' );
		if ( ! $notice_wrap.length ) {
			return;
		}

		$notice_wrap.remove();
	}

	function ajax( action, data, notice_before_selector ) {
		wp.ajax.post( action, data )
			.done( function( response ) {
				var $wrap = $( '#wp-gcs-offload-data' );

				$( '#wp-gcs-offload-data' ).replaceWith( response.html );

				create_sync_notice( response.message, 'success', notice_before_selector );
			})
			.fail( function( message ) {
				remove_sync_notice();

				create_sync_notice( message, 'error', notice_before_selector );
			});
	}

	var attachment_id;

	$( document ).ready( function() {
		attachment_id = $( '#post_ID' ).val();
		if ( ! attachment_id ) {
			return;
		}

		attachment_id = parseInt( attachment_id, 10 );
	});

	$( document ).on( 'click', '#wp-gcs-offload-sync-attachment-upstream', function( e ) {
		e.preventDefault();

		if ( ! attachment_id ) {
			return;
		}

		ajax( 'wpgcso_sync_attachment_upstream', {
			nonce: data.sync_attachment_upstream_nonce,
			attachment_id: attachment_id
		}, 'misc-pub-gcs-sync-upstream' );
	});

	$( document ).on( 'click', '#wp-gcs-offload-sync-attachment-downstream', function( e ) {
		e.preventDefault();

		if ( ! attachment_id ) {
			return;
		}

		ajax( 'wpgcso_sync_attachment_downstream', {
			nonce: data.sync_attachment_downstream_nonce,
			attachment_id: attachment_id
		}, 'misc-pub-gcs-sync-downstream' );
	});

	$( document ).on( 'click', '#wp-gcs-offload-delete-attachment-upstream', function( e ) {
		e.preventDefault();

		if ( ! attachment_id ) {
			return;
		}

		ajax( 'wpgcso_delete_attachment_upstream', {
			nonce: data.delete_attachment_upstream_nonce,
			attachment_id: attachment_id
		}, 'misc-pub-gcs-delete-upstream' );
	});

	$( document ).on( 'click', '#wp-gcs-offload-delete-attachment-downstream', function( e ) {
		e.preventDefault();

		if ( ! attachment_id ) {
			return;
		}

		ajax( 'wpgcso_delete_attachment_downstream', {
			nonce: data.delete_attachment_downstream_nonce,
			attachment_id: attachment_id
		}, 'misc-pub-gcs-delete-downstream' );
	});
}( wp, jQuery, _wpGCSOffloadAttachmentEdit ) );

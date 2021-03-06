(function($){ // closure

	var firstBtn,
		secondBtn,
		args = {
			autoOpen: false,
			resizable: false,
			modal: false,
			width: 500,
			buttons: {}
		};

	if ( 'negative' == dialogParams.btnFirst )
	{
		args.buttons[dialogParams.btnNo] = function() {
			$( this ).dialog( 'close' );
		};
		args.buttons[dialogParams.btnYes] = function() {
			yesButtonCallback( $( this ) );
		};
	}
	else
	{
		args.buttons[dialogParams.btnYes] = function() {
			yesButtonCallback( $( this ) );
		};
		args.buttons[dialogParams.btnNo] = function() {
			$( this ).dialog( 'close' );
		};
	}

	$( 'div#wpbody-content' ).append(
		'<div id="the-dialog">' + dialogParams.text + '</div>'
	);

	$( "div#the-dialog" ).dialog( args );

	$( '#' + dialogParams.btnID ).click( function() {
		$( "div#the-dialog" ).dialog('open');
		return false;
	});

	function yesButtonCallback( $this ) {
		$this.dialog( 'close' );
		var action = $( '#' + dialogParams.btnID ).closest( 'form' ).attr('action');
		$('div#wpbody-content').append(
			'<div id="vca-asm-loading-overlay"><div class="modal"><h2 class="vca-asm-loading-message">'+
			dialogParams.loadingText+
			'</h2><img src="'+
			dialogParams.loadingImgSrc+
			'" title="Loading..." alt="Loading animation" /></div></div>'
		);
		$('div#vca-asm-loading-overlay').show();
		$.post(
			action,
			$( '#' + dialogParams.btnID ).closest( 'form' ).serialize(),
			function(data){
				$('div#wpbody-content').append('<div id="processed-url-wrap" class="utility-hidden" style="display:none;"></div>');
				$('div#processed-url-wrap').append(data);
				if ( $('span#processed-url').length ) {
					window.location = $('span#processed-url').first().text();
				} else {
					window.location = action;
				}
			}
		);
	}

})(jQuery); // closure
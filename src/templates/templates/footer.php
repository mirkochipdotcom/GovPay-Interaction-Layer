	</div>
  </main>	
<footer class="it-footer text-white mt-4 fixed-bottom">
	<div class="it-footer-main mt-4">
	  <div class="container">
		<div class="row">
			<div class="py-2 col-12 col-md-6 text-left">Comune di Montesilvano - Servizi ai cittadini</div>
			<div class="py-2 col col-md-6 text-right d-none d-lg-block">
				<!-- images removed: /layout/img/pagopa.png and /spid/images/spid-agid-logo-bb.png not present locally -->
			</div>
		</div>
	  </div>
	</div>
</footer>
<script>
$(document).ready( function() {
   // Scorre tutte le tabelle nella pagina in cerca delle tabelle da rendere dinamiche
   $('table').each( function() {
      if( $(this).data("dynamic")===true ) {
		 lunghezza = $(this).data("righe_visibili");
		 if( lunghezza == "" )
			 lunghezza = 100;
         colonna = 0;
         ordine  = 'asc';
		 
		 if( $(this).data("no_select_page")===true )
			 length_change = false;
		 else
			 length_change = true;	
		 
		 if( $(this).data("no_paging")===true )
			 paging = false;
		 else
			 paging = true;		
		 
		 if( $(this).data("no_search")===true )
			 search = false;
		 else
			 search = true;
		 
		 if (typeof $(this).data("select") !== 'undefined') {
			 select_style = $(this).data("select");
			 
			 var tabella = $(this).DataTable({
			   "order"         : [[ colonna, ordine ]],
			   "pageLength"    : lunghezza,
			   "bLengthChange" : length_change,
			   "paging"		   : paging,
			   "info"		   : paging,
			   "searching"	   : search,
			   // language file removed because /layout/datatables/traduzione/italian.json is not present in the repository
			   select		   : { style : select_style }
			 });
			 
			 if (typeof $(this).data("select_function") !== 'undefined') {
				tabella.on( 'select', function ( e, dt, type, indexes ) {
					if ( type === 'row' ) {
						callback = $(this).data("select_function");
						
						window[callback]( dt, true );
					}
				}); 				
				
				tabella.on( 'deselect', function ( e, dt, type, indexes ) {
					if ( type === 'row' ) {
						callback = $(this).data("select_function");
						
						window[callback]( dt, false );
					}
				}); 
			 }
		 } else {	
			 var tabella = $(this).DataTable({
			   "order"         : [[ colonna, ordine ]],
			   "pageLength"    : lunghezza,
			   "bLengthChange" : length_change,
			   "paging"		   : paging,
			   "info"		   : paging,
			   "searching"	   : search,
			   // language file removed because /layout/datatables/traduzione/italian.json is not present in the repository
			 });
		 }
		 
		 
		 if (typeof $(this).data("buttons") !== 'undefined') {
			tabella.on( 'init.dt', function ( e, dt, type, indexes ) {
				callback = $(this).data("buttons");
						
				window[callback]( tabella ); 
			});
		 } 
      }   
   });
   
   $('.it-date-datepicker').datepicker({
      inputFormat: ["dd/MM/yyyy"],
      outputFormat: 'dd/MM/yyyy',
   });
   
   $('[data-toggle="popover"]').popover();
   
<?php

	if( isset( $DOCUMENT_READY ))
		echo $DOCUMENT_READY;
?>
});
</script>
</body>
</html>
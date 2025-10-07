<?php					
	if( session_status() !== PHP_SESSION_ACTIVE )
		session_start();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it-it" lang="it-it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="PagoPa GIL">
<meta name="author" content="github\mirkochipdotcom">
<title>PagoPA - <?php echo getenv('ENTE_TITOLO') ?></title>
<link href="/public/assets/bootstrap-italia/css/bootstrap-italia.min.css" rel="stylesheet">
<link rel="stylesheet" href="/public/assets/fontawesome/css/all.min.css">
<link href="/modules/OSM/open_layers/ol.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="/layout/datatables_2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="/layout/agid_template/agid.css"/>
<link rel="stylesheet" type="text/css" href="/pagopa/css/pagopa.css"/>
<link rel="stylesheet" type="text/css" href="/layout/chat/chat.css" type="text/css" />
<script>window.__PUBLIC_PATH__ = "/public/assets/bootstrap-italia/fonts"</script>
<script src="/public/assets/bootstrap-italia/js/bootstrap-italia.bundle.min.js?{{ buildTime }}"></script>
<script src="/public/assets/fontawesome/js/all.js"></script>
<script type="text/javascript" src="/layout/datatables_2/datatables.min.js"></script>
<script type="text/javascript" src="/spid/button/js/spid-sp-access-button.min.js"></script>
<script type="text/javascript" src="/layout/charts/charts.js"></script>
<!-- External CDN scripts removed: jquery-ui and knockout from remote CDNs -->
<script type="text/javascript" src="/layout/sevenSeg/sevenSeg.js"></script>
<script type="text/javascript" src="/layout/chartjs/chart.umd.js"></script>
<!-- Matomo removed: no external analytics tracking configured in this instance -->
</head>
<body>

<header class="it-header-wrapper">
  <div class="it-header-slim-wrapper">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <div class="it-header-slim-wrapper-content">
            <a class="d-lg-block navbar-brand" href="#"><?php echo getenv('ENTE_REGIONE') ?></a>
            <div class="it-header-slim-right-zone">
              <div class="nav-item dropdown">
                <a aria-expanded="false" class="nav-link dropdown-toggle"
                   data-toggle="dropdown" href="#">
                  <span>ITA</span>
                  <svg class="icon icon-white d-none d-lg-block">
                    <use xlink:href="/public/assets/bootstrap-italia/svg/sprite.svg#it-expand"></use>
                  </svg>
                </a>
                <div class="dropdown-menu">
                  <div class="row">
                    <div class="col-12">
                      <div class="link-list-wrapper">
                        <ul class="link-list">
                          <li>
                            <a class="list-item" href="#"><span>ITA</span></a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="it-user-wrapper nav-item dropdown show">
                <div class="dropdown-menu" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 48px, 0px);">
                  <div class="row">
                    <div class="col-20">
                      <div class="link-list-wrapper">
                        <ul class="link-list">

                        <?php
                                    if( array_key_exists( "PORTALE_USER_CF_GESTIONE", $_SESSION ) && $_SESSION["PORTALE_USER_CF_GESTIONE"] != "" ) {
                                        if( isset($DATI_UTENTE) ) {
                                          if( isset($DATI_UTENTE["amministratore"]) && $DATI_UTENTE["amministratore"] == 1 ){

                                            echo '<li>
                                                    <a class="list-item left-icon" href="/admin/" title="Admin">
                                                    <span class="fal"><i class="fas fa-user-gear fa-lg" aria-hidden="true"></i></span>
                                                    <span class="font-weight-bold">Admin</span></a>
                                                </li>';
                                          }
                                        }
                                        if ($_SESSION["PORTALE_USER_CF_GESTIONE"] == $_SESSION["PORTALE_USER_CODICE_FISCALE"] ) {
?>
                                              <li>
                                                <a class="list-item left-icon" href="<?php echo getenv('URL_ENTE'); ?>/profilo_utente.php" title="Profilo utente">
                                                  <span class="fal"><i class="fas fa-address-card fa-lg" aria-hidden="true"></i></span>
                                                  <span class="font-weight-bold">Dati utente</span>
                                                </a>
                                              </li>
<?php
                                        }
                                        
                                        echo '<li>
                                        <div class="container-fluid">
                                                <span class="divider"></span>
                                                </div>
                                              </li>';

?>
                                              <li>
                                                <a class="list-item left-icon" href="<?php echo getenv('URL_ENTE'); ?>/logout.php">
                                                  <span class="fal"><i class="fas fa-arrow-right-from-bracket fa-lg" aria-hidden="true"></i></span>
                                                  <span class="font-weight-bold">Esci</span>
                                                </a>
                                              </li>
<?php

                                        
                                   
                                    }else{


?>
                                          <li>
                                            <a class="list-item left-icon" href="<?php echo getenv('URL_ENTE'); ?>/login.php" title="Login">
                                              <span class="fal"><i class="fas fa-arrow-right-to-bracket fa-lg" aria-hidden="true"></i></span>
                                              <span class="font-weight-bold">Accedi</span>
                                            </a>
                                          </li>
<?php
                                    }
                                    ?>
                          
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
     </div>
  </div>
  </div>

  <div class="it-nav-wrapper">
    <div class="it-header-center-wrapper">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <div class="it-header-center-content-wrapper">
              <div class="it-brand-wrapper">
                <a href="<?php echo getenv('URL_ENTE'); ?>/" style="display: flex; align-items: center; gap: 10px;">
                  <img src="/public/img/stemma_ente.png" alt="Logo ente" style="height: 48px; width: auto;" />
                  <div class="it-brand-text">
                    <h2 class="no_toc">Comune di Montesilvano</h2>
                    <h3 class="no_toc d-none d-md-block">
                      Provincia di Pescara
                    </h3>
                  </div>
                </a>
              </div>
              <div class="it-right-zone">
                <div class="it-brand-wrapper">
<?php  
		if( isset($_SESSION["sess_HEADER_MESSAGE"]) && $_SESSION["sess_HEADER_MESSAGE"]!='')
	   		echo $_SESSION["sess_HEADER_MESSAGE"];   
?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="it-header-navbar-wrapper">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <nav class="navbar navbar-expand-lg has-megamenu">
              <button aria-controls="nav10" aria-expanded="false"
                      aria-label="Toggle navigation" class="custom-navbar-toggler"
                      data-target="#nav10" type="button">
                <svg class="icon">
                  <use
                    xlink:href="/public/assets/bootstrap-italia/svg/sprite.svg#it-burger"></use>
                </svg>
              </button>
              <div class="navbar-collapsable" id="nav10">
                <div class="overlay"></div>
                <div class="close-div sr-only">
                  <button class="btn close-menu" type="button">
                    <span class="it-close"></span>close
                  </button>
                </div>
                <div class="menu-wrapper">
                  <ul class="navbar-nav">
<?php

				global $TEMPLATE_MENU;
				if( !is_array($TEMPLATE_MENU) || !array_key_exists( "CUSTOM", $TEMPLATE_MENU ) ) {			
                    $TEMPLATE_MENU = array( "Home"    => getenv('URL_COMUNE') . "/",
                                            "Tributi" => array( "Valori Aree ai fini IMU"      => getenv('URL_COMUNE') . "/valori_aree.php"),
                                            "Servizi" => array( /*"Prenotazione Appuntamenti"    => getenv('URL_COMUNE') . "/sportello.php",*/
                                                                  "Risultati elettorali" => getenv('URL_COMUNE') . "/elezioni/risultati/" ),
                                            "Pagamenti (PagoPA)" => getenv('URL_COMUNE') . "/pagopa/" );
					
				
				} else unset( $TEMPLATE_MENU["CUSTOM"] );
				
				
				if( array_key_exists( "AGID_SECTION", $_SESSION ))
					$SEZIONE = $_SESSION["AGID_SECTION"];
				else
					$SEZIONE = "";
				
				foreach( $TEMPLATE_MENU as $nome => $voce ) {
					echo '<li class="nav-item';
					if( $SEZIONE == base64_encode( $nome ) )
						echo ' active';
					
					if( is_array( $voce ) ) {
						// Secondo livello...
						echo ' dropdown">'; 
						echo '<a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" aria-expanded="false"><span>'.$nome.'</span>';
						echo '<svg class="icon icon-xs"><use xlink:href="/public/assets/bootstrap-italia/svg/sprite.svg#it-expand"></use></svg></a>';
						echo '<div class="dropdown-menu"><div class="link-list-wrapper">';
						echo '<ul class="link-list">';
						foreach( $voce as $descrizione => $link ) {
							echo '<li>';
							echo '<a class="list-item" href="'.$link.'"><span class="text-nowrap">'.$descrizione.'</span></a>';
							echo '</li>';
						}
						echo '</ul></div></div></li>';
					} else {
						echo '"><a class="nav-link" href="'.$voce.'"><span>'.$nome.'</span></a>';
						if( $SEZIONE == base64_encode( $nome ) )
							echo '<span class="sr-only">menu selezionato</span>';
					}
					echo '</li>';
				}

          //CURRENT $url
        if( (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"]==="https"))   
          $URL = "https://";   
        else  
          $URL = "http://";   
          // Append the host(domain name, ip) to the URL.   
        $URL.= $_SERVER['HTTP_HOST'];   

          // Append the requested resource location to the URL   
        $URL.= $_SERVER['REQUEST_URI'];    

?>
              
                  </ul>

                </div>
              </div>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>
<main style="padding-top:0px">
	<div class="container my-4" style="padding-bottom:100px">

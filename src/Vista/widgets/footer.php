<?php
$pie = FMT\VistasGenericas::pie();
$config = FMT\Configuracion::instancia();
if(!empty($config['analytics']['habilitado']) && !empty($config['analytics']['ua'])) {
	$pie .= '
		<!-- Google Analytics -->
		<script type="text/javascript" src="'.$config['app']['endpoint_cdn'].'/js/analytics.js"></script>
		<script type="text/javascript">
		ga("create", "'.$config['analytics']['ua'].'", "auto");
		ga("send", "pageview");
		</script>
		<!-- End Google Analytics -->
		';
}
echo $pie;
?>
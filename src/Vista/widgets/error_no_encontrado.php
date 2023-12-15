<?php
	$template = new \FMT\Template(TEMPLATE_PATH.'/error_no_encontrado.html', ['ERROR_NO_ENCONTRADO'=>\App\Helper\Vista::get_url().'/img/error_no_encontrado.png']);

	echo $template;
<?php
	$template = new \FMT\Template(TEMPLATE_PATH.'/base.html',$vars,['CLEAN'=>true]);
	echo $template;

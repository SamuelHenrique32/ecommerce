<?php

use \Hcode\Page;								//acessa a partir da raiz
	
$app->get('/', function() {
    
	$page = new Page();

	$page->setTpl("index");

});

?>
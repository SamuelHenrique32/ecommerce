<?php

use \Hcode\Page;								//acessa a partir da raiz
use \Hcode\Model\Product;
	
$app->get('/', function() {

    $products = Product::listAll();             //lista produtos

	$page = new Page();

    $page->setTpl("index", [                    //passa por parametro para template
           'products'=>Product::checkList($products)
    ]);

});

?>
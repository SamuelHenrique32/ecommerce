<?php

use \Hcode\Page;								//acessa a partir da raiz
use \Hcode\Model\Product;
use \Hcode\Model\Category;
	
$app->get('/', function() {

    $products = Product::listAll();             //lista produtos

	$page = new Page();

    $page->setTpl("index", [                    //passa por parametro para template
           'products'=>Product::checkList($products)
    ]);

});

$app->get("/categories/:idcategory", function($idcategory){

    $category = new Category();

    $category->get((int)$idcategory);			//retorna pagina do site

    $page = new Page();

    $page->setTpl("category", [
        'category'=>$category->getValues(),
        'products'=>Product::checkList($category->getProducts())
    ]);

});

?>
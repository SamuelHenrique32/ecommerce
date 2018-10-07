<?php 

use \Hcode\PageAdmin;								//acessa a partir da raiz
use \Hcode\Model\User;
use \Hcode\Model\Product;

$app->get("/admin/products", function(){			//tela inicial no admin
	User::verifyLogin();
	$products = Product::listAll();
	$page = new PageAdmin();
	$page->setTpl("products", [
		"products"=>$products
	]);
});

$app->get("/admin/products/create", function(){		//tela cadastra novo
	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTpl("products-create");
});

$app->post("/admin/products/create", function(){	//salva novo cadastro
	User::verifyLogin();
	$product = new Product();
	$product->setData($_POST);
	$product->save();
	header("Location: /admin/products");			//redireciona para lista de produtos
	exit;
});

$app->get("/admin/products/:idproduct", function($idproduct){				//tela de edicao
	User::verifyLogin();
	$product = new Product();
	$product->get((int)$idproduct);
	$page = new PageAdmin();
	$page->setTpl("products-update", [
		'product'=>$product->getValues()
	]);
});

$app->post("/admin/products/:idproduct", function($idproduct){				//salva edicao
	User::verifyLogin();
	$product = new Product();
	$product->get((int)$idproduct);
	$product->setData($_POST);
	$product->save();
	$product->setPhoto($_FILES["file"]);									//add a foto ao cadastro
	header('Location: /admin/products');									//redirect
	exit;
});

$app->get("/admin/products/:idproduct/delete", function($idproduct){
	User::verifyLogin();
	$product = new Product();
	$product->get((int)$idproduct);
	$product->delete();
	header('Location: /admin/products');
	exit;
});

 ?> 
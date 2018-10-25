<?php 

use \Hcode\PageAdmin;								//acessa a partir da raiz
use \Hcode\Model\User;
use \Hcode\Model\Product;

$app->get("/admin/products", function(){

    User::verifyLogin();
    $search = (isset($_GET['search'])) ? $_GET['search'] : "";
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
    if ($search != '') {
        $pagination = Product::getPageSearch($search, $page);
    } else {
        $pagination = Product::getPage($page);
    }
    $pages = [];
    for ($x = 0; $x < $pagination['pages']; $x++)
    {
        array_push($pages, [
            'href'=>'/admin/products?'.http_build_query([
                    'page'=>$x+1,
                    'search'=>$search
                ]),
            'text'=>$x+1
        ]);
    }
    //$products = Product::listAll();
    $page = new PageAdmin();
    $page->setTpl("products", [
        "products"=>$pagination['data'],
        "search"=>$search,
        "pages"=>$pages
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
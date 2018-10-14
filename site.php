<?php

use \Hcode\Page;								//acessa a partir da raiz
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
	
$app->get('/', function() {

    $products = Product::listAll();             //lista produtos

	$page = new Page();

    $page->setTpl("index", [                    //passa por parametro para template
           'products'=>Product::checkList($products)
    ]);

});

$app->get("/categories/:idcategory", function($idcategory){

    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    $category = new Category();

    $category->get((int)$idcategory);			               //retorna pagina do site

    $pagination = $category->getProductsPage($page);

    $pages=[];

    for ($i=1; $i <= $pagination['pages']; $i++) {
        array_push($pages, [
            'link'=>'/categories/'.$category->getidcategory().'?page='.$i,          //caminho para mandar usuario se clicar na pagina
            'page'=>$i
        ]);
    }

    $page = new Page();

    $page->setTpl("category", [
        'category'=>$category->getValues(),
        'products'=>$pagination["data"],                       //puxa quantidade de itens para pagina
        'pages'=>$pages
    ]);

});

$app->get("/products/:desurl", function($desurl){              //visualizar dfetalhes do produto
    $product = new Product();
    $product->getFromURL($desurl);
    $page = new Page();
    $page->setTpl("product-detail", [
        'product'=>$product->getValues(),
        'categories'=>$product->getCategories()
    ]);
});

$app->get("/cart", function(){                                  //carrinho de compras
    $cart = Cart::getFromSession();
    $page = new Page();
    $page->setTpl("cart", [
        'cart'=>$cart->getValues(),
        'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
    ]);
});

$app->get("/cart/:idproduct/add", function($idproduct){
    $product = new Product();
    $product->get((int)$idproduct);
    $cart = Cart::getFromSession();                             //recupera carrinho ou cria novo
    $qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;       //na pagina de detalhes, permite adicionar mais de uma unidade
    for ($i = 0; $i < $qtd; $i++) {

        $cart->addProduct($product);
    }
    header("Location: /cart");
    exit;
});

$app->get("/cart/:idproduct/minus", function($idproduct){       //remove um
    $product = new Product();
    $product->get((int)$idproduct);
    $cart = Cart::getFromSession();
    $cart->removeProduct($product);
    header("Location: /cart");
    exit;
});

$app->get("/cart/:idproduct/remove", function($idproduct){
    $product = new Product();
    $product->get((int)$idproduct);
    $cart = Cart::getFromSession();
    $cart->removeProduct($product, true);
    header("Location: /cart");
    exit;
});

$app->post("/cart/freight", function(){                           //calculo frete
    $cart = Cart::getFromSession();
    $cart->setFreight($_POST['zipcode']);
    header("Location: /cart");
    exit;
});

$app->get("/checkout", function(){
    User::verifyLogin(false);                                   //false e para nao admin
    $cart = Cart::getFromSession();

    $address = new Address();

    $page = new Page();
    $page->setTpl("checkout", [
        'cart'=>$cart->getValues(),
        'address'=>$address->getValues()
    ]);
});

$app->get("/login", function(){                                 //mostra pagina de login
    $page = new Page();
    $page->setTpl("login", [
        'error'=>User::getError()
    ]);
});

$app->post("/login", function(){                                //recupera via post
    try {
        User::login($_POST["login"], $_POST["password"]);
    } catch (Exception $e) {
        User::setError($e->getMessage());
    }
    header("Location: /checkout");
    exit;
});

$app->get("/logout", function(){
    User::logout();
    header("Location: /login");
    exit;
});

?>
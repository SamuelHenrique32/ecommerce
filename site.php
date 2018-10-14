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
        'error'=>User::getError(),
        'errorRegister'=>User::getErrorRegister(),
        'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'', 'phone'=>'']
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

$app->post("/register", function(){
    $_SESSION['registerValues'] = $_POST;                                                //nao limpar dados no formulario de login
    //$_SESSION['postValues']['password'] = '';
    if (!isset($_POST['name']) || $_POST['name']=='') {                                  //nao definido ou vazio
        User::setErrorRegister("Preencha o seu nome.");
        header('Location: /login');
        exit;
    }
    if (!isset($_POST['email']) || $_POST['email']=='') {
        User::setErrorRegister("Preencha o seu e-mail.");
        header('Location: /login');
        exit;
    }
    if (!isset($_POST['password']) || $_POST['password']=='') {
        User::setErrorRegister("Preencha a senha.");
        header('Location: /login');
        exit;
    }
    if (User::checkLoginExist($_POST['email'])) {
        User::setErrorRegister("Este usuário já está cadastrado. Use a opção esqueci a senha.");
        header('Location: /login');
        exit;
    }
    $user = new User();
    $user->setData([
        'inadmin'=>0,                                                               //nao e adm
        'deslogin'=>$_POST['email'],
        'desperson'=>$_POST['name'],
        'desemail'=>$_POST['email'],
        'despassword'=>$_POST['password'],                                          //metodo save faz criptografia
        'nrphone'=>$_POST['phone']
    ]);

    $user->save();
    User::login($_POST["email"], $_POST["password"]);
    /*try {
        User::login($_POST["email"], $_POST["password"]);
    } catch (Exception $e) {
        User::setError($e->getMessage());
        header('Location: /login');
        exit;
    }
    $_SESSION['postValues'] = ['name'=>'', 'email'=>'', 'phone'=>''];*/
    header('Location: /checkout');
    exit;
});

$app->get("/forgot", function() {
    $page = new Page();
    $page->setTpl("forgot");
});

$app->post("/forgot", function(){
    $user = User::getForgot($_POST["email"], false);                //false pois nao e adm
    header("Location: /forgot/sent");
    exit;
});

$app->get("/forgot/sent", function(){
    $page = new Page();
    $page->setTpl("forgot-sent");
});

$app->get("/forgot/reset", function(){
    $user = User::validForgotDecrypt($_GET["code"]);
    $page = new Page();
    $page->setTpl("forgot-reset", array(
        "name"=>$user["desperson"],
        "code"=>$_GET["code"]
    ));
});

$app->post("/forgot/reset", function(){
    $forgot = User::validForgotDecrypt($_POST["code"]);
    User::setFogotUsed($forgot["idrecovery"]);
    $user = new User();
    $user->get((int)$forgot["iduser"]);
    $password = User::getPasswordHash($_POST["password"]);
    $user->setPassword($password);
    $page = new Page();
    $page->setTpl("forgot-reset-success");
});

$app->get("/profile", function(){                                      //exibe tela
    User::verifyLogin(false);                                          //nao adm
    $user = User::getFromSession();
    $page = new Page();
    $page->setTpl("profile", [
        'user'=>$user->getValues(),
        'profileMsg'=>User::getSuccess(),
        'profileError'=>User::getError()
    ]);
});

$app->post("/profile", function(){                                     //valida e salva dados no banco
    User::verifyLogin(false);
    if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {   //nao existir ou igual a vazio
        User::setError("Preencha o seu nome.");
        header('Location: /profile');
        exit;
    }
    if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
        User::setError("Preencha o seu e-mail.");
        header('Location: /profile');
        exit;
    }
    $user = User::getFromSession();
    if ($_POST['desemail'] !== $user->getdesemail()) {                  //alterou o e-mail
        if (User::checkLoginExists($_POST['desemail'])) {
            User::setError("Este endereço de e-mail já está cadastrado.");
            header('Location: /profile');
            exit;
        }
    }
    $_POST['inadmin'] = $user->getinadmin();                             //pega inadmin do banco, evita command injection
    $_POST['despassword'] = $user->getdespassword();
    $_POST['deslogin'] = $_POST['desemail'];
    $user->setData($_POST);
    $user->save();
    User::setSuccess("Dados alterados com sucesso!");
    header('Location: /profile');
    exit;
});

?>
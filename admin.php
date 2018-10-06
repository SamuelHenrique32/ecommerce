<?php 

use \Hcode\PageAdmin;
use \Hcode\Model\User;

$app->get('/admin', function() {
    
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("index");

});

$app->get('/admin/login', function() {

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("login");

});

$app->post('/admin/login', function() {

	User::login($_POST["login"], $_POST["password"]);

	header("Location: /admin");
	exit;

});

$app->get('/admin/logout', function() {

	User::logout();

	header("Location: /admin/login");
	exit;

});

//perdeu senha
$app->get("/admin/forgot", function() {
    //desabilitar header e footer
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot");	

});

//recupera via post ao digitar e-mail
$app->post("/admin/forgot", function(){

    //email via post <form  action="/admin/forgot" method="post">
	$user = User::getForgot($_POST["email"]);
    //redireciona para tela de confirmacao de envio
	header("Location: /admin/forgot/sent");
	exit;

});

//e-mail enviado
$app->get("/admin/forgot/sent", function(){

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	//renderiza sent
	$page->setTpl("forgot-sent");	

});

//ao clicar no link recebido por e-mail
$app->get("/admin/forgot/reset", function(){

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
    //parametros para template
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));

});

$app->post("/admin/forgot/reset", function(){

	$forgot = User::validForgotDecrypt($_POST["code"]);	

	//update no banco, recuperacao ja foi realizada
	User::setFogotUsed($forgot["idrecovery"]);              // metodo estatico

	$user = new User();

	$user->get((int)$forgot["iduser"]);
    //criptografa senha
	$password = User::getPasswordHash($_POST["password"]);
    //set hash da nova senha
	$user->setPassword($password);

	//template para sucesso na alteracao da senha, nao precisa de variavel
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset-success");

});

 ?>
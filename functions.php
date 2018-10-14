<?php

use \Hcode\Model\User;

function formatPrice(float $vlprice)
{
    return number_format($vlprice, 2, ",", ".");
}

function checkLogin($inadmin = true)            //usar no template no escopo global
{
    return User::checkLogin($inadmin);
}

function getUserName()
{
    $user = User::getFromSession();
    return $user->getdesperson();
}

?>
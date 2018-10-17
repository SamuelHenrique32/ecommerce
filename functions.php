<?php

use \Hcode\Model\User;
use \Hcode\Model\Cart;

function formatPrice($vlprice)
{
    if(!$vlprice > 0) $vlprice = 0;
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

function getCartNrQtd()                         //quantos produtos ha no carrinho
{
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();
    return $totals['nrqtd'];
}

function getCartVlSubTotal()                    //valor total do carrinho
{
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();
    return formatPrice($totals['vlprice']);
}

?>
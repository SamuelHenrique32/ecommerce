<?php
namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;

class Cart extends Model {
    const SESSION = "Cart";                                     //sessao para carrinho

    public static function getFromSession()                     //inserir carrinho novo ou pegar da sessao
    {
        $cart = new Cart();
        if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {       //ja esta na sessao
            $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);                                    //carrega
        } else {
            $cart->getFromSessionID();                                                              //recupera pelo session ID
            if (!(int)$cart->getidcart() > 0) {                                                     //nao carregou
                $data = [
                    'dessessionid'=>session_id()                                                    //cria novo
                ];
                if (User::checkLogin(false)) {                                                      //usuario logado
                    $user = User::getFromSession();

                    $data['iduser'] = $user->getiduser();
                }
                $cart->setData($data);
                $cart->save();
                $cart->setToSession();
            }
        }
        return $cart;
    }
    public function setToSession()                                                                  //usa this, nao e estatico
    {
        $_SESSION[Cart::SESSION] = $this->getValues();
    }
    public function getFromSessionID()
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
            ':dessessionid'=>session_id()
        ]);
        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }
    public function get(int $idcart)
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", [
            ':idcart'=>$idcart
        ]);
        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }
    public function save()
    {
        $sql = new Sql();
        $results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", [
            ':idcart'=>$this->getidcart(),
            ':dessessionid'=>$this->getdessessionid(),
            ':iduser'=>$this->getiduser(),
            ':deszipcode'=>$this->getdeszipcode(),
            ':vlfreight'=>$this->getvlfreight(),
            ':nrdays'=>$this->getnrdays()
        ]);
        $this->setData($results[0]);                    //set no objeto
    }

}
?>
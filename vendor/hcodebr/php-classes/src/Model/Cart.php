<?php
namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;

class Cart extends Model {
    const SESSION = "Cart";                                     //sessao para carrinho
    const SESSION_ERROR = "CartError";

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

    public function addProduct(Product $product)
    {
        $sql = new Sql();
        $sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES(:idcart, :idproduct)", [
            ':idcart'=>$this->getidcart(),
            ':idproduct'=>$product->getidproduct()
        ]);

        $this->getCalculateTotal();
    }

    public function removeProduct(Product $product, $all = false)
    {
        $sql = new Sql();
        if ($all) {                         //remove todos
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL", [
                ':idcart'=>$this->getidcart(),
                ':idproduct'=>$product->getidproduct()
            ]);
        } else {                            //apenas um
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1", [
                ':idcart'=>$this->getidcart(),
                ':idproduct'=>$product->getidproduct()
            ]);
        }

        $this->getCalculateTotal();
    }

    public function getProducts()               //retorna produtos em carrinho
    {
        $sql = new Sql();
        $rows = $sql->select("
			SELECT b.idproduct, b.desproduct , b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal 
			FROM tb_cartsproducts a 
			INNER JOIN tb_products b ON a.idproduct = b.idproduct 
			WHERE a.idcart = :idcart AND a.dtremoved IS NULL 
			GROUP BY b.idproduct, b.desproduct , b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl 
			ORDER BY b.desproduct
		", [
            ':idcart'=>$this->getidcart()
        ]);
        return Product::checkList($rows);       //verifica foto
    }

    public function getProductsTotals()         //para calculo do frete
    {
        $sql = new Sql();
        $results = $sql->select("
			SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd 
			FROM tb_products a
			INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
			WHERE b.idcart = :idcart AND dtremoved IS NULL
		", [
            ':idcart'=>$this->getidcart()
        ]);
        if (count($results) > 0) {
            return $results[0];
        } else {
            return [];
        }
    }

    public function setFreight($nrzipcode)
    {
        $nrzipcode = str_replace("-", "", $nrzipcode);
        $totals = $this->getProductsTotals();                               //total dos produtos
        if ($totals['nrqtd'] > 0) {
           if($totals['vlheight'] < 2 ) $totals['vlheight'] = 2;            //altura n pode ser menor que 2
           if($totals['vllength'] < 16 ) $totals['vllength'] = 16;          //comprimento n pode ser menor que 16

            $qs = http_build_query([                                        //monta query
                'nCdEmpresa'=>'',
                'sDsSenha'=>'',
                'nCdServico'=>'40010',
                'sCepOrigem'=>'09853120',
                'sCepDestino'=>$nrzipcode,
                'nVlPeso'=>$totals['vlweight'],
                'nCdFormato'=>'1',
                'nVlComprimento'=>$totals['vllength'],
                'nVlAltura'=>$totals['vlheight'],
                'nVlLargura'=>$totals['vlwidth'],
                'nVlDiametro'=>'0',
                'sCdMaoPropria'=>'S',
                'nVlValorDeclarado'=>$totals['vlprice'],
                'sCdAvisoRecebimento'=>'S'
            ]);
            //funcao para trabalhar com xml
            $xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);
            //echo json_encode($xml);
            //exit;
            $result = $xml->Servicos->cServico;
            if ($result->MsgErro != '') {                                       //mensagem de erro
                Cart::setMsgError($result->MsgErro);
            } else{
                Cart::clearMsgError();
            }
            $this->setnrdays($result->PrazoEntrega);
            $this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
            $this->setdeszipcode($nrzipcode);
            $this->save();
            return $result;
        } else{

        }
    }

    public static function formatValueToDecimal($value):float               //troca , por . retorna float
    {
        $value = str_replace('.', '', $value);
        return str_replace(',', '.', $value);
    }

    public static function setMsgError($msg)
    {
        $_SESSION[Cart::SESSION_ERROR] = $msg;
    }

    public static function getMsgError()
    {
        $msg = (isset($_SESSION[Cart::SESSION_ERROR]))  ? $_SESSION[Cart::SESSION_ERROR] : "";      //if ternario
        Cart::clearMsgError();
        return $msg;
    }

    public static function clearMsgError()
    {
        $_SESSION[Cart::SESSION_ERROR] = NULL;
    }

    public function updateFreight()
    {
        if ($this->getdeszipcode() != '') {
            $this->setFreight($this->getdeszipcode());
        }
    }

    public function getValues(){                                                                        //override
        $this->getCalculateTotal();

        return parent::getValues();
    }

    public function getCalculateTotal(){

        $this->updateFreight();
        $totals = $this->getProductsTotals();
        $this->setvlsubtotal($totals['vlprice']);
        $this->setvltotal($totals['vlprice'] + $this->getvlfreight());
    }

}

?>
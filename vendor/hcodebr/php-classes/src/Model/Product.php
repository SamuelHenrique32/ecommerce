<?php 
namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Product extends Model {

	public static function listAll()
	{
		$sql = new Sql();
		return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
	}

	public function save()
	{
		$sql = new Sql();
		$results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", array(
			":idproduct"=>$this->getidproduct(),
			":desproduct"=>$this->getdesproduct(),
			":vlprice"=>$this->getvlprice(),
			":vlwidth"=>$this->getvlwidth(),
			":vlheight"=>$this->getvlheight(),
			":vllength"=>$this->getvllength(),
			":vlweight"=>$this->getvlweight(),
			":desurl"=>$this->getdesurl()
		));
		$this->setData($results[0]);
	}

	public function get($idproduct)
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", [
			':idproduct'=>$idproduct
		]);
		$this->setData($results[0]);
	}

	public function delete()
	{
		$sql = new Sql();
		$sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", [
			':idproduct'=>$this->getidproduct()
		]);
	}

	public function checkPhoto()
	{
		if (file_exists(
			$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
			"res" . DIRECTORY_SEPARATOR . 
			"site" . DIRECTORY_SEPARATOR . 
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR . 
			$this->getidproduct() . ".jpg"
			)) {
			$url = "/res/site/img/products/" . $this->getidproduct() . ".jpg";		//img do produto
		} else {
			$url = "/res/site/img/product.jpg";										//img padrao
		}
		return $this->setdesphoto($url);											//retorna caminho da img
	}

	public function getValues()											//polimorfismo
	{
		$this->checkPhoto();											//verifica se produto possui foto
		$values = parent::getValues();
		return $values;
	}

	public function setPhoto($file)
	{	//explode para procurar por ponto
		$extension = explode('.', $file['name']);						//verifica tipo de extensao
		$extension = end($extension);									//extensao e depois do ponto
		switch ($extension) {
			case "jpg":
			case "jpeg":
			$image = imagecreatefromjpeg($file["tmp_name"]);
			break;
			case "gif":
			$image = imagecreatefromgif($file["tmp_name"]);
			break;
			case "png":
			$image = imagecreatefrompng($file["tmp_name"]);
			break;
		}
		$dist = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
			"res" . DIRECTORY_SEPARATOR . 
			"site" . DIRECTORY_SEPARATOR . 
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR . 
			$this->getidproduct() . ".jpg";
		imagejpeg($image, $dist);										//salva no destino
		imagedestroy($image);
		$this->checkPhoto();
	}
}
 ?> 
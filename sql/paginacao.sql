select SQL_CALC_FOUND_ROWS *
from tb_products a
inner join tb_productscategories b on a.idproduct = b.idproduct
inner join tb_categories c on c.idcategory = b.idcategory
where c.idcategory = 3
limit 0,3;

select FOUND_ROWS() as nrtotal;
<?php
function formatPrice(float $vlprice)                //forca float
{
    return number_format($vlprice, 2, ",", ".");
}
?>
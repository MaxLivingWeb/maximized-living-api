<?php

$content = $contentHeader;
$content .= '<span '.$this->compareLocationChange($location->name,$locationBeforeUpdate->name,$type).'>Location Name:</span> '.$location->name;
$content .= '<span '.$this->compareLocationChange($location->email,$locationBeforeUpdate->email,$type).'>Email:</span> '.$location->email;
$content .= '<span '.$this->compareLocationChange($location->telephone,$locationBeforeUpdate->telephone,$type).'>Telephone:</span> '.$location->telephone;
$content .= '<span '.$this->compareLocationChange($location->telephone_ext,$locationBeforeUpdate->telephone_ext,$type).'>Telephone Ext:</span> '.$location->telephone_ext;
$content .= '<span '.$this->compareLocationChange($location->fax,$locationBeforeUpdate->fax,$type).'>Fax:</span> '.$location->fax;
$content .= '<br><span '.$this->compareLocationChange($addresses[0]['address_1'],$locationBeforeUpdateAddress['address_1'],$type).'>Address 1:</span> '.$addresses[0]['address_1'];
$content .= '<br><span '.$this->compareLocationChange($addresses[0]['address_2'],$locationBeforeUpdateAddress['address_2'],$type).'>Address 2:</span> '.$addresses[0]['address_2'];
$content .= '<br><span '.$this->compareLocationChange($addresses[0]['city'],$locationBeforeUpdateAddress['city'],$type).'>City:</span> '.$addresses[0]['city'];
$content .= '<br><span '.$this->compareLocationChange($addresses[0]['region'],$locationBeforeUpdateAddress['region'],$type).'>Region:</span> '.$addresses[0]['region'];
$content .= '<br><span '.$this->compareLocationChange($addresses[0]['zip_postal_code'],$locationBeforeUpdateAddress['zip_postal_code'],$type).'>Postal Code:</span> '.$addresses[0]['zip_postal_code'];
$content .= '<br><span '.$this->compareLocationChange($addresses[0]['country'],$locationBeforeUpdateAddress['country'],$type).'>Country:</span> '.$addresses[0]['country'];

//Before Location Update information
if ($type==='update') {
    $content .= '<br><br><h4>Previous information:</h4>';
    $content .= 'Location Name: '.$locationBeforeUpdate->name;
    $content .= '<br>Email: '.$locationBeforeUpdate->email;
    $content .= '<br>Telephone: '.$locationBeforeUpdate->telephone;
    $content .= '<br>Telephone Ext: '.$locationBeforeUpdate->telephone_ext;
    $content .= '<br>Fax: '.$locationBeforeUpdate->fax;
    $content .= '<br>Address 1: '.$locationBeforeUpdateAddress['address_1'];
    $content .= '<br>Address 2: '.$locationBeforeUpdateAddress['address_2'];
    $content .= '<br>City: '.$locationBeforeUpdateAddress['city'];
    $content .= '<br>Region: '.$locationBeforeUpdateAddress['region'];
    $content .= '<br>Postal Code: '.$locationBeforeUpdateAddress['zip_postal_code'];
    $content .= '<br>Country: '.$locationBeforeUpdateAddress['country'];
}

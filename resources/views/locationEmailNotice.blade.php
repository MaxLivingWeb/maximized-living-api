{!! $contentHeader !!}

<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($location->name,$locationBeforeUpdate->name,$type) }}>Location Name: {{$location->name}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($location->email,$locationBeforeUpdate->email,$type) }}>Email: {{$location->email}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($location->telephone,$locationBeforeUpdate->telephone,$type) }}>Telephone: {{$location->telephone}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($location->telephone_ext,$locationBeforeUpdate->telephone_ext,$type) }}>Telephone Ext: {{$location->telephone_ext}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($location->fax,$locationBeforeUpdate->fax,$type) }}>Fax: {{$location->fax}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($addresses[0]['address_1'],$locationBeforeUpdateAddress['address_1'],$type) }}>Address 1: {{$addresses[0]['address_1']}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($addresses[0]['address_2'],$locationBeforeUpdateAddress['address_2'],$type) }}>Address 2: {{$addresses[0]['address_2']}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($addresses[0]['city'],$locationBeforeUpdateAddress['city'],$type) }}>City: {{$addresses[0]['city']}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($addresses[0]['region'],$locationBeforeUpdateAddress['region'],$type) }}>Region: {{$addresses[0]['region']}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($addresses[0]['zip_postal_code'],$locationBeforeUpdateAddress['zip_postal_code'],$type) }}>Postal Code: {{$addresses[0]['zip_postal_code']}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($addresses[0]['country'],$locationBeforeUpdateAddress['country'],$type) }}>Country: {{$addresses[0]['country']}}</div>
<br><br><h4>Previous information:</h4>
<div>Location Name: {{$locationBeforeUpdate->name}}</div>
<div>Email: {{$locationBeforeUpdate->email}}</div>
<div>Telephone: {{$locationBeforeUpdate->telephone}}</div>
<div>Telephone Ext: {{$locationBeforeUpdate->telephone_ext}}</div>
<div>Fax: {{$locationBeforeUpdate->fax}}</div>
<div>Address 1: {{$locationBeforeUpdateAddress['address_1']}}</div>
<div>Address 2: {{$locationBeforeUpdateAddress['address_2']}}</div>
<div>City: {{$locationBeforeUpdateAddress['city']}}</div>
<div>Region: {{$locationBeforeUpdateAddress['region']}}</div>
<div>Postal Code: {{$locationBeforeUpdateAddress['zip_postal_code']}}</div>
<div>Country: {{$locationBeforeUpdateAddress['country']}}</div>

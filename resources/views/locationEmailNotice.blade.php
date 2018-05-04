{!! $contentHeader !!}

<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($location->name,$locationBeforeUpdate->name) }}>Location Name: {{$location->name}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($location->email,$locationBeforeUpdate->email) }}>Email: {{$location->email}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($location->telephone,$locationBeforeUpdate->telephone) }}>Telephone: {{$location->telephone}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($location->telephone_ext,$locationBeforeUpdate->telephone_ext) }}>Telephone Ext: {{$location->telephone_ext}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($location->fax,$locationBeforeUpdate->fax) }}>Fax: {{$location->fax}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($addresses[0]['address_1'],$locationBeforeUpdateAddress['address_1']) }}>Address 1: {{$addresses[0]['address_1']}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($addresses[0]['address_2'],$locationBeforeUpdateAddress['address_2']) }}>Address 2: {{$addresses[0]['address_2']}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($addresses[0]['city'],$locationBeforeUpdateAddress['city']) }}>City: {{$addresses[0]['city']}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($addresses[0]['region'],$locationBeforeUpdateAddress['region']) }}>Region: {{$addresses[0]['region']}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($addresses[0]['zip_postal_code'],$locationBeforeUpdateAddress['zip_postal_code']) }}>Postal Code: {{$addresses[0]['zip_postal_code']}}</div>
<div {{ \App\Helpers\EmailFormattingHelper::compareLocationChange($addresses[0]['country'],$locationBeforeUpdateAddress['country']) }}>Country: {{$addresses[0]['country']}}</div>
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

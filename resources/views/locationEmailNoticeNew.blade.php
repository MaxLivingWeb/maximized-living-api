{!! $contentHeader !!}

<div>Location Name: {{$location->name}}</div>
<div>Email: {{$location->email}}</div>
<div>Telephone: {{$location->telephone}}</div>
<div>Telephone Ext: {{$location->telephone_ext}}</div>
<div>Fax: {{$location->fax}}</div>
<div>Address 1: {{$addresses[0]['address_1']}}</div>
<div>Address 2: {{$addresses[0]['address_2']}}</div>
<div>City: {{$addresses[0]['city']}}</div>
<div>Region: {{$addresses[0]['region']}}</div>
<div>Postal Code: {{$addresses[0]['zip_postal_code']}}</div>
<div>Country: {{$addresses[0]['country']}}</div>

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransactionalEmail extends Model
{
    //

    public function getTableColumnsWithoutId()
    {
        $columns = $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());

        return array_slice($columns, 1);
    }
}

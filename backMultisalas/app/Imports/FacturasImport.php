<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FacturasImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Factura([
            'name'  => $row['name'],
            'email' => $row['email'],
            'at'    => $row['password'],
        ]);
    }
}

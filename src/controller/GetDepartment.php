<?php

declare(strict_types=1);

namespace controller;

use model\Departement;

final class GetDepartment 
{
    private array $departments = [];

    public function getAllDepartments(): array 
    {
        return Departement::orderBy('nom_departement')
            ->get()
            ->toArray();
    }
}
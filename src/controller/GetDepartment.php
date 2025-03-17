<?php

namespace controller;

use model\Departement;

class GetDepartment {

    protected $departments = array();

    public function getAllDepartments() {
        return Departement::orderBy('nom_departement')->get()->toArray();
    }
}
<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use controller\getDepartment;
use db\Connection;

class getDepartmentTest extends TestCase
{
    private $getDepartmentController;

    protected function setUp(): void
    {
        Connection::createConn();
        $this->getDepartmentController = new getDepartment();
    }

    public function testGetDepartment()
    {


        $departements = $this->getDepartmentController->getAllDepartments();

        $this->assertIsArray($departements);
        $this->assertNotEmpty($departements);

        $this->assertArrayHasKey('id_departement', $departements[0]);
        $this->assertSame(6, $departements[0]['id_departement']);
        $this->assertArrayHasKey('id_region', $departements[0]);
        $this->assertSame(2, $departements[0]['id_region']);
        $this->assertArrayHasKey('nom_departement', $departements[0]);
        $this->assertSame('Bas-Rhin', $departements[0]['nom_departement']);
    }
}
<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use controller\getCategorie;
use db\Connection;

class getCategorieTest extends TestCase
{
    private $getCategorieController;

    protected function setUp(): void
    {
        Connection::createConn();
        $this->getCategorieController = new getCategorie();
    }

    public function testGetCategories()
    {

        $categories = $this->getCategorieController->getCategories();

        $this->assertIsArray($categories);
        $this->assertNotEmpty($categories);


        $this->assertArrayHasKey('id_categorie', $categories[0]);
        $this->assertSame(2, $categories[0]['id_categorie']);
        $this->assertArrayHasKey('nom_categorie', $categories[0]);
        $this->assertSame('Immobilier', $categories[0]['nom_categorie']);
    }
}
<?php

declare(strict_types=1);

namespace controller;

use model\ApiKey;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;

final class KeyGenerator 
{
    public function show(
        Twig $twig, 
        array $menu, 
        string $chemin, 
        array $cat
    ): Response {
        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => "{$chemin}/api/key", 'text' => "Générateur de clé API"]
        ];

        return $twig->render(
            new SlimResponse(),
            "key-generator.html.twig", 
            [
                "breadcrumb" => $menu, 
                "chemin" => $chemin, 
                "categories" => $cat
            ]
        );
    }

    public function generateKey(
        Twig $twig, 
        array $menu, 
        string $chemin, 
        array $cat, 
        string $nom
    ): Response {
        $cleanName = trim(str_replace(' ', '', $nom));
        
        if($cleanName === '') {
            return $this->renderError($twig, $menu, $chemin, $cat);
        }

        $key = $this->createAndSaveApiKey($cleanName);

        return $twig->render(
            new SlimResponse(),
            "key-generator-result.html.twig",
            [
                "breadcrumb" => [
                    ['href' => $chemin, 'text' => 'Accueil'],
                    ['href' => "{$chemin}/api/key", 'text' => "Clé générée"]
                ],
                "chemin" => $chemin, 
                "categories" => $cat, 
                "key" => $key
            ]
        );
    }

    private function renderError(
        Twig $twig, 
        array $menu, 
        string $chemin, 
        array $cat
    ): Response {
        return $twig->render(
            new SlimResponse(),
            "key-generator-error.html.twig",
            [
                "breadcrumb" => [
                    ['href' => $chemin, 'text' => 'Accueil'],
                    ['href' => "{$chemin}/api/key", 'text' => "Erreur"]
                ],
                "chemin" => $chemin,
                "categories" => $cat
            ]
        );
    }

    private function createAndSaveApiKey(string $name): string
    {
        $key = uniqid();
        
        $apikey = new ApiKey();
        $apikey->id_apikey = $key;
        $apikey->name_key = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $apikey->save();

        return $key;
    }
}
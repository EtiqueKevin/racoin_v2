<?php

namespace controller;

use model\ApiKey;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;

class KeyGenerator 
{
    public function show(Twig $twig, array $menu, string $chemin, array $cat): Response 
    {
        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => $chemin."/api/key", 'text' => "Générateur de clé API"]
        ];

        $response = new SlimResponse();
        return $twig->render($response, "key-generator.html.twig", [
            "breadcrumb" => $menu, 
            "chemin" => $chemin, 
            "categories" => $cat
        ]);
    }

    public function generateKey(Twig $twig, array $menu, string $chemin, array $cat, string $nom): Response 
    {
        $nospace_nom = str_replace(' ', '', $nom);
        $response = new SlimResponse();

        if($nospace_nom === '') {
            $menu = [
                ['href' => $chemin, 'text' => 'Accueil'],
                ['href' => $chemin."/api/key", 'text' => "Erreur"]
            ];

            return $twig->render($response, "key-generator-error.html.twig", [
                "breadcrumb" => $menu, 
                "chemin" => $chemin, 
                "categories" => $cat
            ]);
        }

        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => $chemin."/api/key", 'text' => "Clé générée"]
        ];

        // Generate unique 13-character key
        $key = uniqid();
        
        // Save key to database
        $apikey = new ApiKey();
        $apikey->id_apikey = $key;
        $apikey->name_key = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');
        $apikey->save();

        return $twig->render($response, "key-generator-result.html.twig", [
            "breadcrumb" => $menu, 
            "chemin" => $chemin, 
            "categories" => $cat, 
            "key" => $key
        ]);
    }
}
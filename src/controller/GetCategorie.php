<?php

declare(strict_types=1);

namespace controller;

use model\{Annonce, Annonceur, Categorie, Photo};
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;

final class GetCategorie 
{
    private array $annonces = [];

    public function getCategories(): array 
    {
        return Categorie::orderBy('nom_categorie')
            ->get()
            ->toArray();
    }

    private function getCategorieContent(string $chemin, int $n): void 
    {
        $recentAnnonces = Annonce::with("Annonceur")
            ->orderBy('id_annonce', 'desc')
            ->where('id_categorie', "=", $n)
            ->get();
            
        $annonces = [];
        foreach ($recentAnnonces as $annonce) {
            $annonce->nb_photo = Photo::where("id_annonce", "=", $annonce->id_annonce)->count();
            
            $annonce->url_photo = $annonce->nb_photo > 0
                ? Photo::select("url_photo")
                    ->where("id_annonce", "=", $annonce->id_annonce)
                    ->first()->url_photo
                : "{$chemin}/img/noimg.png";
            
            $annonce->nom_annonceur = Annonceur::select("nom_annonceur")
                ->where("id_annonceur", "=", $annonce->id_annonceur)
                ->first()->nom_annonceur;
                
            $annonces[] = $annonce;
        }
        $this->annonces = $annonces;
    }

    public function displayCategorie(
        Twig $twig, 
        array $menu, 
        string $chemin, 
        array $cat, 
        int $n
    ): Response {
        $categorie = Categorie::find($n)?->nom_categorie;
        
        if (!$categorie) {
            return (new SlimResponse())->withStatus(404);
        }

        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => "{$chemin}/cat/{$n}", 'text' => $categorie]
        ];

        $this->getCategorieContent($chemin, $n);
        
        return $twig->render(
            new SlimResponse(),
            "index.html.twig",
            [
                "breadcrumb" => $menu,
                "chemin" => $chemin,
                "categories" => $cat,
                "annonces" => $this->annonces
            ]
        );
    }
}
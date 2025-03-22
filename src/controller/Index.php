<?php

declare(strict_types=1);

namespace controller;

use model\Annonce;
use model\Annonceur;
use model\Photo;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;

final class Index
{
    private array $annonces = [];

    public function displayAllAnnonce(
        Twig $twig,
        array $menu,
        string $chemin,
        array $cat
    ): Response {
        $this->fetchAllAnnonces($chemin);
        
        return $twig->render(
            new SlimResponse(),
            "index.html.twig",
            [
                "breadcrumb" => $menu,
                "chemin"     => $chemin,
                "categories" => $cat,
                "annonces"   => $this->annonces
            ]
        );
    }

    private function fetchAllAnnonces(string $chemin): void
    {
        $recentAnnonces = Annonce::with("Annonceur")
            ->orderBy('id_annonce', 'desc')
            ->take(12)
            ->get();
            
        $annonces = [];
        foreach ($recentAnnonces as $annonce) {
            $photoCount = Photo::where("id_annonce", "=", $annonce->id_annonce)->count();
            $annonce->nb_photo = $photoCount;
            
            $annonce->url_photo = $photoCount > 0
                ? Photo::select("url_photo")
                    ->where("id_annonce", "=", $annonce->id_annonce)
                    ->first()->url_photo
                : '/img/noimg.png';
            
            $annonce->nom_annonceur = Annonceur::select("nom_annonceur")
                ->where("id_annonceur", "=", $annonce->id_annonceur)
                ->first()->nom_annonceur;
                
            $annonces[] = $annonce;
        }
        
        $this->annonces = $annonces;
    }
}
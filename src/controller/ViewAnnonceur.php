<?php

declare(strict_types=1);

namespace controller;

use model\Annonce;
use model\Annonceur;
use model\Photo;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;

final class ViewAnnonceur 
{
    private ?Annonceur $annonceur = null;

    public function afficherAnnonceur(
        Twig $twig, 
        array $menu, 
        string $chemin, 
        int $n, 
        array $cat
    ): Response {
        $this->annonceur = Annonceur::find($n);
        
        if (!$this->annonceur) {
            return (new SlimResponse())->withStatus(404);
        }

        $recentAnnonces = Annonce::where('id_annonceur', '=', $n)->get();
        $annonces = [];

        foreach ($recentAnnonces as $annonce) {
            $annonce->nb_photo = Photo::where('id_annonce', '=', $annonce->id_annonce)->count();
            
            $annonce->url_photo = $annonce->nb_photo > 0
                ? Photo::select('url_photo')
                    ->where('id_annonce', '=', $annonce->id_annonce)
                    ->first()->url_photo
                : "{$chemin}/img/noimg.png";

            $annonces[] = $annonce;
        }

        return $twig->render(
            new SlimResponse(), 
            "annonceur.html.twig", 
            [
                'nom' => $this->annonceur,
                "chemin" => $chemin,
                "annonces" => $annonces,
                "categories" => $cat,
                "breadcrumb" => $menu
            ]
        );
    }
}
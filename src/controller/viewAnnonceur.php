<?php

namespace controller;

use model\Annonce;
use model\Annonceur;
use model\Photo;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;

class viewAnnonceur 
{
    private ?Annonceur $annonceur;

    public function afficherAnnonceur(Twig $twig, array $menu, string $chemin, int $n, array $cat): Response 
    {
        $this->annonceur = Annonceur::find($n);
        
        if (!isset($this->annonceur)) {
            $response = new SlimResponse();
            return $response->withStatus(404);
        }

        $tmp = Annonce::where('id_annonceur', '=', $n)->get();
        $annonces = [];

        foreach ($tmp as $a) {
            $a->nb_photo = Photo::where('id_annonce', '=', $a->id_annonce)->count();
            
            if ($a->nb_photo > 0) {
                $a->url_photo = Photo::select('url_photo')
                    ->where('id_annonce', '=', $a->id_annonce)
                    ->first()->url_photo;
            } else {
                $a->url_photo = $chemin.'/img/noimg.png';
            }

            $annonces[] = $a;
        }

        $response = new SlimResponse();
        return $twig->render($response, "annonceur.html.twig", [
            'nom' => $this->annonceur,
            "chemin" => $chemin,
            "annonces" => $annonces,
            "categories" => $cat,
            "breadcrumb" => $menu
        ]);
    }
}
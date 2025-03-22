<?php

namespace controller;

use model\Annonce;
use model\Annonceur;
use model\Categorie;
use model\Photo;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;

class getCategorie 
{
    protected array $annonce = [];

    public function getCategories(): array 
    {
        return Categorie::orderBy('nom_categorie')->get()->toArray();
    }

    private function getCategorieContent(string $chemin, int $n): void 
    {
        $tmp = Annonce::with("Annonceur")
            ->orderBy('id_annonce', 'desc')
            ->where('id_categorie', "=", $n)
            ->get();
            
        $annonce = [];
        foreach ($tmp as $t) {
            $t->nb_photo = Photo::where("id_annonce", "=", $t->id_annonce)->count();
            
            if ($t->nb_photo > 0) {
                $t->url_photo = Photo::select("url_photo")
                    ->where("id_annonce", "=", $t->id_annonce)
                    ->first()->url_photo;
            } else {
                $t->url_photo = $chemin.'/img/noimg.png';
            }
            
            $t->nom_annonceur = Annonceur::select("nom_annonceur")
                ->where("id_annonceur", "=", $t->id_annonceur)
                ->first()->nom_annonceur;
                
            $annonce[] = $t;
        }
        $this->annonce = $annonce;
    }

    public function displayCategorie(Twig $twig, array $menu, string $chemin, array $cat, int $n): Response 
    {
        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => $chemin."/cat/".$n, 'text' => Categorie::find($n)->nom_categorie]
        ];

        $this->getCategorieContent($chemin, $n);
        
        $response = new SlimResponse();
        return $twig->render($response, "index.html.twig", [
            "breadcrumb" => $menu,
            "chemin" => $chemin,
            "categories" => $cat,
            "annonces" => $this->annonce
        ]);
    }
}
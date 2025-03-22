<?php

namespace controller;

use model\Annonce;
use model\Categorie;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;

class Search 
{
    public function show(Twig $twig, array $menu, string $chemin, array $cat): Response 
    {
        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => $chemin."/search", 'text' => "Recherche"]
        ];

        $response = new SlimResponse();
        return $twig->render($response, "search.html.twig", [
            "breadcrumb" => $menu, 
            "chemin" => $chemin, 
            "categories" => $cat
        ]);
    }

    public function research(array $array, Twig $twig, array $menu, string $chemin, array $cat): Response 
    {
        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => $chemin."/search", 'text' => "Résultats de la recherche"]
        ];

        $nospace_mc = str_replace(' ', '', $array['motclef']);
        $nospace_cp = str_replace(' ', '', $array['codepostal']);

        $query = Annonce::select();

        if ($this->isEmptySearch($nospace_mc, $nospace_cp, $array)) {
            $annonce = Annonce::all();
        } else {
            $query = $this->buildSearchQuery($query, $nospace_mc, $nospace_cp, $array);
            $annonce = $query->get();
        }

        $response = new SlimResponse();
        return $twig->render($response, "index.html.twig", [
            "breadcrumb" => $menu, 
            "chemin" => $chemin, 
            "annonces" => $annonce, 
            "categories" => $cat
        ]);
    }

    private function isEmptySearch(string $nospace_mc, string $nospace_cp, array $array): bool 
    {
        return ($nospace_mc === "") &&
               ($nospace_cp === "") &&
               (($array['categorie'] === "Toutes catégories" || $array['categorie'] === "-----")) &&
               ($array['prix-min'] === "Min") &&
               (($array['prix-max'] === "Max") || ($array['prix-max'] === "nolimit"));
    }

    private function buildSearchQuery($query, string $nospace_mc, string $nospace_cp, array $array) 
    {
        if ($nospace_mc !== "") {
            $query->where('description', 'like', '%'.$array['motclef'].'%');
        }

        if ($nospace_cp !== "") {
            $query->where('ville', '=', $array['codepostal']);
        }

        if ($array['categorie'] !== "Toutes catégories" && $array['categorie'] !== "-----") {
            $categ = Categorie::select('id_categorie')
                ->where('id_categorie', '=', $array['categorie'])
                ->first()->id_categorie;
            $query->where('id_categorie', '=', $categ);
        }

        $this->addPriceFilters($query, $array);

        return $query;
    }

    private function addPriceFilters($query, array $array): void 
    {
        if ($array['prix-min'] !== "Min" && $array['prix-max'] !== "Max") {
            if ($array['prix-max'] !== "nolimit") {
                $query->whereBetween('prix', [$array['prix-min'], $array['prix-max']]);
            } else {
                $query->where('prix', '>=', $array['prix-min']);
            }
        } elseif ($array['prix-max'] !== "Max" && $array['prix-max'] !== "nolimit") {
            $query->where('prix', '<=', $array['prix-max']);
        } elseif ($array['prix-min'] !== "Min") {
            $query->where('prix', '>=', $array['prix-min']);
        }
    }
}
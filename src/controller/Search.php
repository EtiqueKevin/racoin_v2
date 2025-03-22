<?php

declare(strict_types=1);

namespace controller;

use model\Annonce;
use model\Categorie;
use Illuminate\Database\Query\Builder;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;

final class Search 
{
    public function show(
        Twig $twig, 
        array $menu, 
        string $chemin, 
        array $cat
    ): Response {
        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => "{$chemin}/search", 'text' => "Recherche"]
        ];

        return $twig->render(
            new SlimResponse(), 
            "search.html.twig", 
            [
                "breadcrumb" => $menu, 
                "chemin" => $chemin, 
                "categories" => $cat
            ]
        );
    }

    public function research(
        array $array, 
        Twig $twig, 
        array $menu, 
        string $chemin, 
        array $cat
    ): Response {
        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => "{$chemin}/search", 'text' => "Résultats de la recherche"]
        ];

        $cleanKeyword = trim(str_replace(' ', '', $array['motclef'] ?? ''));
        $cleanPostalCode = trim(str_replace(' ', '', $array['codepostal'] ?? ''));

        $query = Annonce::select();
        $annonces = $this->isEmptySearch($cleanKeyword, $cleanPostalCode, $array)
            ? Annonce::all()
            : $this->buildSearchQuery($query, $cleanKeyword, $cleanPostalCode, $array)->get();

        return $twig->render(
            new SlimResponse(), 
            "index.html.twig", 
            [
                "breadcrumb" => $menu, 
                "chemin" => $chemin, 
                "annonces" => $annonces, 
                "categories" => $cat
            ]
        );
    }

    private function isEmptySearch(string $keyword, string $postalCode, array $array): bool 
    {
        return $keyword === "" 
            && $postalCode === ""
            && ($array['categorie'] === "Toutes catégories" || $array['categorie'] === "-----")
            && $array['prix-min'] === "Min"
            && ($array['prix-max'] === "Max" || $array['prix-max'] === "nolimit");
    }

    private function buildSearchQuery(
        Builder $query, 
        string $keyword, 
        string $postalCode, 
        array $array
    ): Builder {
        if ($keyword !== "") {
            $query->where('description', 'like', "%{$array['motclef']}%");
        }

        if ($postalCode !== "") {
            $query->where('ville', '=', $array['codepostal']);
        }

        if (!in_array($array['categorie'], ["Toutes catégories", "-----"], true)) {
            $category = Categorie::select('id_categorie')
                ->where('id_categorie', '=', $array['categorie'])
                ->first();
                
            if ($category) {
                $query->where('id_categorie', '=', $category->id_categorie);
            }
        }

        $this->addPriceFilters($query, $array);

        return $query;
    }

    private function addPriceFilters(Builder $query, array $array): void 
    {
        $minPrice = $array['prix-min'] ?? 'Min';
        $maxPrice = $array['prix-max'] ?? 'Max';

        match(true) {
            $minPrice !== "Min" && $maxPrice !== "Max" => 
                $maxPrice !== "nolimit" 
                    ? $query->whereBetween('prix', [$minPrice, $maxPrice])
                    : $query->where('prix', '>=', $minPrice),
            $maxPrice !== "Max" && $maxPrice !== "nolimit" =>
                $query->where('prix', '<=', $maxPrice),
            $minPrice !== "Min" =>
                $query->where('prix', '>=', $minPrice),
            default => null
        };
    }
}
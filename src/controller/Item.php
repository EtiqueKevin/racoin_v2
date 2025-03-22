<?php

declare(strict_types=1);

namespace controller;

use AllowDynamicProperties;
use model\{Annonce, Annonceur, Categorie, Departement, Photo};
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;
use DateTimeZone;

#[AllowDynamicProperties] 
final class Item 
{
    private ?Annonce $annonce = null;
    private ?Annonceur $annonceur = null;
    private ?Departement $departement = null;
    private array $photo = [];
    private string $categItem = '';
    private string $dptItem = '';

    public function afficherItem(
        Twig $twig, 
        array $menu, 
        string $chemin, 
        int $n, 
        array $cat
    ): Response {
        $this->annonce = Annonce::find($n);
        
        if (!$this->annonce) {
            return (new SlimResponse())->withStatus(404);
        }

        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => "{$chemin}/cat/{$n}", 'text' => Categorie::find($this->annonce->id_categorie)?->nom_categorie],
            ['href' => "{$chemin}/item/{$n}", 'text' => $this->annonce->titre]
        ];

        $this->annonceur = Annonceur::find($this->annonce->id_annonceur);
        $this->departement = Departement::find($this->annonce->id_departement);
        $this->photo = Photo::where('id_annonce', '=', $n)->get()->toArray();


        return $twig->render(
            new SlimResponse(), 
            "item.html.twig", 
            [
                "breadcrumb" => $menu,
                "chemin" => $chemin,
                "annonce" => $this->annonce,
                "annonceur" => $this->annonceur,
                "dep" => $this->departement->nom_departement,
                "photo" => $this->photo,
                "categories" => $cat
            ]
        );
    }

    public function supprimerItemGet(
        Twig $twig, 
        array $menu, 
        string $chemin, 
        int $n
    ): Response {
        $this->annonce = Annonce::find($n);
        
        if (!$this->annonce) {
            return (new SlimResponse())->withStatus(404);
        }

        return $twig->render(
            new SlimResponse(),
            "delGet.html.twig",
            [
                "breadcrumb" => $menu,
                "chemin" => $chemin,
                "annonce" => $this->annonce
            ]
        );
    }

    public function supprimerItemPost(
        Twig $twig, 
        array $menu, 
        string $chemin, 
        int $n, 
        array $cat
    ): Response {
        $this->annonce = Annonce::find($n);
        $reponse = false;
        
        if (isset($_POST["pass"]) && password_verify($_POST["pass"], $this->annonce->mdp)) {
            $reponse = true;
            Photo::where('id_annonce', '=', $n)->delete();
            $this->annonce->delete();
        }

        return $twig->render(
            new SlimResponse(),
            "delPost.html.twig",
            [
                "breadcrumb" => $menu,
                "chemin" => $chemin,
                "annonce" => $this->annonce,
                "pass" => $reponse,
                "categories" => $cat
            ]
        );
    }

    public function edit(
        Twig $twig, 
        array $menu, 
        string $chemin, 
        array $allPostVars, 
        int $id
    ): Response {
        date_default_timezone_set((new DateTimeZone('Europe/Paris'))->getName());
        
        $errors = $this->validateInput($allPostVars);

        if (!empty(array_filter($errors))) {
            return $twig->render(
                new SlimResponse(),
                "add-error.html.twig",
                [
                    "breadcrumb" => $menu,
                    "chemin" => $chemin,
                    "errors" => array_values(array_filter($errors))
                ]
            );
        }

        $this->annonce = Annonce::find($id);
        $this->annonceur = Annonceur::find($this->annonce->id_annonceur);

        $this->updateAnnonceurData($allPostVars);
        $this->updateAnnonceData($allPostVars);

        $this->annonceur->save();
        $this->annonceur->annonce()->save($this->annonce);

        return $twig->render(
            new SlimResponse(),
            "modif-confirm.html.twig",
            [
                "breadcrumb" => $menu,
                "chemin" => $chemin
            ]
        );
    }

    private function validateInput(array $data): array
    {
        return [
            'nameAdvertiser' => empty(trim($data['nom'] ?? '')) ? 'Veuillez entrer votre nom' : '',
            'emailAdvertiser' => !filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL) 
                ? 'Veuillez entrer une adresse mail correcte' 
                : '',
            // continuer pour les autres champs
        ];
    }

    private function updateAnnonceurData(array $data): void
    {
        $this->annonceur->email = htmlspecialchars($data['email'] ?? '', ENT_QUOTES, 'UTF-8');
        $this->annonceur->nom_annonceur = htmlspecialchars($data['nom'] ?? '', ENT_QUOTES, 'UTF-8');
        $this->annonceur->telephone = htmlspecialchars($data['phone'] ?? '', ENT_QUOTES, 'UTF-8');
    }

    private function updateAnnonceData(array $data): void
    {
        $this->annonce->ville = htmlspecialchars($data['ville'] ?? '', ENT_QUOTES, 'UTF-8');
        $this->annonce->id_departement = $data['departement'] ?? 0;
        $this->annonce->prix = htmlspecialchars($data['price'] ?? '', ENT_QUOTES, 'UTF-8');
        $this->annonce->mdp = password_hash($data['psw'] ?? '', PASSWORD_DEFAULT);
        $this->annonce->titre = htmlspecialchars($data['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $this->annonce->description = htmlspecialchars($data['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $this->annonce->id_categorie = $data['categorie'] ?? 0;
        $this->annonce->date = date('Y-m-d');
    }
}
<?php

namespace controller;

use AllowDynamicProperties;
use model\Annonce;
use model\Annonceur;
use model\Categorie;
use model\Departement;
use model\Photo;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;

#[AllowDynamicProperties] 
class item 
{
    public function afficherItem(Twig $twig, array $menu, string $chemin, int $n, array $cat): Response
    {
        $this->annonce = Annonce::find($n);
        if (!isset($this->annonce)) {
            $response = new SlimResponse();
            return $response->withStatus(404);
        }

        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => $chemin."/cat/".$n, 'text' => Categorie::find($this->annonce->id_categorie)?->nom_categorie],
            ['href' => $chemin."/item/".$n, 'text' => $this->annonce->titre]
        ];

        $this->annonceur = Annonceur::find($this->annonce->id_annonceur);
        $this->departement = Departement::find($this->annonce->id_departement);
        $this->photo = Photo::where('id_annonce', '=', $n)->get();

        $response = new SlimResponse();
        return $twig->render($response, "item.html.twig", [
            "breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce,
            "annonceur" => $this->annonceur,
            "dep" => $this->departement->nom_departement,
            "photo" => $this->photo,
            "categories" => $cat
        ]);
    }

    public function supprimerItemGet(Twig $twig, array $menu, string $chemin, int $n): Response
    {
        $this->annonce = Annonce::find($n);
        if (!isset($this->annonce)) {
            $response = new SlimResponse();
            return $response->withStatus(404);
        }

        $response = new SlimResponse();
        return $twig->render($response, "delGet.html.twig", [
            "breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce
        ]);
    }

    public function supprimerItemPost(Twig $twig, array $menu, string $chemin, int $n, array $cat): Response
    {
        $this->annonce = Annonce::find($n);
        $reponse = false;
        
        if (password_verify($_POST["pass"], $this->annonce->mdp)) {
            $reponse = true;
            Photo::where('id_annonce', '=', $n)->delete();
            $this->annonce->delete();
        }

        $response = new SlimResponse();
        return $twig->render($response, "delPost.html.twig", [
            "breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce,
            "pass" => $reponse,
            "categories" => $cat
        ]);
    }

    public function modifyGet(Twig $twig, array $menu, string $chemin, int $id): Response
    {
        $this->annonce = Annonce::find($id);
        if (!isset($this->annonce)) {
            $response = new SlimResponse();
            return $response->withStatus(404);
        }

        $response = new SlimResponse();
        return $twig->render($response, "modifyGet.html.twig", [
            "breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce
        ]);
    }

    public function modifyPost(Twig $twig, array $menu, string $chemin, int $n, array $cat, array $dpt): Response
    {
        $this->annonce = Annonce::find($n);
        $this->annonceur = Annonceur::find($this->annonce->id_annonceur);
        $this->categItem = Categorie::find($this->annonce->id_categorie)->nom_categorie;
        $this->dptItem = Departement::find($this->annonce->id_departement)->nom_departement;

        $reponse = password_verify($_POST["pass"], $this->annonce->mdp);

        $response = new SlimResponse();
        return $twig->render($response, "modifyPost.html.twig", [
            "breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce,
            "annonceur" => $this->annonceur,
            "pass" => $reponse,
            "categories" => $cat,
            "departements" => $dpt,
            "dptItem" => $this->dptItem,
            "categItem" => $this->categItem
        ]);
    }

    public function edit(Twig $twig, array $menu, string $chemin, array $allPostVars, int $id): Response
    {
        date_default_timezone_set('Europe/Paris');
        
        // Validate input data
        $errors = $this->validateInput($allPostVars);

        if (!empty($errors)) {
            $response = new SlimResponse();
            return $twig->render($response, "add-error.html.twig", [
                "breadcrumb" => $menu,
                "chemin" => $chemin,
                "errors" => array_values(array_filter($errors))
            ]);
        }

        // Update database
        $this->annonce = Annonce::find($id);
        $this->annonceur = Annonceur::find($this->annonce->id_annonceur);

        $this->updateAnnonceurData($allPostVars);
        $this->updateAnnonceData($allPostVars);

        $this->annonceur->save();
        $this->annonceur->annonce()->save($this->annonce);

        $response = new SlimResponse();
        return $twig->render($response, "modif-confirm.html.twig", [
            "breadcrumb" => $menu, 
            "chemin" => $chemin
        ]);
    }

    private function validateInput(array $data): array
    {
        $errors = [
            'nameAdvertiser' => '',
            'emailAdvertiser' => '',
            'phoneAdvertiser' => '',
            'villeAdvertiser' => '',
            'departmentAdvertiser' => '',
            'categorieAdvertiser' => '',
            'titleAdvertiser' => '',
            'descriptionAdvertiser' => '',
            'priceAdvertiser' => ''
        ];

        if (empty(trim($data['nom']))) {
            $errors['nameAdvertiser'] = 'Veuillez entrer votre nom';
        }
        if (!filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
            $errors['emailAdvertiser'] = 'Veuillez entrer une adresse mail correcte';
        }
        // ...continue validation for other fields

        return $errors;
    }

    private function updateAnnonceurData(array $data): void
    {
        $this->annonceur->email = htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8');
        $this->annonceur->nom_annonceur = htmlspecialchars($data['nom'], ENT_QUOTES, 'UTF-8');
        $this->annonceur->telephone = htmlspecialchars($data['phone'], ENT_QUOTES, 'UTF-8');
    }

    private function updateAnnonceData(array $data): void
    {
        $this->annonce->ville = htmlspecialchars($data['ville'], ENT_QUOTES, 'UTF-8');
        $this->annonce->id_departement = $data['departement'];
        $this->annonce->prix = htmlspecialchars($data['price'], ENT_QUOTES, 'UTF-8');
        $this->annonce->mdp = password_hash($data['psw'], PASSWORD_DEFAULT);
        $this->annonce->titre = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        $this->annonce->description = htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8');
        $this->annonce->id_categorie = $data['categorie'];
        $this->annonce->date = date('Y-m-d');
    }
}
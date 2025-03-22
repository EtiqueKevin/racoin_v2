<?php

namespace controller;

use model\Annonce;
use model\Annonceur;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;

class addItem
{
    public function addItemView(Twig $twig, array $menu, string $chemin, array $cat, array $dpt): Response
    {
        $response = new SlimResponse();
        return $twig->render($response, "add.html.twig", [
            "breadcrumb"   => $menu,
            "chemin"       => $chemin,
            "categories"   => $cat,
            "departements" => $dpt
        ]);
    }

    public function addNewItem(Twig $twig, array $menu, string $chemin, array $allPostVars): Response
    {
        date_default_timezone_set('Europe/Paris');

        // Validate input data
        $errors = $this->validateInput($allPostVars);

        // If there are errors, show error page
        if (!empty($errors)) {
            $response = new SlimResponse();
            return $twig->render($response, "add-error.html.twig", [
                "breadcrumb" => $menu,
                "chemin"     => $chemin,
                "errors"     => array_values(array_filter($errors))
            ]);
        }

        // Create and save new announcement
        $annonce = new Annonce();
        $annonceur = new Annonceur();

        $this->updateAnnonceurData($annonceur, $allPostVars);
        $this->updateAnnonceData($annonce, $allPostVars);

        $annonceur->save();
        $annonceur->annonce()->save($annonce);

        $response = new SlimResponse();
        return $twig->render($response, "add-confirm.html.twig", [
            "breadcrumb" => $menu, 
            "chemin" => $chemin
        ]);
    }

    private function validateInput(array $data): array
    {
        $errors = [
            'nameAdvertiser'        => '',
            'emailAdvertiser'       => '',
            'phoneAdvertiser'       => '',
            'villeAdvertiser'       => '',
            'departmentAdvertiser'  => '',
            'categorieAdvertiser'   => '',
            'titleAdvertiser'       => '',
            'descriptionAdvertiser' => '',
            'priceAdvertiser'       => '',
            'passwordAdvertiser'    => ''
        ];

        if (empty(trim($data['nom']))) {
            $errors['nameAdvertiser'] = 'Veuillez entrer votre nom';
        }
        if (!$this->isEmail(trim($data['email']))) {
            $errors['emailAdvertiser'] = 'Veuillez entrer une adresse mail correcte';
        }
        if (empty($data['phone']) || !is_numeric($data['phone'])) {
            $errors['phoneAdvertiser'] = 'Veuillez entrer votre numéro de téléphone';
        }
        if (empty($data['ville'])) {
            $errors['villeAdvertiser'] = 'Veuillez entrer votre ville';
        }
        if (!is_numeric($data['departement'])) {
            $errors['departmentAdvertiser'] = 'Veuillez choisir un département';
        }
        if (!is_numeric($data['categorie'])) {
            $errors['categorieAdvertiser'] = 'Veuillez choisir une catégorie';
        }
        if (empty($data['title'])) {
            $errors['titleAdvertiser'] = 'Veuillez entrer un titre';
        }
        if (empty($data['description'])) {
            $errors['descriptionAdvertiser'] = 'Veuillez entrer une description';
        }
        if (empty($data['price']) || !is_numeric($data['price'])) {
            $errors['priceAdvertiser'] = 'Veuillez entrer un prix';
        }
        if (empty($data['psw']) || empty($data['confirm-psw']) || $data['psw'] !== $data['confirm-psw']) {
            $errors['passwordAdvertiser'] = 'Les mots de passes ne sont pas identiques';
        }

        return $errors;
    }

    private function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function updateAnnonceurData(Annonceur $annonceur, array $data): void
    {
        $annonceur->email = htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8');
        $annonceur->nom_annonceur = htmlspecialchars($data['nom'], ENT_QUOTES, 'UTF-8');
        $annonceur->telephone = htmlspecialchars($data['phone'], ENT_QUOTES, 'UTF-8');
    }

    private function updateAnnonceData(Annonce $annonce, array $data): void
    {
        $annonce->ville = htmlspecialchars($data['ville'], ENT_QUOTES, 'UTF-8');
        $annonce->id_departement = $data['departement'];
        $annonce->prix = htmlspecialchars($data['price'], ENT_QUOTES, 'UTF-8');
        $annonce->mdp = password_hash($data['psw'], PASSWORD_DEFAULT);
        $annonce->titre = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        $annonce->description = htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8');
        $annonce->id_categorie = $data['categorie'];
        $annonce->date = date('Y-m-d');
    }
}
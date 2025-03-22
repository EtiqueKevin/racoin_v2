<?php

declare(strict_types=1);

namespace controller;

use DateTimeZone;
use model\{Annonce, Annonceur};
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Psr7\Response as SlimResponse;

final class AddItem
{
    public function addItemView(
        Twig $twig, 
        array $menu, 
        string $chemin, 
        array $cat, 
        array $dpt
    ): Response {
        return $twig->render(
            new SlimResponse(),
            "add.html.twig",
            [
                "breadcrumb"   => $menu,
                "chemin"       => $chemin,
                "categories"   => $cat,
                "departements" => $dpt
            ]
        );
    }

    public function addNewItem(
        Twig $twig, 
        array $menu, 
        string $chemin, 
        array $allPostVars
    ): Response {
        date_default_timezone_set((new DateTimeZone('Europe/Paris'))->getName());

        $errors = $this->validateInput($allPostVars);

        if (!empty(array_filter($errors))) {
            return $twig->render(
                new SlimResponse(),
                "add-error.html.twig",
                [
                    "breadcrumb" => $menu,
                    "chemin"     => $chemin,
                    "errors"     => array_values(array_filter($errors))
                ]
            );
        }

        $annonce = new Annonce();
        $annonceur = new Annonceur();

        $this->updateAnnonceurData($annonceur, $allPostVars);
        $this->updateAnnonceData($annonce, $allPostVars);

        $annonceur->save();
        $annonceur->annonce()->save($annonce);

        return $twig->render(
            new SlimResponse(),
            "add-confirm.html.twig",
            [
                "breadcrumb" => $menu, 
                "chemin" => $chemin
            ]
        );
    }

    private function validateInput(array $data): array
    {
        return [
            'nameAdvertiser' => empty(trim($data['nom'] ?? '')) 
                ? 'Veuillez entrer votre nom' 
                : '',
            'emailAdvertiser' => !$this->isEmail(trim($data['email'] ?? '')) 
                ? 'Veuillez entrer une adresse mail correcte' 
                : '',
            'phoneAdvertiser' => !is_numeric($data['phone'] ?? '') 
                ? 'Veuillez entrer votre numéro de téléphone' 
                : '',
            'villeAdvertiser' => empty($data['ville'] ?? '') 
                ? 'Veuillez entrer votre ville' 
                : '',
            'departmentAdvertiser' => !is_numeric($data['departement'] ?? '') 
                ? 'Veuillez choisir un département' 
                : '',
            'categorieAdvertiser' => !is_numeric($data['categorie'] ?? '') 
                ? 'Veuillez choisir une catégorie' 
                : '',
            'titleAdvertiser' => empty($data['title'] ?? '') 
                ? 'Veuillez entrer un titre' 
                : '',
            'descriptionAdvertiser' => empty($data['description'] ?? '') 
                ? 'Veuillez entrer une description' 
                : '',
            'priceAdvertiser' => !is_numeric($data['price'] ?? '') 
                ? 'Veuillez entrer un prix' 
                : '',
            'passwordAdvertiser' => $this->validatePassword($data)
        ];
    }

    private function validatePassword(array $data): string
    {
        if (empty($data['psw'] ?? '') || empty($data['confirm-psw'] ?? '')) {
            return 'Veuillez remplir les deux champs de mot de passe';
        }
        
        return ($data['psw'] !== $data['confirm-psw'])
            ? 'Les mots de passes ne sont pas identiques'
            : '';
    }

    private function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function updateAnnonceurData(Annonceur $annonceur, array $data): void
    {
        $annonceur->email = htmlspecialchars($data['email'] ?? '', ENT_QUOTES, 'UTF-8');
        $annonceur->nom_annonceur = htmlspecialchars($data['nom'] ?? '', ENT_QUOTES, 'UTF-8');
        $annonceur->telephone = htmlspecialchars($data['phone'] ?? '', ENT_QUOTES, 'UTF-8');
    }

    private function updateAnnonceData(Annonce $annonce, array $data): void
    {
        $annonce->ville = htmlspecialchars($data['ville'] ?? '', ENT_QUOTES, 'UTF-8');
        $annonce->id_departement = (int)($data['departement'] ?? 0);
        $annonce->prix = htmlspecialchars($data['price'] ?? '', ENT_QUOTES, 'UTF-8');
        $annonce->mdp = password_hash($data['psw'] ?? '', PASSWORD_DEFAULT);
        $annonce->titre = htmlspecialchars($data['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $annonce->description = htmlspecialchars($data['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $annonce->id_categorie = (int)($data['categorie'] ?? 0);
        $annonce->date = date('Y-m-d');
    }
}
<?php
declare(strict_types=1);

require '../vendor/autoload.php';
error_reporting(E_ALL ^ E_DEPRECATED);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Psr7\Response as Psr7Response;
use controller\{GetCategorie, GetDepartment, Index, Item, AddItem, Search, ViewAnnonceur, KeyGenerator};
use db\Connection;
use model\{Annonce, Annonceur, Categorie, Departement};

// Create Connection
Connection::createConn();

// Create App
$app = AppFactory::create();

// Create Twig
$twig = Twig::create(__DIR__ . '/../template', ['cache' => false]);

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));

// Add trailing slash middleware
$app->add(function (Request $request, $handler): Response {
    $uri = $request->getUri();
    $path = $uri->getPath();
    
    if ($path !== '/' && str_ends_with($path, '/')) {
        $uri = $uri->withPath(rtrim($path, '/'));
        
        if ($request->getMethod() === 'GET') {
            $response = new Psr7Response();
            return $response->withHeader('Location', (string)$uri)
                          ->withStatus(301);
        }
        
        return $handler->handle($request->withUri($uri));
    }
    
    return $handler->handle($request);
});

// Start session
if (!isset($_SESSION)) {
    session_start();
    $_SESSION['formStarted'] = true;
}

if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
    $_SESSION['token_time'] = time();
}

$menu = [
    ['href' => './index.php', 'text' => 'Accueil']
];

$chemin = dirname($_SERVER['SCRIPT_NAME']);
$cat = new GetCategorie();
$dpt = new GetDepartment();

// Routes
$app->get('/', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat): Response {
    $index = new Index();
    return $index->displayAllAnnonce($twig, $menu, $chemin, $cat->getCategories());
});

$app->get('/item/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat): Response {
    $item = new Item();
    return $item->afficherItem($twig, $menu, $chemin, (int)$args['n'], $cat->getCategories());
});

$app->get('/add', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat, $dpt): Response {
    $ajout = new AddItem();
    return $ajout->addItemView($twig, $menu, $chemin, $cat->getCategories(), $dpt->getAllDepartments());
});

$app->post('/add', function (Request $request, Response $response) use ($twig, $menu, $chemin): Response {
    $allPostVars = $request->getParsedBody();
    $ajout = new AddItem();
    return $ajout->addNewItem($twig, $menu, $chemin, $allPostVars);
});

$app->get('/item/{id}/edit', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin): Response {
    $item = new Item();
    return $item->modifyGet($twig, $menu, $chemin, (int)$args['id']);
});

$app->post('/item/{id}/edit', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat, $dpt): Response {
    $allPostVars = $request->getParsedBody();
    $item = new Item();
    return $item->modifyPost($twig, $menu, $chemin, (int)$args['id'], $allPostVars, $cat->getCategories(), $dpt->getAllDepartments());
});

$app->map(['GET', 'POST'], '/item/{id}/confirm', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin): Response {
    $allPostVars = $request->getParsedBody();
    $item = new Item();
    return $item->edit($twig, $menu, $chemin, (int)$args['id'], $allPostVars);
});

$app->get('/search', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat): Response {
    $s = new Search();
    return $s->show($twig, $menu, $chemin, $cat->getCategories());
});

$app->post('/search', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat): Response {
    $array = $request->getParsedBody();
    $s = new Search();
    return $s->research($array, $twig, $menu, $chemin, $cat->getCategories());
});

$app->get('/annonceur/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat): Response {
    $annonceur = new ViewAnnonceur();
    return $annonceur->afficherAnnonceur($twig, $menu, $chemin, (int)$args['n'], $cat->getCategories());
});

$app->get('/del/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin): Response {
    $item = new Item();
    return $item->supprimerItemGet($twig, $menu, $chemin, (int)$args['n']);
});

$app->post('/del/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat): Response {
    $item = new Item();
    return $item->supprimerItemPost($twig, $menu, $chemin, (int)$args['n'], $cat->getCategories());
});

$app->get('/cat/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat): Response {
    $categorie = new GetCategorie();
    return $categorie->displayCategorie($twig, $menu, $chemin, $cat->getCategories(), (int)$args['n']);
});

// API Routes
$app->group('/api', function ($app) use ($twig, $menu, $chemin, $cat): void {
    $app->get('', function (Request $request, Response $response) use ($twig, $menu, $chemin): Response {
        $template = $twig->load('api.html.twig');
        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => "{$chemin}/api", 'text' => 'Api']
        ];
        $response->getBody()->write($template->render(['breadcrumb' => $menu, 'chemin' => $chemin]));
        return $response;
    });

    $app->group('/annonce', function ($app): void {
        $app->get('/{id}', function (Request $request, Response $response, array $args): Response {
            $id = (int)$args['id'];
            $annonceList = [
                'id_annonce',
                'id_categorie as categorie',
                'id_annonceur as annonceur',
                'id_departement as departement',
                'prix',
                'date',
                'titre',
                'description',
                'ville'
            ];
            
            $return = Annonce::select($annonceList)->find($id);

            if ($return) {
                $return->categorie = Categorie::find($return->categorie);
                $return->annonceur = Annonceur::select('email', 'nom_annonceur', 'telephone')
                    ->find($return->annonceur);
                $return->departement = Departement::select('id_departement', 'nom_departement')
                    ->find($return->departement);
                $return->links = ['self' => ['href' => "/api/annonce/{$return->id_annonce}"]];
                
                $response->getBody()->write($return->toJson());
                return $response->withHeader('Content-Type', 'application/json');
            }
            
            return $response->withStatus(404);
        });
    });

    $app->group('/annonces', function ($app): void {
        $app->get('', function (Request $request, Response $response): Response {
            $annonceList = ['id_annonce', 'prix', 'titre', 'ville'];
            $annonces = Annonce::all($annonceList);
            
            foreach ($annonces as $ann) {
                $ann->links = ['self' => ['href' => "/api/annonce/{$ann->id_annonce}"]];
            }
            $annonces->links = ['self' => ['href' => '/api/annonces/']];
            
            $response->getBody()->write($annonces->toJson());
            return $response->withHeader('Content-Type', 'application/json');
        });
    });

    $app->group('/categorie', function ($app): void {
        $app->get('/{id}', function (Request $request, Response $response, array $args): Response {
            $id = (int)$args['id'];
            $annonces = Annonce::select('id_annonce', 'prix', 'titre', 'ville')
                ->where('id_categorie', '=', $id)
                ->get();

            foreach ($annonces as $ann) {
                $ann->links = ['self' => ['href' => "/api/annonce/{$ann->id_annonce}"]];
            }

            $categorie = Categorie::find($id);
            $categorie->links = ['self' => ['href' => "/api/categorie/{$id}"]];
            $categorie->annonces = $annonces;
            
            $response->getBody()->write($categorie->toJson());
            return $response->withHeader('Content-Type', 'application/json');
        });
    });

    $app->group('/categories', function ($app): void {
        $app->get('', function (Request $request, Response $response): Response {
            $categories = Categorie::get();
            
            foreach ($categories as $cat) {
                $cat->links = ['self' => ['href' => "/api/categorie/{$cat->id_categorie}"]];
            }
            $categories->links = ['self' => ['href' => '/api/categories/']];
            
            $response->getBody()->write($categories->toJson());
            return $response->withHeader('Content-Type', 'application/json');
        });
    });

    $app->get('/key', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat): Response {
        $kg = new KeyGenerator();
        return $kg->show($twig, $menu, $chemin, $cat->getCategories());
    });

    $app->post('/key', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat): Response {
        $data = $request->getParsedBody();
        $nom = $data['nom'] ?? '';
        $kg = new KeyGenerator();
        return $kg->generateKey($twig, $menu, $chemin, $cat->getCategories(), $nom);
    });
});

$app->run();
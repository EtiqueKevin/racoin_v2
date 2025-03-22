<?php
require '../vendor/autoload.php';
error_reporting(E_ALL ^ E_DEPRECATED);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use controller\getCategorie;
use controller\getDepartment;
use controller\index;
use controller\item;
use db\Connection;
use model\Annonce;
use model\Annonceur;
use model\Categorie;
use model\Departement;

// Create Connection
Connection::createConn();

// Create App
$app = AppFactory::create();

// Create Twig
$twig = Twig::create(__DIR__ . '/../template', ['cache' => false]);

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));

// Add trailing slash middleware
$app->add(function (Request $request, $handler) {
    $uri = $request->getUri();
    $path = $uri->getPath();
    
    if ($path != '/' && str_ends_with($path, '/')) {
        $uri = $uri->withPath(substr($path, 0, -1));
        
        if ($request->getMethod() == 'GET') {
            $response = new \Slim\Psr7\Response();
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
    $token = md5(uniqid(rand(), TRUE));
    $_SESSION['token'] = $token;
    $_SESSION['token_time'] = time();
}

$menu = [
    ['href' => './index.php', 'text' => 'Accueil']
];

$chemin = dirname($_SERVER['SCRIPT_NAME']);
$cat = new getCategorie();
$dpt = new getDepartment();

// Routes
$app->get('/', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat) {
    $index = new index();
    return $index->displayAllAnnonce($twig, $menu, $chemin, $cat->getCategories());
});

$app->get('/item/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat) {
    $n = $args['n'];
    $item = new item();
    return $item->afficherItem($twig, $menu, $chemin, $n, $cat->getCategories());
});

$app->get('/add', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat, $dpt) {
    $ajout = new \controller\addItem();
    return $ajout->addItemView($twig, $menu, $chemin, $cat->getCategories(), $dpt->getAllDepartments());
});

$app->post('/add', function (Request $request, Response $response) use ($twig, $menu, $chemin) {
    $allPostVars = $request->getParsedBody();
    $ajout = new \controller\addItem();
    return $ajout->addNewItem($twig, $menu, $chemin, $allPostVars);
});

$app->get('/item/{id}/edit', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin) {
    $id = $args['id'];
    $item = new item();
    return $item->modifyGet($twig, $menu, $chemin, $id);
});

$app->post('/item/{id}/edit', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat, $dpt) {
    $id = $args['id'];
    $allPostVars = $request->getParsedBody();
    $item = new item();
    return $item->modifyPost($twig, $menu, $chemin, $id, $allPostVars, $cat->getCategories(), $dpt->getAllDepartments());
});

$app->map(['GET', 'POST'], '/item/{id}/confirm', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin) {
    $id = $args['id'];
    $allPostVars = $request->getParsedBody();
    $item = new item();
    return $item->edit($twig, $menu, $chemin, $id, $allPostVars);
});

$app->get('/search', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat) {
    $s = new \controller\Search();
    return $s->show($twig, $menu, $chemin, $cat->getCategories());
});

$app->post('/search', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat) {
    $array = $request->getParsedBody();
    $s = new \controller\Search();
    return $s->research($array, $twig, $menu, $chemin, $cat->getCategories());
});

$app->get('/annonceur/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat) {
    $n = $args['n'];
    $annonceur = new \controller\viewAnnonceur();
    return $annonceur->afficherAnnonceur($twig, $menu, $chemin, $n, $cat->getCategories());
});

$app->get('/del/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin) {
    $n = $args['n'];
    $item = new \controller\item();
    return $item->supprimerItemGet($twig, $menu, $chemin, $n);
});

$app->post('/del/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat) {
    $n = $args['n'];
    $item = new \controller\item();
    return $item->supprimerItemPost($twig, $menu, $chemin, $n, $cat->getCategories());
});

$app->get('/cat/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat) {
    $n = $args['n'];
    $categorie = new \controller\getCategorie();
    return $categorie->displayCategorie($twig, $menu, $chemin, $cat->getCategories(), $n);
});

// API Routes
$app->group('/api', function ($app) use ($twig, $menu, $chemin, $cat) {
    $app->get('', function (Request $request, Response $response) use ($twig, $menu) {
        $template = $twig->load('api.html.twig');
        $menu = [
            ['href' => $chemin, 'text' => 'Accueil'],
            ['href' => $chemin . '/api', 'text' => 'Api']
        ];
        $response->getBody()->write($template->render(['breadcrumb' => $menu, 'chemin' => $chemin]));
        return $response;
    });

    $app->group('/annonce', function ($app) {
        $app->get('/{id}', function (Request $request, Response $response, array $args) {
            $id = $args['id'];
            $annonceList = ['id_annonce', 'id_categorie as categorie', 'id_annonceur as annonceur', 
                           'id_departement as departement', 'prix', 'date', 'titre', 'description', 'ville'];
            $return = Annonce::select($annonceList)->find($id);

            if ($return) {
                $return->categorie = Categorie::find($return->categorie);
                $return->annonceur = Annonceur::select('email', 'nom_annonceur', 'telephone')
                    ->find($return->annonceur);
                $return->departement = Departement::select('id_departement', 'nom_departement')
                    ->find($return->departement);
                $return->links = ['self' => ['href' => '/api/annonce/' . $return->id_annonce]];
                
                $response->getBody()->write($return->toJson());
                return $response->withHeader('Content-Type', 'application/json');
            }
            
            return $response->withStatus(404);
        });
    });

    $app->group('/annonces', function ($app) {
        $app->get('', function (Request $request, Response $response) {
            $annonceList = ['id_annonce', 'prix', 'titre', 'ville'];
            $a = Annonce::all($annonceList);
            
            foreach ($a as $ann) {
                $ann->links = ['self' => ['href' => '/api/annonce/' . $ann->id_annonce]];
            }
            $a->links = ['self' => ['href' => '/api/annonces/']];
            
            $response->getBody()->write($a->toJson());
            return $response->withHeader('Content-Type', 'application/json');
        });
    });

    $app->group('/categorie', function ($app) {
        $app->get('/{id}', function (Request $request, Response $response, array $args) {
            $id = $args['id'];
            $a = Annonce::select('id_annonce', 'prix', 'titre', 'ville')
                ->where('id_categorie', '=', $id)
                ->get();

            foreach ($a as $ann) {
                $ann->links = ['self' => ['href' => '/api/annonce/' . $ann->id_annonce]];
            }

            $c = Categorie::find($id);
            $c->links = ['self' => ['href' => '/api/categorie/' . $id]];
            $c->annonces = $a;
            
            $response->getBody()->write($c->toJson());
            return $response->withHeader('Content-Type', 'application/json');
        });
    });

    $app->group('/categories', function ($app) {
        $app->get('', function (Request $request, Response $response) {
            $c = Categorie::get();
            
            foreach ($c as $cat) {
                $cat->links = ['self' => ['href' => '/api/categorie/' . $cat->id_categorie]];
            }
            $c->links = ['self' => ['href' => '/api/categories/']];
            
            $response->getBody()->write($c->toJson());
            return $response->withHeader('Content-Type', 'application/json');
        });
    });

    $app->get('/key', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat) {
        $kg = new \controller\KeyGenerator();
        return $kg->show($twig, $menu, $chemin, $cat->getCategories());
    });

    $app->post('/key', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat) {
        $data = $request->getParsedBody();
        $nom = $data['nom'];
        $kg = new \controller\KeyGenerator();
        return $kg->generateKey($twig, $menu, $chemin, $cat->getCategories(), $nom);
    });
});

$app->run();
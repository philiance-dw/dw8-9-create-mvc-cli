#! /usr/bin/php
<?php

function displaySuccessMessage(string $dirName) {
  echo "\033[32m\n";
  echo "Projet initialisé avec succès!\n";
  echo "\033[0m";
  echo "Vous pouvez vous déplacer dans le dossier en faisant: cd $dirName\n";
}

function displayError(string $message = '') {
  if ($message) {
    echo "\033[31m";
    echo <<< 'EOL'
     _____ ____  ____   ___  ____
    | ____|  _ \|  _ \ / _ \|  _ \
    |  _| | |_) | |_) | | | | |_) |
    | |___|  _ <|  _ <| |_| |  _ <
    |_____|_| \_\_| \_\\___/|_| \_\

    EOL;
    echo "\033[0m";
    echo "\033[31m$message\033[0m\n";
  }
}

function displayMissingDependenciesMessage() {
  echo "-----------------------------------------------------------------------------------------------------------------------------------";
  echo "\033[33m\n";
  echo <<< EOL
   ____  _____ ____  _____ _   _ ____    _    _   _  ____ _____ ____       _      ___ _   _ ____ _____  _    _     _     _____ ____
  |  _ \| ____|  _ \| ____| \ | |  _ \  / \  | \ | |/ ___| ____/ ___|     / \    |_ _| \ | / ___|_   _|/ \  | |   | |   | ____|  _ \
  | | | |  _| | |_) |  _| |  \| | | | |/ _ \ |  \| | |   |  _| \___ \    / _ \    | ||  \| \___ \ | | / _ \ | |   | |   |  _| | |_) |
  | |_| | |___|  __/| |___| |\  | |_| / ___ \| |\  | |___| |___ ___) |  / ___ \   | || |\  |___) || |/ ___ \| |___| |___| |___|  _ <
  |____/|_____|_|   |_____|_| \_|____/_/   \_\_| \_|\____|_____|____/  /_/   \_\ |___|_| \_|____/ |_/_/   \_\_____|_____|_____|_| \_\

  EOL;
  echo "\033[0m";
  echo "-----------------------------------------------------------------------------------------------------------------------------------";
  echo "\n\tvlucas/phpdotenv";
  echo "\n\taltorouter/altorouter";
  echo "\n\ttwig/twig";
  echo "\n\tramsey/uuid\n";
}

function displayHelp(int $code = 0) {
  echo <<< EOL
  Ce programme permet de créer un point de départ pour un projet PHP en MVC avec routeur, moteur de template et controllers.

  Options:
  \t-d, --directory \tnom du dossier dans lequel créer le projet (requis)
  \t-n, --name \t\tnom de l'auteur du projet

  \t-h \t\t\taffiche ce message d'aide

  EOL;

  exit($code);
}

function getOption(string $shortOption, string $longOption = '') {
  // -d, --directory = dossier dans lequel mettre le projet
  // -n, --name = nom de l'auteur du proje
  $options = getopt('d:n:h', ['directory:', 'name:']);

  if (isset($options['h'])) {
    return true;
  }

  $optionValue = $options[$shortOption] ?? null;

  if (!$optionValue) {
    $optionValue = $options[$longOption] ?? null;
  }

  return $optionValue;
}

function createFile(string $path, string $data, string $mode = "w+") {
  $file = fopen($path, $mode);
  fwrite($file, $data);
  fclose($file);
}

if (getOption('h')) {
  displayHelp();
}

$rootDirectoryName = getOption('d', 'directory');
$name = getOption('n', 'name');

if (!$rootDirectoryName) {
  displayError("Aucun nom de dossier spécifié!\n");
  displayHelp(1);
}

if (!$name) {
  displayError("Le nom de l'auteur doit être spécifié\n");
  displayHelp(1);

}

// foreach ($argv as $key => $value) {
//   if (str_contains($value, 'directory')) {
//     $rootDirectory = $argv[$key + 1];
//   }
// }

if (is_dir($rootDirectoryName)) {
  displayError("Ce dossier existe déjà, merci de choisir un nom différent.");
  displayHelp(1);
}

mkdir($rootDirectoryName);
// equivalent de cd
chdir($rootDirectoryName);

$indexPHPContent = <<< 'EOL'
  <?php

  session_start();

  require_once __DIR__ . '/vendor/autoload.php';

  use App\Router;

  $router = new Router();

  $router->get("/", "MainController#getHome");

  // $router->post('/', "");

  $router->start();
  EOL;

$routerContent = <<< 'EOL'
<?php

namespace App;

use App\Controller\ErrorController;

class Router extends \AltoRouter{

  /**
   *
   * Ajoute une route à faire correspondre en méthode GET
   *
   * @param string $route la route à faire correspondre (peut prendre la forme d'une regex). On peut utiliser des filtres comme [i:id] cf. doc AltoRouter
   * @param mixed $target la chose à faire lorsqu'une route trouve une correspondance
   * @param string $name le nom à donner à la route
   *
   */
  public function get($route, $target, $name = null) {
    $this->map('GET', $route, $target, $name);
    $this->map('GET', $route . '/', $target, $name);
    return $this;
  }

  /**
   *
   * Ajoute une route à faire correspondre en méthode POST
   *
   * @param string $route la route à faire correspondre (peut prendre la forme d'une regex). On peut utiliser des filtres comme [i:id] cf. doc AltoRouter
   * @param mixed $target la chose à faire lorsqu'une route trouve une correspondance
   * @param string $name le nom à donner à la route
   *
   */
  public function post($route, $target, $name = null) {
    $this->map('POST', $route, $target, $name);
    $this->map('POST', $route . '/', $target, $name);
    return $this;
  }

  /**
   *
   * Ajoute une route à faire correspondre en méthode DELETE
   *
   * @param string $route la route à faire correspondre (peut prendre la forme d'une regex). On peut utiliser des filtres comme [i:id] cf. doc AltoRouter
   * @param mixed $target la chose à faire lorsqu'une route trouve une correspondance
   * @param string $name le nom à donner à la route
   *
   */
  public function delete($route, $target, $name = null) {
    $this->map('DELETE', $route, $target, $name);
    $this->map('DELETE', $route . '/', $target, $name);
    return $this;
  }

  public function start() {
    $match = $this->match();

    if (is_array($match)) {
      $this->protectAdminRoutes();

      $target = $match['target'];
      $params = $match['params'];

      [$controller, $method] = explode('#', $target);

      $controller = "App\Controller\\$controller";
      $obj = new $controller();

      if (is_callable([$obj, $method])) {
        if (!empty($params)) {

          $obj->$method(...array_values($params));
          return;
        }

        $obj->$method();
        return;
      }
    }

    $errorContoller = new ErrorController();
    $errorContoller->get404();
  }

  private function getActiveURL() {
    return $_SERVER['REQUEST_URI'];
  }

  private function protectAdminRoutes() {
    $url = $this->getActiveURL();

    $user = $_SESSION['user'] ?? null;
    $user = unserialize($user) ?? null;

    if (str_contains($url, 'admin') && (!$user || $user->getUserType() !== "admin")) {
      header('Location: /');
    }
  }
}
EOL;

$formContent = <<<'EOL'
<?php

namespace App;

use Ramsey\Uuid\Uuid;

class Form {
  /**
   *
   * Cette méthode permet de valider le formulaire en vérifiant si des champs sont vides, que l'email est bien un email, etc...
   *
   * @param array $data tableau contenant les données du formulaire
   * @param array $optionnalFields un tableau qui represente les champs optionnels d'un formulaire
   * @param bool $loginForm paramètre permettant de savoir s'il s'agit d'un formulaire de connexion
   *
   * @return array Un tableau contenant les potentielles erreurs du formulaire
   *
   */
  public static function validate(array $data, array $optionnalFields = [], bool $loginForm = false): array{
    $errors = [];

    // on boulce sur le tableau data pour recuperer les données 1 par 1
    foreach ($data as $key => $value) {
      // si le champs est vide ET que le champs n'est pas compris dans le tableau de champs optionnels
      if (!$value && !in_array($key, $optionnalFields)) {
        $errors[$key] = 'Ce champs ne peut pas être vide.';
        // on passe au tour de boucle suivant donc le code qui se trouve en dessous de continue ne sera pas executé pour ce tour de boucle
        continue;
      }

      if (!$loginForm) {
        // str_contains permet de vérifier si une chaine inclus une autre chaine
        if (str_contains($key, 'email') && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
          $errors[$key] = "Cet email n'est pas un email valide.";
        }

        // on vérifie que le mot de passe contient au minimum une majuscule, une miniscule, une lettre, un chiffre, un symbole
        if (str_contains($key, 'password') && !preg_match('/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-\/+_]).{8,}$/', $value)) {
          $errors[$key] = 'Votre mot de passe est trop faible.';
        }
      }
    }

    return $errors;
  }

  /**
   *
   * Cette méthode permet d'envoyer un fichier au serveur et de le stocker
   *
   * @param string $name Nom à aller chercher dans le tableau $_FILES
   * @param string $uploadDir Le chemin vers le fichier à partir de la racine du projet
   * @param array &$errors Le tableau d'erreur à modifier en cas d'erreur fichier
   * @param array $options Un tableau d'options avec la clé allowedExtensions contenant un tableau correspondant aux fichiers acceptés
   *
   * @return array Le(s) chemin(s) d'ajout du/des fichier(s) à stocker en base de donnée
   *
   */
  public static function uploadFile(string $name, string $uploadDir, array &$errors = [], array $options = []) {
    $allowedTypes = $options['allowedTypes'] ?? null;
    $absolutePath = dirname(__DIR__) . $uploadDir . '/';

    if (!is_dir($absolutePath)) {
      mkdir($absolutePath, 0755, true);
    }

    $images = $_FILES[$name] ?? null;

    $imagePaths = null;

    if ($images) {
      for ($i = 0; $i < sizeof($images); $i++) {
        if ($images['error'][$i] === UPLOAD_ERR_OK) {
          ['extension' => $extension, 'filename' => $filename] = pathinfo($images['name'][$i]);

          if ($allowedTypes && !in_array($extension, $allowedTypes)) {
            $errors['fileError'] = "Type de fichier non accepté.";
          }

          if (empty($errors) && $filename) {
            $filename = hash('sha256', Uuid::uuid4() . $filename);
            $filename .= ".$extension";

            $uploadFile = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($images['tmp_name'][$i], dirname(__DIR__) . $uploadFile)) {
              $errors['fileError'] = "Problème durant l'ajout du fichier.";
              continue;
            }

            if (!$imagePaths) {
              $imagePaths = [];
            }

            array_push($imagePaths, $uploadFile);

          }

        }
      }
    }

    return $imagePaths;
  }
}
}
EOL;

$databaseContent = <<<'EOL'
<?php

namespace App;

use Dotenv\Dotenv;
use PDO;
use PDOException;
use App\Controller\ErrorController;

class Database {
  /**
   *
   * Cette méthode permet de charger les variables d'environnement et retourne une instance PDO servant à effectuer des requetes en base de donnée
   *
   * @return PDO retourn une instance de PDO
   *
   */
  public static function getConnection(): PDO | null{
    // on utilise le paquet phpdotenv pour charger les variables d'environement
    $dotenv = Dotenv::createImmutable(dirname(__DIR__)); // ici on point vers la racine du projet à l'endroit ou se trouve le fichier .env
    $dotenv->load();

    $pdo = null;

    try {
      $pdo = new PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]);
    } catch (PDOException $e) {
      // si on a un problème durant la creation de la connexion on envoie une page 500
      $errorController = new ErrorController();
      $errorController->get500();
      return null;
    }

    if ($pdo) {
      return $pdo;
    }
  }
}
EOL;

$errorControllerContent = <<< 'EOL'
<?php

namespace App\Controller;

class ErrorController extends Controller {
  public function get404() {
    // on envoie un header avec le code d'erreur 404 not found
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
    $this->render('404.twig');
  }

  public function get500() {
    // on envoie un header avec le code d'erreur 500 internal server error
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    $this->render('500.twig');
  }
}
EOL;

$mainControllerContent = <<< 'EOL'
<?php

namespace App\Controller;

class MainController extends Controller {
  public function getHome() {
    $this->render('home.twig');
  }
}
EOL;

$controllerContent = <<< 'EOL'
<?php

namespace App\Controller;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Controller {
  /** @var Environment */
  private $twig;

  public function __construct() {
    // on met en place Twig
    // d'après la doc on doit instancier un FilesystemLoader qui permet de dire à twig ou se trouvent nos vues (templates)
    $loader = new FilesystemLoader(dirname(dirname(__DIR__)) . '/views');
    // on instancie la class Environment en lui passant le loader defini ci dessus et en lui passant un tableau d'option concernant le cache
    $twig = new Environment($loader, [
      'cache' => false/** mettre l'endroit ou stocker les templates deja chargé */,
    ]);

    $user = $_SESSION['user'] ?? null;

    // on ajoute l'utilisateur connecté en tant que variable globale pour toutes les vues chargés par Twig
    $twig->addGlobal("user", $user ? unserialize($user) : null);

    // on affecte twig à la propriété de notre class twig
    $this->twig = $twig;
  }

  /**
   *
   * Affiche un template twig
   *
   *  @param string $name chemin de la vue à charger à partir du dossier defini à l'instanciation de twig
   *  @param array $context un tableau associatif des paramètres/variables/valeurs à envoyer à la vue. Ex: ['title' => 'Accueil']
   *
   */
  protected function render(string $name, array $context = []) {
    echo $this->twig->render($name, $context);
  }
}
EOL;

$modelContent = <<< 'EOL'
<?php

namespace App\Model;

class Model {
  protected $id;
  protected $created_at;
  protected $updated_at;

  public function getId() {
    return $this->id;
  }
  public function setId(int $id): self {
    $this->id = $id;
    return $this;
  }
  public function getCreatedAt() {
    return $this->created_at;
  }
  public function setCreatedAt(string $created_at) {
    $this->created_at = $created_at;
    return $this;
  }

  public function getUpdatedAt() {
    return $this->updated_at;
  }
  public function setUpdatedAt(string $updated_at) {
    $this->updated_at = $updated_at;
    return $this;
  }
}
EOL;

$homeTwig = <<< 'EOL'
{% extends "layouts/main.twig" %}

{% block head %}{% endblock %}

{% block main %}
	<h1>Bienvenue dans votre nouveau projet MVC</h1>
{% endblock %}
EOL;

$mainLayoutTwigContent = <<< 'EOL'
<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="/public/assets/scss/main.css">
		<script src="/public/assets/js/main.js" defer></script>
		{% block head %}
		  <title>Projet MVC</title>
    {% endblock %}
	</head>
	<body>
		<div class="backdrop"></div>
		<form class="modal">
			<div class="cross"></div>
			<div class="modal-content"></div>
		</form>
		<div class=" container">
			<header class="header">
				<div class="logo"></div>
				<nav>
					<ul>
						<li><a href="/">Accueil</a></li>
					</ul>
				</nav>
			</header>
			<main class="main"> {% block main %}{% endblock %}
				</main>
				<footer class="footer">&copy;</footer>
			</div>
		</body>
	</html>
EOL;

$mainJsContent = "console.log('JS chargé !');";

$mainScssContent = <<< 'EOL'
@import 'includes/reset';
@import 'includes/variables';

.container {
  min-height: 100vh;
  display: grid;
  grid-template-rows: 6rem auto 4rem;
  grid-template-areas: 'header' 'main' 'footer';
}

.header {
  grid-area: header;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
}

.main {
  grid-area: main;
}

footer {
  grid-area: footer;
}

EOL;

$resetScssContent = <<< 'EOL'
/* http://meyerweb.com/eric/tools/css/reset/ v2.0 | 20110126 */

html, body, div, span, applet, object, iframe,
h1, h2, h3, h4, h5, h6, p, blockquote, pre,
a, abbr, acronym, address, big, cite, code,
del, dfn, em, img, ins, kbd, q, s, samp,
small, strike, strong, sub, sup, tt, var,
b, u, i, center,
dl, dt, dd, ol, ul, li,
fieldset, form, label, legend,
table, caption, tbody, tfoot, thead, tr, th, td,
article, aside, canvas, details, embed,
figure, figcaption, footer, header, hgroup,
menu, nav, output, ruby, section, summary,
time, mark, audio, video {
	margin: 0;
	padding: 0;
	border: 0;
	font: inherit;
	font-size: 100%;
	vertical-align: baseline;
}
/* HTML5 display-role reset for older browsers */
article, aside, details, figcaption, figure,
footer, header, hgroup, menu, nav, section {
  display: block;
}

body,html{
  height: 100%;
}

body {
	line-height: 1;
}
ol, ul {
	list-style: none;
}
blockquote, q {
	quotes: none;
}

blockquote::before, blockquote::after,
q:before, q:after {
	content: '';
	content: none;
}
table {
	border-collapse: collapse;
	border-spacing: 0;
}

/* Reset perso */
a, del, ins {
  text-decoration: none;
}
a {
  color: inherit;
}

label, button {
  cursor: pointer;
}

html {
  box-sizing: border-box;
}

*, *::before, *::after {
  box-sizing: inherit;
}

//! A ENLEVER POUR LA PRODUCTION
input, button {
  outline: 0;
}
EOL;

$variablesScssContent = <<< 'EOL'
$header-size: 6rem;
$medium-radius: 6px;

// Small tablets and large smartphones (landscape view)
$screen-sm-min: 576px;

// Small tablets (portrait view)
$screen-md-min: 768px;

// Tablets and small desktops
$screen-lg-min: 992px;

// Large tablets and desktops
$screen-xl-min: 1200px;

// 2K and higher screens
$screen-xxl-min: 1800px;

// Small devices
@mixin sm {
  @media (min-width: #{$screen-sm-min}) {
    @content;
  }
}

// Medium devices
@mixin md {
  @media (min-width: #{$screen-md-min}) {
    @content;
  }
}

// Large devices
@mixin lg {
  @media (min-width: #{$screen-lg-min}) {
    @content;
  }
}

// Extra large devices
@mixin xl {
  @media (min-width: #{$screen-xl-min}) {
    @content;
  }
}

// Extra extra large devices
@mixin xxl {
  @media (min-width: #{$screen-xxl-min}) {
    @content;
  }
}

// Custom devices
@mixin rwd($screen) {
  @media (min-width: #{$screen}px) {
    @content;
  }
}

@mixin flex($align: stretch, $justify: flex-start, $flex-flow: row nowrap) {
  display: flex;
  flex-flow: $flex-flow;
  align-items: $align;
  justify-content: $justify;
}
EOL;

$mainCssMapContent = <<< 'EOL'
{
    "version": 3,
    "mappings": "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",
    "sources": [
        "main.scss",
        "includes/_reset.scss",
        "includes/_variables.scss"
    ],
    "names": [],
    "file": "main.css"
}
EOL;

$mainCssContent = <<< 'EOL'
/* http://meyerweb.com/eric/tools/css/reset/ v2.0 | 20110126 */
html, body, div, span, applet, object, iframe,
h1, h2, h3, h4, h5, h6, p, blockquote, pre,
a, abbr, acronym, address, big, cite, code,
del, dfn, em, img, ins, kbd, q, s, samp,
small, strike, strong, sub, sup, tt, var,
b, u, i, center,
dl, dt, dd, ol, ul, li,
fieldset, form, label, legend,
table, caption, tbody, tfoot, thead, tr, th, td,
article, aside, canvas, details, embed,
figure, figcaption, footer, header, hgroup,
menu, nav, output, ruby, section, summary,
time, mark, audio, video {
  margin: 0;
  padding: 0;
  border: 0;
  font: inherit;
  font-size: 100%;
  vertical-align: baseline;
}

/* HTML5 display-role reset for older browsers */
article, aside, details, figcaption, figure,
footer, header, hgroup, menu, nav, section {
  display: block;
}

body, html {
  height: 100%;
}

body {
  line-height: 1;
}

ol, ul {
  list-style: none;
}

blockquote, q {
  quotes: none;
}

blockquote::before, blockquote::after,
q:before, q:after {
  content: '';
  content: none;
}

table {
  border-collapse: collapse;
  border-spacing: 0;
}

/* Reset perso */
a, del, ins {
  text-decoration: none;
}

a {
  color: inherit;
}

label, button {
  cursor: pointer;
}

html {
  -webkit-box-sizing: border-box;
          box-sizing: border-box;
}

*, *::before, *::after {
  -webkit-box-sizing: inherit;
          box-sizing: inherit;
}

input, button {
  outline: 0;
}

.container {
  min-height: 100vh;
  display: -ms-grid;
  display: grid;
  -ms-grid-rows: 6rem auto 4rem;
      grid-template-rows: 6rem auto 4rem;
      grid-template-areas: 'header' 'main' 'footer';
}

.header {
  -ms-grid-row: 1;
  -ms-grid-column: 1;
  grid-area: header;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
}

.main {
  -ms-grid-row: 2;
  -ms-grid-column: 1;
  grid-area: main;
}

footer {
  -ms-grid-row: 3;
  -ms-grid-column: 1;
  grid-area: footer;
}
/*# sourceMappingURL=main.css.map */
EOL;

$gitIgnoreContent = <<< 'EOL'
vendor
.env
EOL;

$htaccessContent = <<< 'EOL'
RewriteEngine On

# Deny access to .htaccess
<Files .htaccess>
Order allow,deny
Deny from all
</Files>

# Deny access to .env
<Files ~  "^\.env">
Order allow,deny
Deny from all
</Files>

# Deny access to composer.json
<Files ~  "^composer.json">
Order allow,deny
Deny from all
</Files>

# Deny access to composer.lock
<Files ~  "^composer.lock">
Order allow,deny
Deny from all
</Files>

Options -Indexes

# Deny to folders
RewriteRule (^|/)database(/|$) - [F]
RewriteRule (^|/)src(/|$) - [F]
RewriteRule (^|/)utils(/|$) - [F]
RewriteRule (^|/)vendor(/|$) - [F]
RewriteRule (^|/)views(/|$) - [F]

# alto router routing
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . index.php [L]
EOL;

$composerJsonContent = <<< "EOL"
{
  "name": "$name/$rootDirectoryName",
  "require": {
    "twig/twig": "^3.3",
    "vlucas/phpdotenv": "^5.3",
    "ramsey/uuid": "^4.2",
    "altorouter/altorouter": "^2.0"
  },
  "autoload": {
    "psr-4": {
      "App\\\": "src"
    }
  }
}
EOL;

$envContent = <<< 'EOL'
# le dsn de connexion sous la forme
# mysql:host=...;dbname=...;
DB_DSN=

# utilisateur servant à la connexion bdd
DB_USER=
# mot de pass de l'utilisateur
DB_PASS=
EOL;

mkdir('public/assets/scss/includes', 0777, true);
mkdir('public/assets/js');
mkdir('views/layouts', 0777, true);
mkdir('src/Controller', 0777, true);
mkdir('src/Model');

createFile("index.php", $indexPHPContent);
createFile('src/Router.php', $routerContent);
createFile('src/Form.php', $formContent);
createFile('src/Database.php', $databaseContent);
createFile('src/Controller/MainController.php', $mainControllerContent);
createFile('src/Controller/ErrorController.php', $errorControllerContent);
createFile('src/Controller/Controller.php', $controllerContent);
createFile('src/Model/Model.php', $modelContent);
createFile('views/home.twig', $homeTwig);
createFile('views/layouts/main.twig', $mainLayoutTwigContent);
createFile('public/assets/js/main.js', $mainJsContent);
createFile('public/assets/scss/main.scss', $mainScssContent);
createFile('public/assets/scss/includes/_reset.scss', $resetScssContent);
createFile('public/assets/scss/includes/_variables.scss', $variablesScssContent);
createFile('public/assets/scss/main.css.map', $mainCssMapContent);
createFile('public/assets/scss/main.css', $mainCssContent);
createFile('.env', $envContent);
createFile('.env.example', $envContent);
createFile('.gitignore', $gitIgnoreContent);
createFile('.htaccess', $htaccessContent);
createFile("composer.json", $composerJsonContent);

if (file_exists('composer.json')) {
  if (empty(shell_exec('composer'))) {
    displayError("Composer n'est pas installé sur votre machine, \nveuillez l'installer en suivant les instructions sur https://getcomposer.org/download/");
    displayMissingDependenciesMessage();
    die();
  }

  exec('composer install');
  displaySuccessMessage($rootDirectoryName);
  die();
}

displaySuccessMessage($rootDirectoryName);
displayMissingDependenciesMessage();
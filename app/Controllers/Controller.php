<?php
namespace App\Controllers;

use App\Helpers\Date;
use App\Helpers\Text;
use App\Models\ArticlesModel;
use App\Models\UsersModel;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Controller {

    private FilesystemLoader $loader;

    protected Environment $twig;

    protected ArticlesModel $articles;

    protected UsersModel $users;

    protected Text $text;

    protected Date $date;

    protected array $params;

    public function __construct()
    {
        // Checks the status of the session and starts if necessary
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Initialize Twig
        $this->loader = new FilesystemLoader(ROOT . '/app/Views');
        $this->twig = new Environment($this->loader, [
            'cache' => false // ROOT . '/tmp/cache',
        ]);

        // Initialize Models
        $this->articles = new ArticlesModel;
        $this->users = new UsersModel;

        // Initialize Helpers class
        $this->text = new Text;
        $this->date = new Date;
    }

    // Check if user is logged in
    public function isLogged()
    {
        $auth = [
            'isLogged' => false,
            'isAdmin' => false
        ];
        if (isset($_SESSION['auth'])) {
            $auth['isLogged'] = true;
            $test = $this->users->isAdmin($_SESSION['auth']['user_id']);
            dump($_SESSION);
            dump($test);
        }   
    }

    // Display the Twig renderer
    public function view(string $path, $datas = []): void
    {
        // Defines a global variable containing the authentication status
        $auth = $this->isLogged();
        $this->twig->addGlobal('auth', $auth);

        // Display Twig render
        echo $this->twig->render($path, $datas);
    }

    // Get the URL parameters from the router
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

}

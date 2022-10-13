<?php
namespace App\Controllers;

class ArticlesController extends Controller
{

    public function index(): void
    {
        // Pagination
        $countArticles = $this->articles->countAll('articles');
        $nbArticles = (int) $countArticles->nb_articles;
        $pages = $this->pagination->pagination($nbArticles, 6);

        // Get data of all articles
        $data = $this->articles->findAll($pages[0]['limitFirst'], $pages[0]['perPage']);

        // Render
        $this->view('pages/articles/index.html.twig', [
            'lastPage' => $pages[0]['lastPage'],
            'currentPage' => $pages[0]['currentPage'],
            'articles' => $data
        ]);
    }

    public function show(): void
    {
        $checkCommentSent = false;
        if (isset($_GET['commentSent'])) {
            $checkCommentSent = true;
        }

        // Get data of current article
        $data = $this->articles->find($this->params['id']);

        // Pagination
        $countComments = $this->comments->countAllValidFromArticle($this->params['id']);
        $nbComments = (int) $countComments->nb_comments;
        $pages = $this->pagination->pagination($nbComments, 3);

        // Get validate comments of current article
        $comments = $this->comments->findAllValidFromArticle($this->params['id'], $pages[0]['limitFirst'], $pages[0]['perPage']);

        // Check if user is logged in
        $checkAuth = false;
        $checkAdmin = false;
        $articlePermission = false;
        if ($this->checkAuth()['isLogged'] === true) {
            $checkAuth = true; 
            // Check if user is author of article
            if ($this->session->auth['user_id'] === $data[0]->getAuthor_id()) {
                $articlePermission = true;
            }
            // Check if user is admin
            if ($this->checkAuth()['isAdmin'] === true) {
                $checkAdmin = true;
            }
        }

        // Render
        $this->view('pages/articles/show.html.twig', [
            'article' => $data[0], 
            'userArticle' => $data[1], 
            'comments' => $comments,
            'checkAuth' => $checkAuth,
            'articlePermission' => $articlePermission,
            'checkAdmin' => $checkAdmin,
            'checkCommentSent' => $checkCommentSent,
            'lastPage' => $pages[0]['lastPage'],
            'currentPage' => $pages[0]['currentPage'],
        ]);
    }

    public function create(): void
    {
        $errors = null;

        // Checks if user is logged in and if he is admin
        if ($this->checkAuth()['isLogged'] !== true && $this->checkAuth()['isAdmin'] !== true) {
            // Error : Forbidden
            header('Location: /erreur/acces-interdit');
        } 

        // Check if form as sent
        if (isset($_POST) && !empty($_POST)) {

            // Slug of article
            $slug = $this->text->slugify($_POST['title']);

            // Default image of article
            $file = '01default.jpg';

            // File management (image of article)
            if (isset($_FILES)) {
                // Get file extension
                $fileExtension = explode('.', $_FILES['image']['name']);
                $extension = strtolower(end($fileExtension));

                // Checks file extension
                $permittedExtension = ['jpg', 'png', 'jpeg'];
                if (in_array($extension, $permittedExtension)) {
                    // Saves file
                    $file = $slug . '.' . $extension;
                    move_uploaded_file($_FILES['image']['tmp_name'], ROOT . '/public/assets/img/articles/' . $file);
                }
            }

            // Check if title already exists
            $checkTitle = $this->articles->checkExists('articles', 'title', $_POST['title']);
            if ($checkTitle === false) {

                // Add data of article in array
                $data = [
                    'title' => $_POST['title'],
                    'slug' => $slug,
                    'caption' => $_POST['caption'],
                    'content' => $_POST['content'],
                    'author_id' => $_SESSION['auth']['user_id'],
                    'image' => $file
                ];

                // Check form data 
                $errors = $this->formValidator->checkArticleForm($data);
        
                // If check form data is ok
                if ($errors === null) {
                    // Creation of article and redirection
                    $hydratedData = $this->articles->hydrate($data);
                    $this->articles->create('articles', $hydratedData); 
                    header('Location: ' . '/articles'); 
                }
            } 

            // Error : Title already exist
            $errors = $this->errorsHandling->newError('Ce titre existe déjà.');       
        }

        // Render
        $this->view('pages/articles/create.html.twig', ['errors' => $errors]); 
    }

    public function update(): void
    {
        $errors = null;

        // Get data of current article
        $data = $this->articles->find($this->params['id']);

        // Get all admin
        $admins = $this->users->findAllAdmin();
        $listAdmins = [];
        foreach ($admins as $admin) {
            if ($admin->id != $data[0]->getAuthor_id()) {
                $listAdmin = [
                    'id' => $admin->id,
                    'firstname' => $admin->firstname,
                    'lastname' => $admin->lastname
                ];
                array_push($listAdmins, $listAdmin);
            }  
        } 

        // Checks if user is logged in and if he is author of article or admin
        if ($this->checkAuth()['isLogged'] !== true && (($_SESSION['auth']['user_id'] !== $data[0]->getAuthor_id() || $this->checkAuth()['isAdmin'] !== true))) {
            // Error : Forbidden
            header('Location: /erreur/acces-interdit');  
        }

        // Check if form as sent
        if (isset($_POST) && !empty($_POST)) {
            // Checks if title exist and title is not equal to this title
            $checkTitle = $this->articles->checkExists('articles', 'title', $_POST['title']);
            if ($checkTitle === false || $_POST['title'] === $data[0]->getTitle()) {
                // Check form data
                $errors = $this->formValidator->checkArticleForm([
                    'title' => $_POST['title'],
                    'caption' => $_POST['caption'],
                    'content' => $_POST['content'],
                    'author_id' => $_POST['author']
                ]);
                // If check form data is ok
                if ($errors === null) {
                    // Update of article and redirection
                    $this->articles->setId($this->params['id'])
                                ->setTitle($_POST['title'])
                                ->setSlug($this->text->slugify($_POST['title']))
                                ->setContent($_POST['content'])
                                ->setCaption($_POST['caption'])
                                ->setAuthor_id($_POST['author'])
                                ->setUpdated_at($this->date->getDateNow())
                                ->update('articles', $this->params['id']);
                    header('Location: /article/' . $this->articles->getSlug() . '/' . $this->articles->getId()); 
                }
            }
            // Error : Title already exist
            $errors = $this->errorsHandling->newError('Ce titre existe déjà.'); 
        }

        // Render
        $this->view('pages/articles/update.html.twig', [
            'article' => $data[0], 
            'user' => $data[1], 
            'errors' => $errors,
            'admins' => $listAdmins
        ]);  
    }

    public function delete(): void
    {
        // Get data of current articles
        $data = $this->articles->find($this->params['id']);

        // Check if user is logged in and if he is author of article or admin
        if ($this->checkAuth()['isLogged'] !== true && ($_SESSION['auth']['user_id'] !== $data[0]->getAuthor_id() || $this->checkAuth()['isAdmin'] !== true)) {
            // Error : Forbidden
            header('Location: /erreur/acces-interdit');  
        }

        // Delete image from article
        $image = $data[0]->getImage();
        if ($image !== '01default.jpg') {
            unlink(ROOT . '/public/assets/img/articles/' . $image);
        }

        // Delete comments associated with article
        $comments = $this->comments->findAllBy('comments', 'article_id', $this->params['id']);
        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $this->comments->delete('comments', $comment->id);
            }
        }
        
        // Delete article and redirection
        $this->articles->delete('articles', $this->params['id']);
        header('Location: /articles'); 
    }

}

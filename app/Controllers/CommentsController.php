<?php
namespace App\Controllers;

class CommentsController extends Controller
{

    public function create() 
    {
        $commentSent = false; 

        // Checks if user is logged in
        if ($this->checkAuth()['isLogged'] !== true) {
            header('Location: /erreur/acces-interdit');
        } 

        // Check if form as sent
        if (!empty($this->superglobals->get_POST())) {
            // Add data of article in array
            $data = [
                'author_id' => $this->superglobals->get_SESSION()['user_id'],
                'content' => $this->superglobals->get_POST()['content'],
                'article_id' => $this->params['id'],
            ];

            // Creation of article and redirection
            $hydratedData = $this->comments->hydrate($data);
            $this->comments->create('comments', $hydratedData); 
            $commentSent = true;
            header('Location: /article/' . $this->params['slug'] . '/' . $this->params['id'] . '?commentSent=' . $commentSent);
        }
    }

    public function validation()
    {
        // Check if user is logged in and if he is admin
        if ($this->checkAuth()['isLogged'] !== true && $this->checkAuth()['isAdmin'] !== true) {
            header('Location: /erreur/acces-interdit');
        } 

        // Valid comment and redirection
        $this->comments->validComment($this->params['id'], $this->superglobals->get_SESSION()['user_id']);
        header('Location: /administration/commentaires');
    }

    public function delete()
    {
        // Check if user is logged in and if he is admin
        if ($this->checkAuth()['isLogged'] !== true && $this->checkAuth()['isAdmin'] !== true) {
            header('Location: /erreur/acces-interdit');
        }

        // Delete comment and redirection
        $this->comments->delete('comments', $this->params['id']);
        header('Location: /administration/commentaires');
    }

}
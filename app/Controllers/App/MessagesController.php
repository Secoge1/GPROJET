<?php

/**

 * GLOBALO - App mobile : Messages (délègue à MessagesController, garde le shell /app)

 */



declare(strict_types=1);



namespace App\Controllers\App;



use App\Core\Controller;

use App\Core\Router;

use App\Core\Auth;

use App\Controllers\MessagesController as WebMessagesController;



class MessagesController extends Controller

{

    public function __construct(Router $router)

    {

        parent::__construct($router);

    }



    public function index(): void

    {

        if (!Auth::check()) {

            $this->redirect(rtrim(BASE_URL, '/') . '/auth/connexion');

            return;

        }

        (new WebMessagesController($this->router))->index();

    }



    public function conversation(): void

    {

        if (!Auth::check()) {

            $this->redirect(rtrim(BASE_URL, '/') . '/auth/connexion');

            return;

        }

        (new WebMessagesController($this->router))->conversation();

    }

}



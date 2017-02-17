<?php

namespace Inc\Auth;

/**
 * Handles the user authentication
 * 
 * Needs User class to be present in $app
 * 
 */
use \Model\User;
use \Model\Usertoken;
use \Model\Usersetting;

class AuthMiddleware extends \Slim\Middleware {
	public function call() {
        $app = $this->app;

        //Logout user if needed
        if ($app->request()->get('logout')) {
            $app->logout();
        }

        //User is not logged IN
        $this->app->user_logged = false;
        $this->app->User = null;

        //Check if user exists via SESSION
        if (isset($_COOKIE['user'])) {
            $_COOKIE['user'] = json_decode($_COOKIE['user']);
            //Get user by token
            if (isset($_COOKIE['user']->token)) {
                $this->app->User = Usertoken::getWithUserByToken($_COOKIE['user']->token);
            }
        }

        //Let's check if TOKEN is active
        if (!isset($this->app->User) || !$this->app->User) {
            //USERTOKEN request
            $auth = null;
            $dynamicAuth = null;
            //Get user with user token if available
            if (isset($_SERVER['HTTP_AUTHENTICATION'])) {
                $auth = $_SERVER['HTTP_AUTHENTICATION'];    
            }
            //Get user with user dynamic token if available
            if (isset($_SERVER['HTTP_DYNAMICAUTHENTICATION'])) {
                $dynamicAuth = $_SERVER['HTTP_DYNAMICAUTHENTICATION'];    
            }

            //Get user from db
            $this->app->User = Usertoken::getWithUserByToken(str_replace('Bearer ', '', $auth));

            //Check dynamic token
            if ($this->app->User && $this->app->User['User']->dynamic_token) {
                //If we are using dynamic token
                //Check dynamic token
                if ($dynamicAuth != $this->app->User['Usertoken']->dynamic_token) {
                    $this->app->User = null;
                }
            }
        }

        //Check if user is active
        if (isset($this->app->User) && $this->app->User) {
            $u = array_merge($this->app->User, ['Usersetting' => Usersetting::getSettings($this->app->User['User']->id)]);
            $this->app->User = $u;
            $this->app->view()->set('CurrentUser', $this->app->User);
            $this->app->user_logged = true;
        }

        //Set language
        $app->setLanguage();
        
        //Optionally call the next middleware
        $this->next->call();
    }
}

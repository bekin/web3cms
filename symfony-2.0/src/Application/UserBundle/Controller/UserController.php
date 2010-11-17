<?php

namespace Application\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form;
use Application\UserBundle\Model\Login;

class UserController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('UserBundle:User:index', array('name' => $name));
    }

    /**
     * User login page
     * Display one simple form to authenticate visitor, and process it.
     */
    public function loginAction()
    {
        $model = new Login();

        $form = new Form\Form('login', $model, $this->container->getValidatorService());
        $form->add(new Form\CheckboxField('rememberMe'));
        $form->add(new Form\PasswordField('password'));
        $form->add(new Form\TextField('username'));

        if ($this['request']->getMethod() == 'POST') {
            $form->bind($this['request']->get('login'));

            if ($form->isValid()) {
                // connect to db and authenticate

                $this['request']->getSession()->setFlash('topSummary', '<strong>{screenName}</strong>, you have been successfully logged in.');

                return $this->redirect($this->generateUrl('homepage')); // goto profile
            } else {
                $this['request']->getSession()->setFlash('topError', 'An error occured while validating this form.');
                $response = $this->render('UserBundle:User:login', array('form' => $form));
                // unset flash message so it does not show up on the next page load
                $this['request']->getSession()->setFlash('topError', null);

                return $response;
            }
        }

        return $this->render('UserBundle:User:login', array('form' => $form));
    }
}
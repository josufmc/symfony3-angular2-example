<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Services\Helpers;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{  
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ]);
    }
    
    public function pruebasAction(Request $request)
    {
        $helpers = $this->get("app.helpers");
        
        $hash = $request->get("authorization", null);
        $check = $helpers->authCheck($hash, true);
        //$check = $helpers->authCheck($hash);
        
        var_dump($check);
        die();
        /*
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('BackendBundle:User')->findAll();
        
        $helpers = $this->get("app.helpers");
        return $helpers->json($users); 
        */
    }
    
    public function loginAction(Request $request){
        $helpers = $this->get("app.helpers");
        $jwt_auth = $this->get("app.jwt_auth");
        $json = $request->get("json", NULL);
        
        if($json !== NULL){
            $params = json_decode($json); 
            $email = (isset($params->email)) ? $params->email : NULL;
            $password = (isset($params->password)) ? $params->password : NULL;
            $getHash = (isset($params->getHash)) ? $params->getHash : NULL;
            
            $emailConstraint = new Assert\Email();
            $emailConstraint->message = "Invalid email";
            $validate_email = $this->get("validator")->validate($email, $emailConstraint);
            
            if (count($validate_email) == 0 && $password !== NULL){
                $pwd = hash('sha256', $password);
                $signUp = $jwt_auth->signUp($email, $pwd, $getHash);
                return new JsonResponse($signUp);
            } else {
                return $helpers->json(array("status" => "error", "data" => "Login not valid!"));
            }  
        } else {
             return $helpers->json(array("status" => "error", "data" => "Send JSON wih post!"));
        }
        
        
        /*$em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('BackendBundle:User')->findAll();
        
        $helpers = $this->get("app.helpers");
        return $helpers->json($users); */
    }
}

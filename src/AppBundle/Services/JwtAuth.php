<?php
namespace AppBundle\Services;

use Firebase\JWT\JWT;


class JwtAuth{  
    
    private $manager;
    private $key;
    
    public function __construct($manager){
        $this->manager = $manager;
        $this->key = "clave-secreta";
    }
    
    public function signup($email, $password, $getHash = NULL){
        $user = $this->manager->getRepository('BackendBundle:User')->findOneBy(
                    array(
                        'email' => $email,
                        'password' => $password
                    )
                );
        $signUp = false;
        if (is_object($user)){
            $signUp = true;
        }
        
        if ($signUp){
            $token = array(
                "sub" => $user->getId(),        // Identificador
                "iat" => time(),                // Tiempo de creación
                "exp" => time() + (7*24*60*60),  // Sumamos una semana al timestamp
                "email" => $user->getEmail(),
                "name" => $user->getName(),
                "surname" => $user->getSurname(),
                "image" => $user->getImage(),
            );
            
            $jwt = JWT::encode($token, $this->key, 'HS256');          // Codifica los datos
            $decoded = JWT::decode($jwt, $this->key, array('HS256')); // Devuelve los datos sin cifrar
            
            if ($getHash !== NULL){
                return $jwt;
            } else {
                return $decoded;
            }
        } else {
            return array("status" => "error", "data" => "Login failed!!");
        }
    }
    
    public function checkToken($jwt, $getIdentity = false){
        $auth = false;  
        try{
            $decoded = JWT::decode($jwt, $this->key, array('HS256'));
        } catch (\UnexpectedValueExceptionException $ex) {
        } catch (\DomainException $ex) { 
        }
        if (isset($decoded->sub)){
            $auth = true;
        }
        if ($getIdentity == true){
            return $decoded;
        }
        return $auth;
    }
    
}


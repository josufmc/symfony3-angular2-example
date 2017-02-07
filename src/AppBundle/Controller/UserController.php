<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use BackendBundle\Entity\User;

class UserController extends Controller
{  
    public function newAction(Request $request)
    {
        $helpers = $this->get('app.helpers');
        $json = $request->get('json', null);
        $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'User not created'
            );
        
        if($json != null){
            $params = json_decode($json);
            $createdAt = new \DateTime();
            $image = null;
            $role = "user";
            
            $email = (isset($params->email)) ? $params->email : null;
            $name = (isset($params->name) && ctype_alpha($params->name)) ? $params->name : null;
            $surname = (isset($params->surname) && ctype_alpha($params->surname)) ? $params->surname : null;
            $password = (isset($params->password)) ? $params->password : null;
            
            $emailConstraint = new Assert\Email();
            $emailConstraint->message = "Invalid email";
            $validate_email = $this->get("validator")->validate($email, $emailConstraint);
            
            if ($email !== null 
                    && count($validate_email) == 0
                    && $password !== null
                    && $name !== null
                    && $surname !== null
                    ){
                $user = new User();
                $user->setCreatedAt($createdAt);
                $user->setImage($image);
                $user->setRole($role);
                $user->setEmail($email);
                $user->setName($name);
                $user->setSurname($surname);
                
                // Cifrado de password
                $pwd = hash('sha256', $password);
                $user->setPassword($pwd);
                
                $em = $this->getDoctrine()->getManager();
                $isset_user = $em->getRepository('BackendBundle:User')->findBy(
                        array("email" => $email)
                        );
                if (count($isset_user) == 0){
                    $em->persist($user);
                    $em->flush();
                    
                    $data = array(
                        'status' => 'success',
                        'code' => 201,
                        'msg' => 'User created!!'
                    );
                } else {
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'msg' => 'User already exists!!'
                    );
                }
            }
        }
        
        return $helpers->json($data);
    }
    
    
    public function editAction(Request $request)
    {
        $helpers = $this->get('app.helpers');
        
        $hash = $request->get('authorization', null);
        $authCheck = $helpers->authCheck($hash);
        if (!$authCheck){
            $data = array(
                'status' => 'error',
                'code' => 401,
                'msg' => 'Auth error'
            );
            return $helpers->json($data);
        }
        
        $identity = $helpers->authCheck($hash, true);
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('BackendBundle:User')->findOneBy(array('id'=>$identity->sub));
        
        $json = $request->get('json', null);
        $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'User not created'
            );
        
        if($json != null){
            $params = json_decode($json);
            $image = null;
            $role = "user";
            
            $email = (isset($params->email)) ? $params->email : null;
            $name = (isset($params->name) && ctype_alpha($params->name)) ? $params->name : null;
            $surname = (isset($params->surname) && ctype_alpha($params->surname)) ? $params->surname : null;
            $password = (isset($params->password)) ? $params->password : null;
            
            $emailConstraint = new Assert\Email();
            $emailConstraint->message = "Invalid email";
            $validate_email = $this->get("validator")->validate($email, $emailConstraint);
            
            if ($email !== null 
                    && count($validate_email) == 0
                    && $name !== null
                    && $surname !== null
                    ){
                $user->setImage($image);
                $user->setRole($role);
                $user->setEmail($email);
                $user->setName($name);
                $user->setSurname($surname);
                
                
                if ($password != null){
                    // Cifrado de password
                    $pwd = hash('sha256', $password);
                    $user->setPassword($pwd);
                }
                
                $em = $this->getDoctrine()->getManager();
                $isset_user = $em->getRepository('BackendBundle:User')->findBy(
                        array("email" => $email)
                        );
                if (count($isset_user) == 0 || $identity->email == $email){
                    $em->persist($user);
                    $em->flush();
                    
                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'msg' => 'User edited!!'
                    );
                } else {
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'msg' => 'User not updated!!'
                    );
                }
            }
        }
        
        return $helpers->json($data);
    }
    
    public function uploadImageAction(Request $request){
        $helpers = $this->get('app.helpers');
        $hash = $request->get('authorization', null);
        $authCheck = $helpers->authCheck($hash);
        $data = array();
        if (!$authCheck){
            $data = array(
                'status' => 'error',
                'code' => 401,
                'msg' => 'Auth error'
            );
            return $helpers->json($data);
        }
        
        $identity = $helpers->authCheck($hash, true);
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('BackendBundle:User')->findOneBy(array('id'=>$identity->sub));
        
        // Upload file
        $file = $request->files->get("image");
        if($file !== null && !empty($file)){
            $ext = $file->guessExtension();
            if ($ext == "jpeg" || $ext == "jpg" || $ext == "png" || $ext == "gif"){
                $file_name = time() . '.' . $ext;
                $file->move("uploads/users", $file_name);
                $user->setImage($file_name);
                $em->persist($user);
                $em->flush();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'msg' => 'Image uploaded!!'
                );
            } else {
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'Image not valid!!'
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'Image not valid!!'
            );
        }
        return $helpers->json($data);
    }
    
    
    public function channelAction(Request $request, $id = null){
        $helpers = $this->get('app.helpers');
        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('BackendBundle:User')->findOneBy(array('id'=>$id));
        
        $dql = "SELECT v FROM BackendBundle:Video v WHERE v.user=:user ORDER BY v.id DESC";
        $query = $em->createQuery($dql);
        $query->setParameter("user", $user->getId());
        
        $page = $request->query->getInt("page", 1);
        $paginator = $this->get("knp_paginator");
        $items_per_page = 2;
        
        $pagination = $paginator->paginate($query, $page, $items_per_page);
        $total_items_count = $pagination->getTotalItemCount();
       
        if ($user) {
            $data = array(
                "status" => "success",
                "code" => 200,
                "total_items_count" => $total_items_count,
                "page_actual" => $page,
                "items_per_page" => $items_per_page,
                "total_pages" => ceil($total_items_count / $items_per_page)
            );
            $data["data"]["videos"] = $pagination;
            $data["data"]["user"] = $user;
            
        } else {
            $data = array(
                "status" => "error",
                "code" => 400,
                "msg" => "User dont exists"
            );
        }
        
        return $helpers->json($data);
    }
    
}

<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use BackendBundle\Entity\User;
use BackendBundle\Entity\Video;
use BackendBundle\Entity\Comment;

class CommentController extends Controller
{  
    public function newAction(Request $request)
    {
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
        $json = $request->get('json', null);
        if ($json != null){
            $params = json_decode($json);

            $createdAt = new \DateTime();
            $user_id = $identity->sub;
            $video_id = (isset($params->video_id))? $params->video_id: null;
            $body = (isset($params->body))? $params->body: null;
            
            if($user_id != null && $video_id != null && $body != null){
                $em = $this->getDoctrine()->getManager();
                $user = $em->getRepository('BackendBundle:User')->findOneBy(array('id'=>$user_id));
                $video = $em->getRepository('BackendBundle:Video')->findOneBy(array('id'=>$video_id));
                
                $comment = new Comment();
                $comment->setUser($user);
                $comment->setVideo($video);
                $comment->setBody($body);
                $comment->setCreatedAt($createdAt);
                
                $em->persist($comment);
                $em->flush();
                
                $comment = $em->getRepository('BackendBundle:Comment')->findOneBy(
                        array('id'=>$comment->getId())
                    );
                
                $data = array(
                        'status' => 'success',
                        'code' => 201,
                        'msg' => 'Comment created!!',
                        'data' => $comment
                    );
            } else {
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'Comment not created!!'
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'Comment not created!!'
            );
        }
        
        return $helpers->json($data);
    }
    
    public function deleteAction(Request $request, $id = null){
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
        $user_id = $identity->sub;

        if($user_id != null && $id != null){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('BackendBundle:User')->findOneBy(array('id'=>$user_id));
            $comment = $em->getRepository('BackendBundle:Comment')->findOneBy(array('id'=>$id));
            if (
                    $comment != null && (
                            $user->getId() == $comment->getUser()->getId() ||           // Si es el autor del comentario
                            $user->getId() == $comment->getVideo()->getUser()->getId()  // Si es el propietario del vídeo
                        )
                    ){
                $em->remove($comment);
                $em->flush();

                $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'msg' => 'Comment deleted!!'
                    );
            } else {
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'Comment not deleted!!'
                );  
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'Comment not deleted!!'
            );
        }
        
        return $helpers->json($data);
    }
    
    
    public function listAction(Request $request, $id){
        $helpers = $this->get('app.helpers');
        $em = $this->getDoctrine()->getManager();
        $video = $em->getRepository('BackendBundle:Video')->findOneBy(array('id'=>$id));
        $comments = $em->getRepository('BackendBundle:Comment')->findBy(
                array('video' => $video->getId()),
                array('id' => 'DESC')
                );
        if (count($comments) >= 1){
            $data = array(
                "status" => "success",
                "code" => 200,
                "data" => $comments
            );
        } else {
            $data = array(
                "status" => "error",
                "code" => 400,
                "msg" => "No comments! ;) "
            );
            
        }
        
        /*$dql = "SELECT v FROM BackendBundle:Video v ORDER BY v.id DESC";
        $query = $em->createQuery($dql);
        
        $page = $request->query->getInt("page", 1);
        $paginator = $this->get("knp_paginator");
        $items_per_page = 2;
        
        $pagination = $paginator->paginate($query, $page, $items_per_page);
        $total_items_count = $pagination->getTotalItemCount();
        $data = array(
            "status" => "success",
            "code" => 200,
            "total_items_count" => $total_items_count,
            "page_actual" => $page,
            "items_per_page" => $items_per_page,
            "total_pages" => ceil($total_items_count / $items_per_page),
            "data" => $pagination
        );*/
        
        return $helpers->json($data);
    }
}

<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use BackendBundle\Entity\User;
use BackendBundle\Entity\Video;

class VideoController extends Controller
{  
    public function pruebasAction(){
        echo('pruebas');
        die();
    }
    
    public function newAction(Request $request){
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
            $updatedAt = new \DateTime();
            $imagen = null;
            $video_path = null;
            
            $user_id = $identity->sub;
            $title = (isset($params->title))? $params->title: null;
            $description = (isset($params->description))? $params->description: null;
            $status = (isset($params->status))? $params->status: null;
            
            if($user_id != null && $title != null){
                $em = $this->getDoctrine()->getManager();
                $user = $em->getRepository('BackendBundle:User')->findOneBy(array('id'=>$user_id));
                $video = new Video();
                $video->setUser($user);
                $video->setTitle($title);
                $video->setDescription($description);
                $video->setStatus($status);
                $video->setCreatedAt($createdAt);
                $video->setUpdatedAt($updatedAt);
                
                $em->persist($video);
                $em->flush();
                
                $video = $em->getRepository('BackendBundle:Video')->findOneBy(
                        array('id'=>$video->getId())
                        /*array(
                            'user' => $user,
                            'createdAt' => $createdAt
                        )*/
                    );
                
                $data = array(
                        'status' => 'success',
                        'code' => 201,
                        'msg' => 'Video created!!',
                        'data' => $video
                    );
            } else {
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'Video not created!!'
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'Video not created!!'
            );
        }
        
        return $helpers->json($data);
    }
    
    
    public function editAction(Request $request, $id = null){
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

            $updatedAt = new \DateTime();
            $imagen = null;
            $video_path = null;
            
            $user_id = $identity->sub;
            $title = (isset($params->title))? $params->title: null;
            $description = (isset($params->description))? $params->description: null;
            $status = (isset($params->status))? $params->status: null;
            $em = $this->getDoctrine()->getManager();
            $video = $em->getRepository('BackendBundle:Video')->findOneBy(array('id'=>$id));
            
            if($user_id != null && $title != null){
                if (isset($identity->sub) && $identity->sub == $video->getUser()->getId()) {
                    $video->setTitle($title);
                    $video->setDescription($description);
                    $video->setStatus($status);
                    $video->setUpdatedAt($updatedAt);

                    $em->persist($video);
                    $em->flush();

                    $data = array(
                            'status' => 'success',
                            'code' => 201,
                            'msg' => 'Video update success!!'
                        );
                } else {
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'msg' => 'This user is not the owner!!'
                    );
                }
            } else {
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'Video not updated!!'
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'msg' => 'Video not updated!!'
            );
        }
        
        return $helpers->json($data);
    }
    
    
    public function uploadAction(Request $request, $id){
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
        $video_id = $id;
        $em = $this->getDoctrine()->getManager();
        $video = $em->getRepository('BackendBundle:Video')->findOneBy(array('id'=>$video_id));

        if ($video != null && isset($identity->sub) && $identity->sub == $video->getUser()->getId()){
            $file = $request->files->get('image', null);
            $file_video = $request->files->get('video', null);

            if ($file != null && !empty($file)){
                $ext = $file->guessExtension();
                if ($ext == "jpg" || $ext == "jpeg" || $ext == "png" || $ext == "bmp"){
                    $file_name = time() . "." . $ext;
                    $path_of_file = "uploads/video_images/video_" . $video->getId();
                    $file->move($path_of_file, $file_name);

                    $video->setImage($file_name);
                    $em->persist($video);
                    $em->flush();

                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'msg' => 'Image file uploaded!!'
                    );
                } else {
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'msg' => 'Image file format not valid!!'
                    );
                }

            } else {
                if ($file_video != null && !empty($file_video)){
                    $ext = $file_video->guessExtension();
                    if ($ext == "mp4" || $ext == "avi"){
                        $file_name = time() . "." . $ext;
                        $path_of_file = "uploads/video_files/video_" . $video->getId();
                        $file_video->move($path_of_file, $file_name);

                        $video->setVideoPath($file_name);
                        $em->persist($video);
                        $em->flush();

                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'msg' => 'Video file uploaded!!'
                        );
                    } else {
                        $data = array(
                            'status' => 'error',
                            'code' => 400,
                            'msg' => 'Video file format not valid!!'
                        );
                    }
                }

            }
        }
            
        
        return $helpers->json($data);
    }
    
    
    public function videosAction(Request $request){
        $helpers = $this->get('app.helpers');
        $em = $this->getDoctrine()->getManager();
        $dql = "SELECT v FROM BackendBundle:Video v ORDER BY v.id DESC";
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
        );
        
        return $helpers->json($data);
    }
    
    public function lastVideosAction(Request $request){
        $helpers = $this->get('app.helpers');
        $em = $this->getDoctrine()->getManager();
        $dql = "SELECT v FROM BackendBundle:Video v ORDER BY v.id DESC";
        $query = $em->createQuery($dql)->setMaxResults(5); 
        $videos = $query->getResult();
        
        $data = array(
            "status" => "success",
            "code" => 200,
            "data" => $videos
        );
        
        return $helpers->json($data);
    }
    
    public function videoAction(Request $request, $id = null){
        $helpers = $this->get('app.helpers');
        $em = $this->getDoctrine()->getManager();
        $video = $em->getRepository('BackendBundle:Video')->findOneBy(array('id'=>$id));
        $data = array();
        
        if ($video){
            $data = array(
                "status" => "success",
                "code" => 200,
                "data" => $video
            );
        } else {
            $data = array(
                "status" => "error",
                "code" => 400,
                "msg" => 'Video doesnt exists!!'
            );
        }
        
        return $helpers->json($data);
    }
    
    public function searchAction(Request $request, $search = null){
        $helpers = $this->get('app.helpers');
        $em = $this->getDoctrine()->getManager();
        $query = null;
        if ($search != null){
            $dql = "SELECT v FROM BackendBundle:Video v "
                    . "WHERE v.title LIKE :search OR v.description LIKE :search "
                    . "ORDER BY v.id DESC";
            $query = $em->createQuery($dql);
            $query->setParameter("search", "%$search%");
        } else{
            $dql = "SELECT v FROM BackendBundle:Video v ORDER BY v.id DESC";
            $query = $em->createQuery($dql);
        }
        
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
        );
        
        return $helpers->json($data);
    }
    
}

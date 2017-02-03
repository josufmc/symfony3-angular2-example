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
    
}

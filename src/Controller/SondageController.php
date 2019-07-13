<?php

namespace App\Controller;

use App\Entity\Question;
use App\Repository\QuestionRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SondageController extends AbstractController
{
    /**
     * @Route("/sondage", name="sondage")
     */
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/SondageController.php',
        ]);
    }


    /**
     * @Route("/question/{id}", methods={"GET"}, name="get_single_question")
     */
    public function getQuestion(int $id, RegistryInterface $doctrine)
    {
        $question = $doctrine->getManager()->find(Question::class, $id);

        if(null === $question){
            return $this->json([
                'status' => 'failure',
                'message' => 'question not existing',
            ], 404);
        }

        return $this->json([
            'status' => 'success',
            'code' => 200,
            'data' => $question
        ]);
    }


    /**
     * @Route("/sondage/{id}", methods={"GET"}, name="get_single_sondage")
     */
    public function getSondage(int $id, RegistryInterface $doctrine)
    {
        $sondage = $doctrine->getManager()->find(Sondage::class, $id);

        if (null === $sondage) {
            return $this->json([
                'status' => 'failure',
                'message' => 'sondage not existing',
            ], 404);
        }

        return $this->json([
            'status' => 'success',
            'code' => 200,
            'data' => $sondage
        ]);
    }

    /**
     * @Route("/choix/{id}", methods={"GET"}, name="get_single_choix")
     */
    public function getChoix(int $id, RegistryInterface $doctrine)
    {
        $choix = $doctrine->getManager()->find(Choix::class, $id);

        if (null === $choix) {
            return $this->json([
                'status' => 'failure',
                'message' => 'choix not existing',
            ], 404);
        }

        return $this->json([
            'status' => 'success',
            'code' => 200,
            'data' => $choix
        ]);
    }


    /**
     * @Route("/votant/{id}", methods={"GET"}, name="get_single_votant")
     */
    public function getVotant(int $id, RegistryInterface $doctrine)
    {
        $votant = $doctrine->getManager()->find(Votant::class, $id);

        if(null === $votant){
            return $this->json([
                'status' => 'failure',
                'message' => 'votant not existing',
            ], 404);
        }

        return $this->json([
            'status' => 'success',
            'code' => 200,
            'data' => $votant
        ]);
    }


    /**
     * @Route("/votant_choix/{id}", methods={"GET"}, name="get_single_question")
     */
//    public function getQuestion(int $id, RegistryInterface $doctrine)
//    {
//        $question = $doctrine->getManager()->find(Question::class, $id);
//
//        if(null === $question){
//            return $this->json([
//                'status' => 'failure',
//                'message' => 'question not existing',
//            ], 404);
//        }
//
//        return $this->json([
//            'status' => 'success',
//            'code' => 200,
//            'data' => $question
//        ]);
//    }

    protected $nom;




}


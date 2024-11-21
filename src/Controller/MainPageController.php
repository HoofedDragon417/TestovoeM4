<?php

namespace App\Controller;

use App\Repository\NetworkRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainPageController extends AbstractController
{

    #[Route('/', name: 'mainpage')]
    public function index(
        NetworkRepository $httpClient
    ): Response
    {
        $renderData = [];

        $login = $httpClient->login('dev3', '3CQM5K');
        $renderData['login'] = $login;

        $httpClient->getStatuses();

        $date = new DateTime();
        $date->modify('-3 day');
        $date->modify('00:00:00');
        $tasks = $httpClient->getTasks($date, $GLOBALS['STATUSES']);
        $renderData['tasks'] = $tasks;

        $secondTaskDetails = $httpClient->getTaskDetails($GLOBALS['SECOND_TASK_ID']);
        $renderData['secondTaskDetails'] = $secondTaskDetails;

        $comment = $httpClient->addCommentToTask($GLOBALS['SECOND_TASK_ID'], 'Abrosimon Yaroslav test task');
        $renderData['comment'] = $comment;

        $logout = $httpClient->logout();
        $renderData['logout'] = $logout;

        return $this->render('mainpage/index.html.twig', $renderData);
    }
}

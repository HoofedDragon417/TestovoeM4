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

        $login = $httpClient->login(username:'dev3', password:'3CQM5K');
        $renderData['login'] = $login;

        $httpClient->getStatuses();

        $date = new DateTime();
        $date->modify('-3 day');
        $date->modify('00:00:00');
        $tasks = $httpClient->getTasks(dateTime:$date,statuses: $GLOBALS['STATUSES']);
        $renderData['tasks'] = $tasks;

        if(isset($GLOBALS['SECOND_TASK_ID'])){
            $secondTaskDetails = $httpClient->getTaskDetails(taskId:$GLOBALS['SECOND_TASK_ID']);
            $renderData['secondTaskDetails'] = $secondTaskDetails;

            $comment = $httpClient->addCommentToTask(taskId:$GLOBALS['SECOND_TASK_ID'], comment:'Abrosimov Yaroslav test task');
            $renderData['comment'] = $comment;
        }

        $logout = $httpClient->logout();
        $renderData['logout'] = $logout;

        return $this->render('mainpage/index.html.twig', $renderData);
    }
}

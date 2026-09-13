<?php

namespace App\Controller;

use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function index(TaskRepository $tasks): Response
    {
        $open = $tasks->count(['completed' => false]);
        $done = $tasks->count(['completed' => true]);

        return $this->render('dashboard/index.html.twig', [
            'openCount' => $open,
            'doneCount' => $done,
            'totalCount' => $open + $done,
            'recent' => $tasks->findBy([], ['createdAt' => 'DESC'], 5),
            'completionRate' => $open + $done > 0 ? round($done / ($open + $done) * 100) : 0,
        ]);
    }
}

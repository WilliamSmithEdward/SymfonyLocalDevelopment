<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tasks')]
final class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $tasks,
    ) {
    }

    #[Route('', name: 'task_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('task/index.html.twig', [
            'tasks' => $this->tasks->findBy([], ['createdAt' => 'DESC']),
            'openCount' => $this->tasks->count(['completed' => false]),
            'doneCount' => $this->tasks->count(['completed' => true]),
        ]);
    }

    #[Route('/new', name: 'task_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('task/new.html.twig', [
            'heading' => 'New task',
            'action' => $this->generateUrl('task_index'),
        ]);
    }

    #[Route('/{id}', name: 'task_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Task $task): Response
    {
        return $this->render('task/show.html.twig', [
            'task' => $task,
            'siblings' => $this->tasks->findBy(['completed' => $task->isCompleted()], null, 5),
        ]);
    }

    #[Route('/{id}/edit', name: 'task_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function edit(Task $task): Response
    {
        return $this->render('task/edit.html.twig', [
            'heading' => 'Edit task',
            'task' => $task,
            'action' => $this->generateUrl('task_show', ['id' => $task->getId()]),
        ]);
    }

    /**
     * Returns the template context directly; the #[Template] attribute names
     * the template instead of a render() call.
     */
    #[Route('/board', name: 'task_board', methods: ['GET'])]
    #[Template('task/board.html.twig')]
    public function board(): array
    {
        $all = $this->tasks->findAll();

        return [
            'columns' => [
                'open' => array_filter($all, static fn (Task $t): bool => !$t->isCompleted()),
                'done' => array_filter($all, static fn (Task $t): bool => (bool) $t->isCompleted()),
            ],
            'total' => count($all),
        ];
    }

    /**
     * Builds an email body as a string rather than a response, which is what
     * renderView is for.
     */
    #[Route('/{id}/reminder', name: 'task_reminder', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function reminder(Task $task): Response
    {
        $body = $this->renderView('email/task_reminder.html.twig', [
            'task' => $task,
            'dueLabel' => 'as soon as you can',
        ]);

        return new Response($body, Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }
}

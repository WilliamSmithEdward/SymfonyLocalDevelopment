<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DashboardControllerTest extends WebTestCase
{
    public function testDashboardRendersCounts(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\Task')->execute();

        foreach ([true, false, false] as $index => $completed) {
            $task = new Task();
            $task->setTitle('Task '.$index);
            $task->setCompleted($completed);
            $task->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($task);
        }
        $entityManager->flush();

        $client->request('GET', '/dashboard');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Dashboard');
        self::assertSelectorExists('section.card');
        self::assertSelectorTextContains('.stats', 'Open: 2');
        self::assertSelectorTextContains('.stats', 'Done: 1');
    }

    public function testDashboardShowsTheEmptyStateWithNoTasks(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\Task')->execute();

        $client->request('GET', '/dashboard');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.empty-state');
    }
}

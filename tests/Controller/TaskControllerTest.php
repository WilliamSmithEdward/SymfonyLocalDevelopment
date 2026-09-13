<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TaskControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private int $taskId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Start from a known state; the test environment has no transactional
        // rollback configured, so each test seeds exactly what it asserts on.
        $this->entityManager->createQuery('DELETE FROM App\Entity\Task')->execute();

        $task = new Task();
        $task->setTitle('Write the release notes');
        $task->setDescription('Summarise what changed since the last tag.');
        $task->setCompleted(false);
        $task->setCreatedAt(new \DateTimeImmutable('2026-01-15 09:30:00'));

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->taskId = (int) $task->getId();
    }

    public function testIndexListsTasks(): void
    {
        $crawler = $this->client->request('GET', '/tasks');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Tasks');
        self::assertStringContainsString('Write the release notes', $crawler->html());
    }

    public function testIndexRendersTheDesignNamespaceButton(): void
    {
        $crawler = $this->client->request('GET', '/tasks');

        self::assertResponseIsSuccessful();
        // Proves @Design/button.html.twig resolved through the second namespace.
        self::assertSelectorExists('a.button');
    }

    public function testShowRendersTheEmbeddedCard(): void
    {
        $this->client->request('GET', '/tasks/'.$this->taskId);

        self::assertResponseIsSuccessful();
        // Proves the embed of @Design/card.html.twig overrode its body block.
        self::assertSelectorExists('section.card');
        self::assertSelectorTextContains('.card__title', 'Write the release notes');
        self::assertSelectorTextContains('.card__body', 'Summarise what changed');
    }

    public function testNewRendersTheSharedForm(): void
    {
        $this->client->request('GET', '/tasks/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form.task-form');
    }

    public function testEditRendersTheSharedFormWithValues(): void
    {
        $crawler = $this->client->request('GET', '/tasks/'.$this->taskId.'/edit');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form.task-form');
        self::assertSame(
            'Write the release notes',
            $crawler->filter('input[name="title"]')->attr('value'),
        );
    }

    public function testBoardIsRenderedByTheTemplateAttribute(): void
    {
        $this->client->request('GET', '/tasks/board');

        // The controller returns an array; #[Template] names the template.
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.board');
        self::assertSelectorTextContains('h1', 'Board');
    }

    public function testReminderIsBuiltWithRenderView(): void
    {
        $this->client->request('GET', '/tasks/'.$this->taskId.'/reminder');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'Write the release notes',
            (string) $this->client->getResponse()->getContent(),
        );
    }

    public function testBoardRouteIsNotSwallowedByTheIdRoute(): void
    {
        // /tasks/board must not match /tasks/{id}; the digit requirement is
        // what keeps these two apart.
        $this->client->request('GET', '/tasks/board');
        self::assertResponseIsSuccessful();
    }
}

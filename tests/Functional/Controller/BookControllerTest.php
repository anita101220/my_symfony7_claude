<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Domain\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * Functional tests that exercise the full HTTP stack against SQLite.
 * DATABASE_URL in .env.test must point to SQLite (default: var/test.db).
 */
final class BookControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager = $em;

        // Rebuild schema from entity metadata on every test run.
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    // -------------------------------------------------------------------------
    // POST /api/books
    // -------------------------------------------------------------------------

    public function testCreateBookReturns201(): void
    {
        // Arrange & Act
        $this->client->request('POST', '/api/books', content: json_encode([
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'isbn' => '9780132350884',
            'description' => 'A handbook of agile software craftsmanship',
            'price' => 29.99,
        ]));

        $response = $this->client->getResponse();
        $body = json_decode($response->getContent(), true);

        // Assert
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertNotEmpty($body['data']['id']);
        $this->assertSame('Clean Code', $body['data']['title']);
        $this->assertSame(29.99, $body['data']['price']);
    }

    public function testCreateBookReturns422OnValidationFailure(): void
    {
        // Arrange & Act
        $this->client->request('POST', '/api/books', content: json_encode([
            'title' => '',        // blank — must fail validation
            'author' => 'Author',
            'isbn' => 'INVALID',  // bad ISBN
            'price' => -1.0,      // negative
        ]));

        $response = $this->client->getResponse();
        $body = json_decode($response->getContent(), true);

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertNotEmpty($body['errors']);
    }

    public function testCreateBookReturns409WhenIsbnAlreadyExists(): void
    {
        // Arrange — create the first book
        $this->client->request('POST', '/api/books', content: json_encode([
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'isbn' => '9780132350884',
            'price' => 29.99,
        ]));

        // Act — try to create another with the same ISBN
        $this->client->request('POST', '/api/books', content: json_encode([
            'title' => 'Duplicate',
            'author' => 'Someone',
            'isbn' => '9780132350884',
            'price' => 9.99,
        ]));

        $response = $this->client->getResponse();
        $body = json_decode($response->getContent(), true);

        // Assert
        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertFalse($body['success']);
    }

    // -------------------------------------------------------------------------
    // GET /api/books
    // -------------------------------------------------------------------------

    public function testListBooksReturnsEmptyArray(): void
    {
        // Act
        $this->client->request('GET', '/api/books');

        $response = $this->client->getResponse();
        $body = json_decode($response->getContent(), true);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertSame([], $body['data']);
    }

    public function testListBooksReturnsCreatedBooks(): void
    {
        // Arrange — insert two books directly
        $this->persistBook('9780132350884', 'Clean Code', 'Robert C. Martin');
        $this->persistBook('9780135957059', 'The Pragmatic Programmer', 'David Thomas');

        // Act
        $this->client->request('GET', '/api/books');

        $body = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertCount(2, $body['data']);
    }

    // -------------------------------------------------------------------------
    // GET /api/books/{id}
    // -------------------------------------------------------------------------

    public function testShowBookReturns200(): void
    {
        // Arrange
        $book = $this->persistBook('9780132350884', 'Clean Code', 'Robert C. Martin');

        // Act
        $this->client->request('GET', '/api/books/'.$book->getId()->toRfc4122());

        $body = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Clean Code', $body['data']['title']);
    }

    public function testShowBookReturns404ForUnknownId(): void
    {
        // Act
        $this->client->request('GET', '/api/books/'.Uuid::v4()->toRfc4122());

        $response = $this->client->getResponse();

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // PUT /api/books/{id}
    // -------------------------------------------------------------------------

    public function testUpdateBookReturns200(): void
    {
        // Arrange
        $book = $this->persistBook('9780132350884', 'Clean Code', 'Robert C. Martin');

        // Act
        $this->client->request('PUT', '/api/books/'.$book->getId()->toRfc4122(), content: json_encode([
            'title' => 'Clean Code — Updated',
            'author' => 'Robert C. Martin',
            'isbn' => '9780132350884',
            'price' => 34.99,
        ]));

        $body = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Clean Code — Updated', $body['data']['title']);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/books/{id}
    // -------------------------------------------------------------------------

    public function testDeleteBookReturns204(): void
    {
        // Arrange
        $book = $this->persistBook('9780132350884', 'Clean Code', 'Robert C. Martin');

        // Act
        $this->client->request('DELETE', '/api/books/'.$book->getId()->toRfc4122());

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteBookReturns404WhenNotFound(): void
    {
        // Act
        $this->client->request('DELETE', '/api/books/'.Uuid::v4()->toRfc4122());

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function persistBook(string $isbn, string $title, string $author): Book
    {
        $book = new Book(
            id: Uuid::v4(),
            title: $title,
            author: $author,
            isbn: $isbn,
            description: null,
            price: '29.99',
        );

        $this->entityManager->persist($book);
        $this->entityManager->flush();
        $this->entityManager->clear();

        return $book;
    }
}

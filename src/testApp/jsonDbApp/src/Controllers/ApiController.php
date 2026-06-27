<?php declare(strict_types=1);

namespace Concept\testApp\jsonDbApp\src\Controllers;

use Concept\Extensions\DatabaseEloquent\Contracts\DatabaseInterface;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\testApp\jsonDbApp\src\Models\Item;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

final class ApiController
{
    private const string ERR_TITLE_REQUIRED = 'Field "title" is required.';
    private const string ERR_MIGRATIONS_PENDING = 'Database is not migrated. Run: php src/testApp/jsonDbApp/bin/migrate.php';

    public function __construct(
        private readonly ResponseFactoryInterface $response,
        private readonly DatabaseInterface $database,
    ) {}

    public function ping(): ResponseInterface
    {
        $dbStatus = $this->resolveDatabaseStatus();

        return $this->response->jsonSuccess([
            'app' => 'jsonDbApp',
            'message' => 'pong',
            'database' => $dbStatus,
        ]);
    }

    public function items(): ResponseInterface
    {
        $this->assertItemsTableExists();

        $items = Item::query()
            ->orderByDesc('id')
            ->get(['id', 'title', 'created_at']);

        return $this->response->jsonSuccess([
            'app' => 'jsonDbApp',
            'items' => $items->toArray(),
        ]);
    }

    public function storeItem(ServerRequestInterface $request): ResponseInterface
    {
        $this->assertItemsTableExists();

        $body = $request->getParsedBody();
        $title = is_array($body) ? ($body['title'] ?? null) : null;

        if (!is_string($title) || trim($title) === '') {
            return $this->response->jsonError(self::ERR_TITLE_REQUIRED, 422);
        }

        $item = Item::query()->create([
            'title' => trim($title),
        ]);
        $storedTitle = trim($title);

        return $this->response->jsonSuccess([
            'app' => 'jsonDbApp',
            'item' => [
                'id' => $item->getKey(),
                'title' => $storedTitle,
                'created_at' => $item->getAttribute('created_at'),
            ],
        ], 201);
    }

    /**
     * @return array{connected: bool, driver: string, migrated: bool}
     */
    private function resolveDatabaseStatus(): array
    {
        $driver = (string) $this->database->capsule()->getConnection()->getDriverName();
        $connected = false;
        $migrated = false;

        try {
            $this->database->capsule()->getConnection()->select('select 1');
            $connected = true;
            $migrated = $this->schema()->hasTable('jsondb_items');
        } catch (Throwable) {
            $connected = false;
        }

        return [
            'connected' => $connected,
            'driver' => $driver,
            'migrated' => $migrated,
        ];
    }

    private function assertItemsTableExists(): void
    {
        if ($this->schema()->hasTable('jsondb_items')) {
            return;
        }

        throw new RuntimeException(self::ERR_MIGRATIONS_PENDING);
    }

    private function schema(): SchemaBuilder
    {
        return $this->database->capsule()->schema();
    }
}

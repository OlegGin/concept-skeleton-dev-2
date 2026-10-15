<?php declare(strict_types=1);

namespace Concept\Components\SystemLog\Controllers;

use Concept\App\Models\BaseModel;
use Concept\Components\SystemLog\Constants\RouteName;
use Concept\Components\SystemLog\Constants\ViewName;
use Concept\Components\SystemLog\Models\SystemLogModel;
use Concept\Components\SystemLog\SystemLogEvent;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\SessionSymfony\Contracts\FlashBagInterface;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;

class SystemLogController
{
    private const string MSG_NOT_FOUND = 'Log entry not found';

    private const string CONFIG_PAGINATION_PER_PAGE = 'pagination.per_page';
    private const int DEFAULT_PAGINATION_PER_PAGE = 20;

    private const string CONTEXT_LOGS = 'logs';
    private const string CONTEXT_LOG = 'log';
    private const string CONTEXT_EVENTS = 'events';
    private const string CONTEXT_ACTIVE_EVENT = 'active_event';

    public function __construct(
        private readonly ResponseFactoryInterface $response,
        private readonly ViewResponseFactoryInterface $viewResponse,
        private readonly ConfigInterface $config,
        private readonly FlashBagInterface $flashBag,
        private readonly SystemLogModel $systemLog,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $eventParam = $queryParams['event'] ?? null;
        $activeEvent = is_string($eventParam) && $eventParam !== '' ? $eventParam : null;

        $query = $this->systemLog
            ->newQuery()
            ->orderBy(SystemLogModel::FIELD_CREATED_AT, BaseModel::SORT_DESC)
            ->orderBy(SystemLogModel::FIELD_ID, BaseModel::SORT_DESC);

        if ($activeEvent !== null) {
            $query->where(SystemLogModel::FIELD_EVENT, $activeEvent);
        }

        $logs = $query
            ->paginate($this->config->getInt(self::CONFIG_PAGINATION_PER_PAGE, self::DEFAULT_PAGINATION_PER_PAGE))
            ->withQueryString();

        return $this->viewResponse->create(ViewName::LIST, [
            self::CONTEXT_LOGS => $logs,
            self::CONTEXT_EVENTS => $this->knownEvents(),
            self::CONTEXT_ACTIVE_EVENT => $activeEvent,
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $log = $this->systemLog->newQuery()->find($id);
        if (!($log instanceof SystemLogModel)) {
            $this->flashBag->addError(self::MSG_NOT_FOUND);

            return $this->response->redirectByName(RouteName::INDEX);
        }

        return $this->viewResponse->create(ViewName::SHOW, [
            self::CONTEXT_LOG => $log,
        ]);
    }

    /**
     * @return list<string>
     */
    private function knownEvents(): array
    {
        $constants = (new ReflectionClass(SystemLogEvent::class))->getConstants();
        $events = [];
        foreach ($constants as $value) {
            if (is_string($value)) {
                $events[] = $value;
            }
        }

        sort($events);

        return $events;
    }
}

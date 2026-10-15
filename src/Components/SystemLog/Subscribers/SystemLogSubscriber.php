<?php declare(strict_types=1);

namespace Concept\Components\SystemLog\Subscribers;

use Concept\Components\AuthAdmin\Events\UserCreated;
use Concept\Components\AuthAdmin\Events\UserDeleted;
use Concept\Components\AuthAdmin\Events\UserLoginFailed;
use Concept\Components\AuthAdmin\Events\UserLoginSucceeded;
use Concept\Components\AuthAdmin\Events\UserUpdated;
use Concept\Components\SystemLog\Models\SystemLogModel;
use Concept\Components\SystemLog\SystemLogEvent;
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;

final class SystemLogSubscriber implements ListenerSubscriber
{
    public function __construct(
        private readonly SystemLogModel $systemLog,
    ) {}

    public function subscribeListeners(ListenerRegistry $listenerRegistry): void
    {
        $listenerRegistry->subscribeTo(UserLoginSucceeded::class, $this->onUserLoginSucceeded(...));
        $listenerRegistry->subscribeTo(UserLoginFailed::class, $this->onUserLoginFailed(...));
        $listenerRegistry->subscribeTo(UserCreated::class, $this->onUserCreated(...));
        $listenerRegistry->subscribeTo(UserUpdated::class, $this->onUserUpdated(...));
        $listenerRegistry->subscribeTo(UserDeleted::class, $this->onUserDeleted(...));
    }

    private function onUserLoginSucceeded(UserLoginSucceeded $event): void
    {
        $this->write(
            event: SystemLogEvent::USER_LOGIN_SUCCEEDED,
            message: sprintf('User %s logged in', $event->email),
            actorUserId: $event->userId,
            subjectId: $event->userId,
            context: ['email' => $event->email],
        );
    }

    private function onUserLoginFailed(UserLoginFailed $event): void
    {
        $this->write(
            event: SystemLogEvent::USER_LOGIN_FAILED,
            message: sprintf('Failed login attempt for %s', $event->email),
            context: ['email' => $event->email],
        );
    }

    private function onUserCreated(UserCreated $event): void
    {
        $this->write(
            event: SystemLogEvent::USER_CREATED,
            message: sprintf('User %s created', $event->email),
            actorUserId: $event->actorUserId,
            subjectId: $event->userId,
            context: ['email' => $event->email],
        );
    }

    private function onUserUpdated(UserUpdated $event): void
    {
        $this->write(
            event: SystemLogEvent::USER_UPDATED,
            message: sprintf('User %s updated', $event->email),
            actorUserId: $event->actorUserId,
            subjectId: $event->userId,
            context: ['email' => $event->email],
        );
    }

    private function onUserDeleted(UserDeleted $event): void
    {
        $this->write(
            event: SystemLogEvent::USER_DELETED,
            message: sprintf('User %s deleted', $event->email),
            actorUserId: $event->actorUserId,
            subjectId: $event->userId,
            context: ['email' => $event->email],
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function write(
        string $event,
        string $message,
        ?int $actorUserId = null,
        ?int $subjectId = null,
        array $context = [],
    ): void {
        $this->systemLog->newQuery()->create([
            SystemLogModel::FIELD_EVENT => $event,
            SystemLogModel::FIELD_ACTOR_USER_ID => $actorUserId,
            SystemLogModel::FIELD_SUBJECT_TYPE => $subjectId !== null ? SystemLogModel::SUBJECT_USER : null,
            SystemLogModel::FIELD_SUBJECT_ID => $subjectId,
            SystemLogModel::FIELD_MESSAGE => $message,
            SystemLogModel::FIELD_CONTEXT => $context,
        ]);
    }
}

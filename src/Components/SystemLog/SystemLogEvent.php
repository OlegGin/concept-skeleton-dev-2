<?php declare(strict_types=1);

namespace Concept\Components\SystemLog;

final class SystemLogEvent
{
    public const string USER_LOGIN_SUCCEEDED = 'user.login.succeeded';
    public const string USER_LOGIN_FAILED = 'user.login.failed';
    public const string USER_CREATED = 'user.created';
    public const string USER_UPDATED = 'user.updated';
    public const string USER_DELETED = 'user.deleted';
}

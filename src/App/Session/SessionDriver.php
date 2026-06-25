<?php declare(strict_types=1);

namespace Concept\App\Session;

final class SessionDriver
{
    public const string FILE = 'file';
    public const string REDIS = 'redis';
    public const string PDO = 'pdo';
}

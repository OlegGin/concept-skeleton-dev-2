<?php declare(strict_types=1);

namespace Concept\Components\Health;

enum HealthStatus: string
{
    case Ok = 'ok';
    case Warn = 'warn';
    case Fail = 'fail';
    case Skip = 'skip';
}

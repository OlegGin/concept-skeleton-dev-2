<?php declare(strict_types=1);

namespace Concept\App\Session;

/**
 * App session / flash storage keys (skeleton glue, not extension contracts).
 */
final class SessionKey
{
    public const string VALIDATION_ERRORS = '_validation_errors';
    public const string VALIDATION_DATA = '_validation_data';
}

<?php declare(strict_types=1);

namespace Concept\App\Foundation;

/**
 * App session / flash storage keys (skeleton glue, not extension contracts).
 */
final class SessionKey
{
    public const string VALIDATION_ERRORS = '_validation_errors';
    public const string VALIDATION_DATA = '_validation_data';
    public const string URL_PREVIOUS = '_url_previous';
    public const string URL_CURRENT = '_url_current';
}

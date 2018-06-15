<?php
declare(strict_types=1);

namespace Firehed\API\Authorization;

/**
 * This class exists to loosely mimic a Result type, forcing ProviderInterface
 * implementations to affirmatively return a success state in order to reduce
 * the chance of accidentally failing "open".
 */
class Ok
{
}

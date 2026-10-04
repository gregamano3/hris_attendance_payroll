<?php

namespace App\Features\Auth\SingleSignOn;

use RuntimeException;

/**
 * A sign-in refusal whose message is safe to show to the user.
 */
class SsoUserException extends RuntimeException {}

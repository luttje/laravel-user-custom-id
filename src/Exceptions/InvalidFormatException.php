<?php

namespace Luttje\UserCustomId\Exceptions;

/**
 * Thrown when a (user-defined) format can't be used to generate ids.
 *
 * The message is safe to show to the user that wrote the format.
 */
class InvalidFormatException extends \InvalidArgumentException {}

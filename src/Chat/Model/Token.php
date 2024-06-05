<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Chat\Model;

/**
 * Class Token
 *
 * @package srag\Plugins\Opencast\Chat
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class Token
{
    protected ?string $token;

    public function __construct(string $token = null)
    {
        if (empty($token)) {
            $token = random_bytes(16);
            $token = bin2hex($token);
        }
        $this->token = $token;
    }

    public function toString(): string
    {
        return $this->token;
    }
}

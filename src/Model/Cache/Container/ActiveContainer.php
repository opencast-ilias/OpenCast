<?php

declare(strict_types=1);

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Cache\Container;

use srag\Plugins\Opencast\Model\Cache\Adaptor\Adaptor;
use srag\Plugins\Opencast\Model\Cache\Config;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
final class ActiveContainer implements Container
{
    private const LOCK_UNTIL = '_lock_until';
    private const GLUE = '_|||_';
    private const STRING_PREFIX = 'string' . self::GLUE;
    private const ARRAY_PREFIX = 'array' . self::GLUE;
    private const STD_PREFIX = 'std' . self::GLUE;
    private const INT_PREFIX = 'int' . self::GLUE;
    private const FLOAT_PREFIX = 'float' . self::GLUE;
    private const DOUBLE_PREFIX = 'double' . self::GLUE;
    private const BOOL_PREFIX = 'bool' . self::GLUE;
    private const NULL_PREFIX = 'null' . self::GLUE;
    private const TRUE = 'true';
    private const FALSE = 'false';

    /**
     * @var \srag\Plugins\Opencast\Model\Cache\Container\Request
     */
    private $request;
    /**
     * @var \srag\Plugins\Opencast\Model\Cache\Adaptor\Adaptor
     */
    private $adaptor;
    /**
     * @var \srag\Plugins\Opencast\Model\Cache\Config
     */
    private $config;

    public function __construct(
        Request $request,
        Adaptor $adaptor,
        Config $config
    ) {
        $this->request = $request;
        $this->adaptor = $adaptor;
        $this->config = $config;
    }

    /**
     * @param mixed $value
     */
    private function pack($value): string
    {
        if (is_string($value)) {
            return self::STRING_PREFIX . $value;
        }
        if (is_array($value)) {
            array_walk_recursive($value, function (&$item): void {
                $item = $this->pack($item);
            });

            return self::ARRAY_PREFIX . json_encode($value, JSON_THROW_ON_ERROR);
        }

        if ($value instanceof \stdClass) {
            $value = (array) $value;
            array_walk_recursive($value, function (&$item): void {
                $item = $this->pack($item);
            });

            return self::STD_PREFIX . json_encode($value, JSON_THROW_ON_ERROR);
        }

        if (is_int($value)) {
            return self::INT_PREFIX . $value;
        }
        if (is_float($value)) {
            return self::FLOAT_PREFIX . $value;
        }
        if (is_double($value)) {
            return self::DOUBLE_PREFIX . $value;
        }
        if (is_bool($value)) {
            return self::BOOL_PREFIX . ($value ? self::TRUE : self::FALSE);
        }
        if (is_null($value)) {
            return self::NULL_PREFIX;
        }

        throw new \InvalidArgumentException(
            'Only strings, integers and arrays containing those values are allowed, ' . gettype($value) . ' given.'
        );
    }

    /**
     * @return string|int|mixed[]|bool|null
     */
    private function unpack(?string $value)
    {
        if ($value === null) {
            return null;
        }
        if ($value === self::NULL_PREFIX) {
            return null;
        }
        if (strncmp($value, self::STRING_PREFIX, strlen(self::STRING_PREFIX)) === 0) {
            return str_replace(self::STRING_PREFIX, '', $value);
        }
        if (strncmp($value, self::BOOL_PREFIX, strlen(self::BOOL_PREFIX)) === 0) {
            return (str_replace(self::BOOL_PREFIX, '', $value) === self::TRUE);
        }
        if (strncmp($value, self::ARRAY_PREFIX, strlen(self::ARRAY_PREFIX)) === 0) {
            $value = substr($value, strlen(self::ARRAY_PREFIX));
            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            array_walk_recursive($value, function (&$item): void {
                $item = $this->unpack($item);
            });

            return $value;
        }
        if (strncmp($value, self::STD_PREFIX, strlen(self::STD_PREFIX)) === 0) {
            // cut the first occurence of the prefix
            $value = substr($value, strlen(self::STD_PREFIX));
            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            array_walk_recursive($value, function (&$item): void {
                $item = $this->unpack($item);
            });

            return (object) $value;
        }
        if (strncmp($value, self::INT_PREFIX, strlen(self::INT_PREFIX)) === 0) {
            return (int) str_replace(self::INT_PREFIX, '', $value);
        }
        if (strncmp($value, self::FLOAT_PREFIX, strlen(self::FLOAT_PREFIX)) === 0) {
            return (float) str_replace(self::FLOAT_PREFIX, '', $value);
        }
        if (strncmp($value, self::DOUBLE_PREFIX, strlen(self::DOUBLE_PREFIX)) === 0) {
            return (float) str_replace(self::DOUBLE_PREFIX, '', $value);
        }
        return null;
    }

    public function isLocked(): bool
    {
        $lock_until = $this->adaptor->get($this->request->getContainerKey(), self::LOCK_UNTIL);
        $lock_until = $lock_until === null ? null : (float) $lock_until;

        return $lock_until !== null && $lock_until > microtime(true);
    }

    public function lock(float $seconds): void
    {
        if ($seconds > 300.0 || $seconds < 0.0) {
            throw new \InvalidArgumentException('Locking for more than 5 minutes is not allowed.');
        }
        $lock_until = (string) (microtime(true) + $seconds);
        $this->adaptor->set($this->request->getContainerKey(), self::LOCK_UNTIL, $lock_until, 300);
    }

    public function has(string $key): bool
    {
        if ($this->isLocked()) {
            return false;
        }

        return $this->adaptor->has($this->request->getContainerKey(), $key);
    }

    /**
     * @return string|int|mixed[]|bool|null
     */
    public function get(string $key)
    {
        if ($this->isLocked()) {
            return null;
        }
        return $this->unpack(
            $this->adaptor->get($this->request->getContainerKey(), $key)
        );
    }

    /**
     * @param string|int|mixed[]|bool|null $value
     */
    public function set(string $key, $value): void
    {
        if ($this->isLocked()) {
            return;
        }
        $this->adaptor->set(
            $this->request->getContainerKey(),
            $key,
            $this->pack($value),
            $this->config->getDefaultTTL()
        );
    }

    public function delete(string $key): void
    {
        if ($this->isLocked()) {
            return;
        }
        $this->adaptor->delete($this->request->getContainerKey(), $key);
    }

    public function flush(): void
    {
        $this->adaptor->flushContainer($this->request->getContainerKey());
    }

    public function getAdaptorName(): string
    {
        return $this->config->getAdaptorName();
    }

    public function getContainerName(): string
    {
        return $this->request->getContainerKey();
    }
}

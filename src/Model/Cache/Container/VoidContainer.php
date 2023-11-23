<?php

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

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
final class VoidContainer implements Container
{
    /**
     * @var \srag\Plugins\Opencast\Model\Cache\Container\Request
     */
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function lock(float $seconds): void
    {
    }

    public function isLocked(): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function get(string $key)
    {
        return null;
    }

    /**
     * @param mixed[]|bool|int|string|null $value
     */
    public function set(string $key, $value): void
    {
        // To have a proper InvalidArgumentException, we loop through the array and convert a TypeError to an InvalidArgumentException
        try {
            if (is_array($value)) {
                array_walk_recursive($value, function (&$item) use ($key): void {
                    $this->set($key, $item);
                });
            }
        } catch (\TypeError $exception) {
            throw new \InvalidArgumentException('Only strings, integers and arrays containing those values are allowed, ' . gettype($value) . ' given.', $exception->getCode(), $exception);
        }
    }

    public function delete(string $key): void
    {
    }

    public function flush(): void
    {
    }

    public function getAdaptorName(): string
    {
        return 'null';
    }

    public function getContainerName(): string
    {
        return $this->request->getContainerKey();
    }
}

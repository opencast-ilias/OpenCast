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
 */

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI;

use ILIAS\Data\URI;
use ILIAS\HTTP\Services;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
trait MakeURI
{
    protected function toURI(string|URI $target, ?Services $http = null): URI
    {
        if ($target instanceof URI) {
            return $target;
        }
        global $DIC;
        $http ??= $DIC->http();
        $url = parse_url($target);

        // Ensure path does not get lost, in case there are more to path like "/ilias_10/ilias.php"
        $base_path = $http->request()->getUri()->getPath();
        if (
            $base_path !== $url['path'] &&
            str_contains($base_path, $url['path'])
        ) {
            $url['path'] = $base_path;
        }

        return new URI(
            (string) $http
                ->request()
                ->getUri()
                ->withQuery($url["query"] ?? "")
                ->withPath($url["path"] ?? "")
        );
    }
}

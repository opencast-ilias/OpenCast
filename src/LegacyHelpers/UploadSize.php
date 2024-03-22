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

namespace srag\Plugins\Opencast\LegacyHelpers;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @deprecated
 */
class UploadSize
{
    public static function getUploadSizeLimitBytes(): int
    {
        $limit = 2 * 1024 * 1024;
        if (class_exists('\ilFileUtils') && method_exists('\ilFileUtils', 'getUploadSizeLimitBytes')) {
            $limit = (int) \ilFileUtils::getUploadSizeLimitBytes();
        }
        if (class_exists('ilUtil') && method_exists('ilUtil', 'getUploadSizeLimitBytes')) {
            $limit = (int) \ilUtil::getUploadSizeLimitBytes();
        }
        return $limit;
    }
}

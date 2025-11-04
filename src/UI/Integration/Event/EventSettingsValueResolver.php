<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Event;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
interface EventSettingsValueResolver
{

    public const MD_OWNER = 'owner';
    public const MD_LOCATION = 'location';
    public const MD_DESCRITION = 'description';
    public const MD_PRESENTER = 'presenter';

    public function resolve(EventSettings $setting): mixed;

}

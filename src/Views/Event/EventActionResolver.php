<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Views\Series;

use srag\Plugins\Opencast\UI\Integration\Event\EventActionTargetResolver;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionTarget;
use ILIAS\HTTP\Services;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionParameters;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionParameter;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\UI\Integration\Action;
use srag\Plugins\Opencast\Util\Locale\Translator;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class EventActionResolver extends BaseActionResolver implements EventActionTargetResolver
{
    public function __construct(
        Translator $translator,
        Services $http,
        \ilCtrlInterface $ctrl
    ) {
        parent::__construct($translator, $http, $ctrl);
    }

    protected function setParametersForOtherClasses(?EventActionParameters $parameter): void
    {
        // Set Parameters for other classes
        $this->ctrl->setParameterByClass(
            \xoctEventGUI::class,
            EventActionParameter::EVENT_ID->value,
            $parameter?->get(EventActionParameter::EVENT_ID)
        );

        $this->ctrl->setParameterByClass(
            \xoctPlayerGUI::class,
            EventActionParameter::EVENT_ID->value,
            $parameter?->get(EventActionParameter::EVENT_ID)
        );

        $this->ctrl->setParameterByClass(
            \xoctGrantPermissionGUI::class,
            EventActionParameter::EVENT_ID->value,
            $parameter?->get(EventActionParameter::EVENT_ID)
        );
        $this->ctrl->setParameterByClass(
            \xoctChangeOwnerGUI::class,
            EventActionParameter::EVENT_ID->value,
            $parameter?->get(EventActionParameter::EVENT_ID)
        );
    }

    public function resolve(EventActionTarget $target, ?EventActionParameters $parameter = null): ?Action
    {
        // no actions without event
        $event = $parameter?->get(EventActionParameter::EVENT_OBJECT);
        if (!$event instanceof Event) {
            return null;
        }

        // no actions for non-published events
        if (!in_array($event->getProcessingState(), [
            Event::STATE_SUCCEEDED,
            Event::STATE_NOT_PUBLISHED,
            Event::STATE_READY_FOR_CUTTING,
            Event::STATE_OFFLINE,
            Event::STATE_FAILED,
            Event::STATE_SCHEDULED,
            Event::STATE_SCHEDULED_OFFLINE,
            Event::STATE_LIVE_RUNNING,
            Event::STATE_LIVE_SCHEDULED,
            Event::STATE_LIVE_OFFLINE,
        ], true)) {
            return null;
        }

        $this->setParametersForOtherClasses($parameter);

        switch ($target) {
            case EventActionTarget::EDIT_OWNER:
                return $this->build(
                    $this->translator->translate('event_edit_owner'),
                    \xoctChangeOwnerGUI::class,
                    \xoctChangeOwnerGUI::CMD_STANDARD
                );

            case EventActionTarget::GRANT_ACCESS:
                return $this->build(
                    $this->translator->translate('event_invite_others'),
                    \xoctGrantPermissionGUI::class,
                    \xoctGrantPermissionGUI::CMD_STANDARD
                );

            case EventActionTarget::CUT:
                return $this->build(
                    $this->translator->translate('event_cut'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_CUT
                );

            case EventActionTarget::SET_ONLINE:
                return $this->build(
                    $this->translator->translate('event_set_online'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_SET_ONLINE
                );

            case EventActionTarget::SET_OFFLINE:
                return $this->build(
                    $this->translator->translate('event_set_offline'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_SET_OFFLINE
                );

            case EventActionTarget::DELETE:
                return $this->build(
                    $this->translator->translate('event_delete'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_CONFIRM
                );

            case EventActionTarget::EDIT_METADATA:
                if ($event->isScheduled()) {
                    return $this->build(
                        $this->translator->translate('event_edit_date'),
                        \xoctEventGUI::class,
                        \xoctEventGUI::CMD_EDIT_SCHEDULED
                    );
                }

                return $this->build(
                    $this->translator->translate('event_edit'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_EDIT
                );

            case EventActionTarget::PLAY:
                return $this->build(
                    $this->translator->translate('event_player'),
                    \xoctPlayerGUI::class,
                    \xoctPlayerGUI::CMD_STREAM_VIDEO
                );

            case EventActionTarget::DOWNLOAD:
                return $this->build(
                    $this->translator->translate('event_download'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_DOWNLOAD
                );
            case EventActionTarget::REPUBLISH: // TODO Modals needed, class.xoctEventRenderer.php:673
            case EventActionTarget::REPORT_QUALITY_ISSUE: // TODO Modals needed, class.xoctEventRenderer.php:673
            case EventActionTarget::START_WORKFLOW: // TODO Modals needed, class.xoctEventRenderer.php:673
            default:
                return null;
        }

        return null;
    }

    public function supports(EventActionTarget $target, EventActionParameters $parameters): bool
    {
        // no actions without event
        $event = $parameters?->get(EventActionParameter::EVENT_OBJECT);
        if (!$event instanceof Event) {
            return false;
        }

        switch ($target) {
            case EventActionTarget::EDIT_OWNER:
                return \ilObjOpenCastAccess::checkAction(
                    \ilObjOpenCastAccess::ACTION_EDIT_OWNER,
                    $event
                );

            case EventActionTarget::GRANT_ACCESS:
                return \ilObjOpenCastAccess::checkAction(
                    \ilObjOpenCastAccess::ACTION_SHARE_EVENT,
                    $event
                );

            case EventActionTarget::CUT:
                return \ilObjOpenCastAccess::checkAction(
                    \ilObjOpenCastAccess::ACTION_CUT,
                    $event
                );

            case EventActionTarget::REPUBLISH:
            default:
                return false; // TODO Case not implemented yet since we miss the possibility to call modals and signals. see public/Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Event/class.xoctEventRenderer.php:673

            case EventActionTarget::SET_ONLINE:
            case EventActionTarget::SET_OFFLINE:
                return \ilObjOpenCastAccess::checkAction(
                    \ilObjOpenCastAccess::ACTION_SET_ONLINE_OFFLINE,
                    $event
                )
                    && $event->getXoctEventAdditions()->getIsOnline() === ($target === EventActionTarget::SET_OFFLINE);

            case EventActionTarget::DELETE:
                return \ilObjOpenCastAccess::checkAction(
                    \ilObjOpenCastAccess::ACTION_DELETE_EVENT,
                    $event
                );

            case EventActionTarget::EDIT_METADATA:
                return \ilObjOpenCastAccess::checkAction(
                    \ilObjOpenCastAccess::ACTION_EDIT_EVENT,
                    $event
                );

            case EventActionTarget::REPORT_QUALITY_ISSUE:
                return \ilObjOpenCastAccess::checkAction(
                    \ilObjOpenCastAccess::ACTION_REPORT_QUALITY_PROBLEM,
                    $event
                );

            case EventActionTarget::PLAY:
            case EventActionTarget::DOWNLOAD:
                return $event->getProcessingState() === Event::STATE_SUCCEEDED;
        }
    }

    public function resolveParameter(EventActionParameter $parameter): mixed
    {
        return $this->http->request()->getQueryParams()[$parameter->value] ?? null;
    }
}

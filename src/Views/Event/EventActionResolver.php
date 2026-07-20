<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Views\Series;

use srag\Plugins\Opencast\UI\Integration\Event\EventActionTargetResolver;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionTarget;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionParameters;
use srag\Plugins\Opencast\UI\Integration\Event\EventActionParameter;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\UI\Integration\Action;
use srag\Plugins\Opencast\UI\Integration\ActionType;
use srag\Plugins\Opencast\UI\Integration\Event\EventSettingsValueResolver;
use srag\Plugins\Opencast\UI\Integration\Event\EventSettings;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class EventActionResolver extends BaseActionResolver implements EventActionTargetResolver
{
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

    public function resolve(
        EventActionTarget $target,
        ?EventActionParameters $parameter = null,
        ?EventSettingsValueResolver $settings = null
    ): ?Action {
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
                    \xoctChangeOwnerGUI::CMD_STANDARD,
                    ActionType::INTERNAL_LINK
                );

            case EventActionTarget::GRANT_ACCESS:
                return $this->build(
                    $this->translator->translate('event_invite_others'),
                    \xoctGrantPermissionGUI::class,
                    \xoctGrantPermissionGUI::CMD_STANDARD,
                    ActionType::INTERNAL_LINK
                );

            case EventActionTarget::CUT:
                return $this->build(
                    $this->translator->translate('event_cut'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_CUT,
                    ActionType::EXTERNAL_LINK
                );

            case EventActionTarget::SET_ONLINE:
                return $this->build(
                    $this->translator->translate('event_set_online'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_SET_ONLINE,
                    ActionType::INTERNAL_LINK
                );

            case EventActionTarget::SET_OFFLINE:
                return $this->build(
                    $this->translator->translate('event_set_offline'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_SET_OFFLINE,
                    ActionType::INTERNAL_LINK
                );

            case EventActionTarget::DELETE:
                return $this->build(
                    $this->translator->translate('event_delete'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_CONFIRM,
                    ActionType::INTERNAL_LINK
                );

            case EventActionTarget::EDIT_METADATA:
                if ($event->isScheduled()) {
                    // A scheduled event always goes to the scheduled form: it is the only
                    // one that keeps date and recording station out of the metadata section
                    // and disables the scheduling section unless all metadata is editable.
                    // The setting merely picks the label (see #545).
                    return $this->build(
                        $this->translator->translate(
                            (bool) $settings?->resolve(EventSettings::EDIT_ALL_METADATA)
                                ? 'event_edit_date'
                                : 'event_edit'
                        ),
                        \xoctEventGUI::class,
                        \xoctEventGUI::CMD_EDIT_SCHEDULED,
                        ActionType::INTERNAL_LINK
                    );
                }

                return $this->build(
                    $this->translator->translate('event_edit'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_EDIT,
                    ActionType::INTERNAL_LINK
                );

            case EventActionTarget::PLAY:
                $open_as_modal = (bool) $settings?->resolve(EventSettings::PLAYER_AS_MODAL);
                return $this->build(
                    $this->translator->translate('event_player'),
                    \xoctPlayerGUI::class,
                    $open_as_modal
                        ? \xoctPlayerGUI::CMD_STREAM_VIDEO_MODAL
                        : \xoctPlayerGUI::CMD_STREAM_VIDEO,
                    $open_as_modal
                        ? ActionType::ASYNC_MODAL
                        : ActionType::EXTERNAL_LINK
                );

            case EventActionTarget::DOWNLOAD:
                return $this->build(
                    $this->translator->translate('event_download'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_SELECT_DOWNLOAD,
                    ActionType::ASYNC_MODAL
                );
            case EventActionTarget::REPORT_QUALITY_ISSUE:
                return $this->build(
                    $this->translator->translate('event_report_quality_problem'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_REPORT_QUALITY_MODAL,
                    ActionType::ASYNC_MODAL
                );
            case EventActionTarget::REPORT_DATE_MODIFICATION:
                return $this->build(
                    $this->translator->translate('event_report_date_modification'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_REPORT_DATE_MODAL,
                    ActionType::ASYNC_MODAL
                );
            case EventActionTarget::START_WORKFLOW:
                return $this->build(
                    $this->translator->translate('event_startworkflow'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_START_WORKFLOW_MODAL,
                    ActionType::ASYNC_MODAL
                );
            case EventActionTarget::ANNOTATE:
                return $this->build(
                    $this->translator->translate('event_annotate'),
                    \xoctEventGUI::class,
                    \xoctEventGUI::CMD_ANNOTATE,
                    ActionType::EXTERNAL_LINK
                );
            case EventActionTarget::REPUBLISH: // TODO Modals needed, class.xoctEventRenderer.php:673
            default:
                return null;
        }

        return null;
    }

    private function isEventAccessible(Event $event, ?EventSettingsValueResolver $settings = null): bool
    {
        $processing_state = $event->getProcessingState();

        $accessible = false;

        if ($processing_state === Event::STATE_SUCCEEDED) {
            $accessible = true;
        }

        if ($event->isLiveEvent()) {
            if ($processing_state === Event::STATE_LIVE_RUNNING) {
                $accessible = true;
            }
            if ($processing_state === Event::STATE_LIVE_SCHEDULED) {
                $scheduling = $event->getScheduling();
                if ($scheduling === null || $scheduling->getStart() === null || $scheduling->getEnd() === null) {
                    return false;
                }
                $start = $scheduling->getStart()->getTimestamp();
                $accessible_before_start = (int) (($settings?->resolve(
                        EventSettings::START_X_MINUTES_BEFORE_LIVE
                    ) ?? 0)) * 60;
                $accessible_from = $start - $accessible_before_start;
                $accessible_to = $scheduling->getEnd()->getTimestamp();
                $now = time();
                $accessible = ($accessible_from <= $now) && ($accessible_to >= $now);
            }
        }

        return $accessible;
    }

    public function supports(
        EventActionTarget $target,
        EventActionParameters $parameters,
        ?EventSettingsValueResolver $settings = null
    ): bool {
        // no actions without event
        $event = $parameters?->get(EventActionParameter::EVENT_OBJECT);
        if (!$event instanceof Event) {
            return false;
        }

        switch ($target) {
            case EventActionTarget::EDIT_OWNER:
                return $settings?->resolve(EventSettings::SHOW_OWNER)
                    && \ilObjOpenCastAccess::checkAction(
                        \ilObjOpenCastAccess::ACTION_EDIT_OWNER,
                        $event
                    );

            case EventActionTarget::GRANT_ACCESS:
                return $settings?->resolve(EventSettings::SHOW_OWNER)
                    && \ilObjOpenCastAccess::checkAction(
                        \ilObjOpenCastAccess::ACTION_SHARE_EVENT,
                        $event
                    );

            case EventActionTarget::CUT:
                return \ilObjOpenCastAccess::checkAction(
                    \ilObjOpenCastAccess::ACTION_CUT,
                    $event
                );

            case EventActionTarget::START_WORKFLOW:
                return \ilObjOpenCastAccess::checkAction(
                    \ilObjOpenCastAccess::ACTION_EDIT_EVENT,
                    $event
                )
                    && !$event->isScheduled()
                    && !$event->isRunning();

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

            case EventActionTarget::REPORT_DATE_MODIFICATION:
                return \ilObjOpenCastAccess::checkAction(
                    \ilObjOpenCastAccess::ACTION_REPORT_DATE_CHANGE,
                    $event
                );

            case EventActionTarget::PLAY:
                return $this->isEventAccessible($event, $settings);
            case EventActionTarget::DOWNLOAD:
                return \ilObjOpenCastAccess::checkAction(
                    \ilObjOpenCastAccess::ACTION_DOWNLOAD_EVENT,
                    $event
                );
            case EventActionTarget::ANNOTATE:
                return (bool) $settings?->resolve(EventSettings::USE_ANNOTATIONS)
                    && $event->getProcessingState() === Event::STATE_SUCCEEDED
                    && (bool) $event->publications()->getAnnotationPublication();
            default:
                return false;

        }
    }

    public function resolveParameter(EventActionParameter $parameter): mixed
    {
        return $this->http->request()->getQueryParams()[$parameter->value] ?? null;
    }

    public function resolveBestForEventStatus(string $status, EventActionParameters $parameter): ?Action
    {
        $mapped_action = match ($status) {
            Event::STATE_OFFLINE => EventActionTarget::SET_ONLINE,
            Event::STATE_READY_FOR_CUTTING => EventActionTarget::CUT,
            default => null
        };

        if ($mapped_action === null) {
            return null;
        }

        if (!$this->supports($mapped_action, $parameter)) {
            return null;
        }

        return $this->resolve($mapped_action, $parameter);
    }

}

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

namespace srag\Plugins\Opencast\UI\Integration\Event;

use ILIAS\UI\Renderer;
use ILIAS\UI\Factory as UIFactory;
use srag\Plugins\Opencast\Container\Container;
use srag\Plugins\Opencast\Model\Event\EventAPIRepository;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationSubUsageRepository;
use ILIAS\Data\URI;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\UI\MakeURI;
use ILIAS\UI\Component\Modal\Lightbox;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Publications
{
    use MakeURI;

    private EventAPIRepository $event_repository;
    private Renderer $ui_renderer;
    private PublicationUsageRepository $publication_repository;
    private PublicationSubUsageRepository $publication_sub_repository;

    public function __construct(
        private UIFactory $ui_factory,
        private Container $container,
        private EventActionTargetResolver $resolver,
        private EventSettingsValueResolver $settings_resolver
    ) {
        $this->event_repository = $this->container->get(EventAPIRepository::class);
        $this->ui_renderer = $this->container->ilias()->ui()->renderer();
        $this->publication_repository = new PublicationUsageRepository();
        $this->publication_sub_repository = new PublicationSubUsageRepository();
    }

    /**
     * @see https://github.com/opencast-ilias/OpenCast/issues/477
     */
    protected function getOnCloseAction(): \Closure
    {
        return static fn($id) => 'document.getElementById("' . $id . '").addEventListener("click", function() { 
                                    // find parent dialog and close it after click
                                    let parent = this.closest("dialog");
                                    if(parent) {
                                        parent.close();
                                    }
                            })';
    }

    public function asListInModal(
        string $event_id,
        string|URI $target,
    ): Lightbox {
        return
            $this
                ->ui_factory
                ->modal()
                ->lightbox(
                    $this
                        ->ui_factory
                        ->modal()
                        ->lightboxTextPage(
                            $this
                                ->ui_renderer
                                ->render(
                                    $this->asList(
                                        $event_id,
                                        $target
                                    )
                                ),
                            $this
                                ->container
                                ->translator()
                                ->translate('publication_usage_type_download')
                        )
                );
    }

    public function asList(
        string $event_id,
        string|URI $target,
    ): array {
        $icon = $this->ui_factory->symbol()->glyph()->down();
        $target = $this->toURI($target);
        $event = $this->event_repository->find($event_id);
        $categorized_download_dtos = $event->publications()->getDownloadDtos(false);
        $elements = [];

        foreach ($categorized_download_dtos as $usage_type => $content) {
            foreach ($content as $usage_id => $download_dtos) {
                $download_pub_usage = null;
                if ($usage_type === PublicationUsage::USAGE_TYPE_ORG) {
                    $download_pub_usage = $this->publication_repository->getUsage($usage_id);
                    $display_name = $this->publication_repository->getDisplayName($usage_id);
                } else {
                    $download_pub_usage = $this->publication_sub_repository->convertSingleSubToUsage($usage_id);
                    $display_name = $this->publication_sub_repository->getDisplayName($usage_id);
                }

                if (is_null($download_pub_usage)) {
                    continue;
                }

                if (empty($display_name)) {
                    $display_name = $this->container->translator()->translate('publication_download');
                }

                // Reset Parameters
                $target = $target
                    ->withParameter('pub_id', null)
                    ->withParameter('usage_type', null)
                    ->withParameter('usage_id', null);

                $multi = $download_pub_usage->isAllowMultiple();
                if ($multi) {
                    // Group the individual downloads of this (sub-)usage under its
                    // configured display name as a section title. Without it the entries
                    // only carried their bare resolution/flavor, so every download looked
                    // like an unnamed "Download" and the configured name was lost (#546).
                    $elements[] = $this->ui_factory->divider()->horizontal()->withLabel($display_name);
                    foreach ($download_dtos as $dto) {
                        $elements[] = $this->ui_factory->link()->bulky(
                            $icon,
                            $dto->getResolution(),
                            $target->withParameter('pub_id', $dto->getPublicationId())
                        )->withAdditionalOnLoadCode(
                            $this->getOnCloseAction()
                        );
                        $elements[] = $this->ui_factory->divider()->horizontal();
                    }
                } else {
                    $usage_type = $download_pub_usage->isSub() ? 'sub' : 'org';
                    $elements[] = $this->ui_factory->link()->bulky(
                        $icon,
                        $display_name,
                        $target
                            ->withParameter('usage_type', $usage_type)
                            ->withParameter('usage_id', $download_pub_usage->getSubId())
                    )->withAdditionalOnLoadCode(
                        $this->getOnCloseAction()
                    );
                    $elements[] = $this->ui_factory->divider()->horizontal();
                }
            }
        }

        // Remove last divider
        array_pop($elements);

        $alignment[] = $this
            ->ui_factory
            ->layout()
            ->alignment()
            ->horizontal()
            ->dynamicallyDistributed(
                $this->ui_factory->legacy('&nbsp;'),
                $this->ui_factory->legacy(
                    $this->ui_renderer->render($elements)
                ),
                $this->ui_factory->legacy('&nbsp;'),
            );

        return $alignment;
    }
}

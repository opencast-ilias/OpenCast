<?php

namespace srag\Plugins\Opencast\Model\Metadata\Helper;

use ILIAS\DI\Container;
use ilObjOpenCast;
use InvalidArgumentException;
use srag\Plugins\Opencast\Model\Metadata\Config\MDPrefillOption;
use xoctLog;

class MDPrefiller
{
    /**
     * @var Container
     */
    private $dic;

    /**
     * @param Container $dic
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }


    public function getPrefillValue(MDPrefillOption $prefill_type) : ?string
    {
        switch ($prefill_type->getValue()) {
            case MDPrefillOption::T_COURSE_TITLE:
                $ref_id = $this->dic->http()->request()->getQueryParams()['ref_id'];
                try {
                    $course_or_group = ilObjOpenCast::_getParentCourseOrGroup($ref_id);
                } catch (InvalidArgumentException $e) {
                    xoctLog::getInstance()->writeWarning('couldn\'t fetch parent course or group for prefilling metadata field');
                    return null;
                }
                return $course_or_group->getTitle();
            case MDPrefillOption::T_USERNAME_OF_CREATOR:
                return $this->dic->user()->getPublicName();
            case MDPrefillOption::T_NONE:
            default:
                return null;
        }
    }
}
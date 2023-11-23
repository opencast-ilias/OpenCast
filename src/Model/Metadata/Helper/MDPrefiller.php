<?php

namespace srag\Plugins\Opencast\Model\Metadata\Helper;

use ILIAS\DI\Container;
use ilObjOpenCast;
use InvalidArgumentException;
use xoctLog;

class MDPrefiller
{
    /**
     * @var Container
     */
    private $dic;

    private $course;
    private $user;
    private $metadata;

    public const PLACEHOLDER_REGEX = "/\[[^\]]*\]/";

    public const USER_PLACEHOLDER_FLAG = 'USER';
    public static $user_properties = [
        'firstname' => 'getFirstname',
        'lastname' => 'getLastname',
        'fullname' => 'getFullname',
        'u_title' => 'getUTitle',
        'institution' => 'getInstitution',
        'department' => 'getDepartment',
        'email' => 'getEmail',
        'language' => 'getLanguage',
        'public_name' => 'getPublicName',
        'login' => 'getLogin',
    ];

    public const COURSE_PLACEHOLDER_FLAG = 'COURSE';
    public static $course_properties = [
        'id' => 'getId',
        'ref_id' => 'getRefId',
        'type' => 'getType',
        'presentation_title' => 'getPresentationTitle',
        'title' => 'getTitle',
        'untranslated_title' => 'getUntranslatedTitle',
        'description' => 'getDescription',
        'long_description' => 'getLongDescription',
        'owner_id' => 'getOwner',
        'owner_name' => 'getOwnerName',
        'create_date' => 'getCreateDate',
        'last_update_date' => 'getLastUpdateDate',
    ];
    public const MD_PLACEHOLDER_FLAG = 'META';
    public static $md_properties = [
        'keywords' => 'getKeywordIds',
        'languages' => 'getLanguageIds',
    ];

    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->course = $this->getCourseArray();
        $this->user = $this->getUserArray();
        $this->metadata = $this->getMetadataArray();
    }

    /**
     * Extracts and returns the defined metadata values to be used by placeholders
     *
     * @return array
     */
    public function getMetadataArray(): array
    {
        $metadata = [];
        $ref_id = $this->dic->http()->request()->getQueryParams()['ref_id'];
        try {
            $course_or_group = ilObjOpenCast::_getParentCourseOrGroup($ref_id);
            if (!empty($course_or_group)) {
                $md = new \ilMD($course_or_group->getId(), 0, $course_or_group->getType());
                $md_general = $md->getGeneral();
                if (empty($md_general)) {
                    return [];
                }
                foreach (self::$md_properties as $prop_name => $method_name) {
                    $sub_method_name = str_replace('Ids', '', $method_name);
                    if (method_exists($md_general, $method_name) &&
                        method_exists($md_general, $sub_method_name)) {
                        $ids = $md_general->$method_name();
                        foreach ($ids as $index => $id) {
                            $md = $md_general->$sub_method_name($id);
                            $value = $md->$sub_method_name();
                            $metadata[$prop_name][] = $value;
                        }
                    }
                }
            }
        } catch (InvalidArgumentException $e) {
            xoctLog::getInstance()->writeWarning(
                'couldn\'t fetch parent course or group for prefilling metadata field'
            );
            return [];
        }

        return $metadata;
    }

    /**
     * Extracts and returns the defined course values to be used by placeholders
     *
     * @return array
     */
    public function getCourseArray(): array
    {
        $course = [];
        $ref_id = $this->dic->http()->request()->getQueryParams()['ref_id'];
        try {
            $course_or_group = ilObjOpenCast::_getParentCourseOrGroup($ref_id);
            foreach (self::$course_properties as $prop_name => $method_name) {
                if (method_exists($course_or_group, $method_name)) {
                    $course[$prop_name] = $course_or_group->$method_name();
                }
            }
        } catch (InvalidArgumentException $e) {
            xoctLog::getInstance()->writeWarning(
                'couldn\'t fetch parent course or group for prefilling metadata field'
            );
            return [];
        }

        return $course;
    }

    /**
     * Extracts and returns the defined user values to be used by placeholders
     *
     * @return array
     */
    public function getUserArray(): array
    {
        $user = [];
        foreach (self::$user_properties as $prop_name => $method_name) {
            if (method_exists($this->dic->user(), $method_name)) {
                $user[$prop_name] = $this->dic->user()->$method_name();
            }
        }
        return $user;
    }

    /**
     * Looks for and replaces the placeholders in the prefill text.
     *
     * @param string $prefill_raw_text the raw prefill text
     *
     * @return string replaced prefill text
     */
    public function getReplacedPrefill(string $prefill_raw_text): string
    {
        preg_match_all(self::PLACEHOLDER_REGEX, $prefill_raw_text, $matches);

        $prefilled_replaced_text = $prefill_raw_text;
        if (isset($matches[0]) && !empty($matches[0])) {
            foreach ($matches[0] as $match) {
                $replacement = null;
                preg_match('#\[(.*?)\]#', $match, $placeholder);
                if (empty($placeholder[1])) {
                    continue;
                }
                $splitted = explode('.', $placeholder[1]);
                // For those combonation of key value pairs containing 2 parts.
                if (count($splitted) === 2 &&
                    ($splitted[0] === self::COURSE_PLACEHOLDER_FLAG || $splitted[0] === self::USER_PLACEHOLDER_FLAG)) {
                    if ($splitted[0] === self::COURSE_PLACEHOLDER_FLAG && isset($this->course[strtolower($splitted[1])])) {
                        $replacement = $this->course[strtolower($splitted[1])];
                    } else if ($splitted[0] === self::USER_PLACEHOLDER_FLAG && isset($this->user[strtolower($splitted[1])])) {
                        $replacement = $this->user[strtolower($splitted[1])];
                    }
                } else if (count($splitted) === 3 && $splitted[0] === self::MD_PLACEHOLDER_FLAG) {
                    // For the combination of 3 parts consisting key value and sub-value.
                    // In case of these form (3 parts), we need to pass empty string as replacement,
                    // becasue the case might not be concrete and might not apply everywhere!
                    $index = intval($splitted[2]) - 1;
                    if ($index >= 0 && isset($this->metadata[strtolower($splitted[1])]) &&
                        isset($this->metadata[strtolower($splitted[1])][$index])) {
                        $replacement = $this->metadata[strtolower($splitted[1])][$index];
                    } else {
                        $replacement = '';
                    }
                }
                if (!is_null($replacement)) {
                    $prefilled_replaced_text = str_replace($match, $replacement, $prefilled_replaced_text);
                }
            }
        }

        return $prefilled_replaced_text;
    }
}

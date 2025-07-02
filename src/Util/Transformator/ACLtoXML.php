<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Transformator;

use ilXmlWriter;
use srag\Plugins\Opencast\Model\ACL\ACL;

/**
 * Class ACLtoXML
 *
 * @package srag\Plugins\Opencast\Util\Transformator
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ACLtoXML
{
    /**
     * ACLtoXML constructor.
     */
    public function __construct(protected ACL $acl)
    {
    }

    public function getXML(string $media_package_id = 'mediapackage-1'): string
    {
        $xml_writer = new ilXMLWriter();
        $xml_writer->xmlSetGenCmt("Opencast ILIAS Plugin Access Policy Generator");
        $xml_writer->xmlHeader();
        $xml_writer->xmlStartTag('Policy', [
            'PolicyId' => $media_package_id,
            'RuleCombiningAlgId' => 'urn:oasis:names:tc:xacml:1.0:rule-combining-algorithm:permit-overrides',
            'Version' => '2.0',
            'xmlns' => 'urn:oasis:names:tc:xacml:2.0:policy:schema:os'
        ]);

        // Add Resource id as target tag.
        $xml_writer->xmlStartTag('Target');
        $xml_writer->xmlStartTag('Resources');
        $xml_writer->xmlStartTag('Resource');
        $xml_writer->xmlStartTag('ResourceMatch', [
            'MatchId' => 'urn:oasis:names:tc:xacml:1.0:function:string-equal'
        ]);

        $xml_writer->xmlElement('AttributeValue', [
            'DataType' => 'http://www.w3.org/2001/XMLSchema#string'
        ], $media_package_id);
        $xml_writer->xmlElement('ResourceAttributeDesignator', [
            'AttributeId' => 'urn:oasis:names:tc:xacml:1.0:resource:resource-id',
            'DataType' => 'http://www.w3.org/2001/XMLSchema#string'
        ]);

        $xml_writer->xmlEndTag('ResourceMatch');
        $xml_writer->xmlEndTag('Resource');
        $xml_writer->xmlEndTag('Resources');
        $xml_writer->xmlEndTag('Target');

        foreach ($this->acl->getEntries() as $acl) {
            if ($acl->isAllow()) {
                $xml_writer->xmlStartTag('Rule', [
                    'RuleId' => $acl->getRole() . '_' . $acl->getAction() . '_permit',
                    'Effect' => 'Permit'
                ]);

                $xml_writer->xmlStartTag('Target');
                $xml_writer->xmlStartTag('Actions');
                $xml_writer->xmlStartTag('Action');
                $xml_writer->xmlStartTag('ActionMatch', [
                    'MatchId' => 'urn:oasis:names:tc:xacml:1.0:function:string-equal'
                ]);

                $xml_writer->xmlElement('AttributeValue', [
                    'DataType' => 'http://www.w3.org/2001/XMLSchema#string'
                ], $acl->getAction());
                $xml_writer->xmlElement('ActionAttributeDesignator', [
                    'AttributeId' => 'urn:oasis:names:tc:xacml:1.0:action:action-id',
                    'DataType' => 'http://www.w3.org/2001/XMLSchema#string'
                ]);

                $xml_writer->xmlEndTag('ActionMatch');
                $xml_writer->xmlEndTag('Action');
                $xml_writer->xmlEndTag('Actions');
                $xml_writer->xmlEndTag('Target');

                $xml_writer->xmlStartTag('Condition');
                $xml_writer->xmlStartTag('Apply', [
                    'FunctionId' => 'urn:oasis:names:tc:xacml:1.0:function:string-is-in'
                ]);

                $xml_writer->xmlElement('AttributeValue', [
                    'DataType' => 'http://www.w3.org/2001/XMLSchema#string'
                ], $acl->getRole());
                $xml_writer->xmlElement('SubjectAttributeDesignator', [
                    'AttributeId' => 'urn:oasis:names:tc:xacml:2.0:subject:role',
                    'DataType' => 'http://www.w3.org/2001/XMLSchema#string'
                ]);

                $xml_writer->xmlEndTag('Apply');
                $xml_writer->xmlEndTag('Condition');

                $xml_writer->xmlEndTag('Rule');
            }
        }

        // Deny rule.
        $xml_writer->xmlElement('Rule', [
            'RuleId' => 'DenyRule',
            'Effect' => 'Deny'
        ]);

        $xml_writer->xmlEndTag('Policy');

        return $xml_writer->xmlDumpMem(false);
    }


    /**
     * Generates a JSON representation of the ACL entries for use in the Opencast Studio Link as upload.acl.
     *
     * The JSON format is a nested object where the keys are role names and the values are arrays of actions.
     * Only entries with an "allow" permission are included.
     *
     * @return string The JSON representation of the ACL entries consumable for the Opencast Studio.
     */
    public function getStudioACLObjectNotation(): string
    {
        $acl_notation_array = [];
        foreach ($this->acl->getEntries() as $entry) {
            $role = $entry->getRole();
            $action = $entry->getAction();
            $allow = $entry->isAllow();
            if ($allow) {
                if (!isset($acl_notation_array[$role])) {
                    $acl_notation_array[$role] = [];
                }
                if (!in_array($action, $acl_notation_array[$role])) {
                    $acl_notation_array[$role][] = $action;
                }
            }
        }
        $acl_notation_string = !empty($acl_notation_array) ? json_encode($acl_notation_array) : '';
        return $acl_notation_string;
    }
}

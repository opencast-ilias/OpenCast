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
    protected ACL $acl;

    /**
     * ACLtoXML constructor.
     */
    public function __construct(ACL $acl)
    {
        $this->acl = $acl;
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
}

<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Transformator;

use ilDateTime;
use ilXmlWriter;
use srag\Plugins\Opencast\Model\Metadata\Metadata;

/**
 * Class MetadataToXML
 * used to upload via ingest nodes
 *
 * @package srag\Plugins\Opencast\Util\Transformator
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class MetadataToXML
{
    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * MetadataToXML constructor.
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    public function getXML(): string
    {
        $xml_writer = new ilXMLWriter();
        $xml_writer->xmlHeader();
        $xml_writer->xmlStartTag('dublincore', [
            'xmlns' => 'http://www.opencastproject.org/xsd/1.0/dublincore/',
            'xmlns:dcterms' => 'http://purl.org/dc/terms/',
            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance'
        ]);
        $xml_writer->xmlElement('dcterms:title', [], $this->metadata->getField('title')->getValue());
        $xml_writer->xmlElement('dcterms:description', [], $this->metadata->getField('description')->getValue());
        $xml_writer->xmlElement('dcterms:isPartOf', [], $this->metadata->getField('isPartOf')->getValue());
        $xml_writer->xmlElement('dcterms:source', [], $this->metadata->getField('source')->getValue());
        $creator = $this->metadata->getField('creator')->getValue();
        if (!empty($creator)) {
            $creator = implode(',', $creator);
        }
        $xml_writer->xmlElement('dcterms:creator', [], $creator);
        $xml_writer->xmlElement('dcterms:spatial', [], $this->metadata->getField('location')->getValue());
        $xml_writer->xmlElement('dcterms:rightsHolder', [], $this->metadata->getField('rightsHolder')->getValue());

        $start_end_string_iso = (new ilDateTime(
            strtotime(
                $this->metadata->getField('startDate')->getValueFormatted() . ' ' . $this->metadata->getField(
                    'startTime'
                )->getValueFormatted()
            ),
            IL_CAL_UNIX
        )
        )->get(IL_CAL_FKT_DATE, 'Y-m-d\TH:i:s.u\Z');
        $xml_writer->xmlElement('dcterms:temporal', [
            'xsi:type' => 'dcterms:Period'
        ], 'start=' . $start_end_string_iso . '; ' . 'end=' . $start_end_string_iso . '; scheme=W3C-DTF;');

        $xml_writer->xmlElement(
            'dcterms:created',
            [],
            (new ilDateTime(time(), IL_CAL_UNIX))
                ->get(IL_CAL_FKT_DATE, 'Y-m-d\TH:i:s.u\Z', 'UTC')
        );

        $xml_writer->xmlEndTag('dublincore');
        return $xml_writer->xmlStr;
    }
}

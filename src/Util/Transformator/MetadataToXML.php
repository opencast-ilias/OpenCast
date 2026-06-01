<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Transformator;

use ilDateTime;
use ilXmlWriter;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use xoctException;

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
     * MetadataToXML constructor.
     */
    public function __construct(protected Metadata $metadata)
    {
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

        // Get start date and time with fallback to current date/time if not provided
        try {
            $start_date = $this->metadata->getField('startDate')->getValueFormatted();
        } catch (xoctException $e) {
            // If startDate field doesn't exist, use current date
            $start_date = date('Y-m-d');
        }
        
        try {
            $start_time = $this->metadata->getField('startTime')->getValueFormatted();
        } catch (xoctException $e) {
            // If startTime field doesn't exist, use current time or 00:00:00 as default
            $start_time = '00:00:00';
        }

        $start_end_string_iso = (new ilDateTime(
            strtotime($start_date . ' ' . $start_time),
            IL_CAL_UNIX
        )
        )->get(IL_CAL_FKT_DATE, 'Y-m-d\TH:i:s.u\Z', 'GMT');
        $xml_writer->xmlElement('dcterms:temporal', [
            'xsi:type' => 'dcterms:Period'
        ], 'start=' . $start_end_string_iso . '; ' . 'end=' . $start_end_string_iso . '; scheme=W3C-DTF;');

        $xml_writer->xmlElement(
            'dcterms:created',
            [],
            (new ilDateTime(time(), IL_CAL_UNIX))
                ->get(IL_CAL_FKT_DATE, 'Y-m-d\TH:i:s.u\Z', 'GMT')
        );

        $xml_writer->xmlEndTag('dublincore');

        return $xml_writer->xmlDumpMem(false);
    }
}

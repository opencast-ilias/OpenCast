<?php

namespace srag\Plugins\Opencast\Util\Transformator;

use ilDateTime;
use ilXmlWriter;
use Metadata;

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
     *
     * @param Metadata $metadata
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }


    /**
     * @return string
     */
    public function getXML() : string
    {
        $xml_writer = new ilXMLWriter();
        $xml_writer->xmlHeader();
        $xml_writer->xmlStartTag('dublincore', [
            'xmlns'         => 'http://www.opencastproject.org/xsd/1.0/dublincore/',
            'xmlns:dcterms' => 'http://purl.org/dc/terms/',
            'xmlns:xsi'     => 'http://www.w3.org/2001/XMLSchema-instance'
        ]);
        $xml_writer->xmlElement('dcterms:title', [], $this->metadata->getField('title')->getValue());
        $xml_writer->xmlElement('dcterms:description', [], $this->metadata->getField('description')->getValue());
        $xml_writer->xmlElement('dcterms:isPartOf', [], $this->metadata->getField('isPartOf')->getValue());
        $xml_writer->xmlElement('dcterms:source', [], $this->metadata->getField('source')->getValue());
        $xml_writer->xmlElement('dcterms:creator', [], implode(',', $this->metadata->getField('creator')->getValue()));
        $xml_writer->xmlElement('dcterms:spatial', [], $this->metadata->getField('location')->getValue());
        $xml_writer->xmlElement('dcterms:startDate', [], $this->metadata->getField('startDate')->getValue());
        $xml_writer->xmlElement('dcterms:startTime', [], $this->metadata->getField('startTime')->getValue());

        $xml_writer->xmlEndTag('dublincore');
        return $xml_writer->xmlStr;
    }
}
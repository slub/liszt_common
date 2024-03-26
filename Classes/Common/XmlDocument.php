<?php

namespace Slub\LisztCommon\Common;

class XmlDocument
{

    protected string $xmlString;

    public function __construct(string $xmlString)
    {
        $this->xmlString = $xmlString;
    }

    public static function from (string $xmlString): XmlDocument
    {
        return new XmlDocument($xmlString);
    }

    public function toArray(): array
    {
        // add function here
        return [];
    }

    public function toJson(): string
    {
        // add function here
        return '';
    }

    public function convert(XmlDocument $xmlDocument): XmlDocument
    {
        $result = XmlDocument::from('');
        // add function here

        return $result;
    }
}

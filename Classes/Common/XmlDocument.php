<?php

namespace Slub\LisztCommon\Common;

use SimpleXMLElement;

class XmlDocument
{

    protected string $xmlString;

    // Set up configuration vars
    protected bool $includeLiteralString;
    protected bool $includeXmlId;
    protected array $splitSymbols;

    // Private helper vars
    private array $convertedArray;


    public function __construct(string $xmlString)
    {
        $this->xmlString = $xmlString;

        // Deal with xml-reserved symbols
        $this->xmlString = str_replace("&", "&amp;", $this->xmlString);
        //$this->xmlString = str_replace("<", "&lt;", $this->xmlString);
        //$this->xmlString = str_replace(">", "&gt;", $this->xmlString);

        // Default config 
        $this->includeLiteralString = false;
        $this->includeXmlId = true;
        $this->splitSymbols = array();
    }

    // Set up config, if needed

    public function setConfig(array $config): void
    {
        $this->includeLiteralString = $config['literalString'];
        $this->includeXmlId = $config['xmlId'];
        $this->splitSymbols = $config['splitSymbols'];
    }

    public static function from(string $xmlString): XmlDocument
    {
        return new XmlDocument($xmlString);
    }

    public function toArray(): array
    {
        // Check if array is already converted
        if (isset($this->convertedArray)) {
            return $this->convertedArray;
        }

        $this->convertedArray = [];
        $xml = simplexml_load_string($this->xmlString);
        $this->convertedArray[strval($xml->attributes('xml', true)->id)] = $this->convert($xml);
        return $this->convertedArray;
    }


    public function toJson(): array
    {
        $result = [];
        $xmlArray = $this->toArray();
        foreach ($xmlArray as $id => $value) {
            $result[$id] = json_encode($value, JSON_PRETTY_PRINT);
        }
        return $result;
    }

    private function convert(SimpleXMLElement $node): array
    {

        $result = [];
        // Parse attributes
        $attributes = $node->attributes();
        foreach ($attributes as $attrName => $attrValue) {
            $trimmedValue = trim(strval($attrValue));
            if (!empty($trimmedValue)) {
                $result['@attributes'][$attrName] = $trimmedValue;
            }
        }

        // Parse value
        $nodeValue = trim(strval($node));
        if (!empty($nodeValue)) {
            $result['@value'] = $nodeValue;
        }

        // Include xml:id attribute
        if ($this->includeXmlId) {

            $xmlId = $node->attributes('xml', true)->id;
            $trimmedXmlId = trim(strval($xmlId));
            if (!empty($trimmedXmlId)) {
                $result['@xml:id'] = $trimmedXmlId;
            }
        }


        // Check if node is a mixed-content element

        if ($this->includeLiteralString) {
            if ($node->getName() == "p" && $node->count() > 0 && !empty($node)) {

                // Add literal string, to store the node order
                $literal = str_replace(array("\n", "\r"), '', trim($node->asXML()));
                $result['@literal'] = $literal;
            }
        }

        // Parse child nodes
        foreach ($node->children() as $childNode) {
            $childName = $childNode->getName();
            $xmlString = $childNode->asXML();
            $found = false;
            foreach ($this->splitSymbols as $symbol) {
                if (str_contains($childName, $symbol)) {
                    $found = true;
                    $result["@link"] = strval($childNode->attributes('xml', true)->id);
                    $this->convertedArray[strval($node->attributes('xml', true)->id)] = $this->convert($childNode);
                }
            }
            if ($found) {
                return $result;
            }
            $childData = $this->convert($childNode);
            // Always parse child nodes as array
            if (!isset($result[$childName])) {
                $result[$childName] = [];
            }
            $result[$childName][] = $childData;
        }

        return $result;
    }
}

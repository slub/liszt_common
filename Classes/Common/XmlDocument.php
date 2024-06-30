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
    protected array $convertedArray;


    public function __construct(string $xmlString)
    {
        $this->xmlString = $xmlString;

        // Deal with xml-reserved symbols
        $this->xmlString = str_replace("&", "&amp;", $this->xmlString);


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

    // Functions to set single config aspects

    public function setXmlId(bool $xmlId): void
    {
        $this->includeXmlId = $xmlId;
    }

    public function setLiteralString(bool $literal): void
    {
        $this->includeLiteralString = $literal;
    }

    public function setSplitSymbols(array $splitSymbols): void
    {
        $this->splitSymbols = $splitSymbols;
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
        $this->convertedArray[$this->getXmlId($xml)] = $this->convert($xml);
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

    protected function convert(SimpleXMLElement $node): array
    {

        $result = [];


        // Parse attributes
        $attrs = collect($node->attributes())->filter(function ($attrValue) {
            return !empty(trim((string) $attrValue));
        })->mapWithKeys(function ($attrValue, $attrName) {
            return [$attrName => trim((string) $attrValue)];
        })->toArray();

        // Merge parsed attributes with result array
        if (!empty($attrs)) {
            $result = array_merge_recursive($result, ['@attributes' => $attrs]);
        }

        // Parse value
        $nodeValue = trim((string) $node);
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


        // Check if node is a mixed-content element (if literalString is set to true)

        if ($this->includeLiteralString) {
            if ($node->getName() == "p" && $node->count() > 0 && !empty($node)) {

                // Add literal string, to store the node order
                $literal = str_replace(array("\n", "\r"), '', trim($node->asXML()));
                $result['@literal'] = $literal;
            }
        }

        $unsplitedChildNodes = collect($node->children());
        $toParse = $unsplitedChildNodes->filter(function ($subject) use ($node, &$result) {
            foreach ($this->splitSymbols as $symbol) {

                if ($subject->getName() == $symbol) {

                    $result['@link'] = $this->getXmlId($node);
                    $result[$this->getXmlId($subject)] = $this->convert($subject);
                    return false;
                }
            }
            return true;
        });

        $toParse = $toParse->each(function ($subject) use (&$result) {
            $result = $this->parseChild($subject, $result);
        });

        return $result;
    }

    private function parseChild(SimpleXMLElement $child, array $result) {
        $childName = $child->getName();
            $childData = $this->convert($child);
            // Always parse child nodes as array
            if (!isset($result[$childName])) {
                $result[$childName] = [];
            }
            $result[$childName][] = $childData;

            return $result;
    }

    private function getXmlId(SimpleXMLElement $xml)
    {
        return strval($xml->attributes('xml', true)->id);
    }

}


<?php

namespace Slub\LisztCommon\Common;
use SimpleXMLElement;

class XmlDocument
{

    protected string $xmlString;

    // Set up configuration vars
    protected bool $include_literal_string;
    protected bool $include_xml_id;
    protected array $splitSymbols;

    // Private helper vars
    private array $converted_array;
    

    public function __construct(string $xmlString)
    {
        $this->xmlString = $xmlString;

        // Deal with xml-reserved symbols
        $this->xmlString = str_replace("&", "&amp;", $this->xmlString);
        //$this->xmlString = str_replace("<", "&lt;", $this->xmlString);
        //$this->xmlString = str_replace(">", "&gt;", $this->xmlString);
        
        // Default config 
        $this->include_literal_string = false;
        $this->include_xml_id = true;
        $this->splitSymbols = array();
    }

    // Set up config, if needed

    public function setConfig(array $config) : void {
        $this->include_literal_string = $config['literalString'];
        $this->include_xml_id = $config['xmlId'];
        $this->splitSymbols = $config['splitSymbols'];
        var_dump($this->include_xml_id);
    }

    public static function from (string $xmlString): XmlDocument
    {
        return new XmlDocument($xmlString);
    }

    public function toArray(): array {

        //

        if(isset($this->converted_array)) {
            return $this->converted_array;
        }else{
            $this->converted_array = [];
            $xml = simplexml_load_string($this->xmlString);
            $this->converted_array[strval($xml->attributes('xml',true)->id)] = $this->__convert($xml);
            return $this->converted_array;
        }

    }
        

    public function toJson(): array
    {
        $result = [];
        $xmlArray = $this->toArray();
        foreach($xmlArray as $id => $value) {
            $result[$id] = json_encode($value, JSON_PRETTY_PRINT);
        }
        return $result;
    }

    public function __convert(SimpleXMLElement $node) {

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
        if($this->include_xml_id) {

            $xmlId = $node->attributes('xml', true)->id;
            $trimmedXmlId = trim(strval($xmlId));
            if (!empty($trimmedXmlId)) {
            $result['@xml:id'] = $trimmedXmlId;
            }

        }


        // Check if node is a mixed-content element
        
        if($this->include_literal_string) {
            if($node->getName() == "p") {    
                if($node->count() > 0 && !empty($node)) {
                    // Add literal string, to store the node order
                    $literal = str_replace(array("\n","\r"),'',trim($node->asXML()));
                    $result['@literal'] = $literal;
                }
            }
        }
        
        // Parse child nodes
        foreach($node->children() as $childNode) {
            $childName = $childNode->getName();
            $xmlString =  $childNode->asXML();
            $found = false;
            foreach($this->splitSymbols as $symbol) {
                if(str_contains($childName,$symbol)){
                    $found = true;
                    $result["@link"] = strval($childNode->attributes('xml', true)->id);
                    $this->converted_array[strval($node->attributes('xml',true)->id)] = $this->__convert($childNode);
                }
            }
            if($found) {
                return $result;
            }
            $childData = $this->__convert($childNode);
            // Always parse child nodes as array
            if (!isset($result[$childName])) {
                $result[$childName] = [];
            }
            $result[$childName][] = $childData;
        }
        
        return $result;
    
    }
}

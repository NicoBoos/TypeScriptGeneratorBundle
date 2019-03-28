<?php

namespace Janit\TypeScriptGeneratorBundle\Parser;

use PhpParser;
use PhpParser\Node;

class Visitor extends PhpParser\NodeVisitorAbstract
{
    private $isActive = false;

    /** @var TypeScript/Interface_[] */
    private $output = [];

    /** @var TypeScript\Interface_ */
    private $currentInterface;

    public function enterNode(Node $node)
    {
        if ($node instanceof PhpParser\Node\Stmt\Class_) {

            /** @var PhpParser\Node\Stmt\Class_ $class */
            $class = $node;

            if ($class->getDocComment()) {
                $this->isActive = true;
                $this->output[] = $this->currentInterface = new ParserInterface($class->name);
            }
        }

        if ($this->isActive) {
            if ($node instanceof PhpParser\Node\Stmt\Property) {
                /** @var PhpParser\Node\Stmt\Property $property */
                $property = $node;

                $this->currentInterface->properties[] = $this->parsePhpDocForProperty($property->getDocComment(), $property->props[0]->name);
            }
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof PhpParser\Node\Stmt\Class_) {
            $this->isActive = false;
        }
    }

    /**
     * @param \PhpParser\Comment|null $phpDoc
     */
    private function parsePhpDocForProperty($phpDoc, string $name)
    {
        $type = 'any';
        $isArray = false;
        $isNullable = false;

        if ($phpDoc !== null) {
            if (preg_match('/@var[ \t]+([a-z0-9\[\]\|]+)/i', $phpDoc->getText(), $matches)) {
                $phpDocType = strtolower(trim($matches[1]));

                $isArray = $this->isArrayType($phpDocType);
                $isNullable = $this->isNullable($phpDocType);

                switch ($phpDocType) {
                    case 'int': // no break
                    case 'integer': // no break
                    case 'float':
                        $type = 'number';
                        break;
                    case 'string':
                        $type = 'string';
                        break;
                    case 'bool': // no break
                    case 'boolean':
                        $type = 'boolean';
                        break;
                    case 'array':
                        $type = 'any';
                        $isArray = true;
                        break;
                }
            }
        }

        return new Property($name, $type, $isNullable, $isArray);
    }

    public function getOutput()
    {
        return implode("\n\n", array_map(function ($i) {
            return (string)$i;
        }, $this->output));
    }

    /**
     * Return true if the property is an array of a specific type (example: int[]).
     * @param string $type
     * @return bool
     */
    private function isArrayType(string $type): bool
    {
        return strpos($type, '[]') !== false;
    }

    /**
     * Return true if the property can be nullable (example: int|null).
     * @param string $type
     * @return bool
     */
    private function isNullable(string $type): bool
    {
        $types = explode('|', $type);
        if (\in_array('null', $types)) {
            return true;
        }

        return false;
    }
}

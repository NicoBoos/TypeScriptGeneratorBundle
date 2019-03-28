<?php

namespace Janit\TypeScriptGeneratorBundle\Parser;

use PhpParser;
use PhpParser\Node;

class Visitor extends PhpParser\NodeVisitorAbstract
{
    /** @var TypeScript/Interface_[] */
    private $output = [];

    /** @var TypeScript\Interface_ */
    private $currentInterface;

    public function enterNode(Node $node)
    {
        if ($node instanceof PhpParser\Node\Stmt\Class_) {

            /** @var PhpParser\Node\Stmt\Class_ $class */
            $class = $node;
            $this->output[] = $this->currentInterface = new ParserInterface($class->name);
        }

        if ($node instanceof PhpParser\Node\Stmt\Property) {
            /** @var PhpParser\Node\Stmt\Property $property */
            $property = $node;

            $this->currentInterface->properties[] = $this->parsePhpDocForProperty($property->getDocComment(), $property->props[0]->name);
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
                $phpDocType = trim($matches[1]);

                $types = explode('|', $phpDocType);

                if ($this->isNullable($types)) {
                    $isNullable = true;
                    $phpDocType = $this->nullableType($types);
                }

                // If the property is an array of type (int[])
                if ($this->isArrayType($phpDocType)) {
                    $isArray = true;
                    $phpDocType = $this->arrayType($phpDocType);
                }

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
                    case 'mixed':
                        $type = 'any';
                        break;
                    default:
                        $type = $phpDocType;
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
     * Return the type of an array of type (example: for "int[]" it will return "int").
     * @param string $type
     * @return string
     */
    private function arrayType(string $type): string
    {
        return str_replace('[]', '', $type);
    }

    /**
     * Return true if the property can be nullable (example: int|null).
     * @param array $types
     * @return bool
     */
    private function isNullable(array $types): bool
    {
        if (\in_array('null', $types)) {
            return true;
        }

        return false;
    }

    /**
     * Return true if the property can be nullable (example: int|null).
     * @param array $types
     * @return string|null
     */
    private function nullableType(array $types)
    {
        foreach ($types as $type) {
            // We return the first type which is not null
            if ($type !== 'null') {
                return $type;
            }
        }
        return null;
    }
}

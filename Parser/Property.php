<?php

namespace Janit\TypeScriptGeneratorBundle\Parser;

class Property
{
    /** @var string */
    public $name;
    /** @var string */
    public $type;
    /** @var bool */
    public $isNullable;
    /** @var bool */
    public $isArray;

    public function __construct($name, $type = 'any', bool $nullable = false, bool $array = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->isNullable = $nullable;
        $this->isArray = $array;
    }

    public function __toString()
    {
        $type = $this->isArray ? "Array<{$this->type}>" : $this->type;
        $operator = $this->isNullable ? '?:' : ':';

        return "{$this->name}$operator $type";
    }
}

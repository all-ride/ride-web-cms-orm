<?php

namespace ride\web\cms\content\text\variable;

use ride\library\cms\content\text\variable\AbstractVariableParser;
use ride\library\reflection\ReflectionHelper;
use ride\web\cms\node\EntryNode;

/**
 * Implementation to parse entry variables
 */
class EntryVariableParser extends AbstractVariableParser {

    /**
     * Instance of the reflection helper
     * @var \ride\library\reflection\ReflectionHelper
     */
    protected $reflectionHelper;

    /**
     * Constructs a new entry variable parser
     * @param \ride\library\reflection\ReflectionHelper $reflectionHelper
     * @return null
     */
    public function __construct(ReflectionHelper $reflectionHelper) {
        $this->reflectionHelper = $reflectionHelper;
    }

    /**
     * Parses the provided variable
     * @param string $variable Full variable
     * @return mixed Value of the variable if resolved, null otherwise
     */
    public function parseVariable($variable) {
        $tokens = explode('.', $variable);

        if ($tokens[0] !== 'entry') {
            return null;
        }

        $node = $this->textParser->getNode();
        while ($node && !$node instanceof EntryNode) {
            $node = $node->getParentNode();
        }

        if (!$node) {
            return null;
        }

        $value = $node->getEntry();

        $numTokens = count($tokens);
        for ($i = 1; $i < $numTokens; $i++) {
            $value = $this->reflectionHelper->getProperty($value, $tokens[$i]);
        }

        return $value;
    }

}

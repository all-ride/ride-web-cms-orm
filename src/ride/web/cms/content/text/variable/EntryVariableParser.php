<?php

namespace ride\web\cms\content\text\variable;

use ride\library\cms\content\text\variable\AbstractVariableParser;
use ride\library\cms\content\text\variable\NodeVariableParser;
use ride\library\cms\node\NodeModel;
use ride\library\reflection\ReflectionHelper;

use ride\web\cms\node\type\EntryNodeType;
use ride\web\cms\node\EntryNode;

/**
 * Implementation to parse entry variables
 */
class EntryVariableParser extends AbstractVariableParser {

    /**
     * Instance of the node model
     * @var \ride\library\cms\node\NodeModel
     */
    protected $nodeModel;

    /**
     * Instance of the reflection helper
     * @var \ride\library\reflection\ReflectionHelper
     */
    protected $reflectionHelper;

    /**
     * Constructs a new text parser
     * @param \ride\library\cms\node\NodeModel $nodeModel Instance of the node
     * model
     * @param \ride\library\reflection\ReflectionHelper $reflectionHelper
     * @return null
     */
    public function __construct(NodeModel $nodeModel, ReflectionHelper $reflectionHelper) {
        $this->nodeModel = $nodeModel;
        $this->reflectionHelper = $reflectionHelper;
    }

    /**
     * Parses the provided variable
     * @param string $variable Full variable
     * @return mixed Value of the variable if resolved, null otherwise
     */
    public function parseVariable($variable) {
        $tokens = explode('.', $variable);

        $numTokens = count($tokens);

        if ($numTokens < 2) {
            return null;
        } elseif ($numTokens === 2 || $tokens[0] === 'node' && $tokens[1] === 'var') {
            $node = $this->textParser->getNode();
            while ($node && !$node instanceof EntryNode) {
                $node = $node->getParentNode();
            }

            if (!$node) {
                return null;
            }
        }

        $value = null;

        if ($tokens[0] === 'entry' && $numTokens === 2) {
            switch ($tokens[1]) {
                case NodeVariableParser::VARIABLE_URL:
                    $value = $this->textParser->getBaseUrl() . $node->getRoute($this->textParser->getLocale());

                    break;
                case NodeVariableParser::VARIABLE_NAME:
                    $value = $node->getName($this->textParser->getLocale());

                    break;
            }

            return $value;
        } elseif ($tokens[0] === 'node' && $tokens[1] === 'var' && $numTokens > 2) {
            $value = $node->getEntry();

            for ($i = 2; $i < $numTokens; $i++) {
                $value = $this->reflectionHelper->getProperty($value, $tokens[$i]);
                if ($value === null) {
                    break;
                }
            }
        } elseif ($tokens[0] === 'entry') {
            // lookup entry node
            $modelName = ucfirst($tokens[1]);
            $entryId = $tokens[2];

            $nodes = $this->nodeModel->getNodes();
            foreach ($nodes as $node) {
                if ($node->getType() !== EntryNodeType::NAME || $node->getEntryModel() !== $modelName || $node->getEntryId() !== $entryId) {
                    continue;
                }

                $locale = isset($tokens[4]) ? $tokens[4] : $this->textParser->getLocale();

                switch ($tokens[3]) {
                    case NodeVariableParser::VARIABLE_URL:
                        $value = $this->textParser->getBaseUrl() . $node->getRoute($locale);

                        break 2;
                    case NodeVariableParser::VARIABLE_NAME:
                        $value = $node->getName($locale);

                        break 2;
                    case NodeVariableParser::VARIABLE_LINK:
                        $value = '<a href="' . $this->textParser->getBaseUrl() . $node->getRoute($locale) . '">' . $node->getName($locale) . '</a>';

                        break 2;
                }

                break;
            }
        }

        return $value;
    }

}

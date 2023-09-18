<?php

declare(strict_types=1);

namespace Sitegeist\Taxonomy\FlowQuery;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;

class WithSubTaxonomiesOperation extends AbstractOperation
{
    use CreateNodeHashTrait;
    use FlattenSubtreeTrait;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'withSubTaxonomies';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 100;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\InjectConfiguration(path: "contentRepository.taxonomyNodeType")]
    protected string $taxonomyNodeType;

    /**
     * {@inheritdoc}
     *
     * @param array<int,mixed> $context (or array-like object) onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return isset($context[0]) && ($context[0] instanceof Node && $context[0]->nodeType->isOfType($this->taxonomyNodeType));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $filter = FindSubtreeFilter::create(
            $this->taxonomyNodeType
        );

        $nodes = Nodes::createEmpty();
        foreach ($flowQuery->getContext() as $node) {
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
            $subtree = $subgraph->findSubtree($node->nodeAggregateId, $filter);
            if ($subtree) {
                $nodes = $nodes->merge($this->flattenSubtree($subtree));
            }
        }

        $nodesByHash = [];
        foreach ($nodes as $node) {
            $hash = $this->createNodeHash($node);
            if (!array_key_exists($hash, $nodesByHash)) {
                $nodesByHash[$hash] = $node;
            }
        }
        $flowQuery->setContext(array_values($nodesByHash));
    }
}
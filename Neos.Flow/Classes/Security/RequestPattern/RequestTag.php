<?php
namespace Neos\Flow\Security\RequestPattern;

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Exception\InvalidRequestPatternException;
use Neos\Flow\Security\RequestPatternInterface;

/**
 *
 */
class RequestTag implements RequestPatternInterface
{
    /**
     * @var array
     */
    protected array $options = [];

    /**
     * @param array{"tag": string} $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param ActionRequest $request
     * @return bool
     * @throws InvalidRequestPatternException
     */
    public function matchRequest(ActionRequest $request): bool
    {
        if (!isset($this->options["tag"]) || !is_string($this->options["tag"])) {
            throw new InvalidRequestPatternException('Needs a "tag" option to match against');
        }

        $tagToMatch = \Neos\Flow\Mvc\RequestTag::fromString($this->options["tag"]);
        foreach ($request->getRequestTags() as $tag) {
            if ($tagToMatch->matches($tag)) {
                return true;
            }
        }

        return false;
    }
}

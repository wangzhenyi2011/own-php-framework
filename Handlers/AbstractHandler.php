<?php

namespace Baicaowei\Handlers;

use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractHandler
{
    protected $knownContentTypes = [
        'application/json',
        'application/xml',
        'text/xml',
        'text/html',
    ];

    protected function determineContentType(ServerRequestInterface $request)
    {
        $acceptHeader = $request->getHeaderLine('Accept');
        $selectedContentTypes = array_intersect(explode(',', $acceptHeader), $this->knownContentTypes);

        if (count($selectedContentTypes)) {
            return current($selectedContentTypes);
        }
        
        if (preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
            $mediaType = 'application/' . $matches[1];
            if (in_array($mediaType, $this->knownContentTypes)) {
                return $mediaType;
            }
        }

        return 'text/html';
    }
}

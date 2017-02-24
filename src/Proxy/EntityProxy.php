<?php

namespace GraphAware\Neo4j\OGM\Proxy;

interface EntityProxy
{
    public function __setInitializers(array $initializers);

    public function __setNode($node);
}
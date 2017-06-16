<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Proxy;

use GraphAware\Common\Type\Node;
use GraphAware\Neo4j\OGM\EntityManager;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata;

class ProxyFactory
{
    protected $em;

    protected $classMetadata;

    protected $proxyDir;

    public function __construct(EntityManager $em, NodeEntityMetadata $classMetadata)
    {
        $this->em = $em;
        $this->classMetadata = $classMetadata;
        $this->proxyDir = $em->getProxyDirectory();
    }

    public function fromNode(Node $node, array $mappedByProperties = [])
    {
        $object = $this->createProxy();
        $object->__setNode($node);
        $initializers = [];
        foreach ($this->classMetadata->getSimpleRelationships() as $relationship) {
            if (!in_array($relationship->getPropertyName(), $mappedByProperties, true)) {
                if (!$relationship->isCollection()) {
                    $initializer = $this->getInitializerFor($relationship);
                    $initializers[$relationship->getPropertyName()] = $initializer;
                } else {
                    $mappedByProperties[] = $relationship->getPropertyName();
                }
            }
        }

        foreach ($this->classMetadata->getRelationshipEntities() as $relationshipEntity) {
            if (!$relationshipEntity->isCollection()) {
                if (!in_array($relationshipEntity->getPropertyName(), $mappedByProperties, true)) {
                    $initializer = new RelationshipEntityInitializer($this->em, $relationshipEntity, $this->classMetadata);
                    $initializers[$relationshipEntity->getPropertyName()] = $initializer;

                }
            } else {
                if (!in_array($relationshipEntity->getPropertyName(), $mappedByProperties, true)) {
//                    $initializer = new RelationshipEntityCollectionInitializer($this->em, $relationshipEntity, $this->classMetadata);
//                    $initializers[$relationshipEntity->getPropertyName()] = $initializer;

                    $mappedByProperties[] = $relationshipEntity->getPropertyName();
                }
            }
        }
        $object->__setInitializers($initializers);
        foreach ($mappedByProperties as $mappedByProperty) {
            $object->__setInitialized($mappedByProperty);
        }

        foreach ($this->classMetadata->getRelationships() as $relationship) {
            if ($relationship->isCollection()) {
                $initializer = $relationship->isRelationshipEntity()
                    ? new RelationshipEntityCollectionInitializer($this->em, $relationship, $this->classMetadata)
                    : $this->getInitializerFor($relationship);
                $lc = new LazyCollection($initializer, $node, $object, $relationship);
                $relationship->setValue($object, $lc);
            }
        }

        return $object;
    }

    protected function createProxy()
    {
        $class = $this->classMetadata->getClassName();
        $proxyClass = $this->getProxyClass();
        $proxyFile = $this->proxyDir.'/'.$proxyClass.'.php';
        $methodProxies = $this->getMethodProxies();

        $content = <<<PROXY
<?php

use GraphAware\\Neo4j\\OGM\\Proxy\\EntityProxy;

class $proxyClass extends $class implements EntityProxy
{
    private \$em;
    private \$initialized = [];
    private \$initializers = [];
    private \$node;
    
    public function __setNode(\$node)
    {
        \$this->node = \$node;
    }
    
    public function __setInitializers(array \$initializers)
    {
        \$this->initializers = \$initializers;
    }
    
    public function __setInitialized(\$property)
    {
        \$this->initialized[\$property] = null;
    }
    
    public function __initializeProperty(\$propertyName)
    {
        if (!array_key_exists(\$propertyName, \$this->initialized)) {
            \$this->initializers[\$propertyName]->initialize(\$this->node, \$this);
            \$this->initialized[\$propertyName] = null;
        }
    }
    
    $methodProxies
}

PROXY;

        $this->checkProxyDirectory();
        file_put_contents($proxyFile, $content);

        if (!class_exists($proxyClass)) {
            require $proxyFile;
        }

        return $this->newProxyInstance($proxyClass);
    }

    protected function getMethodProxies()
    {
        $proxies = '';
        foreach ($this->classMetadata->getRelationships() as $relationship) {
            if ($relationship->isFetch()) {
                continue;
            }
            $getter = 'get'.ucfirst($relationship->getPropertyName()).'()';
            $returnStr = $getter;

            if (PHP_VERSION_ID > 70000) {
                $reflClass = new \ReflectionClass($this->classMetadata->getClassName());
                $g = 'get'.ucfirst($relationship->getPropertyName());
                if ($reflClass->hasMethod($g)) {
                    $reflMethod = $reflClass->getMethod($g);
                    if ($reflMethod->hasReturnType()) {
                        $rt = $reflMethod->getReturnType();
                        $getter .= ': '.$rt;
                    }
                }
            }

            $propertyName = $relationship->getPropertyName();
            $proxies .= <<<METHOD
public function $getter
{
    self::__initializeProperty('$propertyName');
    return parent::$returnStr;
}

METHOD;
        }

        return $proxies;
    }

    protected function getProxyClass()
    {
        return 'neo4j_ogm_proxy_'.str_replace('\\', '_', $this->classMetadata->getClassName());
    }

    private function getInitializerFor(RelationshipMetadata $relationship)
    {
        if (!$relationship->isCollection()) {
            $initializer = new SingleNodeInitializer($this->em, $relationship, $this->classMetadata);
        } elseif ($relationship->isCollection()) {
            $initializer = new NodeCollectionInitializer($this->em, $relationship, $this->classMetadata);
        }

        return $initializer;
    }

    private function newProxyInstance($proxyClass)
    {
        static $prototypes = [];

        if (!array_key_exists($proxyClass, $prototypes)) {
            $rc = @unserialize(sprintf('C:%d:"%s":0:{}', strlen($proxyClass), $proxyClass));

            if (false === $rc) {
                $rc = new \ReflectionClass($proxyClass);
                return $rc->newInstanceWithoutConstructor();
            }

            $prototypes[$proxyClass] = $rc;
        }

        return clone $prototypes[$proxyClass];
    }

    private function checkProxyDirectory()
    {
        if (!is_dir($this->em->getProxyDirectory())) {
            @mkdir($this->em->getProxyDirectory());
        }
    }
}

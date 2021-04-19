<?php


namespace Bytes\TwitchClientBundle\Tests;


use Bytes\Tests\Common\TestFullSerializerTrait;
use Bytes\TwitchResponseBundle\Serializer\ConditionNormalizer;
use Bytes\TwitchResponseBundle\Serializer\SubscriptionNormalizer;

trait FullSerializerTrait
{
    use TestFullSerializerTrait;

    /**
     * @before
     */
    protected function setUpSerializer()
    {
        $this->setupObjectNormalizerParts();
        $this->serializer = $this->createSerializer(prependNormalizers: [
            new SubscriptionNormalizer($this->classMetadataFactory, $this->metadataAwareNameConverter, $this->propertyInfo, $this->classDiscriminatorFromClassMetadata),
            new ConditionNormalizer($this->classMetadataFactory, $this->metadataAwareNameConverter, $this->propertyInfo, $this->classDiscriminatorFromClassMetadata),
        ]);
    }
}

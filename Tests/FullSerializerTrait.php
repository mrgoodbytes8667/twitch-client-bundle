<?php


namespace Bytes\TwitchClientBundle\Tests;


use Bytes\Tests\Common\TestFullSerializerTrait;
use Bytes\TwitchResponseBundle\Normalizer\TwitchDateTimeNormalizer;
use Bytes\TwitchResponseBundle\Serializer\ConditionNormalizer;

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
            new TwitchDateTimeNormalizer($this->classMetadataFactory, $this->metadataAwareNameConverter, $this->propertyAccessor, $this->propertyInfo, $this->classDiscriminatorFromClassMetadata),
            new ConditionNormalizer($this->classMetadataFactory, $this->metadataAwareNameConverter, $this->propertyInfo, $this->classDiscriminatorFromClassMetadata),
        ]);
    }
}

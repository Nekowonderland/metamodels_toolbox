<?php
/**
 * Created by PhpStorm.
 * User: stefan.heimes
 * Date: 31.08.2016
 * Time: 12:44
 */

namespace MetaModels\Toolbox;


use MetaModels\IMetaModel;
use MetaModels\IMetaModelsServiceContainer;

class Api
{
    /**
     * Retrieve the service container.
     *
     * @return IMetaModelsServiceContainer
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    static public function getServiceContainer()
    {
        return $GLOBALS['container']['metamodels-service-container'];
    }

    /**
     * Get the event Dispatcher.
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    static public function getEventDispatcher()
    {
        return self::getServiceContainer()->getEventDispatcher();
    }

    /**
     * Get a MetaModels by his name.
     *
     * @param string $name The name of the MetaModels.
     *
     * @return IMetaModel|null The metamodels or null on error.
     */
    static public function getMetaModels($name)
    {
        return self::getServiceContainer()->getFactory()->getMetaModel($name);
    }

    /**
     * Get the id of the MetaModels.
     *
     * @param string $name The name of the MetaModels.
     *
     * @return string|null The id of the metamodels or null on error.
     */
    static public function getIdOfMetaModels($name)
    {
        $metamodels = self::getMetaModels($name);
        if (null === $metamodels) {
            return null;
        }

        return $metamodels->get('id');
    }

    /**
     * Get all attributes from a metamodels.
     *
     * @param string $metamodelsName The name of the metamoels.
     *
     * @return \MetaModels\Attribute\IAttribute[] The attributes,
     */
    static public function getAttributes($metamodelsName)
    {
        $metamodels = self::getMetaModels($metamodelsName);

        return $metamodels->getAttributes();
    }

    /**
     * Get a collection for the given MetaModels.
     *
     * @param IMetaModel|String $metaModels The MetaModels or the name of it.
     *
     * @param string            $id         The id of the rendersetting.
     *
     * @return \MetaModels\Render\Setting\ICollection|null
     */
    static public function getCollection($metaModels, $id)
    {
        // Check if we have string, if so try to get the metamodels.
        if (is_string($metaModels)) {
            $metaModels = self::getMetaModels($metaModels);
        }

        // Check if we have a object.
        if (null === $metaModels || !is_object($metaModels)) {
            return null;
        }

        return self::getServiceContainer()
            ->getRenderSettingFactory()
            ->createCollection($metaModels, $id);
    }

    /**
     * Get a filter setting.
     *
     * @param string $id The id of the filter setting.
     *
     * @return \MetaModels\Filter\Setting\ICollection The filter collection. Could be empty collection.
     */
    static public function getFilter($id)
    {
        return self::getServiceContainer()
            ->getFilterFactory()
            ->createCollection($id);
    }
}

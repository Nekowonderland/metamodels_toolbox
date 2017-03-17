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
     * Get the default service container for MetaModels. This is the basic to work with MetaModels.
     * Since MetaModels add all main functions to this container. We should always use it.
     *
     * The better way is to inject this. But in all cases we would use the default container. So we can
     * leave it like it is.
     *
     * @return IMetaModelsServiceContainer The service container.
     *
     * @throws \RuntimeException If the container is not the right one.
     */
    public static function getDefaultServiceContainer()
    {
        // Get the container...
        $serviceContainer = $GLOBALS['container']['metamodels-service-container'];

        // and check if we have the right one.
        if (!($serviceContainer instanceof IMetaModelsServiceContainer)) {
            throw new \RuntimeException('Unable to retrieve default service container.');
        }

        return $serviceContainer;
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
     * Get a MetaModels by the given name.
     *
     * @param string $metaModelIdentifier The name of the MetaModels
     *
     * @return IMetaModel
     */
    public static function getMetaModels($metaModelIdentifier)
    {
        $serviceContainer = static::getDefaultServiceContainer();

        // If the identifier ist numeric, use the id to name function.
        if (is_numeric($metaModelIdentifier)) {
            $metaModelIdentifier = $serviceContainer->getFactory()->translateIdToMetaModelName($metaModelIdentifier);
        }

        $metamodels = $serviceContainer
            ->getFactory()
            ->getMetaModel($metaModelIdentifier);

        if ($metamodels === null) {
            throw new \RuntimeException(sprintf('Could not find a MetaModels with the name %s', $metaModelIdentifier));
        }

        return $metamodels;
    }
    
    /**
     * Get a filter colection by the id.
     *
     * @param string $strId The id of the collection.
     *
     * @return ICollection The collection with the filter or an empty collection if the ID is unknown.
     */
    public static function getFilterCollection($strId)
    {
        $serviceContainer = static::getDefaultServiceContainer();

        return $serviceContainer
            ->getFilterFactory()
            ->createCollection($strId);
    }
    
     /**
     * Get a render setting by the id.
     *
     * @param string $metamodels The name of the metamodels.
     *
     * @param string $strId      The id of the collection.
     *
     * @return \MetaModels\Render\Setting\ICollection|null
     */
    public static function getRenderSetting($metamodels, $strId)
    {
        $serviceContainer = static::getDefaultServiceContainer();
        $metamodels       = static::getMetaModels($metamodels);
        if ($metamodels === null) {
            return null;
        }

        return $serviceContainer
            ->getRenderSettingFactory()
            ->createCollection($metamodels, $strId);
    }
    
     /**
     * Get a attribute from a metamodels.
     *
     * @param string $metamodels The name of the metamodels.
     *
     * @param string $attribute  The name of the attribute.
     *
     * @return IAttribute|null The attribute or null on error.
     */
    public static function getAttribute($metamodels, $attribute)
    {
        $metamodels = self::getMetaModels($metamodels);
        $attribute  = $metamodels->getAttribute($attribute);

        return $attribute;
    }
    
    /**
     * Get some data by the id.
     *
     * @param string|int $metaModelId The name or the id of the metamodels.
     *
     * @param array      $Ids         The ids.
     *
     * @param array      $arrAttrOnly A list of attributes we want.
     *
     * @return IItems The items.
     */
    public static function getByIds($metaModelId, $Ids, $arrAttrOnly = [])
    {
        // Get the mm and a new empty filter.
        $metaModel = static::getMetaModels($metaModelId);

        // Placeholder for SQL.
        $placeholder = array_fill(0, count($Ids), '?');

        // Add the filter for the pid.
        $filter = $metaModel->getEmptyFilter();
        $filter->addFilterRule(
            new SimpleQuery(
                sprintf(
                    'SELECT id FROM %s WHERE id IN (%s)',
                    $metaModel->getTableName(),
                    implode(', ', $placeholder)
                ),
                $Ids
            )
        );

        return $metaModel->findByFilter($filter, '', 0, 0, 'ASC', $arrAttrOnly);
    }
    
     /**
     * Get some data by the given field.
     *
     * @param string|int $metaModelId The name or the id of the metamodels.
     *
     * @param string     $field       The name of the field.
     *
     * @param array      $values      The list with all values.
     *
     * @param array      $arrAttrOnly A list of attributes we want.
     *
     * @return \MetaModels\IItem[]|IItems The items.
     */
    public static function getManyByField($metaModelId, $field, $values, $arrAttrOnly = [])
    {
        // Get the mm and a new empty filter.
        $metaModel = static::getMetaModels($metaModelId);
        $attribute = $metaModel->getAttribute($field);
        if ($attribute === null) {
            return null;
        }

        // Add the filter for the pid.
        $filter = $metaModel->getEmptyFilter();
        $or     = new ConditionOr();
        foreach ($values as $value) {
            $search = new SearchAttribute($attribute, $value);
            $or->addChild($search);
        }
        $filter->addFilterRule($or);

        return $metaModel->findByFilter($filter, '', 0, 0, 'ASC', $arrAttrOnly);
    }
    
    /**
     * Get items by a given filter id.
     *
     *
     * @param string $strMetaModels   The name of the MetaModels.
     *
     * @param string $strFilterId     The id of the filter collection.
     *
     * @param int    $intOffset       The offset.
     *
     * @param int    $intLimit        The Limit.
     *
     * @param string $strSortBy       The field to sort.
     *
     * @param string $strSortOrder    The order of sorting.
     *
     * @param array  $arrAttrOnly     A list of attributes we want. If a empty array is added, all attributes will be
     *                                returned.
     *
     * @return IItems The items fitting the filter.
     */
    public static function getByFilterId(
        $strMetaModels,
        $strFilterId,
        $intOffset = 0,
        $intLimit = 0,
        $strSortBy = 'sorting',
        $strSortOrder = 'ASC',
        $arrAttrOnly = []
    ) {
        // Get the MetaModels and the collection.
        $objMetaModels       = static::getMetaModels($strMetaModels);
        $objCollectionFilter = static::getFilterCollection($strFilterId);

        // Make a new filter and ignore GET/POST data.
        $filter = $objMetaModels->getEmptyFilter();
        $objCollectionFilter->addRules($filter, []);

        // Get the items and return it.
        $items = $objMetaModels
            ->findByFilter($filter, $strSortBy, $intOffset, $intLimit, $strSortOrder, $arrAttrOnly);

        return $items;
    }
}

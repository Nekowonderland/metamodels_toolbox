<?php


use MetaModels\Attribute\IAttribute;
use MetaModels\IMetaModel;

class AttributeUpdate
{
    /**
     * @var IMetaModel
     */
    protected $metamodels;

    /**
     * @var IAttribute
     */
    protected $attribute;

    /**
     * @var bool
     */
    protected $dryMode;

    /**
     * AttributeUpdate constructor.
     */
    public function __construct()
    {

    }

    /**
     * Flag if we want a try run.
     *
     * @return bool
     */
    public function getDryMode()
    {
        return $this->dryMode;
    }

    /**
     * Set the dry mode.
     *
     * @param bool $mode The mode.
     */
    public function setDryMode($mode)
    {
        $this->dryMode = $mode;
    }

    /**
     * Set the metamodels.
     *
     * @param string $metaModelsName The name of the MetaModels.
     *
     * @return $this
     */
    public function setMetaModels($metaModelsName)
    {
        $this->metamodels = Helper::getMetaModels($metaModelsName);

        if ($this->metamodels === null) {
            throw new \RuntimeException('Could not find the metamodels with the name ' . $metaModelsName);
        }

        return $this;
    }

    /**
     * Write some data.
     *
     * @param string $str The msg to the world.
     *
     * @return void
     */
    public function addMsg($str)
    {
        echo $str;
        echo "\n";
    }

    /**
     * Get the attribute.
     *
     * @param string $attributeName The name of the attribute.
     *
     * @return void
     *
     * @throws \RuntimeException If the system can't find the attribute or it is not from type select.
     */
    protected function loadAttribute($attributeName)
    {
        // Get the attribute and check if we have some data.
        $this->attribute = $this->metamodels->getAttribute($attributeName);
        if ($this->attribute == null) {
            throw new \RuntimeException('Could not find the attribute with the name ' . $attributeName);
        }

        // Check the class.
        $className = get_class($this->attribute);
        if ($className != 'MetaModels\Attribute\Select\MetaModelSelect' && $className != 'MetaModels\Attribute\Select\Select') {
            throw new \RuntimeException('Given attribute is not a select: ' . $attributeName);
        }
    }

    /**
     * Rewrite the data from a select to a tag.
     *
     * @param string $attributeName The id of the attribute we want to rewrite.
     *
     * @return bool
     */
    public function rewriteData($attributeName)
    {
        // Get some meta information.
        $this->loadAttribute($attributeName);
        $attributeID      = $this->attribute->get('id');
        $attributeColName = $this->attribute->getColName();

        // Get all data.
        $sql  = 'SELECT id, %s as updatevalue FROM %s';
        $data = \Database::getInstance()
            ->prepare(sprintf(
                $sql,
                $attributeColName,
                $this->metamodels->getTableName()
            ))
            ->execute();

        // Save a backup.
        $this->saveBackup($data->fetchAllAssoc());
        $data->reset();

        $this->addMsg(str_repeat('-', 50));
        $this->addMsg(str_repeat('-', 50));
        $this->addMsg('Start Update');
        $this->addMsg('Field: ' . $attributeColName);

        // List of skipped ids.
        $skippedIds = array();
        $totalCount = 0;

        $sqlInsert = 'INSERT INTO tl_metamodel_tag_relation (att_id, item_id, value_sorting, value_id) VALUES(?,?,0,?)';
        while ($data->next()) {
            // Increase the total count.
            $totalCount++;

            // Check if we have some data.
            if ($data->updatevalue === 0 || $data->updatevalue === '0') {
                $skippedIds[] = $data->id;
                continue;
            }

            // Write data.
            if ($this->getDryMode()) {
                $this->addMsg($sqlInsert);
                $this->addMsg($attributeID);
                $this->addMsg($data->id);
                $this->addMsg($data->updatevalue);
                $this->addMsg(str_repeat('-', 25));
            } else {
                \Database::getInstance()
                    ->prepare($sqlInsert)
                    ->execute($attributeID, $data->id, $data->updatevalue);
            }

            $this->addMsg('Update ID: ' . $data->id);
        }

        $this->addMsg('');
        $this->addMsg('Total: ' . $totalCount);
        $this->addMsg('Skipped: ' . count($skippedIds));
        $this->addMsg('');
        $this->addMsg('List: ');
        $this->addMsg(implode(', ', $skippedIds));
        $this->addMsg('');
        $this->addMsg('End Update');

        return true;
    }



    public function duplicateConfig($attributeName)
    {
        // Get some meta information.
        $this->loadAttribute($attributeName);
        $attributeID = $this->attribute->get('id');

        $sql  = 'SELECT * FROM tl_metamodel_attribute WHERE id = ?';
        $data = \Database::getInstance()
            ->prepare($sql)
            ->execute($attributeID);

        $sqlUpdate  = 'UPDATE tl_metamodel_attribute %s WHERE id = ?';
        $updateData = array(
            'tag_table'        => $data->select_table,
            'tag_column'       => $data->select_column,
            'tag_id'           => $data->select_id,
            'tag_alias'        => $data->select_alias,
            'tag_sorting'      => $data->select_sorting,
            'tag_where'        => $data->select_where,
            'tag_filter'       => $data->select_filter,
            'tag_filterparams' => $data->select_filterparams,
        );

        if ($this->getDryMode()) {
            $this->addMsg(\Database::getInstance()
                ->prepare($sqlUpdate)
                ->set($updateData)
                ->query);
            $this->addMsg('');
        } else {
            \Database::getInstance()
                ->prepare($sqlUpdate)
                ->set($updateData)
                ->execute($attributeID);
        }

        $this->addMsg('Setting data copy done for attribute ' . $this->attribute->getColName() . ' id: ' . $this->attribute->get('id'));
    }

    /**
     * Write the data in a file for checking if all runs as i think and as backup.
     *
     * @param array $data The data for the file.
     *
     * @return void
     */
    protected function saveBackup($data)
    {
        // Check if we have some data.
        if (empty($data)) {
            return;
        }

        // Create the file and name.
        $filePath = sprintf(
            'tl_files/backup/att-update--%s--%s--%s.csv',
            $this->metamodels->getTableName(),
            $this->attribute->getColName(),
            date('Ymd')
        );

        // Open.
        $file = new \File($filePath);
        $file->write('');

        // Add the fields.
        $keys = array_keys($data[0]);
        fputcsv($file->handle, $keys, ',');

        // Add the data.
        foreach ($data as $row) {
            fputcsv($file->handle, $row, ',');
        }

        // Close.
        $file->close();
    }
}

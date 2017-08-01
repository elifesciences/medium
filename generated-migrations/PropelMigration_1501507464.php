<?php

use Propel\Generator\Manager\MigrationManager;

class PropelMigration_1501507464
{
    public $comment = '';

    public function preUp(MigrationManager $manager)
    {
        // add the pre-migration code here
    }

    public function postUp(MigrationManager $manager)
    {
        // add the post-migration code here
    }

    public function preDown(MigrationManager $manager)
    {
        // add the pre-migration code here
    }

    public function postDown(MigrationManager $manager)
    {
        // add the post-migration code here
    }

    /**
     * Get the SQL statements for the Up migration.
     *
     * @return array list of the SQL strings to execute for the Up migration
     *               the keys being the datasources
     */
    public function getUpSQL()
    {
        return array(
  'default' => '
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `medium_articles`

  CHANGE `image_domain` `image_domain` VARCHAR(255),

  CHANGE `image_path` `image_path` VARCHAR(255),

  CHANGE `image_alt` `image_alt` VARCHAR(255),

  ADD `image_media_type` VARCHAR(255) AFTER `image_alt`,

  ADD `image_width` INTEGER AFTER `image_media_type`,

  ADD `image_height` INTEGER AFTER `image_width`;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
',
);
    }

    /**
     * Get the SQL statements for the Down migration.
     *
     * @return array list of the SQL strings to execute for the Down migration
     *               the keys being the datasources
     */
    public function getDownSQL()
    {
        return array(
  'default' => '
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `medium_articles`

  CHANGE `image_domain` `image_domain` VARCHAR(255) NOT NULL,

  CHANGE `image_path` `image_path` VARCHAR(255) NOT NULL,

  CHANGE `image_alt` `image_alt` VARCHAR(255) NOT NULL,

  DROP `image_media_type`,

  DROP `image_width`,

  DROP `image_height`;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
',
);
    }
}

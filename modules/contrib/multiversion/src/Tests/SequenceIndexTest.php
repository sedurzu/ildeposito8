<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\SequenceIndexTest.
 */

namespace Drupal\multiversion\Tests;

use Drupal\entity_test\Entity\EntityTestRev;

/**
 * Test the entity sequence functionality.
 *
 * @group multiversion
 */
class SequenceIndexTest extends MultiversionWebTestBase {

  /**
   * @var \Drupal\multiversion\Entity\Index\SequenceIndexInterface
   */
  protected $sequenceIndex;

  protected function setUp() {
    parent::setUp();
    $this->sequenceIndex = \Drupal::service('entity.index.sequence');
  }

  public function testRecord() {
    $entity = EntityTestRev::create();
    // We don't want to save the entity and trigger the hooks in the storage
    // controller. We just want to test the sequence storage here, so we mock
    // entity IDs here.
    $expected = [
      'entity_type_id' => 'entity_test_rev',
      'entity_id' => 1,
      'entity_uuid' => $entity->uuid(),
      'revision_id' => 1,
      'deleted' => FALSE,
      'rev' => FALSE,
      'local' => (boolean) $entity->getEntityType()->get('local'),
      'is_stub' => FALSE,
    ];
    $entity->id->value = $expected['entity_id'];
    $entity->revision_id->value = $expected['revision_id'];
    $entity->_deleted->value = $expected['deleted'];
    $entity->_rev->value = $expected['rev'];

    $values = $this->sequenceIndex->getRange(2);
    $this->assertEqual(2, count($values), 'There are two index entries');

    $this->sequenceIndex->add($entity);
    $expected['seq'] = $this->multiversionManager->lastSequenceId();

    // We should have 2 entities of user entity type (anonymous and root user)
    // and one entity_test_rev.
    $values = $this->sequenceIndex->getRange(3);
    $this->assertEqual(3, count($values), 'One new index entry was added.');

    foreach ($expected as $key => $value) {
      $this->assertIdentical($value, $values[2][$key], "Index entry key $key have value $value");
    }

    $entity = EntityTestRev::create();
    $workspace_name = $this->randomMachineName();
    /** @var \Drupal\Core\Entity\EntityStorageInterface $workspace_storage */
    $workspace_storage = $this->container->get('entity.manager')->getStorage('workspace');
    $workspace_storage->create(['name' => $workspace_name]);
    // Generate a new sequence ID.
    $this->sequenceIndex->useWorkspace($workspace_name)->add($entity);

    $values = $this->sequenceIndex->getRange(3);
    $this->assertEqual(1, count($values), 'One index entry was added to the new workspace.');
  }

}
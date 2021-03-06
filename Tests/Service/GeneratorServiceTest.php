<?php
namespace Tpg\ExtjsBundle\Tests\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Test\TestBundle\Mockup\TwigEngineMokcup;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Tpg\ExtjsBundle\Service\GeneratorService;

class GeneratorServiceTest extends TestCase {

    /** @var GeneratorService */
    protected $service;

    /** @var TwigEngineMokcup */
    protected $twigEngine;

    protected function setUp() {
        parent::setUp();
        $this->service = new GeneratorService();
        $this->service->setAnnotationReader(new AnnotationReader());
        $this->twigEngine = new TwigEngineMokcup();
        $this->service->setTwigEngine($this->twigEngine);
        $this->service->setModelFieldsParameters(array(
          "date" => array( "format" => "d-m-y")
        ));
    }

    public function testCustomFieldParameters() {
      $this->service->generateMarkupForEntity('Test\TestBundle\Model\Person');
      $fieldsType = array();
      foreach ($this->twigEngine->renderParameters['fields'] as $field) {
        if (isset($field['format'])) {
          $fieldsType[$field['name']] = $field['format'];
        }
      }
      $this->assertEquals("d-m-y", $fieldsType['createdAt']);
    }

    public function testEntityProperty() {
        $this->service->generateMarkupForEntity('Test\TestBundle\Model\Person');
        $this->assertContains("Test.model.Person", $this->twigEngine->renderParameters['name']);
        $fieldsName = array();
        foreach ($this->twigEngine->renderParameters['fields'] as $field) {
            $fieldsName[] = $field['name'];
        }
        $this->assertContains("id", $fieldsName);
        $this->assertContains("firstName", $fieldsName);
        $this->assertContains("lastName", $fieldsName);
        $this->assertNotContains("dob", $fieldsName);
    }

    public function testEntityPropertyType() {
        $this->service->generateMarkupForEntity('Test\TestBundle\Model\Person');
        $fieldsType = array();
        foreach ($this->twigEngine->renderParameters['fields'] as $field) {
            $fieldsType[$field['name']] = $field['type'];
        }
        $this->assertEquals("int", $fieldsType['id']);
        $this->assertEquals("string", $fieldsType['firstName']);
    }

    public function testEntityPropertyValidation() {
        $this->service->generateMarkupForEntity('Test\TestBundle\Model\Person');
        $fields = array();
        foreach ($this->twigEngine->renderParameters['validators'] as $validator) {
            $fields[$validator['field']][] = $validator['type'];
        }
        $this->assertContains("presence", $fields['firstName']);
        $this->assertContains("presence", $fields['lastName']);
        $this->assertContains("email", $fields['email']);
        $this->assertContains("length", $fields['email']);
        $this->assertContains("length", $fields['email']);
    }

    public function testEntityAssociation() {
        $this->service->generateMarkupForEntity('Test\TestBundle\Model\Person');
        $associations = array();
        foreach ($this->twigEngine->renderParameters['associations'] as $assoc) {
            $associations[$assoc['name']] = $assoc;
        }
        $this->assertEquals('Test.model.Book', $associations['books']['model']);
        $this->assertEquals('books', $associations['books']['name']);
        $this->assertEquals('OneToMany', $associations['books']['type']);
        $this->service->generateMarkupForEntity('Test\TestBundle\Model\Book');
        $associations = array();
        foreach ($this->twigEngine->renderParameters['associations'] as $assoc) {
            $associations[$assoc['name']] = $assoc;
        }
        $this->assertEquals('Test.model.Person', $associations['person']['model']);
        $this->assertEquals('person', $associations['person']['name']);
        $this->assertEquals('ManyToOne', $associations['person']['type']);
        $fieldsName = array();
        foreach ($this->twigEngine->renderParameters['fields'] as $field) {
            $fieldsName[] = $field['name'];
        }
    }

    public function testEntityProxy() {
        $this->service->generateMarkupForEntity('Test\TestBundle\Model\Person');
        $parameters = $this->twigEngine->renderParameters;
        $this->assertNotNull($parameters['proxy']);
    }

    public function testGenerateRemotingApi() {
        $this->service->setRemotingBundles(array('TestBundle'=>'Test\TestBundle\TestTestBundle'));
        $api = $this->service->generateRemotingApi();
        $this->assertSame(array(
            'Test.TestBundle'=>array(
                'Test'=>array(
                    array(
                        'name'=>'test',
                        'len'=>0,
                    ),
                    array(
                        'name'=>'test2',
                        'len'=>1,
                    ),
                    array(
                        'name'=>'testRequestParam',
                        'len'=>1,
                    )
                ),
            ),
        ), $api);
    }
}
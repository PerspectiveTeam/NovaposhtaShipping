<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Ui\DataProvider\Box;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\Package\Package\Collection;
use Perspective\NovaposhtaCatalog\Model\ResourceModel\Package\Package\CollectionFactory;
use Perspective\NovaposhtaShipping\Api\BoxManagementInterface;
use Perspective\NovaposhtaShipping\Ui\DataProvider\Box\Form;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    private function form(array $params = [], ?array $persisted = null): Form
    {
        $collections = $this->createStub(CollectionFactory::class);
        $collections->method('create')->willReturn($this->createStub(Collection::class));
        $management = $this->createStub(BoxManagementInterface::class);
        $management->method('getDuplicateData')->willReturn(['description_ua' => 'Pallet', 'max_weight' => 500,
            'boxpacker_enabled' => 0, 'empty_weight' => 0]);
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(fn ($name) => $params[$name] ?? null);
        $persistor = $this->createStub(DataPersistorInterface::class);
        $persistor->method('get')->willReturn($persisted);
        return new Form('boxes', 'id', 'id', $collections, $management, $request, $persistor);
    }

    public function testNewBoxDefaultsUseTheEmptyRequestKeyAndToggleValueMapType(): void
    {
        $form = $this->form();
        $data = $form->getData();
        self::assertArrayHasKey('', $data);
        self::assertSame('1', $data['']['boxpacker_enabled']);
        self::assertSame(0, $data['']['empty_weight']);
        self::assertSame($data, $form->getData());
    }

    public function testDuplicateFormPreservesSourceReferenceOnlyForSubmission(): void
    {
        $data = $this->form(['duplicate_id' => 123])->getData()[''];
        self::assertSame(123, $data['duplicate_id']);
        self::assertSame('0', $data['boxpacker_enabled']);
        self::assertSame(500, $data['max_weight']);
        self::assertArrayNotHasKey('id', $data);
    }

    public function testValidationErrorsPreserveEditableValuesAndCannotReplaceIdentity(): void
    {
        $data = $this->form(['id' => 5], ['id' => 5, 'description_ua' => 'Corrected', 'source' => 'api',
            'ref' => 'api-ref', 'boxpacker_enabled' => '1'])->getData()[5];
        self::assertSame(5, $data['id']);
        self::assertSame('Corrected', $data['description_ua']);
        self::assertSame('1', $data['boxpacker_enabled']);
        self::assertArrayNotHasKey('source', $data);
        self::assertArrayNotHasKey('ref', $data);
        self::assertSame('Pallet', $this->form(['id' => 5], ['id' => 6, 'description_ua' => 'Other'])->getData()[5]['description_ua']);
    }
}

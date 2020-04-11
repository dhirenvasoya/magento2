<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CheckoutAgreements\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\CheckoutAgreements\Model\ResourceModel\Agreement\Collection;
use Magento\Store\Model\Store;
use Magento\CheckoutAgreements\Model\AgreementModeOptions;
use Magento\CheckoutAgreements\Model\AgreementsProvider;
use Magento\Store\Model\ScopeInterface;

class AgreementsProviderTest extends TestCase
{
    /**
     * @var AgreementsProvider
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var MockObject
     */
    protected $agreementCollFactoryMock;

    /**
     * @var MockObject
     */
    protected $storeManagerMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->agreementCollFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $this->model = $objectManager->getObject(
            AgreementsProvider::class,
            [
                'agreementCollectionFactory' => $this->agreementCollFactoryMock,
                'storeManager' => $this->storeManagerMock,
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    public function testGetRequiredAgreementIdsIfAgreementsEnabled()
    {
        $storeId = 100;
        $expectedResult = [1, 2, 3, 4, 5];
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(AgreementsProvider::PATH_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);

        $agreementCollection = $this->createMock(
            Collection::class
        );
        $this->agreementCollFactoryMock->expects($this->once())->method('create')->willReturn($agreementCollection);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $agreementCollection->expects($this->once())->method('addStoreFilter')->with($storeId)->willReturnSelf();
        $agreementCollection->expects($this->at(1))
            ->method('addFieldToFilter')
            ->with('is_active', 1)
            ->willReturnSelf();
        $agreementCollection->expects($this->at(2))
            ->method('addFieldToFilter')
            ->with('mode', AgreementModeOptions::MODE_MANUAL)
            ->willReturnSelf();
        $agreementCollection->expects($this->once())->method('getAllIds')->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->model->getRequiredAgreementIds());
    }

    public function testGetRequiredAgreementIdsIfAgreementsDisabled()
    {
        $expectedResult = [];
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(AgreementsProvider::PATH_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(false);
        $this->assertEquals($expectedResult, $this->model->getRequiredAgreementIds());
    }
}

<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Controller\Adminhtml\Box;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Perspective\NovaposhtaShipping\Block\Adminhtml\Box\AddButton;
use Perspective\NovaposhtaShipping\Controller\Adminhtml\Box\AbstractBox;
use Perspective\NovaposhtaShipping\Controller\Adminhtml\Box\Delete;
use Perspective\NovaposhtaShipping\Controller\Adminhtml\Box\Edit;
use Perspective\NovaposhtaShipping\Controller\Adminhtml\Box\MassAction;
use Perspective\NovaposhtaShipping\Controller\Adminhtml\Box\Save;
use Perspective\NovaposhtaShipping\Controller\Adminhtml\Box\Toggle;
use Perspective\NovaposhtaShipping\Ui\Component\Listing\Column\BoxActions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase
{
    #[DataProvider('controllers')]
    public function testAllControllersRequireTheManagementPermission(string $class): void
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->expects(self::once())->method('isAllowed')->with(AbstractBox::ADMIN_RESOURCE)->willReturn(false);
        $controller = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty($controller, '_authorization'))->setValue($controller, $authorization);
        self::assertFalse((new \ReflectionMethod($controller, '_isAllowed'))->invoke($controller));
    }

    public static function controllers(): array
    {
        return [[Edit::class], [Save::class], [Delete::class], [Toggle::class], [MassAction::class]];
    }

    public function testUnauthorizedAdministratorsSeeNoCreationButtonOrRowActions(): void
    {
        $authorization = $this->createStub(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn(false);
        $urls = $this->createMock(UrlInterface::class);
        $urls->expects(self::never())->method('getUrl');
        self::assertSame([], (new AddButton($urls, $authorization))->getButtonData());
        $column = new BoxActions($this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class), $urls, $authorization, [], ['name' => 'actions']);
        $source = ['data' => ['items' => [['id' => 1, 'source' => 'custom', 'boxpacker_enabled' => 1]]]];
        self::assertSame($source, $column->prepareDataSource($source));
    }
}

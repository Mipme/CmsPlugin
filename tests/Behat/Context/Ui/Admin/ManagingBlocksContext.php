<?php

/**
 * This file was created by the developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on kontakt@bitbag.pl.
 */

namespace Tests\BitBag\CmsPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use BitBag\CmsPlugin\Entity\BlockInterface;
use BitBag\CmsPlugin\Exception\TemplateTypeNotFound;
use BitBag\CmsPlugin\Repository\BlockRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Behat\NotificationType;
use Sylius\Behat\Page\SymfonyPageInterface;
use Sylius\Behat\Service\NotificationCheckerInterface;
use Sylius\Behat\Service\Resolver\CurrentPageResolverInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Tests\BitBag\CmsPlugin\Behat\Behaviour\GenericBlock;
use Tests\BitBag\CmsPlugin\Behat\Page\Admin\Block\CreatePageInterface;
use Tests\BitBag\CmsPlugin\Behat\Page\Admin\Block\IndexPageInterface;
use Tests\BitBag\CmsPlugin\Behat\Page\Admin\Block\UpdatePageInterface;
use Webmozart\Assert\Assert;

/**
 * @author Mikołaj Król <mikolaj.krol@bitbag.pl>
 */
final class ManagingBlocksContext implements Context
{
    /**
     * @var IndexPageInterface
     */
    private $indexPage;

    /**
     * @var CreatePageInterface
     */
    private $createPage;

    /**
     * @var UpdatePageInterface
     */
    private $updatePage;

    /**
     * @var CurrentPageResolverInterface
     */
    private $currentPageResolver;

    /**
     * @var NotificationCheckerInterface
     */
    private $notificationChecker;

    /**
     * @var SharedStorageInterface
     */
    private $sharedStorage;

    /**
     * @var BlockRepositoryInterface
     */
    private $blockRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param IndexPageInterface $indexPage
     * @param CreatePageInterface $createPage
     * @param UpdatePageInterface $updatePage
     * @param CurrentPageResolverInterface $currentPageResolver
     * @param NotificationCheckerInterface $notificationChecker
     * @param SharedStorageInterface $sharedStorage
     * @param BlockRepositoryInterface $blockRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        IndexPageInterface $indexPage,
        CreatePageInterface $createPage,
        UpdatePageInterface $updatePage,
        CurrentPageResolverInterface $currentPageResolver,
        NotificationCheckerInterface $notificationChecker,
        SharedStorageInterface $sharedStorage,
        BlockRepositoryInterface $blockRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->createPage = $createPage;
        $this->updatePage = $updatePage;
        $this->indexPage = $indexPage;
        $this->currentPageResolver = $currentPageResolver;
        $this->notificationChecker = $notificationChecker;
        $this->sharedStorage = $sharedStorage;
        $this->blockRepository = $blockRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @When I go to the cms blocks page
     */
    public function iGoToTheCmsBlocksPage()
    {
        $this->indexPage->open();
    }

    /**
     * @When I go to the create :blockType block page
     */
    public function iGoToTheCreateImageBlockPage($blockType)
    {

        if (BlockInterface::TEXT_BLOCK_TYPE === $blockType) {
            $this->createPage->open(['type' => BlockInterface::TEXT_BLOCK_TYPE]);

            return;
        }

        if (BlockInterface::HTML_BLOCK_TYPE === $blockType) {
            $this->createPage->open(['type' => BlockInterface::HTML_BLOCK_TYPE]);

            return;
        }

        if (BlockInterface::IMAGE_BLOCK_TYPE === $blockType) {
            $this->createPage->open(['type' => BlockInterface::IMAGE_BLOCK_TYPE]);

            return;
        }

        throw new TemplateTypeNotFound($blockType);
    }

    /**
     * @When I go to the update :code block page
     */
    public function iGoToTheUpdateBlockPage($code)
    {
        $id = $this->blockRepository->findOneByCode($code)->getId();

        $this->updatePage->open(['id' => $id]);
    }

    /**
     * @When I fill the code with :code
     */
    public function iFillTheCodeWith($code)
    {
        $this->resolveCurrentPage()->fillCode($code);
    }

    /**
     * @When I fill the name with :name
     */
    public function iFillTheNameWith($name)
    {
        $this->resolveCurrentPage()->fillName($name);
    }

    /**
     * @When I fill the link with :link
     */
    public function iFillTheLinkWith($link)
    {
        $this->resolveCurrentPage()->fillLink($link);
    }

    /**
     * @When I upload the :image image
     */
    public function iUploadTheImage($image)
    {
        $this->resolveCurrentPage()->uploadImage($image);
    }

    /**
     * @When I disable it
     */
    public function iDisableIt()
    {
        $this->resolveCurrentPage()->disable();
    }

    /**
     * @When I fill the content with :content
     */
    public function iFillTheContentWith($content)
    {
        $this->resolveCurrentPage()->fillContent($content);
    }

    /**
     * @When I remove this image block
     */
    public function iRemoveThisImageBlock()
    {
        /** @var BlockInterface $block */
        $block = $this->sharedStorage->get('block');
        $code = $block->getCode();

        $this->indexPage->removeBlock($code);
    }

    /**
     * @When I add it
     */
    public function iAddIt()
    {
        $this->createPage->create();
    }

    /**
     * @When I update it
     */
    public function iUpdateIt()
    {
        $this->updatePage->saveChanges();
    }

    /**
     * @Then no dynamic content blocks should appear in the store
     */
    public function noDynamicContentBlocksShouldAppearInTheStore()
    {
        Assert::eq(0, count($this->blockRepository->findAll()));
    }

    /**
     * @Then I should see :number dynamic content blocks with :type type
     */
    public function iShouldSeeDynamicContentBlocksWithType($number, $type)
    {
        Assert::eq($number, $this->indexPage->getBlocksWithTypeCount($type));
    }

    /**
     * @Then I should be notified that new image block was created
     * @Then I should be notified that new text block was created
     */
    public function iShouldBeNotifiedThatNewImageBlockWasCreated()
    {
        $this->notificationChecker->checkNotification("Block has been successfully created.", NotificationType::success());
    }

    /**
     * @Then I should be notified that the block was successfully updated
     */
    public function iShouldBeNotifiedThatTheBlockWasSuccessfullyUpdated()
    {
        $this->notificationChecker->checkNotification("Block has been successfully updated.", NotificationType::success());
    }

    /**
     * @Then I should be notified that this block was removed
     */
    public function iShouldBeNotifiedThatThisBlockWasRemoved()
    {
        $this->notificationChecker->checkNotification("Block has been successfully deleted.", NotificationType::success());
    }

    /**
     * @Then block with :code code and :content content should exist in the store
     */
    public function blockWithCodeAndContentShouldBeInTheStore($code, $content)
    {
        $block = $this->blockRepository->findOneByCodeAndContent($code, $content);

        Assert::isInstanceOf($block, BlockInterface::class);
    }

    /**
     * @Then block with :type type and :content content should exist in the store
     */
    public function blockWithTypeAndContentShouldBeInTheStore($type, $content)
    {
        $block = $this->blockRepository->findOneByTypeAndContent($type, $content);
        $this->sharedStorage->set('block', $block);

        Assert::isInstanceOf($block, BlockInterface::class);
    }

    /**
     * @Then this block should also have :name name and :link link
     * @Then this block should also have :name name
     */
    public function thisBlockShouldAlsoHaveNameAndLink($name, $link = null)
    {
        /** @var BlockInterface $block */
        $block = $this->sharedStorage->get('block');

        Assert::eq($name, $block->getName());
        Assert::eq($link, $block->getLink());
    }

    /**
     * @Then image block with :code code and :image image should exist in the store
     */
    public function imageBlockWithTypeAndImageShouldBeInTheStore($code, $image)
    {
        $block = $this->blockRepository->findOneByCode($code);
        $blockImage = $block->getImage();
        $this->entityManager->refresh($blockImage);
        $this->sharedStorage->set('block', $block);

        Assert::eq(BlockInterface::IMAGE_BLOCK_TYPE, $block->getType());
        Assert::eq(
            file_get_contents(__DIR__ . '/../../../../Application/web/media/image/' . $block->getImage()->getPath()),
            file_get_contents(__DIR__ . '/../../../Resources/images/' . $image)
        );
    }

    /**
     * @Then I should be able to select between :firstBlockType, :secondBlockType and :thirdBlockType block types under Create button
     */
    public function iShouldBeAbleToSelectBetweenAndBlockTypesUnderCreateButton(...$blockTypes)
    {
        $blockTypesOnPage = $this->indexPage->getBlockTypes();

        Assert::eq(count($blockTypesOnPage), count($blockTypes));

        foreach ($blockTypes as $blockType) {
            Assert::oneOf($blockType, $blockTypesOnPage);
        }
    }

    /**
     * @Then block with :code should not appear in the store
     */
    public function blockWithShouldNotAppearInTheStore($code)
    {
        Assert::null($this->blockRepository->findOneByCode($code));
    }

    /**
     * @return CreatePageInterface|UpdatePageInterface|GenericBlock|SymfonyPageInterface
     */
    private function resolveCurrentPage()
    {
        return $this->currentPageResolver->getCurrentPageWithForm([$this->createPage, $this->updatePage]);
    }
}

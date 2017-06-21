<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 3/29/17 4:29 PM
 * @file: MonkeyStore.php
 */

namespace Ebizmarts\MailChimp\Model\Config\Backend;

class  MonkeyStore extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $_date;
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    private $_storeManager;

    private $oldListId = null;


    /**
     * ApiKey constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Store\Model\StoreManager $storeManager,
        array $data = []
    ) {
        $this->_helper          = $helper;
        $this->resourceConfig   = $resourceConfig;
        $this->_date            = $date;
        $this->_storeManager    = $storeManager;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }


    public function beforeSave()
    {
        $generalData = $this->getData();
        $data = $this->getData('groups');
        $found = 0;
        if (isset($data['ecommerce']['fields']['active']['value'])) {
            $active = $data['ecommerce']['fields']['active']['value'];
        } elseif ($data['ecommerce']['fields']['active']['inherit']) {
            $active = $data['ecommerce']['fields']['active']['inherit'];
        }
        if ($active&&$this->isValueChanged()) {
            $mailchimpStore     = $this->getOldValue();
            $newListId = $data['general']['fields']['monkeylist']['value'];
            $this->oldListId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_LIST, $this->getScopeId());

            $createWebhook = true;
            foreach ($this->_storeManager->getStores() as $storeId => $val) {
                $mstoreId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE, $storeId);
                if ( $mstoreId == $mailchimpStore) {
                    $found++;
                }
                $listId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_LIST, $storeId);
                if ( $listId == $newListId) {
                    $createWebhook = false;
                }
                $this->_helper->deleteConfig(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_JS_URL, $storeId , \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
            }
            if ($found==1) {
                $this->_helper->markAllBatchesAs($mailchimpStore, 'canceled');
            }
            if ($createWebhook) {
                $this->_helper->createWebHook($data['general']['fields']['apikey']['value'], $newListId);
            }
//            $this->_helper->deleteConfig(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_JS_URL, $this->getScopeId() ,$this->getScope());

        }
        return parent::beforeSave();
    }
}
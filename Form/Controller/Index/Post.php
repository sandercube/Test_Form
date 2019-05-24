<?php

namespace Test\Form\Controller\Index;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\Translate\Inline\StateInterface;
use \Magento\Framework\Mail\Template\TransportBuilder;
use \Magento\Framework\App\Config\ScopeConfigInterface;


class Post extends \Magento\Framework\App\Action\Action
{
    const XML_PATH_EMAIL_SENDER_NAME = 'trans_email/ident_general/name';
    const XML_PATH_EMAIL_SENDER_EMAIL = 'trans_email/ident_general/email';

    protected $inlineTranslation;
    protected $transportBuilder;
    protected $scopeConfig;

    public function __construct(
        Context $context,
        StateInterface $inlineTranslation,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if ( ! $post) {
            $this->_redirect('*/*/');

            return;
        }
        try {
            $this->inlineTranslation->suspend();
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

            $sender = [
                'name' => $post['name'],
                'email' => $post['email'],
                'phone' => $post['phone'],
                'message'=> $post['message']
            ];

            $sentToEmail = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_SENDER_EMAIL,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $sentToName = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_SENDER_NAME,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
            ];
            $templateVars    = [
                'name' => $post['name'],
                'email' => $post['email'],
                'phone' => $post['phone'],
                'message'=> $post['message']
            ];
            $from = ['name' => $sender['name'], 'email' => $sender['email']];
            $to = [$sentToEmail];
            $this->inlineTranslation->suspend();
            $transport = $this->transportBuilder->setTemplateIdentifier('test_form_email_template')
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                /*->addTo($to)*/
                ->setFrom($from)
                ->addBcc($to)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            $this->messageManager->addSuccessMessage('Email send successfully');
            $this->_redirect('testform/index/index');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_redirect('testform/index/index');
            exit;
        }
    }
}

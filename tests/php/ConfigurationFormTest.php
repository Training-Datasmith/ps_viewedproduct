<?php

require_once dirname(__FILE__) . '/ViewedProductTestCase.php';

class ConfigurationFormTest extends ViewedProductTestCase
{
    public function testGetContentRendersFormWhenNothingSubmitted()
    {
        $html = $this->module->getContent();
        $this->assertContains('FORM_SENTINEL', $html);
        $this->assertCount(0, Stubs::$updateValueLog);
        $this->assertCount(0, Stubs::$clearCacheLog);
    }

    public function testEmptyValueShowsRequiredError()
    {
        Stubs::$post['submitBlockViewed'] = '1';
        Stubs::$post['PRODUCTS_VIEWED_NBR'] = '';
        $html = $this->module->getContent();
        $this->assertContains('You must fill in the \'Products displayed\' field.', $html);
        $this->assertNotContains('The settings have been updated.', $html);
        $this->assertLoggedTranslationDomain(
            'You must fill in the \'Products displayed\' field.',
            'Modules.Viewedproduct.Admin'
        );
        $this->assertCount(0, Stubs::$updateValueLog);
        $this->assertCount(0, Stubs::$clearCacheLog);
    }

    public function testMissingValueShowsRequiredError()
    {
        Stubs::$post['submitBlockViewed'] = '1';
        $html = $this->module->getContent();
        $this->assertContains('You must fill in the \'Products displayed\' field.', $html);
        $this->assertCount(0, Stubs::$updateValueLog);
    }

    public function testZeroShowsInvalidNumber()
    {
        Stubs::$post['submitBlockViewed'] = '1';
        Stubs::$post['PRODUCTS_VIEWED_NBR'] = '0';
        $html = $this->module->getContent();
        $this->assertContains('Invalid number.', $html);
        $this->assertNotContains('You must fill in the \'Products displayed\' field.', $html);
        $this->assertCount(0, Stubs::$updateValueLog);
    }

    public function testNegativeShowsInvalidNumber()
    {
        Stubs::$post['submitBlockViewed'] = '1';
        Stubs::$post['PRODUCTS_VIEWED_NBR'] = '-4';
        $html = $this->module->getContent();
        $this->assertContains('Invalid number.', $html);
        $this->assertCount(0, Stubs::$updateValueLog);
        $this->assertNotContains('The settings have been updated.', $html);
    }

    public function testNonNumericShowsInvalidNumber()
    {
        Stubs::$post['submitBlockViewed'] = '1';
        Stubs::$post['PRODUCTS_VIEWED_NBR'] = 'abc';
        $html = $this->module->getContent();
        $this->assertContains('Invalid number.', $html);
        $this->assertCount(0, Stubs::$updateValueLog);
    }

    public function testPositiveIntegerIsStoredAndCacheCleared()
    {
        Stubs::$post['submitBlockViewed'] = '1';
        Stubs::$post['PRODUCTS_VIEWED_NBR'] = '6';
        $html = $this->module->getContent();
        $this->assertCount(1, Stubs::$updateValueLog);
        $this->assertSame(6, Stubs::$updateValueLog[0]['value']);
        $this->assertCount(1, Stubs::$clearCacheLog);
        $this->assertSame(
            'module:ps_viewedproduct/views/templates/hook/ps_viewedproduct.tpl',
            Stubs::$clearCacheLog[0]['template']
        );
        $this->assertContains('The settings have been updated.', $html);
        $this->assertLoggedTranslationDomain('The settings have been updated.', 'Admin.Notifications.Success');
        $this->assertNotContains('Invalid number.', $html);
        $this->assertNotContains('You must fill in the \'Products displayed\' field.', $html);
    }

    public function testConfigFieldsPreferSubmittedValue()
    {
        Stubs::$post['PRODUCTS_VIEWED_NBR'] = '9';
        Stubs::$config['PRODUCTS_VIEWED_NBR'] = '4';
        $values = $this->module->getConfigFieldsValues();
        $this->assertSame('9', $values['PRODUCTS_VIEWED_NBR']);
    }

    public function testConfigFieldsFallBackToStoredValue()
    {
        Stubs::$config['PRODUCTS_VIEWED_NBR'] = '4';
        $values = $this->module->getConfigFieldsValues();
        $this->assertSame('4', $values['PRODUCTS_VIEWED_NBR']);
    }

    public function testRenderFormWiresHelperFromContext()
    {
        Stubs::$config['PS_LANG_DEFAULT'] = '3';
        Stubs::$config['PS_BO_ALLOW_EMPLOYEE_FORM_LANG'] = '2';
        $html = $this->module->renderForm();
        $this->assertSame('FORM_SENTINEL', $html);
        $this->assertSame(array(array('AdminModules', false)), Stubs::$linkAdminLinkCalls);
        $helper = HelperForm::$lastInstance;
        $this->assertInstanceOf('HelperForm', $helper);
        $this->assertFalse($helper->show_toolbar);
        $this->assertSame('module', $helper->table);
        $this->assertSame(3, $helper->default_form_language);
        $this->assertSame('2', $helper->allow_employee_form_lang);
        $this->assertSame('id_module', $helper->identifier);
        $this->assertSame('submitBlockViewed', $helper->submit_action);
        $this->assertSame('TOKEN_LITE', $helper->token);
        $this->assertSame(
            Stubs::$linkAdminLink
            . '&configure=ps_viewedproduct&tab_module=front_office_features&module_name=ps_viewedproduct',
            $helper->currentIndex
        );
        $this->assertSame(7, $helper->tpl_vars['id_language']);
        $this->assertSame($this->module->getConfigFieldsValues(), $helper->tpl_vars['fields_value']);
        $this->assertSame(
            $this->module->context->controller->getLanguages(),
            $helper->tpl_vars['languages']
        );
        $this->assertSame('PRODUCTS_VIEWED_NBR', $helper->generatedForms[0]['form']['input'][0]['name']);
    }

    public function testRenderFormTreatsEmptyEmployeeFormLangAsZero()
    {
        unset(Stubs::$config['PS_BO_ALLOW_EMPLOYEE_FORM_LANG']);
        $this->module->renderForm();
        $this->assertSame(0, HelperForm::$lastInstance->allow_employee_form_lang);
    }
}

/**
 * Module: TYPO3/CMS/BoliusFormZendesk/Backend/FormEditor/ViewModel
 */
define(
  ['jquery', 'TYPO3/CMS/Form/Backend/FormEditor/Helper'],
  function($, Helper) {
    'use strict';

    return (function($, Helper) {

      /**
       * @private
       *
       * @var object
       */
      var _formEditorApp = null;

      /**
       * @private
       *
       * @return object
       */
      function getFormEditorApp() {
        return _formEditorApp;
      };

      /**
       * @private
       *
       * @return object
       */
      function getPublisherSubscriber() {
        return getFormEditorApp().getPublisherSubscriber();
      };

      /**
       * @private
       *
       * @return object
       */
      function getUtility() {
        return getFormEditorApp().getUtility();
      };

      /**
       * @private
       *
       * @param object
       * @return object
       */
      function getHelper() {
        return Helper;
      };

      /**
       * @private
       *
       * @return object
       */
      function getCurrentlySelectedFormElement() {
        return getFormEditorApp().getCurrentlySelectedFormElement();
      };

      /**
       * @private
       *
       * @param mixed test
       * @param string message
       * @param int messageCode
       * @return void
       */
      function assert(test, message, messageCode) {
        return getFormEditorApp().assert(test, message, messageCode);
      };

      /**
       * @private
       *
       * @return void
       * @throws 1491643380
       */
      function _helperSetup() {
        assert('function' === $.type(Helper.bootstrap),
          'The view model helper does not implement the method "bootstrap"',
          1491643380
        );
        Helper.bootstrap(getFormEditorApp());
      };

      /**
       * @private
       *
       * @return void
       */
      function _subscribeEvents() {
        /**
         * @private
         *
         * @param string
         * @param array
         *              args[0] = editorConfiguration
         *              args[1] = editorHtml
         *              args[2] = collectionElementIdentifier
         *              args[3] = collectionName
         * @return void
         */
        getPublisherSubscriber().subscribe('view/inspector/editor/insert/perform', function(topic, args) {
          if (args[0]['templateName'] === 'Inspector-ZendeskInspectorEditor') {
            renderZendeskInspectorEditor(
              args[0],
              args[1],
              args[2],
              args[3]
            );
          }
        });
      };

      /**
       * @private
       *
       * @param object editorConfiguration
       * @param object editorHtml
       * @param string collectionElementIdentifier
       * @param string collectionName
       * @return void
       */
      function renderZendeskInspectorEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
        var propertyData, propertyPath, selectElement;

        propertyPath = getFormEditorApp().buildPropertyPath(
          editorConfiguration['propertyPath'],
          collectionElementIdentifier,
          collectionName
        );

        getHelper()
        .getTemplatePropertyDomElement('label', editorHtml)
        .append(editorConfiguration['label']);

        var dynamicOptionsTmpl = getHelper()
        .getTemplatePropertyDomElement('dynamicOptions', editorHtml).get(0).content;

        function _createOption(elm, propertyData){
          var option;

          if (elm['value'] === propertyData) {
            option = new Option(elm.label, elm.value, false, true);
          } else {
            option = new Option(elm.label, elm.value);
          }

          $(option).data({value: elm['value']});

          return option;
        }

        propertyData = getCurrentlySelectedFormElement().get(propertyPath);
        selectElement = getHelper().getTemplatePropertyDomElement('selectOptions', editorHtml);

        var tmplChildren = dynamicOptionsTmpl.children;

        for (let i = 0; i < tmplChildren.length; i++) {
          if(tmplChildren[i].tagName === 'OPTGROUP'){

            var optGrp = document.createElement('optgroup');
            optGrp.label = tmplChildren[i].label;
            selectElement.append(optGrp);

            var optGrpC = tmplChildren[i].children;

            for (let x = 0; x < optGrpC.length; x++) {
              if (optGrpC[x].tagName === 'OPTION'){
                optGrp.append(_createOption(optGrpC[x], propertyData));
              }
            }

          } else if (tmplChildren[i].tagName === 'OPTION'){
            selectElement.append(_createOption(tmplChildren[i], propertyData));
          }
        }

        selectElement.on('change', function() {
          console.log($('option:selected', $(this)).data('value'));
          getCurrentlySelectedFormElement().set(propertyPath, $('option:selected', $(this)).data('value'));
        });
      };

      /**
       * @public
       *
       * @param object formEditorApp
       * @return void
       */
      function bootstrap(formEditorApp) {
        _formEditorApp = formEditorApp;
        _helperSetup();
        _subscribeEvents();
      };

      /**
       * Publish the public methods.
       * Implements the "Revealing Module Pattern".
       */
      return {
        bootstrap: bootstrap
      };

  })($, Helper);
});

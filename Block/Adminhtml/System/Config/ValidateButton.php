<?php
namespace Azguards\WhatsAppConnect\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ValidateButton extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $ajaxUrl = $this->getUrl('whatsappconnect/validate/credentials'); // Your controller
        return '
        <button id="wa_validate_btn" type="button" class="scalable" onclick="validateCredentials()">Generate Token</button>
        <div id="wa-validation-message" style="margin-top:10px;"></div>
        <script type="text/javascript">
            require(["jquery", "Magento_Ui/js/modal/alert"], function($, alert) {
                window.validateCredentials = function() {
                    var clientId = $("#whatsApp_conector_general_client_id").val();
                    var clientSecret = $("#whatsApp_conector_general_client_secret_key").val();
                    var grantType = $("#whatsApp_conector_general_grant_type").val();
                    var authUrl = $("#whatsApp_conector_general_authentication_api_url").val();
                    if (!clientId || !clientSecret || !grantType || !authUrl) {
                        alert({ title: "Error", content: "Please fill in all required fields before validating." });
                        return;
                    }
                    $.ajax({
                        url: "' . $ajaxUrl . '",
                        type: "POST",
                        data: {
                            client_id: clientId,
                            client_secret: clientSecret,
                            grant_type: grantType,
                            auth_url: authUrl
                        },
                        dataType: "json",
                        showLoader: true,
                        success: function(response) {
                            var typeClass = response.success ? "message-success success" : "message-error error";
                            var messageHtml = \'<div class="messages"><div class="message \' + typeClass + \'"><div>\' + response.message + \'</div></div></div>\';
                            $("#wa-validation-message").html(messageHtml);
                        },
                        error: function(xhr) {
                            $("#wa-validation-message").html(
                                \'<div class="messages"><div class="message message-error error"><div>Error occurred. Check console.</div></div></div>\'
                            );
                            console.error(xhr.responseText);
                        }
                    });
                }
            });
        </script>';
    }

}
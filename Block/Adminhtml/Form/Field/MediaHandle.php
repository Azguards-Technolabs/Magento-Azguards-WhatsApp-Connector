<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class MediaHandle extends Field
{
    /**
     * Render media-handle configuration UI.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $elementId = $element->getHtmlId();
        $name = $element->getName();
        $currentValue = (string)$element->getValue();

        $uploadUrl = $this->getUrl('whatsappconnect/campaign/upload');
        $resolveUrl = $this->getUrl('whatsappconnect/config/resolvemedia');
        $metaUrl = $this->getUrl('whatsappconnect/config/templatemeta');
        $previewUnavailableText = $this->escapeJs(__('Media handle saved. Preview not available yet.'));
        $openMediaText = $this->escapeJs(__('Open uploaded media'));
        $previewLookupFailedText = $this->escapeJs(__('Media handle saved. Preview lookup failed.'));
        $defaultHeaderHintText = $this->escapeJs(
            __(
                'Template has a media header. If you don’t upload here, '
                . 'the template’s default header media will be used.'
            )
        );
        $uploadHandleHintText = $this->escapeJs(
            __('Template has a media header. Upload once to generate a reusable Media Handle.')
        );

        $html = '<div class="wa-media-config wa-hidden">';
        $html .= '<input type="hidden" id="' . $elementId . '" name="' . $name
            . '" value="' . $this->escapeHtmlAttr($currentValue) . '"/>';

        $html .= '<div class="wa-media-config__row">';
        $html .= '<button type="button" class="action-secondary wa-media-config__upload-btn"><span>'
            . __('Upload Header Media') . '</span></button>';
        $html .= '<span class="wa-media-config__status"></span>';
        $html .= '</div>';

        $html .= '<div class="wa-media-config__preview wa-hidden"></div>';
        $html .= '<div class="wa-media-config__hint wa-hidden"></div>';
        $html .= '</div>';

        $html .= '<script>
require(["jquery"], function ($) {
    var $wrap = $("#' . $elementId . '").closest(".wa-media-config");
    if (!$wrap.length) { return; }
    var $row = $wrap.closest("tr");

    var $hidden = $("#' . $elementId . '");
    var $status = $wrap.find(".wa-media-config__status");
    var $btn = $wrap.find(".wa-media-config__upload-btn");
    var $preview = $wrap.find(".wa-media-config__preview");
    var $hint = $wrap.find(".wa-media-config__hint");
    var $fieldset = $wrap.closest("fieldset");
    var $templateSelect = $fieldset.find("select[class*=searchable-dropdown-]").first();
    var currentHeaderFormat = "";

    var $file = $("<input/>", { type: "file", style: "display:none" })
        .attr("accept", ".jpg,.jpeg,.png,.mp4,.3gp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx")
        .appendTo($wrap);

    function renderPreview(handle) {
        if (!handle) {
            $preview.addClass("wa-hidden").empty();
            return;
        }

        $.getJSON("' . $resolveUrl . '", { handler: handle })
            .done(function (resp) {
                var url = resp && resp.url ? resp.url : "";
                if (!url) {
                    $preview.removeClass("wa-hidden").html(
                        "<div class=\\"wa-upload-status\\">' . $previewUnavailableText . '</div>"
                    );
                    return;
                }
                if (currentHeaderFormat === "IMAGE") {
                    $preview.removeClass("wa-hidden").html(
                        "<div class=\\"wa-media-thumb\\"><img src=\\""
                            + url
                            + "\\" alt=\\"Header image\\"></div>"
                    );
                } else {
                    $preview.removeClass("wa-hidden").html(
                        "<a href=\\""
                            + url
                            + "\\" target=\\"_blank\\" rel=\\"noopener\\">' . $openMediaText . '</a>"
                    );
                }
            })
            .fail(function () {
                $preview.removeClass("wa-hidden").html(
                    "<div class=\\"wa-upload-status\\">' . $previewLookupFailedText . '</div>"
                );
            });
    }

    function updateVisibilityByTemplate() {
        if (!$templateSelect.length) {
            // If we cannot detect template, hide to avoid showing upload UI incorrectly.
            $wrap.addClass("wa-hidden");
            $row.addClass("wa-hidden");
            return;
        }

        var selectedTemplateId = $templateSelect.val() || "";
        if (!selectedTemplateId) {
            $wrap.addClass("wa-hidden");
            $row.addClass("wa-hidden");
            return;
        }

        $.getJSON("' . $metaUrl . '", { template_id: selectedTemplateId })
            .done(function (meta) {
                var hasMedia = !!(meta && meta.has_media_header);
                currentHeaderFormat = meta && meta.header_format ? String(meta.header_format).toUpperCase() : "";

                if (!hasMedia) {
                    $wrap.addClass("wa-hidden");
                    $row.addClass("wa-hidden");
                    $hint.addClass("wa-hidden").empty();
                    return;
                }

                $wrap.removeClass("wa-hidden");
                $row.removeClass("wa-hidden");
                if (meta && meta.header_handle) {
                    $hint.removeClass("wa-hidden").html(
                        "' . $defaultHeaderHintText . '"
                    );
                    if (!$hidden.val()) {
                        // Preview template default handle, but don’t persist it into config silently.
                        renderPreview(meta.header_handle);
                    }
                } else {
                    $hint.removeClass("wa-hidden").html(
                        "' . $uploadHandleHintText . '"
                    );
                }

                if ($hidden.val()) {
                    renderPreview($hidden.val());
                }
            })
            .fail(function () {
                // Conservative: keep hidden on errors so we never show upload UI for non-media templates.
                $wrap.addClass("wa-hidden");
                $row.addClass("wa-hidden");
            });
    }

    updateVisibilityByTemplate();

    $btn.on("click", function () { $file.trigger("click"); });
    $templateSelect.on("change", updateVisibilityByTemplate);

    $file.on("change", function () {
        var file = this.files && this.files[0] ? this.files[0] : null;
        if (!file) { return; }

        $status.text("' . $this->escapeJs(__('Uploading...')) . '").css("color", "#ed672d");
        $btn.prop("disabled", true);

        var formData = new FormData();
        formData.append("media_upload", file);
        formData.append("form_key", window.FORM_KEY);
        formData.append("param_name", "media_upload");

        $.ajax({
            url: "' . $uploadUrl . '",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json"
        }).done(function (resp) {
            $btn.prop("disabled", false);
            if (resp && resp.media_handle) {
                $hidden.val(resp.media_handle);
                $status.text("' . $this->escapeJs(__('Upload Successful!')) . '").css("color", "#27ae60");
                if (resp.url) {
                    currentHeaderFormat = currentHeaderFormat || "IMAGE";
                    if (currentHeaderFormat === "IMAGE") {
                        $preview.removeClass("wa-hidden").html(
                            "<div class=\\"wa-media-thumb\\"><img src=\\""
                                + resp.url
                                + "\\" alt=\\"Header image\\"></div>"
                        );
                    } else {
                        $preview.removeClass("wa-hidden").html(
                            "<a href=\\""
                                + resp.url
                                + "\\" target=\\"_blank\\" rel=\\"noopener\\">' . $openMediaText . '</a>"
                        );
                    }
                } else {
                    renderPreview(resp.media_handle);
                }
            } else {
                $status.text("' . $this->escapeJs(__('Upload Failed.')) . '").css("color", "#e74c3c");
            }
        }).fail(function () {
            $btn.prop("disabled", false);
            $status.text("' . $this->escapeJs(__('Upload Failed.')) . '").css("color", "#e74c3c");
        });
    });
});
</script>';

        return $html;
    }
}

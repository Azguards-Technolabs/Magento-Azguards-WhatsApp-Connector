define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry',
    'jquery'
], function (Select, registry, $) {
    'use strict';

    return Select.extend({
        initialize: function () {
            this._super();
            this.updateVisibility(this.value());
            this.scheduleVisibilityRefresh();

            return this;
        },

        onUpdate: function (value) {
            this.updateVisibility(value);
            this.scheduleVisibilityRefresh();

            return this._super();
        },

        updateVisibility: function (value) {
            var normalizedValue = (value || '').toString().toUpperCase(),
                isCarousel = normalizedValue === 'CAROUSEL',
                isMedia = normalizedValue === 'MEDIA' || normalizedValue === 'IMAGE';

            this.applySectionVisibility(isCarousel);

            return this;
        },

        scheduleVisibilityRefresh: function () {
            var self = this;

            window.setTimeout(function () {
                self.applySectionVisibility(self.isCarouselSelected());
            }, 0);

            window.setTimeout(function () {
                self.applySectionVisibility(self.isCarouselSelected());
            }, 300);

            window.setTimeout(function () {
                self.applySectionVisibility(self.isCarouselSelected());
            }, 1000);

            return this;
        },

        isCarouselSelected: function () {
            return (this.value() || '').toString().toUpperCase() === 'CAROUSEL';
        },

        applySectionVisibility: function (isCarousel) {
            var display = isCarousel ? 'none' : 'block';
            var carouselDisplay = isCarousel ? 'block' : 'none';

            registry.async('whatsapp_template_form.header_section')(function (component) {
                if (typeof component.visible === 'function') {
                    component.visible(!isCarousel);
                }
            });

            registry.async('whatsapp_template_form.carousel_section')(function (component) {
                if (typeof component.visible === 'function') {
                    component.visible(isCarousel);
                }
            });

            $(function () {
                $('.whatsapp-header-section').css('display', display);
                $('.whatsapp-buttons-section').css('display', display);
                $('.whatsapp-footer-section').css('display', display);
                $('.whatsapp-carousel-section').css('display', carouselDisplay);
            });
        }
    });
});

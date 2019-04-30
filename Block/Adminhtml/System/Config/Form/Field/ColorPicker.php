<?php

namespace Cloudinary\Cloudinary\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class ColorPicker extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * add color picker in admin configuration fields
     * @param  AbstractElement $element
     * @return string script
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = $element->getElementHtml();
        $value = $element->getData('value');

        $html .= '<script type="text/javascript">
            require(["jquery", "jquery/colorpicker/js/colorpicker"], function ($) {
                $(document).ready(function () {
                    var $el = $("#' . $element->getHtmlId() . '");
                    $el.css("backgroundColor", $el.val()).val("#" + $el.val().replace(/^(#)/,"").substring(0, 6));
                    $el.ColorPicker({
                        color: "#" + $el.val().replace(/^(#)/,"").substring(0, 6),
                        onChange: function (hsb, hex, rgb) {
                            $el.css("backgroundColor", "#" + hex).val("#" + hex);
                        }
                    });
                    $el.on("change keyup focus", function(){
                        var currentVal = $(this).val().replace(/^(#)/,"").substring(0, 6);
                        $(this).val("#" + currentVal);
                        $(this).css("backgroundColor", "#" + currentVal);
                        $(this).ColorPickerSetColor("#" + currentVal);
                    });
                });
            });
            </script>';

        return $html;
    }
}

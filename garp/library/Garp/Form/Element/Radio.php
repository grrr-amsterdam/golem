<?php
/**
 * Garp_Form_Element_Radio
 * class description
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_Element_Radio extends Zend_Form_Element_Radio {

	public function init() {
		$htmlLegendClass = 'multi-input-legend';
		if ($this->isRequired()) {
			$htmlLegendClass .= ' required';
		}
		$labelText = $this->getLabel();
		if ($this->getDecorator('Label')->getRequiredSuffix() && $this->isRequired()) {
			$labelText .= $this->getDecorator('Label')->getRequiredSuffix();
		}
		$legendHtml = '<p class="'.$htmlLegendClass.'">'.$labelText.'</p>';

		$ulClass = 'multi-input';
		if ($this->isrequired()) {
			$ulClass .= ' required';
		}
		if ($defaulthtmltagrenderer = $this->getdecorator('htmltag')) {
			$parentclass = $defaulthtmltagrenderer->getoption('class');
			$ulClass .= ' '.$parentclass;
		}

		$this->setOptions(array(
			'separator' => '</li><li>',
			'decorators' => array(
				'ViewHelper',
				'Description',
				array(array('tag1' => 'HtmlTag'), array('tag' => 'li')),
				array(array('tag2' => 'HtmlTag'), array('tag' => 'ul', 'class' => $ulClass)),
				array('AnyMarkup', array('markup' => $legendHtml, 'placement' => 'prepend')),
				'Errors',
				array(array('tag3' => 'HtmlTag'), array('tag' => 'div', 'class' => 'multi-input-container')),
			)
		));

		$optionKeys = array_keys($this->options);
		$this->setValue($optionKeys[0]);
	}

}

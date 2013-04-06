<?php
require_once PATH_EXT.'/pfbc/PFBC/Form.php';

/**
 * Description of form
 *
 ** @author Valentin Balt <valentin.balt@gmail.com>
 */
class Form
{
	const ELEMENTS = 'elements';
	const NAME = 'name';
	const LABEL = 'label';
	const TYPE = 'type';
	
	static public function value($value)
	{
		return htmlspecialchars($value);
	}
	
	static public function render($form, $output=false)
	{
		$pfbc = new PFBC\Form(
				isset($form['form']['name'])
					? $form['form']['name']
					: 'form'
				);
		
		$pfbc->configure(array(
			"action" => isset($form['form']['action'])
							? $form['form']['action']
							: \Url::getPath(),
			"prevent" => array(
				"bootstrap", "jQuery",
				'http://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js',
				'https://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js'
				),
			'jQueryOptions' => array (
				'changeMonth' => 'true',
				'changeYear' => 'true',
			),
		));
		
		if (isset ($form['form']['legend'])) {
			$pfbc->addElement(new PFBC\Element\HTML('<legend>' . $form['form']['legend'] . '</legend>'));
		}
		if (isset ($form['hidden'])) {
			foreach ($form['elements']['hidden'] as $el) {
				$pfbc->addElement(new PFBC\Element\Hidden($el['name'], $el['value']));
			}
		}
		foreach ($form['elements'] as $el) {
			if (!isset($el['properties'])) {
				$el['properties']['required'] = 1;
			}
			if (isset($el['properties']['required']) && !$el['properties']['required']) {
				unset ($el['properties']['required']);
			}
			if (isset($el['properties']['readonly']) && $el['properties']['readonly']) {
				$el['properties']['readonly'] = 'readonly';
			}
			if ($el['type'] == 'HTML') {
				$p1 = $el['value'];
				$p2 = $p3 = $p4 = null;
			} elseif (in_array ($el['type'], array (
				'Select', 'Radio', 'Checkbox', 'Checksort', 'Sort'
			))) {
				if (isset($el['value'])) {
					$el['properties']['value'] = $el['value'];
				}
				$p1 = isset($el['label']) ? $el['label'] : '';
				$p2 = isset($el['name']) ? $el['name'] : '';
				$p3 = $el['options'];
				$p4 = isset($el['properties']) ? $el['properties'] : null;
			} elseif (in_array ($el['type'], array (
				'Hidden'
			))) {
				$p1 = isset($el['name']) ? $el['name'] : '';
				$p2 = isset($el['value']) ? $el['value'] : '';
				$p3 = isset($el['properties']) ? $el['properties'] : null;
				$p4 = null;
			} else {
				$p1 = isset($el['label']) ? $el['label'] : '';
				$p2 = isset($el['name']) ? $el['name'] : '';
				$p3 = array_merge(
							array ('value' => isset($el['value'])?$el['value']:''),
							isset($el['properties']) ? $el['properties'] : null
						);
				$p4 = null;
			}
			$Element = 'PFBC\\Element\\' . $el['type'];
			$pfbc->addElement(
					new $Element (
						$p1,
						$p2,
						$p3,
						$p4
					)
			);
		}
		try {
			return $pfbc->render($output);
		} catch (Exception $e) {
			
		}
	}
	
}

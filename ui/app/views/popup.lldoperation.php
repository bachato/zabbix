<?php
/*
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/


/**
 * @var CView $this
 * @var array $data
 */

$output = [
	'header' => $data['title']
];

$options = $data['options'];
$field_values = $data['field_values'];

$operations_popup_form = (new CForm())
	->setId('lldoperation_form')
	->addVar('no', $options['no'])
	->addItem((new CVar('readonly', $options['readonly']))->removeId())
	->addVar('action', 'popup.lldoperation');

// Enable form submitting on Enter.
$operations_popup_form->addItem((new CSubmitButton())->addClass(ZBX_STYLE_FORM_SUBMIT_HIDDEN));

$operations_popup_form_list = (new CFormList())
	->addRow(
		(new CLabel(_('Object'), 'label-operationobject')),
		(new CSelect('operationobject'))
			->setValue($options['operationobject'])
			->setFocusableElementId('label-operationobject')
			->addOptions(CSelect::createOptionsFromArray([
				OPERATION_OBJECT_ITEM_PROTOTYPE => _('Item prototype'),
				OPERATION_OBJECT_TRIGGER_PROTOTYPE => _('Trigger prototype'),
				OPERATION_OBJECT_GRAPH_PROTOTYPE => _('Graph prototype'),
				OPERATION_OBJECT_HOST_PROTOTYPE => _('Host prototype'),
				OPERATION_OBJECT_LLD_RULE_PROTOTYPE => _('Discovery prototype')
			]))
			->setId('operationobject')
			->setReadonly($options['readonly'])
	)
	->addRow((new CLabel(_('Condition'), 'label-operator')), [
		(new CSelect('operator'))
			->setValue($options['operator'])
			->setFocusableElementId('label-operator')
			->addOptions(CSelect::createOptionsFromArray([
				CONDITION_OPERATOR_EQUAL  => _('equals'),
				CONDITION_OPERATOR_NOT_EQUAL  => _('does not equal'),
				CONDITION_OPERATOR_LIKE  => _('contains'),
				CONDITION_OPERATOR_NOT_LIKE  => _('does not contain'),
				CONDITION_OPERATOR_REGEXP => _('matches'),
				CONDITION_OPERATOR_NOT_REGEXP => _('does not match')
			]))
			->setReadonly($options['readonly'])
			->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
		(new CTextBox('value', $options['value'], $options['readonly'],
				DB::getFieldLength('lld_override_operation', 'value')))
			->setWidth(ZBX_TEXTAREA_MACRO_VALUE_WIDTH)
			->setAttribute('placeholder', _('pattern'))
	])
	->addRow(
		(new CVisibilityBox('visible[opstatus]', 'opstatus_status', _('Original')))
			->setLabel(_('Create enabled'))
			->setChecked(array_key_exists('opstatus', $options))
			->setReadonly($options['readonly']),
		(new CRadioButtonList('opstatus[status]', (int) $field_values['opstatus']['status']))
			->addValue(_('Yes'), ZBX_PROTOTYPE_STATUS_ENABLED)
			->addValue(_('No'), ZBX_PROTOTYPE_STATUS_DISABLED)
			->setModern(true)
			->setReadonly($options['readonly']),
		'opstatus_row'
	)
	->addRow(
		(new CVisibilityBox('visible[opdiscover]', 'opdiscover_discover', _('Original')))
			->setLabel(_('Discover'))
			->setChecked(array_key_exists('opdiscover', $options))
			->setReadonly($options['readonly']),
		(new CRadioButtonList('opdiscover[discover]', (int) $field_values['opdiscover']['discover']))
			->addValue(_('Yes'), ZBX_PROTOTYPE_DISCOVER)
			->addValue(_('No'), ZBX_PROTOTYPE_NO_DISCOVER)
			->setModern(true)
			->setReadonly($options['readonly']),
		'opdiscover_row'
	);

$custom_intervals = (new CTable())
	->setId('lld_overrides_custom_intervals')
	->setHeader([
		new CColHeader(_('Type')),
		new CColHeader(_('Interval')),
		new CColHeader(_('Period')),
		''
	])
	->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
	->setAttribute('style', 'min-width: '.ZBX_TEXTAREA_STANDARD_WIDTH.'px;');

foreach ($field_values['opperiod']['delay_flex'] as $i => $delay_flex) {
	$type_input = (new CRadioButtonList('opperiod[delay_flex]['.$i.'][type]', (int) $delay_flex['type']))
		->addValue(_('Flexible'), ITEM_DELAY_FLEXIBLE)
		->addValue(_('Scheduling'), ITEM_DELAY_SCHEDULING)
		->setModern(true)
		->setReadonly($options['readonly']);

	if ($delay_flex['type'] == ITEM_DELAY_FLEXIBLE) {
		$delay_input = (new CTextBox('opperiod[delay_flex]['.$i.'][delay]', $delay_flex['delay'],
				$options['readonly']))
			->setAttribute('placeholder', ZBX_ITEM_FLEXIBLE_DELAY_DEFAULT);
		$period_input = (new CTextBox('opperiod[delay_flex]['.$i.'][period]', $delay_flex['period'],
				$options['readonly']))
			->setAttribute('placeholder', ZBX_DEFAULT_INTERVAL);
		$schedule_input = (new CTextBox('opperiod[delay_flex]['.$i.'][schedule]', '', $options['readonly']))
			->setAttribute('placeholder', ZBX_ITEM_SCHEDULING_DEFAULT)
			->setAttribute('style', 'display: none;');
	}
	else {
		$delay_input = (new CTextBox('opperiod[delay_flex]['.$i.'][delay]', '', $options['readonly']))
			->setAttribute('placeholder', ZBX_ITEM_FLEXIBLE_DELAY_DEFAULT)
			->setAttribute('style', 'display: none;');
		$period_input = (new CTextBox('opperiod[delay_flex]['.$i.'][period]', '', $options['readonly']))
			->setAttribute('placeholder', ZBX_DEFAULT_INTERVAL)
			->setAttribute('style', 'display: none;');
		$schedule_input = (new CTextBox('opperiod[delay_flex]['.$i.'][schedule]', $delay_flex['schedule'],
				$options['readonly']))
			->setAttribute('placeholder', ZBX_ITEM_SCHEDULING_DEFAULT);
	}

	$button = (new CButton('opperiod[delay_flex]['.$i.'][remove]', _('Remove')))
		->addClass(ZBX_STYLE_BTN_LINK)
		->addClass('element-table-remove')
		->setEnabled(!$options['readonly']);

	$custom_intervals->addRow([$type_input, [$delay_input, $schedule_input], $period_input, $button], 'form_row');
}

$custom_intervals->addRow([(new CButton('interval_add', _('Add')))
	->addClass(ZBX_STYLE_BTN_LINK)
	->addClass('element-table-add')
	->setEnabled(!$options['readonly'])
	->removeId()
]);

$operations_popup_form_list
	->addRow(
		(new CVisibilityBox('visible[opperiod]', 'opperiod', _('Original')))
			->setLabel(_('Update interval'))
			->setChecked(array_key_exists('opperiod', $options))
			->setReadonly($options['readonly']),
		(new CFormList('opperiod'))
			->addClass(ZBX_STYLE_TABLE_SUBFORMS)
			->addRow(_('Delay'),
				(new CTextBox('opperiod[delay]', $field_values['opperiod']['delay'], $options['readonly']))
					->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
			)
			->addRow(_('Custom intervals'), $custom_intervals),
		'opperiod_row'
	)
	->addRow(
		(new CVisibilityBox('visible[ophistory]', 'ophistory-field', _('Original')))
			->setLabel(_('History'))
			->setChecked(array_key_exists('ophistory', $options))
			->setReadonly($options['readonly']),
		(new CDiv([
			(new CRadioButtonList('ophistory[history_mode]', (int) $field_values['ophistory']['history_mode']))
				->addValue(_('Do not store'), ITEM_STORAGE_OFF)
				->addValue(_('Store up to'), ITEM_STORAGE_CUSTOM)
				->setModern(true)
				->setReadonly($options['readonly']),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			(new CTextBox('ophistory[history]', $field_values['ophistory']['history'], $options['readonly'],
					DB::getFieldLength('lld_override_ophistory', 'history')))
				->setWidth(ZBX_TEXTAREA_TINY_WIDTH)
				->setAriaRequired()
		]))
			->addClass('wrap-multiple-controls')
			->setId('ophistory-field'),
		'ophistory_row'
	)
	->addRow(
		(new CVisibilityBox('visible[optrends]', 'optrends-field', _('Original')))
			->setLabel(_('Trends'))
			->setChecked(array_key_exists('optrends', $options))
			->setReadonly($options['readonly']),
		(new CDiv([
			(new CRadioButtonList('optrends[trends_mode]', (int) $field_values['optrends']['trends_mode']))
				->addValue(_('Do not store'), ITEM_STORAGE_OFF)
				->addValue(_('Store up to'), ITEM_STORAGE_CUSTOM)
				->setModern(true)
				->setReadonly($options['readonly']),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			(new CTextBox('optrends[trends]', $field_values['optrends']['trends'], $options['readonly'],
					DB::getFieldLength('lld_override_optrends', 'trends')))
				->setWidth(ZBX_TEXTAREA_TINY_WIDTH)
				->setAriaRequired()
		]))
			->addClass('wrap-multiple-controls')
			->setId('optrends-field'),
		'optrends_row'
	)
	->addRow(
		(new CVisibilityBox('visible[opseverity]', 'opseverity_severity', _('Original')))
			->setLabel(_('Severity'))
			->setChecked(array_key_exists('opseverity', $options))
			->setReadonly($options['readonly']),
		(new CSeverity('opseverity[severity]', (int) $field_values['opseverity']['severity']))
			->setReadonly($options['readonly'])
			->setId('opseverity_severity'),
		'opseverity_row'
	)
	->addRow(
		(new CVisibilityBox('visible[optemplate]', 'optemplate-field', _('Original')))
			->setLabel(_('Link templates'))
			->setChecked(array_key_exists('optemplate', $options))
			->setReadonly($options['readonly']),
		(new CMultiSelect([
			'name' => 'optemplate[]',
			'object_name' => 'templates',
			'multiselect_id' => 'optemplate-field',
			'data' => $field_values['optemplate'],
			'readonly' => (bool) $options['readonly'],
			'popup' => [
				'parameters' => [
					'srctbl' => 'templates',
					'srcfld1' => 'hostid',
					'srcfld2' => 'host',
					'dstfrm' => 'lldoperation_form',
					'dstfld1' => 'optemplate_'
				]
			]
		]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH),
		'optemplate_row'
	)
	->addRow(
		(new CVisibilityBox('visible[optag]', 'optag-field', _('Original')))
			->setLabel(_('Tags'))
			->setChecked(array_key_exists('optag', $options))
			->setReadonly($options['readonly']),
		renderTagTable($field_values['optag'], $options['readonly'], ['field_name' => 'optag', 'add_post_js' => false])
			->setHeader([
				(new CColHeader(_('Name')))->setWidth('50%'),
				(new CColHeader(_('Value')))->setWidth('50%'),
				''
			])
			->setId('optag-field')
			->addClass(ZBX_STYLE_TABLE_FORMS_SEPARATOR)
			->addClass('tags-table'),
		'optag_row'
	)
	->addRow(
		(new CVisibilityBox('visible[opinventory]', 'opinventory_inventory_mode', _('Original')))
			->setLabel(_('Host inventory'))
			->setChecked(array_key_exists('opinventory', $options))
			->setReadonly($options['readonly']),
		(new CRadioButtonList('opinventory[inventory_mode]', (int) $field_values['opinventory']['inventory_mode']))
			->addValue(_('Disabled'), HOST_INVENTORY_DISABLED)
			->addValue(_('Manual'), HOST_INVENTORY_MANUAL)
			->addValue(_('Automatic'), HOST_INVENTORY_AUTOMATIC)
			->setModern(true)
			->setReadonly($options['readonly']),
		'opinventory_row'
	);

$output['buttons'] = [
	[
		'title' => ($options['no'] > 0) ? _('Update') : _('Add'),
		'class' => '',
		'keepOpen' => true,
		'enabled' => !$options['readonly'],
		'isSubmit' => true,
		'action' => 'return lldoverrides.operations.edit_form.validate(overlay);'
	]
];

$operations_popup_form->addItem($operations_popup_form_list);

// Operations editing form.
$output['body'] = (new CDiv($operations_popup_form))->toString();
$output['script_inline'] = 'lldoverrides.operations.onOperationOverlayReadyCb('.$options['no'].');';
// Get JS generated by CVisibilityBox fields and for multiselect fields.
$output['script_inline'] .= getPagePostJs();
// Unused action rows should be "removed from dom" only after post JS will be done.
$output['script_inline'] .= 'lldoverrides.operations.edit_form.initHideActionRows();';

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop();
	$output['debug'] = CProfiler::getInstance()->make()->toString();
}

echo json_encode($output);

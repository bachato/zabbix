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


class CControllerValueMapCheck extends CController {

	protected function init() {
		$this->disableCsrfValidation();
	}

	protected function checkInput() {
		$check_source = $this->validateInput([
			'source' => 'required|in host,template,massupdate',
			'valuemap_names' => 'array'
		]);

		if (!$check_source) {
			$this->setResponse(
				new CControllerResponseData(['main_block' => json_encode([
					'error' => [
						'messages' => array_column(get_and_clear_messages(), 'message')
					]
				])])
			);
		}

		switch ($this->getInput('source')) {
			case 'host':
				$rules = CControllerHostCreate::getValidationRules()['fields']['valuemaps'];

				if ($this->hasInput('valuemap_names')) {
					$rules['fields']['name'] += ['not_in' => $this->getInput('valuemap_names')];

					if (!array_key_exists('messages', $rules['fields']['name'])) {
						$rules['fields']['name']['messages'] = [];
					}
					$rules['fields']['name']['messages'] += ['not_in' => _('Given valuemap name is already taken.')];
				}

				$this->setInputValidationMethod(self::INPUT_VALIDATION_FORM);
				$ret = $this->validateInput($rules) && $this->validateValueMap();

				$form_errors = $this->getValidationError();
				$response = $form_errors
					? ['form_errors' => $form_errors]
					: ['error' => [
						'messages' => array_column(get_and_clear_messages(), 'message')
					]];

				$this->setResponse(new CControllerResponseData(['main_block' => json_encode($response)]));
				break;

			default:
				$fields = [
					'mappings' => 'array',
					'name' => 'string',
					'valuemapid' => 'id',
					'valuemap_names' => 'array'
				];

				$ret = $this->validateInput($fields) && $this->validateValueMap();

				if (!$ret) {
					$this->setResponse(
						new CControllerResponseData(['main_block' => json_encode([
							'error' => [
								'messages' => array_column(get_and_clear_messages(), 'message')
							]
						])])
					);
				}
				break;
		}

		return $ret;
	}

	/**
	 * Validate value map to be added.
	 *
	 * @return bool
	 */
	protected function validateValueMap(): bool {
		$name = $this->getInput('name', '');

		if ($name === '') {
			error(_s('Incorrect value for field "%1$s": %2$s.', _('Name'), _('cannot be empty')));

			return false;
		}

		$valuemap_names = $this->getInput('valuemap_names', []);

		if (in_array($name, $valuemap_names)) {
			error(_s('Incorrect value for field "%1$s": %2$s.', _('Name'),
				_s('value %1$s already exists', '('.$name.')'))
			);
			return false;
		}

		$type_uniq = array_fill_keys([VALUEMAP_MAPPING_TYPE_EQUAL, VALUEMAP_MAPPING_TYPE_GREATER_EQUAL,
				VALUEMAP_MAPPING_TYPE_LESS_EQUAL, VALUEMAP_MAPPING_TYPE_IN_RANGE, VALUEMAP_MAPPING_TYPE_REGEXP
			], []
		);
		$number_parser = new CNumberParser();
		$range_parser = new CRangesParser(['with_minus' => true, 'with_float' => true, 'with_suffix' => true]);
		$mappings = [];

		foreach ($this->getInput('mappings', []) as $mapping) {
			$mapping += ['type' => VALUEMAP_MAPPING_TYPE_EQUAL, 'value' => '', 'newvalue' => ''];
			$type = $mapping['type'];
			$value = $mapping['value'];

			if ($type != VALUEMAP_MAPPING_TYPE_DEFAULT && $value === '' && $mapping['newvalue'] === '') {
				continue;
			}

			if ($mapping['newvalue'] === '') {
				error(_s('Incorrect value for field "%1$s": %2$s.', _('Mapped to'), _('cannot be empty')));

				return false;
			}
			elseif ($type == VALUEMAP_MAPPING_TYPE_REGEXP) {
				if ($value === '') {
					error(_s('Incorrect value for field "%1$s": %2$s.', _('Value'), _('cannot be empty')));

					return false;
				}
				elseif (@preg_match('/'.str_replace('/', '\/', $value).'/', '') === false) {
					error(_s('Incorrect value for field "%1$s": %2$s.', _('Value'), _('invalid regular expression')));

					return false;
				}
			}
			elseif ($type == VALUEMAP_MAPPING_TYPE_IN_RANGE) {
				if ($value === '') {
					error(_s('Incorrect value for field "%1$s": %2$s.', _('Value'), _('cannot be empty')));

					return false;
				}
				elseif ($range_parser->parse($value) != CParser::PARSE_SUCCESS) {
					error(_s('Incorrect value for field "%1$s": %2$s.', _('Value'), _('invalid range expression')));

					return false;
				}
			}
			elseif ($type == VALUEMAP_MAPPING_TYPE_LESS_EQUAL || $type == VALUEMAP_MAPPING_TYPE_GREATER_EQUAL) {
				if ($number_parser->parse($value) != CParser::PARSE_SUCCESS) {
					error(_s('Incorrect value for field "%1$s": %2$s.', _('Value'),
						_('a floating point value is expected')
					));

					return false;
				}

				$value = (float) $number_parser->getMatch();
				$value = strval($value);
			}

			if ($type != VALUEMAP_MAPPING_TYPE_DEFAULT && array_key_exists($value, $type_uniq[$type])) {
				error(_s('Incorrect value for field "%1$s": %2$s.', _('Value'),
					_s('value %1$s already exists', '('.$value.')'))
				);

				return false;
			}

			$type_uniq[$type][$value] = true;
			$mappings[] = $mapping;
		}

		if (!$mappings) {
			error(_s('Incorrect value for field "%1$s": %2$s.', _('Mappings'), _('cannot be empty')));
			return false;
		}

		return true;
	}

	protected function checkPermissions() {
		return true;
	}

	protected function doAction() {
		$data = [];
		$mappings = [];
		$default = [];
		$this->getInputs($data, ['valuemapid', 'name', 'mappings']);

		foreach ($data['mappings'] as $mapping) {
			if ($mapping['type'] != VALUEMAP_MAPPING_TYPE_DEFAULT
					&& $mapping['value'] === '' && $mapping['newvalue'] === '') {
				continue;
			}
			elseif ($mapping['type'] == VALUEMAP_MAPPING_TYPE_DEFAULT) {
				$default = $mapping;

				continue;
			}

			$mappings[] = $mapping;
		}

		if ($default) {
			$mappings[] = $default;
		}

		$data['mappings'] = $mappings;
		$this->setResponse((new CControllerResponseData(['main_block' => json_encode($data)])));
	}
}

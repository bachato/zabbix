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


class CControllerSearch extends CController {

	/**
	 * Identifies whether the current user admin.
	 *
	 * @var bool
	 */
	private $admin;

	/**
	 * Search string.
	 *
	 * @var string
	 */
	private $search;

	/**
	 * Limit search string.
	 *
	 * @var int
	 */
	private $limit;

	protected function init() {
		$this->disableCsrfValidation();

		$this->admin = in_array($this->getUserType(), [
			USER_TYPE_ZABBIX_ADMIN,
			USER_TYPE_SUPER_ADMIN
		]);
	}

	protected function checkInput() {
		$ret = $this->validateInput(['search' => 'string']);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	protected function checkPermissions() {
		return ($this->getUserType() >= USER_TYPE_ZABBIX_USER);
	}

	protected function doAction() {
		$this->search = trim($this->getInput('search', ''));
		$this->limit = CWebUser::$data['rows_per_page'];

		$data = [
			'search' => _('Search pattern is empty'),
			'admin' => $this->admin,
			'hosts' => [],
			'template_groups' => [],
			'host_groups' => [],
			'templates' => [],
			'total_host_groups_cnt' => 0,
			'total_template_groups_cnt' => 0,
			'total_hosts_cnt' => 0,
			'total_templates_cnt' => 0,
			'allowed_ui_hosts' => $this->checkAccess(CRoleHelper::UI_MONITORING_HOSTS),
			'allowed_ui_conf_hosts' => $this->checkAccess(CRoleHelper::UI_CONFIGURATION_HOSTS),
			'allowed_ui_latest_data' => $this->checkAccess(CRoleHelper::UI_MONITORING_LATEST_DATA),
			'allowed_ui_problems' => $this->checkAccess(CRoleHelper::UI_MONITORING_PROBLEMS),
			'allowed_ui_conf_templates' => $this->checkAccess(CRoleHelper::UI_CONFIGURATION_TEMPLATES),
			'allowed_ui_conf_host_groups' => $this->checkAccess(CRoleHelper::UI_CONFIGURATION_HOST_GROUPS),
			'allowed_ui_conf_template_groups' => $this->checkAccess(CRoleHelper::UI_CONFIGURATION_TEMPLATE_GROUPS)
		];

		if ($this->search !== '') {
			[$data['hosts'], $data['total_hosts_cnt']] = $this->getHostsData();
			[$data['template_groups'], $data['total_template_groups_cnt']] = $this->getTemplateGroupsData();
			[$data['host_groups'], $data['total_host_groups_cnt']] = $this->getHostGroupsData();

			if ($this->admin) {
				[$data['templates'], $data['total_templates_cnt']]  = $this->getTemplatesData();
			}
			$data['search'] = $this->search;
		}

		$response = new CControllerResponseData($data);
		$response->setTitle(_('Search'));
		$this->setResponse($response);
	}

	/**
	 * Gathers template data, sorts according to search pattern and sets editable flag if necessary.
	 *
	 * @return array  Returns templates, template count and total count all together.
	 */
	protected function getTemplatesData() {
		$templates = API::Template()->get([
			'output' => ['name', 'host'],
			'selectItems' => API_OUTPUT_COUNT,
			'selectTriggers' => API_OUTPUT_COUNT,
			'selectGraphs' => API_OUTPUT_COUNT,
			'selectDashboards' => API_OUTPUT_COUNT,
			'selectHttpTests' => API_OUTPUT_COUNT,
			'selectDiscoveryRules' => API_OUTPUT_COUNT,
			'search' => [
				'host' => $this->search,
				'name' => $this->search
			],
			'searchByAny' => true,
			'sortfield' => 'name',
			'limit' => $this->limit,
			'preservekeys' => true
		]);

		if (!$templates) {
			return [[], 0];
		}

		CArrayHelper::sort($templates, ['name']);
		$templates = CArrayHelper::sortByPattern($templates, 'name', $this->search, $this->limit);

		$rw_templates = API::Template()->get([
			'output' => [],
			'templateids' => array_keys($templates),
			'editable' => true,
			'preservekeys' => true
		]);

		foreach ($templates as $templateid => &$template) {
			$template['editable'] = array_key_exists($templateid, $rw_templates);
		}
		unset($template);

		$total_count = API::Template()->get([
			'countOutput' => true,
			'search' => [
				'host' => $this->search,
				'name' => $this->search
			],
			'searchByAny' => true
		]);

		return [$templates, $total_count];
	}

	/**
	 * Gathers host group data, sorts according to search pattern and sets editable flag if necessary.
	 *
	 * @return array  Returns host groups, group count and total count all together.
	 */
	protected function getHostGroupsData() {
		$groups = API::HostGroup()->get([
			'output' => ['name'],
			'selectHosts' => API_OUTPUT_COUNT,
			'search' => ['name' => $this->search],
			'limit' => $this->limit,
			'preservekeys' => true
		]);

		if (!$groups) {
			return [[], 0];
		}

		CArrayHelper::sort($groups, ['name']);
		$groups = CArrayHelper::sortByPattern($groups, 'name', $this->search, $this->limit);

		$rw_groups = API::HostGroup()->get([
			'output' => [],
			'groupids' => array_keys($groups),
			'editable' => true,
			'preservekeys' => true
		]);

		foreach ($groups as $groupid => &$group) {
			$group['editable'] = ($this->admin && array_key_exists($groupid, $rw_groups));
		}
		unset($group);

		$total_count = API::HostGroup()->get([
			'countOutput' => true,
			'search' => ['name' => $this->search]
		]);

		return [$groups, $total_count];
	}

	/**
	 * Gathers template group data, sorts according to search pattern and sets editable flag if necessary.
	 *
	 * @return array  Returns template groups, group count and total count all together.
	 */
	protected function getTemplateGroupsData() {
		$groups = API::TemplateGroup()->get([
			'output' => ['name'],
			'selectTemplates' => API_OUTPUT_COUNT,
			'search' => ['name' => $this->search],
			'limit' => $this->limit,
			'preservekeys' => true
		]);

		if (!$groups) {
			return [[], 0];
		}

		CArrayHelper::sort($groups, ['name']);
		$groups = CArrayHelper::sortByPattern($groups, 'name', $this->search, $this->limit);

		$rw_groups = API::TemplateGroup()->get([
			'output' => [],
			'groupids' => array_keys($groups),
			'editable' => true,
			'preservekeys' => true
		]);

		foreach ($groups as $groupid => &$group) {
			$group['editable'] = ($this->admin && array_key_exists($groupid, $rw_groups));
		}
		unset($group);

		$total_count = API::TemplateGroup()->get([
			'countOutput' => true,
			'search' => ['name' => $this->search]
		]);

		return [$groups, $total_count];
	}

	/**
	 * Gathers host data, sorts according to search pattern and sets editable flag if necessary.
	 *
	 * @return array  Returns hosts, host count and total count all together.
	 */
	protected function getHostsData() {
		$hosts = API::Host()->get([
			'output' => ['name', 'status', 'host'],
			'selectInterfaces' => ['ip', 'dns'],
			'selectItems' => API_OUTPUT_COUNT,
			'selectTriggers' => API_OUTPUT_COUNT,
			'selectGraphs' => API_OUTPUT_COUNT,
			'selectHttpTests' => API_OUTPUT_COUNT,
			'selectDiscoveryRules' => API_OUTPUT_COUNT,
			'search' => [
				'host' => $this->search,
				'name' => $this->search,
				'dns' => $this->search,
				'ip' => $this->search
			],
			'searchByAny' => true,
			'limit' => $this->limit,
			'preservekeys' => true
		]);

		if (!$hosts) {
			return [[], 0];
		}

		CArrayHelper::sort($hosts, ['name']);
		$hosts = CArrayHelper::sortByPattern($hosts, 'name', $this->search, $this->limit);

		$rw_hosts = API::Host()->get([
			'output' => ['hostid'],
			'hostids' => array_keys($hosts),
			'editable' => true,
			'preservekeys' => true
		]);

		foreach ($hosts as $hostid => &$host) {
			$host['editable'] = ($this->admin && array_key_exists($hostid, $rw_hosts));
		}
		unset($host);

		$total_count = API::Host()->get([
			'countOutput' => true,
			'search' => [
				'host' => $this->search,
				'name' => $this->search,
				'dns' => $this->search,
				'ip' => $this->search
			],
			'searchByAny' => true
		]);

		return [$hosts, $total_count];
	}
}

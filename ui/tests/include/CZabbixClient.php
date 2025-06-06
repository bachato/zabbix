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

require_once 'vendor/autoload.php';

require_once dirname(__FILE__).'/../../include/defines.inc.php';
require_once dirname(__FILE__).'/../../include/classes/core/ZBase.php';
require_once dirname(__FILE__).'/../../include/classes/server/CZabbixServer.php';

/**
 * Client for Zabbix Server/Proxy protocol.
 */
class CZabbixClient extends CZabbixServer {

	/**
	 * @inheritdoc
	 */
	protected function normalizeResponse(array &$response): bool {
		// Response for item data requests contain success status without data.
		if (array_key_exists('response', $response) && $response['response'] === self::RESPONSE_SUCCESS
				&& !array_key_exists('data', $response) && array_key_exists('info', $response)) {
			$response['data'] = $response['info'];
			unset($response['info']);
		}

		return parent::normalizeResponse($response);
	}

	/**
	 * Send value for items to server/proxy.
	 *
	 * @param string $type      data type
	 * @param array  $values    trapper values
	 *
	 * @return array|false    array with result data or false otherwise
	 */
	public function sendDataValues($type, $values) {
		$response = parent::request([
			'request' => $type.' data',
			'data' => $values,
			'clock' => time(),
			'ns' => 0
		]);

		if ($response !== false && $this->error === null) {
			$result = [];

			foreach (explode('; ', $response) as $line) {
				$parts = explode(': ', $line);
				if (count($parts) !== 2) {
					continue;
				}

				$result[$parts[0]] = floatval($parts[1]);
			}

			return $result;
		}

		return false;
	}

	/**
	 * Get active checks for a host.
	 *
	 * @param string $host    host name
	 *
	 * @return array|false    array with active checks or false otherwise
	 */
	public function getActiveChecks($host) {
		return parent::request([
			'request' => 'active checks',
			'host' => $host
		]);
	}
}

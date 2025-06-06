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

#include "audit_server.h"
#include "audit/zbxaudit.h"

#include "zbxalgo.h"

zbx_audit_entry_t	*zbx_audit_settings_create_entry(int audit_context_mode, int audit_action)
{
	int	audit_enabled = 0;

	zbx_audit_get_status(audit_context_mode, &audit_enabled);

	if (0 == audit_enabled)
		return NULL;

	zbx_audit_entry_t	local_audit_entry = {0}, *plocal_audit_entry = &local_audit_entry, **audit_entry;

	local_audit_entry.id_table = AUDIT_CONFIG_ID;

	if (NULL == (audit_entry = (zbx_audit_entry_t **)zbx_hashset_search(zbx_get_audit_hashset(),
			&plocal_audit_entry)))
	{
		zbx_audit_entry_t	*new_entry;

		new_entry = zbx_audit_entry_init(0, AUDIT_CONFIG_ID, "", audit_action, ZBX_AUDIT_RESOURCE_SETTINGS);
		audit_entry = (zbx_audit_entry_t **)zbx_hashset_insert(zbx_get_audit_hashset(), &new_entry,
				sizeof(new_entry));
	}

	return *audit_entry;
}

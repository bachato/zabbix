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

#ifndef ZABBIX_ZBXDIAG_H
#define ZABBIX_ZBXDIAG_H

#include "zbxtypes.h"
#include "zbxjson.h"
#include "zbxalgo.h"
#include "zbxshmem.h"

#define ZBX_DIAG_PREPROC_INFO	0x00000001
#define ZBX_DIAG_PREPROC_SIMPLE	(ZBX_DIAG_PREPROC_INFO)

typedef enum
{
	ZBX_DIAGINFO_UNDEFINED = -1,
	ZBX_DIAGINFO_ALL,
	ZBX_DIAGINFO_HISTORYCACHE,
	ZBX_DIAGINFO_VALUECACHE,
	ZBX_DIAGINFO_PREPROCESSING,
	ZBX_DIAGINFO_LLD,
	ZBX_DIAGINFO_ALERTING,
	ZBX_DIAGINFO_LOCKS,
	ZBX_DIAGINFO_CONNECTOR,
	ZBX_DIAGINFO_PROXYBUFFER,
}
zbx_diaginfo_section_t;

typedef struct
{
	char		*name;
	zbx_uint64_t	value;
}
zbx_diag_map_t;

ZBX_PTR_VECTOR_DECL(diag_map_ptr, zbx_diag_map_t *)

/* diagnostic information section callback function prototype */
typedef int (*zbx_diag_add_section_info_func_t)(const char *section, const struct zbx_json_parse *jp,
		struct zbx_json *json, char **error);

#define ZBX_DIAG_HISTORYCACHE	"historycache"
#define ZBX_DIAG_VALUECACHE	"valuecache"
#define ZBX_DIAG_PREPROCESSING	"preprocessing"
#define ZBX_DIAG_LLD		"lld"
#define ZBX_DIAG_ALERTING	"alerting"
#define ZBX_DIAG_LOCKS		"locks"
#define ZBX_DIAG_CONNECTOR	"connector"
#define ZBX_DIAG_PROXYBUFFER	"proxybuffer"

void	zbx_diag_map_free(zbx_diag_map_t *map);
int	zbx_diag_parse_request(const struct zbx_json_parse *jp, const zbx_diag_map_t *field_map, zbx_uint64_t
		*field_mask, zbx_vector_diag_map_ptr_t *top_views, char **error);
void	zbx_diag_add_mem_stats(struct zbx_json *json, const char *name, const zbx_shmem_stats_t *stats);
int	zbx_diag_add_historycache_info(const struct zbx_json_parse *jp, struct zbx_json *json, char **error);
void	zbx_diag_add_locks_info(struct zbx_json *json);
int	zbx_diag_add_connector_info(const struct zbx_json_parse *jp, struct zbx_json *json, char **error);

void	zbx_diag_init(zbx_diag_add_section_info_func_t cb);
int	zbx_diag_get_info(const struct zbx_json_parse *jp, char **info);
void	zbx_diag_log_info(unsigned int flags, char **result);

#endif /* ZABBIX_ZBXDIAG_H */

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

#include "zbxsysinfo.h"
#include "../sysinfo.h"
#include "../specsysinfo.h"
#include "../common/stats.h"

static zbx_metric_t	parameters_specific[] =
/*	KEY			FLAG		FUNCTION		TEST PARAMETERS */
{
	{"vfs.fs.size",		CF_HAVEPARAMS,	vfs_fs_size,		"/,free"},
	{"vfs.fs.inode",	CF_HAVEPARAMS,	vfs_fs_inode,		"/,free"},
	{"vfs.fs.discovery",	0,		vfs_fs_discovery,	NULL},
	{"vfs.fs.get",		0,		vfs_fs_get,		NULL},

	{"vfs.dev.read",	CF_HAVEPARAMS,	vfs_dev_read,		"hdisk0,operations"},
	{"vfs.dev.write",	CF_HAVEPARAMS,	vfs_dev_write,		"hdisk0,operations"},

	{"net.if.in",		CF_HAVEPARAMS,	net_if_in,		"lo0,bytes"},
	{"net.if.out",		CF_HAVEPARAMS,	net_if_out,		"lo0,bytes"},
	{"net.if.total",	CF_HAVEPARAMS,	net_if_total,		"lo0,bytes"},
	{"net.if.collisions",	CF_HAVEPARAMS,	net_if_collisions,	"lo0"},
	{"net.if.discovery",	0,		net_if_discovery,	NULL},

	{"vm.memory.size",	CF_HAVEPARAMS,	vm_memory_size,		"free"},

	{"proc.num",		CF_HAVEPARAMS,	proc_num,		"inetd"},
	{"proc.mem",		CF_HAVEPARAMS,	proc_mem,		"inetd"},

	{"system.cpu.switches",	0,		system_cpu_switches,	NULL},
	{"system.cpu.intr",	0,		system_cpu_intr,	NULL},
	{"system.cpu.util",	CF_HAVEPARAMS,	system_cpu_util,	"all,user,avg1"},
	{"system.cpu.load",	CF_HAVEPARAMS,	system_cpu_load,	"all,avg1"},
	{"system.cpu.num",	CF_HAVEPARAMS,	system_cpu_num,		"online"},
	{"system.cpu.discovery",0,		system_cpu_discovery,	NULL},

	{"system.uname",	0,		system_uname,		NULL},

	{"system.uptime",	0,		system_uptime,		NULL},

	{"system.stat",		CF_HAVEPARAMS,	system_stat,		"page,fi"},
	{"system.swap.size",	CF_HAVEPARAMS,	system_swap_size,	"all,free"},
	{"system.sw.arch",	0,		system_sw_arch,		NULL},

	{NULL}
};

zbx_metric_t	*get_parameters_specific(void)
{
	return parameters_specific;
}

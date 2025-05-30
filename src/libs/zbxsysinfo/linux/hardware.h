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

#ifndef ZABBIX_HARDWARE_H
#define ZABBIX_HARDWARE_H

#define SMBIOS_STATUS_UNKNOWN	1
#define SMBIOS_STATUS_ERROR	2
#define SMBIOS_STATUS_OK	3

#define DEV_MEM			"/dev/mem"
#define SYS_TABLE_FILE		"/sys/firmware/dmi/tables/DMI"
#define SMBIOS_ENTRY_POINT_SIZE	0x20
#define DMI_HEADER_SIZE		4

#define CHASSIS_TYPE_BITS	0x7f	/* bits 0-6 represent the chassis type */
#define MAX_CHASSIS_TYPE	0x24

#define DMI_GET_TYPE		0x01
#define DMI_GET_VENDOR		0x02
#define DMI_GET_MODEL		0x04
#define DMI_GET_SERIAL		0x08

#define CPU_MAX_FREQ_FILE	"/sys/devices/system/cpu/cpu%d/cpufreq/cpuinfo_max_freq"

#define HW_CPU_INFO_FILE	"/proc/cpuinfo"
#define HW_CPU_ALL_CPUS		-1
#define HW_CPU_SHOW_ALL		1
#define HW_CPU_SHOW_MAXFREQ	2
#define HW_CPU_SHOW_VENDOR	3
#define HW_CPU_SHOW_MODEL	4
#define HW_CPU_SHOW_CURFREQ	5

#endif	/* ZABBIX_HARDWARE_H */

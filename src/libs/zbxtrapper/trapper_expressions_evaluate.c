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

#include "trapper_expressions_evaluate.h"

#include "zbxtrapper.h"
#include "zbxalgo.h"
#include "zbxnum.h"
#include "zbxcommshigh.h"
#include "zbxdbhigh.h"
#include "zbxtime.h"
#include "zbxeval.h"
#include "zbxjson.h"
#include "zbxvariant.h"

static int	trapper_parse_expressions_evaluate(const struct zbx_json_parse *jp, zbx_vector_str_t *expressions,
				char **error)
{
	char			buffer[MAX_STRING_LEN];
	const char		*ptr;
	zbx_user_t		user;
	int			ret = FAIL;
	struct zbx_json_parse	jp_data, jp_expressions;

	zbx_user_init(&user);

	if (FAIL == zbx_get_user_from_json(jp, &user, NULL) || USER_TYPE_ZABBIX_ADMIN > user.type)
	{
		*error = zbx_strdup(NULL, "Permission denied.");
		goto out;
	}

	if (FAIL == zbx_json_brackets_by_name(jp, ZBX_PROTO_TAG_DATA, &jp_data))
	{
		*error = zbx_strdup(NULL, "Missing data field.");
		goto out;
	}

	if (FAIL == zbx_json_brackets_by_name(&jp_data, ZBX_PROTO_TAG_EXPRESSIONS, &jp_expressions))
	{
		*error = zbx_strdup(NULL, "Missing expressions field.");
		goto out;
	}

	for (ptr = NULL; NULL != (ptr = zbx_json_next_value(&jp_expressions, ptr, buffer, sizeof(buffer), NULL));)
	{
		zbx_vector_str_append(expressions, zbx_strdup(NULL, buffer));
	}

	ret = SUCCEED;
out:
	zbx_user_free(&user);
	return ret;
}

static int	trapper_expression_evaluate(const char *expression, const zbx_timespec_t *ts, double *result,
		char **error)
{
	zbx_eval_context_t	ctx;
	int			ret;
	zbx_variant_t		value;

	if (SUCCEED != zbx_eval_parse_expression(&ctx, expression, ZBX_EVAL_PARSE_TRIGGER_EXPRESSION, error))
		return FAIL;

	if (SUCCEED == (ret = zbx_eval_execute(&ctx, ts, &value, error)))
	{
		if (SUCCEED == zbx_variant_convert(&value, ZBX_VARIANT_DBL))
		{
			*result = value.data.dbl;
		}
		else
		{
			*error = zbx_dsprintf(NULL, "invalid result \"%s\" of type \"%s\"",
					zbx_variant_value_desc(&value), zbx_variant_type_desc(&value));
			zbx_variant_clear(&value);
			ret = FAIL;
		}
	}

	zbx_eval_clear(&ctx);

	return ret;
}

static int	trapper_expressions_evaluate_run(const struct zbx_json_parse *jp, struct zbx_json *json, char **error)
{
	int			ret = FAIL;
	zbx_vector_str_t	expressions;
	zbx_timespec_t		ts;

	zbx_vector_str_create(&expressions);

	if (FAIL == trapper_parse_expressions_evaluate(jp, &expressions, error))
		goto out;

	zbx_json_addstring(json, ZBX_PROTO_TAG_RESPONSE, "success", ZBX_JSON_TYPE_STRING);
	zbx_json_addarray(json, ZBX_PROTO_TAG_DATA);

	zbx_timespec(&ts);

	for (int i = 0; i < expressions.values_num; i++)
	{
		double	expr_result;
		char	*errmsg = NULL;

		zbx_json_addobject(json, NULL);
		zbx_json_addstring(json, ZBX_PROTO_TAG_EXPRESSION, expressions.values[i], ZBX_JSON_TYPE_STRING);

		if (SUCCEED != trapper_expression_evaluate(expressions.values[i], &ts, &expr_result, &errmsg))
		{
			zbx_json_addstring(json, ZBX_PROTO_TAG_ERROR, errmsg, ZBX_JSON_TYPE_STRING);
			zbx_free(errmsg);
		}
		else
		{
			zbx_uint64_t	res = (ZBX_INFINITY == expr_result ||
					SUCCEED == zbx_double_compare(expr_result, 0.0)) ? 0 : 1;
			zbx_json_adduint64(json, ZBX_PROTO_TAG_VALUE, res);

		}
		zbx_json_close(json);
	}

	zbx_json_close(json);

	ret = SUCCEED;
out:
	zbx_vector_str_clear_ext(&expressions, zbx_str_free);
	zbx_vector_str_destroy(&expressions);

	return ret;
}

int	zbx_trapper_expressions_evaluate(zbx_socket_t *sock, const struct zbx_json_parse *jp, int config_timeout,
		const zbx_config_tls_t *config_tls, const char *config_frontend_allowed_ip)
{
	char		*error = NULL;
	int		ret;
	struct zbx_json	json;

	if (SUCCEED != zbx_check_frontend_conn_accept(sock, config_tls, config_frontend_allowed_ip))
		return FAIL;

	zbx_json_init(&json, 1024);

	if (SUCCEED == (ret = trapper_expressions_evaluate_run(jp, &json, &error)))
	{
		zbx_tcp_send_bytes_to(sock, json.buffer, json.buffer_size, config_timeout);
	}
	else
	{
		zbx_send_response(sock, ret, error, config_timeout);
		zbx_free(error);
	}

	zbx_json_free(&json);

	return ret;
}

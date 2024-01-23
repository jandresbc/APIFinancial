# Api's

### Cashin Consulta

-   **Consulta las cuotas de un cliente**

    -   Method : GET
    -   URL: /get-quotas-info

Informacion para consultar un prestamo.

| Attribute  | Type   | Required | Description                     | Example              | Default                  |
| :--------- | :----- | :------- | :------------------------------ | :------------------- | :----------------------- |
| `document` | String | SI       | Numero de documento cliente.    | 1007605898           |                          |
| `channel`  | String | NO       | Nombre del que esta consultado. | CREDITEK_SIN_DEFINIR | CREDITEK_SIN_ESPECIFICAR |

Ejemplo para consultar un prestamo.

```json
{
    "document": "1007605898"
}
```

_Ejemplos de respuestas:_

**SIN CUOTAS PENDIENTES**

```json
{
    "data": {
        "person": {
            "id": 2801,
            "document": "1007605898",
            "phone": 0,
            "fullName": "FRANCISCO GUZMÁN JOSÉ"
        },
        "quotas_info": []
    },
    "message": "NO TE ENCONTRARON PAGOS PENDIENTES",
    "code": 200,
    "status": true
}
```

**CONSULTA SIN DATOS CON LA C.C CONSULTADA**

```json
{
    "data": {
        "person": {
            "id": 3455,
            "document": "1007605897",
            "phone": 0,
            "fullName": ""
        },
        "quotas_info": []
    },
    "message": "PARAMETROS INCORRECTOS",
    "code": 200,
    "status": true
}
```
**CLIENTES CON CUOTAS PENDIENTES**

```json

{
    "data": {
        "person": {
            "id": 6681,
            "document": "1031165066",
            "phone": 0,
            "fullName": "FRANCO CHINOME GIAN"
        },
        "quotas_info": [
            {
                "total_debt": 1,
                "quotas_debt": 4,
                "credit_id": 9373,
                "bar_code": "010311650661700072739100080040000000100040222",
                "id_number": "17000727391"
            },
            {
                "total_debt": 85995,
                "quotas_debt": 5,
                "credit_id": 9373,
                "bar_code": "010311650661700072739200080050008599500190222",
                "id_number": "17000727392"
            }
        ]
    },
    "message": "success",
    "code": 200,
    "status": true
}

```


**Error en la consulta**

```json

```

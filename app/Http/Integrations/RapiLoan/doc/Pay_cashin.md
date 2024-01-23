# Api's

### Cashin Pago

-   **Impactar un pago**

        -   Method : POST
        -   URL: /cashin/pay

    Informacion para consultar un prestamo.

| Attribute   | Type   | Required | Description                                             | Example                                       | Default                  |
| :---------- | :----- | :------- | :------------------------------------------------------ | :-------------------------------------------- | :----------------------- |
| `id_number` | String | SI       | Id numérico perteneciente a la quota que se va a pagar. | 17000727391                                   |                          |
| `document`  | String | SI       | Numero de documento cliente.                            | 1007605898                                    |                          |
| `bar_code`  | String | SI       | Codigo de barra de la cuota a pagar.                    | 010076058981700025834200030010010073400310721 |                          |
| `amount`    | String | SI       | Monto a pagar.                                          | 49563.0                                       |                          |
| `date`      | String | SI       | Fecha del pago. Formato Y-m-d H:i:s .                   | 2022-07-19 14:27:00                           |                          |
| `channel`   | String | NO       | Nombre del que esta consultado.                         | EFECTY                                        | CREDITEK_SIN_ESPECIFICAR |

Ejemplo para realizar un pago.

```json
{
    "id_number" : "17000727391",
    "document" : "1031165066",
    "bar_code" : "010311650661700072739600080090008599500200422",
    "amount" : "49563.0",
    "date" : "2022-07-19 14:27:00"
}
```

_Ejemplos de respuestas:_

**PAGO REALIZADO CON EXITO**

```json
{
    "data": {
        "person": {
            "id": 7761,
            "document": "17000727391"
        },
        "pay_info": {
            "id_numero": "17000727391",
            "cod_trx": "17000727391",
            "barra": "010311650661700072739600080090008599500200422",
            "codigo_respuesta": "0",
            "fecha_hora_operacion": "2022-07-19 14:27:00",
            "msg": "Trx ok"
        }
    },
    "message": "Trx ok",
    "code": 200,
    "status": true
}
```

**ERROR EN EL PAGO**

```json
{
    "data": {
        "person": {
            "id": 1740,
            "document": "17000258342"
        },
        "pay_info": {
            "id_numero": "17000258342",
            "cod_trx": "17000258342",
            "barra": "010076058981700025834200030010010073400310721",
            "codigo_respuesta": "10",
            "fecha_hora_operacion": "2022-07-17 17:25:33",
            "msg": "Error interno entidad"
        }
    },
    "message": "Error interno entidad",
    "code": 200,
    "status": true
}
```
